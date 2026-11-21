<?php

namespace App\Services\Absensi;

use App\Enums\MetodeAbsensi;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPeserta;
use App\Support\KelayakanPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\Absensi;
use App\Models\KelasMataKuliah;
use App\Models\PesertaMataKuliah;
use App\Models\RiwayatPerubahanAbsensi;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\Dosen\MataKuliahService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Alur catatan absensi: pengisian mandiri mahasiswa, penutupan sesi dengan
 * alfa otomatis, koreksi dosen yang diaudit, serta daftar/riwayat absensi.
 *
 * Identitas peserta dan pencatat tidak pernah diambil dari request; keduanya
 * ditetapkan dari sesi/login yang sudah diotorisasi.
 */
class AbsensiService
{
    public const ZONA_WAKTU = 'Asia/Jakarta';

    public function __construct(private readonly MataKuliahService $mataKuliah)
    {
    }

    /**
     * @return array{berhasil: bool, pesan: string, absensi: ?Absensi}
     */
    public function isiMandiri(int $mahasiswaId, mixed $sesiId): array
    {
        $sesi = SesiAbsensi::query()
            ->whereKey(is_numeric($sesiId) ? (int) $sesiId : 0)
            ->with('kelasMataKuliah')
            ->firstOrFail();

        $peserta = $this->pesertaUntukMahasiswa($sesi, $mahasiswaId);

        if ($peserta === null) {
            return $this->gagal('Anda tidak terdaftar pada kelas untuk sesi ini.');
        }

        if (! $this->pesertaBerhak($peserta)) {
            return $this->gagal('Status peserta Anda tidak memungkinkan pengisian absensi.');
        }

        if ($sesi->status !== StatusSesiAbsensi::Dibuka) {
            return $this->gagal('Sesi absensi sudah ditutup.');
        }

        if (! $this->dalamWaktu($sesi)) {
            return $this->gagal('Pengisian absensi hanya dapat dilakukan pada rentang waktu sesi.');
        }

        $duplikat = false;
        $model = null;

        try {
            DB::transaction(function () use ($sesi, $peserta, &$duplikat, &$model): void {
                // Kunci baris peserta agar dua permintaan bersamaan dari mahasiswa
                // yang sama diproses berurutan.
                PesertaMataKuliah::query()
                    ->whereKey($peserta->getKey())
                    ->lockForUpdate()
                    ->first();

                $sudahAda = Absensi::query()
                    ->where('sesi_absensi_id', $sesi->getKey())
                    ->where('peserta_mata_kuliah_id', $peserta->getKey())
                    ->exists();

                if ($sudahAda) {
                    $duplikat = true;

                    return;
                }

                $model = new Absensi();
                $model->forceFill([
                    'status' => StatusAbsensi::Hadir,
                    'keterangan' => null,
                    'metode' => MetodeAbsensi::Mandiri->value,
                    'dicatat_pada' => Carbon::now(self::ZONA_WAKTU),
                ]);
                $model->sesiAbsensi()->associate($sesi);
                $model->pesertaMataKuliah()->associate($peserta);
                $model->save();
            });
        } catch (QueryException $exception) {
            if ($this->pelanggaranUnik($exception)) {
                return $this->gagal('Anda sudah mengisi absensi pada sesi ini.');
            }

            return $this->simpanGagal($exception, 'Absensi gagal disimpan. Silakan coba lagi.');
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Absensi gagal disimpan. Silakan coba lagi.');
        }

        if ($duplikat || $model === null) {
            return $this->gagal('Anda sudah mengisi absensi pada sesi ini.');
        }

        return [
            'berhasil' => true,
            'pesan' => 'Absensi hadir berhasil dicatat.',
            'absensi' => $model,
        ];
    }

    /**
     * @return array{berhasil: bool, pesan: string, absensi: ?Absensi, sebelum: ?string, sesudah: ?string}
     */
    public function koreksi(
        int $dosenId,
        mixed $kelasId,
        mixed $sesiId,
        mixed $pesertaId,
        StatusAbsensi $status,
        string $alasan,
        User $actor,
    ): array {
        $kelas = $this->mataKuliah->milikDosen($dosenId, $kelasId);
        $sesi = $this->sesiMilikKelas($kelas, $sesiId);
        $peserta = $this->pesertaMilikKelas($kelas, $pesertaId);

        if ($sesi->status !== StatusSesiAbsensi::Ditutup) {
            return $this->gagal('Koreksi hanya dapat dilakukan setelah sesi ditutup.');
        }

        if (trim($alasan) === '') {
            return $this->gagal('Alasan koreksi wajib diisi.');
        }

        $sebelum = null;
        $model = null;

        try {
            DB::transaction(function () use ($sesi, $peserta, $status, $alasan, $actor, &$sebelum, &$model): void {
                // Mengunci sesi menyerialkan seluruh koreksi pada sesi yang sama.
                SesiAbsensi::query()
                    ->whereKey($sesi->getKey())
                    ->lockForUpdate()
                    ->first();

                $model = Absensi::query()
                    ->where('sesi_absensi_id', $sesi->getKey())
                    ->where('peserta_mata_kuliah_id', $peserta->getKey())
                    ->lockForUpdate()
                    ->first();

                $sebelum = $model?->status?->value;

                if ($model === null) {
                    $model = new Absensi();
                    $model->sesiAbsensi()->associate($sesi);
                    $model->pesertaMataKuliah()->associate($peserta);
                }

                $model->forceFill([
                    'status' => $status,
                    'keterangan' => trim($alasan),
                    'metode' => MetodeAbsensi::Manual->value,
                    'dicatat_pada' => Carbon::now(self::ZONA_WAKTU),
                ]);
                $model->pencatat()->associate($actor);
                $model->save();

                $riwayat = new RiwayatPerubahanAbsensi();
                $riwayat->forceFill([
                    'absensi_id' => $model->getKey(),
                    'peserta_mata_kuliah_id' => $peserta->getKey(),
                    'sesi_absensi_id' => $sesi->getKey(),
                    'status_sebelum' => $sebelum,
                    'status_sesudah' => $status->value,
                    'alasan' => trim($alasan),
                ]);
                $riwayat->actor()->associate($actor);
                $riwayat->save();
            });
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Koreksi gagal disimpan. Silakan coba lagi.', [
                'sebelum' => null,
                'sesudah' => null,
            ]);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Koreksi absensi berhasil disimpan.',
            'absensi' => $model,
            'sebelum' => $sebelum,
            'sesudah' => $status->value,
        ];
    }

    /**
     * Menutup sesi dan mengisi alfa otomatis bagi peserta berhak yang belum
     * memiliki catatan. Idempotent: memanggil ulang tidak mengubah data.
     *
     * @return array{berhasil: bool, pesan: string, alfa: int, sudah_ditutup: bool}
     */
    public function tutupSesi(SesiAbsensi $sesi): array
    {
        $ditambah = 0;
        $sudahDitutup = false;

        try {
            DB::transaction(function () use ($sesi, &$ditambah, &$sudahDitutup): void {
                $kunci = SesiAbsensi::query()
                    ->whereKey($sesi->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($kunci === null || $kunci->status === StatusSesiAbsensi::Ditutup) {
                    $sudahDitutup = true;

                    return;
                }

                $sudahMencatat = Absensi::query()
                    ->where('sesi_absensi_id', $kunci->getKey())
                    ->pluck('peserta_mata_kuliah_id')
                    ->all();

                $peserta = PesertaMataKuliah::query()
                    ->where('kelas_mata_kuliah_id', $kunci->kelas_mata_kuliah_id)
                    ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
                    ->orderBy('id')
                    ->get();

                foreach ($peserta as $item) {
                    if (in_array($item->getKey(), $sudahMencatat, true)) {
                        continue;
                    }

                    $absensi = new Absensi();
                    $absensi->forceFill([
                        'status' => StatusAbsensi::Alfa,
                        'keterangan' => 'Sesi ditutup tanpa catatan kehadiran.',
                        'metode' => MetodeAbsensi::Otomatis->value,
                        'dicatat_pada' => Carbon::now(self::ZONA_WAKTU),
                    ]);
                    $absensi->sesiAbsensi()->associate($kunci);
                    $absensi->pesertaMataKuliah()->associate($item);

                    try {
                        $absensi->save();
                        $ditambah++;
                    } catch (QueryException $exception) {
                        if (! $this->pelanggaranUnik($exception)) {
                            throw $exception;
                        }
                    }
                }

                $kunci->forceFill(['status' => StatusSesiAbsensi::Ditutup])->save();
            });
        } catch (Throwable $exception) {
            return $this->simpanGagal($exception, 'Sesi gagal ditutup. Silakan coba lagi.', [
                'alfa' => 0,
                'sudah_ditutup' => false,
            ]);
        }

        return [
            'berhasil' => true,
            'pesan' => $sudahDitutup
                ? 'Sesi sudah ditutup sebelumnya.'
                : 'Sesi ditutup. '.$ditambah.' catatan alfa otomatis dibuat.',
            'alfa' => $ditambah,
            'sudah_ditutup' => $sudahDitutup,
        ];
    }

    /**
     * Dipanggil oleh scheduler. Hanya sesi dibuka yang waktu selesainya sudah
     * tercapai yang diproses; pemanggilan ulang tidak menghasilkan perubahan.
     *
     * @return array{ditutup: int, alfa: int, gagal: int}
     */
    public function tutupYangJatuhTempo(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now(self::ZONA_WAKTU);
        $hasil = ['ditutup' => 0, 'alfa' => 0, 'gagal' => 0];

        $jatuhTempo = SesiAbsensi::query()
            ->where('status', StatusSesiAbsensi::Dibuka->value)
            ->get()
            ->filter(function (SesiAbsensi $sesi) use ($now): bool {
                $selesai = $this->waktu($sesi, 'waktu_selesai');

                return $selesai !== null && $selesai->lessThanOrEqualTo($now);
            });

        foreach ($jatuhTempo as $sesi) {
            $hasilSesi = $this->tutupSesi($sesi);

            if (! $hasilSesi['berhasil'] || $hasilSesi['sudah_ditutup']) {
                if (! $hasilSesi['berhasil']) {
                    $hasil['gagal']++;
                }

                continue;
            }

            $hasil['ditutup']++;
            $hasil['alfa'] += $hasilSesi['alfa'];
        }

        return $hasil;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function daftarAbsensiSesi(KelasMataKuliah $kelas, SesiAbsensi $sesi): Collection
    {
        if ((int) $sesi->kelas_mata_kuliah_id !== (int) $kelas->getKey()) {
            abort(404);
        }

        return $kelas->peserta()
            ->with([
                'mahasiswa',
                'absensi' => fn ($query) => $query->where('sesi_absensi_id', $sesi->getKey()),
            ])
            ->get()
            ->sortBy(fn (PesertaMataKuliah $item): string => Str::lower((string) $item->mahasiswa?->name))
            ->values()
            ->map(function (PesertaMataKuliah $item) use ($kelas, $sesi): array {
                $absensi = $item->absensi->first();

                return [
                    'peserta_id' => $item->getKey(),
                    'nama' => $item->mahasiswa?->name ?? 'Mahasiswa tidak tersedia',
                    'nim' => $item->mahasiswa?->nim ?? '-',
                    'status_peserta' => $item->status?->value ?? '-',
                    'status_peserta_label' => $this->labelPeserta($item),
                    'status' => $absensi?->status?->value,
                    'status_label' => $absensi === null ? 'Belum ada' : $this->labelStatus($absensi->status),
                    'metode_label' => $absensi === null ? '-' : $this->labelMetode($absensi->metode),
                    'keterangan' => $absensi?->keterangan,
                    'boleh_dikoreksi' => $sesi->status === StatusSesiAbsensi::Ditutup,
                    'koreksi_url' => route('dosen.absensi.koreksi.form', [
                        $kelas->getKey(),
                        $sesi->getKey(),
                        $item->getKey(),
                    ]),
                ];
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, RiwayatPerubahanAbsensi>
     */
    public function riwayatKoreksi(SesiAbsensi $sesi): Collection
    {
        return RiwayatPerubahanAbsensi::query()
            ->where('sesi_absensi_id', $sesi->getKey())
            ->with(['actor', 'pesertaMataKuliah.mahasiswa'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function daftarSesiMahasiswa(int $mahasiswaId): Collection
    {
        $peserta = PesertaMataKuliah::query()
            ->where('mahasiswa_id', $mahasiswaId)
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
                'kelasMataKuliah.sesiAbsensi' => fn ($query) => $query
                    ->orderBy('tanggal')
                    ->orderBy('pertemuan_ke'),
                'absensi',
            ])
            ->get();

        $hasil = collect();

        foreach ($peserta as $item) {
            $kelas = $item->kelasMataKuliah;

            if ($kelas === null) {
                continue;
            }

            foreach ($kelas->sesiAbsensi as $sesi) {
                $absensi = $item->absensi->first(
                    fn (Absensi $record): bool => (int) $record->sesi_absensi_id === (int) $sesi->getKey(),
                );

                $bolehIsi = KelayakanPeserta::berhak($item->status)
                    && $sesi->status === StatusSesiAbsensi::Dibuka
                    && $absensi === null
                    && $this->dalamWaktu($sesi);

                $hasil->push([
                    'sesi_id' => $sesi->getKey(),
                    'kelas_id' => $kelas->getKey(),
                    'mata_kuliah' => $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'kode_kelas' => $kelas->kode_kelas,
                    'periode' => $kelas->tahunAkademik?->label() ?? '-',
                    'pertemuan_ke' => $sesi->pertemuan_ke,
                    'tanggal_label' => $sesi->tanggal?->translatedFormat('D, d F Y') ?? '-',
                    'topik' => $sesi->topik ?: 'Topik belum diisi',
                    'waktu_label' => $this->rentangWaktu($sesi),
                    'status_sesi' => $sesi->status?->value ?? '-',
                    'status_sesi_label' => $sesi->status === StatusSesiAbsensi::Dibuka ? 'Dibuka' : 'Ditutup',
                    'status' => $absensi?->status?->value,
                    'status_label' => $absensi === null ? 'Belum ada' : $this->labelStatus($absensi->status),
                    'metode_label' => $absensi === null ? '-' : $this->labelMetode($absensi->metode),
                    'boleh_isi' => $bolehIsi,
                    'url' => route('mahasiswa.absensi.simpan', $sesi->getKey()),
                ]);
            }
        }

        return $hasil->sortByDesc('sesi_id')->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function riwayatPribadi(int $mahasiswaId): Collection
    {
        return Absensi::query()
            ->select('absensi.*')
            ->join('sesi_absensi', 'sesi_absensi.id', '=', 'absensi.sesi_absensi_id')
            ->whereHas('pesertaMataKuliah', fn ($query) => $query->where('mahasiswa_id', $mahasiswaId))
            ->with('sesiAbsensi.kelasMataKuliah.mataKuliah')
            ->orderByDesc('sesi_absensi.tanggal')
            ->orderByDesc('absensi.id')
            ->get()
            ->map(function (Absensi $absensi): array {
                $sesi = $absensi->sesiAbsensi;
                $kelas = $sesi?->kelasMataKuliah;

                return [
                    'id' => $absensi->getKey(),
                    'tanggal_label' => $sesi?->tanggal?->translatedFormat('D, d F Y') ?? '-',
                    'pertemuan_ke' => $sesi?->pertemuan_ke,
                    'mata_kuliah' => $kelas?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'kode_kelas' => $kelas?->kode_kelas ?? '-',
                    'topik' => $sesi?->topik ?: 'Topik belum diisi',
                    'status' => $absensi->status?->value ?? '-',
                    'status_label' => $this->labelStatus($absensi->status),
                    'metode_label' => $this->labelMetode($absensi->metode),
                    'dicatat_pada_label' => $absensi->dicatat_pada
                        ?->copy()
                        ->setTimezone(self::ZONA_WAKTU)
                        ->translatedFormat('d M Y, H:i') ?? '-',
                ];
            });
    }

    public function pesertaBerhak(PesertaMataKuliah $peserta): bool
    {
        return KelayakanPeserta::berhak($peserta->status);
    }

    public function dalamWaktu(SesiAbsensi $sesi, ?Carbon $now = null): bool
    {
        $mulai = $this->waktu($sesi, 'waktu_mulai');
        $selesai = $this->waktu($sesi, 'waktu_selesai');

        if ($mulai === null || $selesai === null) {
            return false;
        }

        return ($now ?? Carbon::now(self::ZONA_WAKTU))->betweenIncluded($mulai, $selesai);
    }

    public function waktu(SesiAbsensi $sesi, string $field): ?Carbon
    {
        $nilai = $sesi->getAttribute($field);
        $tanggal = $sesi->tanggal;

        if ($nilai === null || $nilai === '' || $tanggal === null) {
            return null;
        }

        try {
            return Carbon::parse($tanggal->toDateString().' '.$nilai, self::ZONA_WAKTU);
        } catch (Throwable) {
            return null;
        }
    }

    public function rentangWaktu(SesiAbsensi $sesi): string
    {
        $mulai = $this->waktu($sesi, 'waktu_mulai');
        $selesai = $this->waktu($sesi, 'waktu_selesai');

        if ($mulai === null || $selesai === null) {
            return 'Waktu belum ditetapkan';
        }

        return $mulai->format('H:i').' – '.$selesai->format('H:i').' WIB';
    }

    public function labelStatus(?StatusAbsensi $status): string
    {
        return match ($status) {
            StatusAbsensi::Hadir => 'Hadir',
            StatusAbsensi::Izin => 'Izin',
            StatusAbsensi::Sakit => 'Sakit',
            StatusAbsensi::Alfa => 'Alfa',
            null => 'Belum ada',
        };
    }

    public function labelMetode(mixed $metode): string
    {
        return MetodeAbsensi::tryFrom((string) $metode)?->label() ?? 'Manual';
    }

    public function labelPeserta(PesertaMataKuliah $peserta): string
    {
        return match ($peserta->status) {
            StatusPeserta::Aktif => 'Aktif',
            StatusPeserta::Mengulang => 'Mengulang',
            StatusPeserta::Nonaktif => 'Nonaktif',
            null => '-',
        };
    }

    private function pesertaUntukMahasiswa(SesiAbsensi $sesi, int $mahasiswaId): ?PesertaMataKuliah
    {
        return PesertaMataKuliah::query()
            ->where('kelas_mata_kuliah_id', $sesi->kelas_mata_kuliah_id)
            ->where('mahasiswa_id', $mahasiswaId)
            ->first();
    }

    private function sesiMilikKelas(KelasMataKuliah $kelas, mixed $sesiId): SesiAbsensi
    {
        return SesiAbsensi::query()
            ->whereKey(is_numeric($sesiId) ? (int) $sesiId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->with('kelasMataKuliah')
            ->firstOrFail();
    }

    private function pesertaMilikKelas(KelasMataKuliah $kelas, mixed $pesertaId): PesertaMataKuliah
    {
        return PesertaMataKuliah::query()
            ->whereKey(is_numeric($pesertaId) ? (int) $pesertaId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->with('mahasiswa')
            ->firstOrFail();
    }

    private function pelanggaranUnik(QueryException $exception): bool
    {
        $pesan = strtoupper($exception->getMessage());

        return $exception->getCode() === '23000'
            || str_contains($pesan, 'UNIQUE CONSTRAINT FAILED')
            || str_contains($pesan, 'DUPLICATE ENTRY');
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function gagal(string $pesan, array $tambahan = []): array
    {
        return ['berhasil' => false, 'pesan' => $pesan, 'absensi' => null, ...$tambahan];
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function simpanGagal(Throwable $exception, string $pesan, array $tambahan = []): array
    {
        Log::error('Gagal memproses absensi', [
            'error' => $exception->getMessage(),
        ]);

        return $this->gagal($pesan, $tambahan);
    }
}
