<?php

namespace App\Services\Admin;

use App\Enums\JenisAgenda;
use App\Enums\JenisNotifikasi;
use App\Enums\JenisUjian;
use App\Enums\Semester;
use App\Models\AgendaAkademik;
use App\Models\TahunAkademik;
use App\Services\Notifikasi\NotifikasiService;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan jadwal UTS/UAS institusional administrator.
 *
 * Ujian disimpan sebagai satu baris agenda_akademik dengan jenis = ujian,
 * jenis_ujian = uts|uas, kelas_mata_kuliah_id = NULL, dan tahun_akademik_id
 * sebagai periode relasional. Tidak ada tabel atau event kalender duplikat.
 */
class JadwalUjianService
{
    private const JUDUL_MAKSIMAL = 200;

    public function __construct(private readonly NotifikasiService $notifikasi)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function daftar(array $filters): LengthAwarePaginator
    {
        Carbon::setLocale('id');

        $query = AgendaAkademik::query()
            ->with(['tahunAkademik', 'kelasMataKuliah.tahunAkademik'])
            ->where('jenis', JenisAgenda::Ujian->value)
            ->whereNotNull('jenis_ujian')
            ->whereNull('kelas_mata_kuliah_id');

        if (($filters['q'] ?? '') !== '') {
            $cari = '%'.strtolower((string) $filters['q']).'%';
            $query->where(function ($group) use ($cari): void {
                $group->whereRaw('lower(judul) like ?', [$cari])
                    ->orWhereRaw("lower(coalesce(deskripsi, '')) like ?", [$cari]);
            });
        }

        if (($filters['jenis_ujian'] ?? '') !== '') {
            $query->where('jenis_ujian', $filters['jenis_ujian']);
        }

        if (($filters['tahun_akademik'] ?? '') !== '') {
            $tahunId = (int) $filters['tahun_akademik'];

            if (TahunAkademik::query()->whereKey($tahunId)->doesntExist()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('tahun_akademik_id', $tahunId);
            }
        }

        if (($filters['semester'] ?? '') !== '') {
            $semester = Semester::tryFrom((string) $filters['semester']);

            if ($semester === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas(
                    'tahunAkademik',
                    fn ($periode) => $periode->where('semester', $semester->value),
                );
            }
        }

        if (($filters['tanggal'] ?? '') !== '') {
            $query->whereDate('tanggal_mulai', $filters['tanggal']);
        }

        $query->orderBy('tanggal_mulai')->orderBy('waktu_mulai')->orderBy('id');

        return $query->paginate(15)->through(fn (AgendaAkademik $item): array => $this->baris($item));
    }

    /**
     * @return array<string, string>
     */
    public function opsiJenisUjian(): array
    {
        return [
            JenisUjian::Uts->value => 'UTS — Ujian Tengah Semester',
            JenisUjian::Uas->value => 'UAS — Ujian Akhir Semester',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function opsiSemester(): array
    {
        return [
            Semester::Ganjil->value => 'Ganjil',
            Semester::Genap->value => 'Genap',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function opsiTahunAkademik(): array
    {
        return TahunAkademik::query()
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('semester')
            ->get()
            ->mapWithKeys(fn (TahunAkademik $tahun): array => [$tahun->id => $this->periodeLabel($tahun)])
            ->all();
    }

    /**
     * Nilai awal formulir. Periode dibaca dari relasi tahun akademik;
     * parsing judul hanya dipakai sebagai fallback data legacy yang belum
     * memiliki tahun_akademik_id.
     *
     * @return array{tahun_akademik_id: int|null, semester: string, judul: string}
     */
    public function formulir(?AgendaAkademik $ujian): array
    {
        $tahun = $ujian === null
            ? TahunAkademik::aktif()->first()
            : $this->periode($ujian);
        $tahun ??= TahunAkademik::aktif()->first();
        $jenisUjian = $ujian?->jenis_ujian ?? JenisUjian::Uts;

        return [
            'tahun_akademik_id' => $tahun === null ? null : (int) $tahun->getKey(),
            'semester' => $tahun?->semester?->value ?? Semester::Ganjil->value,
            'judul' => $tahun === null ? '' : $this->judulOtomatis($jenisUjian, $this->periodeLabel($tahun)),
        ];
    }

    public function temukan(mixed $id, bool $kunci = false): AgendaAkademik
    {
        $query = AgendaAkademik::query()
            ->with(['tahunAkademik', 'kelasMataKuliah.tahunAkademik'])
            ->whereKey(is_numeric($id) ? (int) $id : 0)
            ->where('jenis', JenisAgenda::Ujian->value)
            ->whereNotNull('jenis_ujian')
            ->whereNull('kelas_mata_kuliah_id');

        if ($kunci) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function simpan(array $data, int $adminId): array
    {
        return $this->tulis($data, $adminId, null, null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    public function perbarui(mixed $id, array $data): array
    {
        return $this->tulis($data, null, null, $id);
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapus(mixed $id): array
    {
        return DB::transaction(function () use ($id): array {
            $ujian = $this->temukan($id, true);
            $ujian->delete();

            return ['berhasil' => true, 'pesan' => 'Jadwal ujian berhasil dihapus.'];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, field: ?string}
     */
    private function tulis(array $data, ?int $adminId, ?AgendaAkademik $ujian, mixed $id): array
    {
        $jenisUjian = JenisUjian::tryFrom((string) $data['jenis_ujian']);
        if ($jenisUjian === null) {
            return $this->gagal('Jenis ujian hanya UTS atau UAS.', 'jenis_ujian');
        }

        $tahun = TahunAkademik::query()->whereKey((int) ($data['tahun_akademik_id'] ?? 0))->first();
        if ($tahun === null) {
            return $this->gagal('Tahun akademik tidak valid.', 'tahun_akademik_id');
        }

        $semester = Semester::tryFrom((string) ($data['semester'] ?? ''));
        if ($semester === null || $tahun->semester !== $semester) {
            return $this->gagal('Semester harus sesuai dengan tahun akademik yang dipilih.', 'semester');
        }

        $tanggalMulai = Carbon::createFromFormat('!Y-m-d', (string) ($data['tanggal_mulai'] ?? ''));
        $tanggalSelesai = Carbon::createFromFormat(
            '!Y-m-d',
            (string) ($data['tanggal_selesai'] ?? $data['tanggal_mulai'] ?? ''),
        );

        if (! $tanggalMulai instanceof Carbon || ! $tanggalSelesai instanceof Carbon) {
            return $this->gagal('Format tanggal tidak valid.', 'tanggal_mulai');
        }

        if ($tanggalSelesai->lessThan($tanggalMulai)) {
            return $this->gagal('Tanggal selesai tidak boleh sebelum tanggal mulai.', 'tanggal_selesai');
        }

        $mulai = $this->jam($data['waktu_mulai'] ?? null);
        $selesai = $this->jam($data['waktu_selesai'] ?? null);
        if (($mulai === null) !== ($selesai === null)) {
            return $this->gagal('Jam mulai dan jam selesai harus diisi berpasangan.', 'waktu_mulai');
        }
        if ($mulai !== null && $selesai !== null && $selesai <= $mulai) {
            return $this->gagal('Jam selesai harus lebih besar dari jam mulai.', 'waktu_selesai');
        }

        $lokasi = $this->lokasi($data['lokasi'] ?? null);

        $ujiLama = null;
        if ($ujian === null && $id !== null) {
            $ujiLama = AgendaAkademik::query()
                ->whereKey(is_numeric($id) ? (int) $id : 0)
                ->where('jenis', JenisAgenda::Ujian->value)
                ->first();
        } else {
            $ujiLama = $ujian;
        }

        $hasil = DB::transaction(function () use (
            $data,
            $adminId,
            $ujian,
            $id,
            $jenisUjian,
            $tahun,
            $tanggalMulai,
            $tanggalSelesai,
            $mulai,
            $selesai,
            $lokasi,
            $ujiLama,
        ): array {
            $record = $ujian;
            if ($record === null && $id !== null) {
                $record = $this->temukan($id, true);
            }

            $namaKunci = $this->namaKunciLokasi($lokasi);
            $kunciDiperoleh = false;

            if ($namaKunci !== null && DB::connection()->getDriverName() === 'mysql') {
                $hasil = DB::connection()->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$namaKunci]);
                $kunciDiperoleh = (bool) ($hasil?->acquired ?? 0);

                if (! $kunciDiperoleh) {
                    return $this->gagal(
                        'Lokasi sedang diperiksa transaksi lain. Coba lagi sebentar.',
                        'lokasi',
                    );
                }
            }

            try {
                $bentrok = $this->cariBentrok(
                    $tanggalMulai,
                    $tanggalSelesai,
                    $mulai,
                    $selesai,
                    $lokasi,
                    $record?->getKey(),
                );

                if ($bentrok !== null) {
                    return $this->gagal($bentrok, 'lokasi');
                }

                $atribut = [
                    'kelas_mata_kuliah_id' => null,
                    'tahun_akademik_id' => $tahun->getKey(),
                    'judul' => $this->judul($jenisUjian, $tahun, $data['judul'] ?? null),
                    'deskripsi' => $this->teks($data['deskripsi'] ?? null),
                    'jenis' => JenisAgenda::Ujian->value,
                    'jenis_ujian' => $jenisUjian->value,
                    'tanggal_mulai' => $tanggalMulai->toDateString(),
                    'tanggal_selesai' => $tanggalSelesai->toDateString(),
                    'waktu_mulai' => $mulai === null ? null : $mulai.':00',
                    'waktu_selesai' => $selesai === null ? null : $selesai.':00',
                    'lokasi' => $lokasi,
                    'dipublikasikan_pada' => (bool) ($data['dipublikasikan'] ?? true) ? now() : null,
                ];

                if ($record === null) {
                    $baru = new AgendaAkademik();
                    $baru->forceFill($atribut + ['dibuat_oleh' => $adminId])->save();
                    $pesan = 'Jadwal '.strtolower($jenisUjian->label()).' berhasil ditambahkan.';
                    $ujianTersimpan = $baru;
                } else {
                    $record->fill($atribut)->save();
                    $pesan = 'Jadwal '.strtolower($jenisUjian->label()).' berhasil diperbarui.';
                    $ujianTersimpan = $record;
                }

                if ($atribut['dipublikasikan_pada'] !== null && $this->perubahanJadwalRelevan($ujiLama, $atribut, $jenisUjian)) {
                    $tanda = md5(implode('|', [
                        $atribut['jenis_ujian'],
                        $atribut['judul'],
                        $atribut['tanggal_mulai'],
                        $atribut['tanggal_selesai'],
                        $atribut['waktu_mulai'] ?? '',
                        $atribut['waktu_selesai'] ?? '',
                        $atribut['lokasi'] ?? '',
                    ]));
                    $judulNotifikasi = $ujiLama === null
                        ? 'Jadwal ujian ditambahkan: '.$atribut['judul']
                        : 'Jadwal ujian diperbarui: '.$atribut['judul'];

                    DB::afterCommit(fn (): int => $this->notifikasi->kirimKeSemuaMahasiswa(
                        JenisNotifikasi::Ujian,
                        $judulNotifikasi,
                        'Jadwal '.$jenisUjian->label().' pada '.$atribut['tanggal_mulai'].'. Cek detail jadwal untuk informasi lengkap.',
                        '/mahasiswa/jadwal-ujian',
                        'ujian:'.$ujianTersimpan->getKey().':'.$tanda,
                    ));
                }

                return ['berhasil' => true, 'pesan' => $pesan, 'field' => null];
            } finally {
                if ($kunciDiperoleh) {
                    DB::connection()->selectOne('SELECT RELEASE_LOCK(?) AS released', [$namaKunci]);
                }
            }
        });

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function perubahanJadwalRelevan(?AgendaAkademik $lama, array $atribut, JenisUjian $jenis): bool
    {
        if ($lama === null) {
            return true;
        }

        return $lama->jenis_ujian !== $jenis
            || $lama->judul !== $atribut['judul']
            || $lama->tanggal_mulai->toDateString() !== $atribut['tanggal_mulai']
            || $lama->tanggal_selesai->toDateString() !== $atribut['tanggal_selesai']
            || $lama->waktu_mulai !== ($atribut['waktu_mulai'] ?? null)
            || $lama->waktu_selesai !== ($atribut['waktu_selesai'] ?? null)
            || $lama->lokasi !== ($atribut['lokasi'] ?? null);
    }

    /**
     * @return array{berhasil: false, pesan: string, field: string}
     */
    private function gagal(string $pesan, string $field): array
    {
        return ['berhasil' => false, 'pesan' => $pesan, 'field' => $field];
    }

    /**
     * Kebijakan bentrok:
     * - hanya ujian pada agenda_akademik yang diperiksa;
     * - lokasi kosong tidak dapat dibuktikan sama, jadi tidak dianggap bentrok;
     * - lokasi sama + tanggal tumpang tindih + jam tumpang tindih ditolak;
     * - bila salah satu agenda tanpa jam, tanggal yang tumpang tindih pada
     *   lokasi sama tetap dianggap bentrok (tidak dianggap bebas);
     * - record yang sedang disunting dikecualikan.
     *
     * GET_LOCK dipakai hanya di MySQL/MariaDB dan dikunci per lokasi untuk
     * mengurangi race condition antartransaksi. lockForUpdate() mengunci baris
     * kandidat yang sudah ada. Ini bukan jaminan bebas race condition mutlak.
     */
    private function cariBentrok(
        Carbon $tanggalMulai,
        Carbon $tanggalSelesai,
        ?string $mulai,
        ?string $selesai,
        ?string $lokasi,
        mixed $idDikecualikan,
    ): ?string {
        if ($lokasi === null || $lokasi === '') {
            return null;
        }

        $kandidat = AgendaAkademik::query()
            ->where('jenis', JenisAgenda::Ujian->value)
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai->toDateString())
            ->where(function ($query) use ($tanggalMulai): void {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggalMulai->toDateString());
            })
            ->when($idDikecualikan !== null, fn ($query) => $query->where('id', '!=', $idDikecualikan))
            ->orderBy('tanggal_mulai')
            ->orderBy('waktu_mulai')
            ->lockForUpdate()
            ->get(['id', 'judul', 'tanggal_mulai', 'tanggal_selesai', 'waktu_mulai', 'waktu_selesai', 'lokasi']);

        foreach ($kandidat as $item) {
            if (! $this->lokasiSama($lokasi, $item->lokasi)) {
                continue;
            }

            $itemMulai = $item->tanggal_mulai;
            $itemSelesai = $item->tanggal_selesai ?? $item->tanggal_mulai;

            if (! $itemMulai instanceof Carbon || ! $itemSelesai instanceof Carbon) {
                continue;
            }

            if ($tanggalMulai->greaterThan($itemSelesai) || $itemMulai->greaterThan($tanggalSelesai)) {
                continue;
            }

            $itemJamMulai = $this->jam($item->waktu_mulai);
            $itemJamSelesai = $this->jam($item->waktu_selesai);

            if (! $this->waktuBertumpang($mulai, $selesai, $itemJamMulai, $itemJamSelesai)) {
                continue;
            }

            $waktu = $itemJamMulai !== null && $itemJamSelesai !== null
                ? $itemJamMulai.'–'.$itemJamSelesai
                : 'jam tidak ditentukan';

            return sprintf(
                'Bentrok jadwal: lokasi "%s" sudah dipakai untuk %s pada %s %s.',
                $lokasi,
                $item->judul,
                $itemMulai->translatedFormat('d M Y'),
                $waktu,
            );
        }

        return null;
    }

    private function lokasiSama(?string $kiri, mixed $kanan): bool
    {
        $kanan = $this->lokasi($kanan);

        return $kiri !== null && $kanan !== null
            && mb_strtolower($kiri) === mb_strtolower($kanan);
    }

    private function waktuBertumpang(
        ?string $mulaiA,
        ?string $selesaiA,
        ?string $mulaiB,
        ?string $selesaiB,
    ): bool {
        if ($mulaiA === null || $selesaiA === null || $mulaiB === null || $selesaiB === null) {
            return true;
        }

        return $mulaiA < $selesaiB && $mulaiB < $selesaiA;
    }

    private function namaKunciLokasi(?string $lokasi): ?string
    {
        if ($lokasi === null || $lokasi === '') {
            return null;
        }

        return 'jadwal-ujian-'.substr(sha1(mb_strtolower($lokasi)), 0, 40);
    }

    private function judul(JenisUjian $jenisUjian, TahunAkademik $tahun, mixed $value): string
    {
        $periode = $this->periodeLabel($tahun);
        $suffix = ' — '.$periode;
        $custom = is_string($value) ? trim($value) : '';

        $judul = $custom !== '' && str_contains(strtoupper($custom), $jenisUjian->label())
            ? $custom
            : $this->judulOtomatis($jenisUjian, $periode);

        $judul = preg_replace('/ — (?:Ganjil|Genap) \d{4}\/\d{4}\s*$/u', '', $judul) ?? $judul;
        $maksimal = self::JUDUL_MAKSIMAL - mb_strlen($suffix);

        if (mb_strlen($judul) > $maksimal) {
            $judul = rtrim(mb_substr($judul, 0, $maksimal));
        }

        return $judul.$suffix;
    }

    private function judulOtomatis(JenisUjian $jenisUjian, string $periode): string
    {
        return match ($jenisUjian) {
            JenisUjian::Uts => 'Jadwal Ujian Tengah Semester (UTS) — '.$periode,
            JenisUjian::Uas => 'Jadwal Ujian Akhir Semester (UAS) — '.$periode,
        };
    }

    private function periodeLabel(TahunAkademik $tahun): string
    {
        return ucfirst($tahun->semester->value).' '.$tahun->tahun_mulai.'/'.$tahun->tahun_selesai;
    }

    /**
     * Fallback hanya untuk baris legacy tanpa tahun_akademik_id. Filter dan
     * formulir tidak memakai metode ini.
     */
    private function periode(AgendaAkademik $agenda): ?TahunAkademik
    {
        return $agenda->tahunAkademik
            ?? $agenda->kelasMataKuliah?->tahunAkademik
            ?? $this->tahunDariJudul((string) $agenda->judul);
    }

    private function tahunDariJudul(string $judul): ?TahunAkademik
    {
        if (preg_match('/\b(Ganjil|Genap)\s+(\d{4})\/(\d{4})\b/iu', $judul, $matches) !== 1) {
            return null;
        }

        return TahunAkademik::query()
            ->where('tahun_mulai', (int) $matches[2])
            ->where('tahun_selesai', (int) $matches[3])
            ->where('semester', strtolower($matches[1]))
            ->first();
    }

    private function jam(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $nilai = trim($nilai);
        if ($nilai === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $nilai) === 1 ? substr($nilai, 0, 5) : null;
    }

    private function lokasi(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $lokasi = trim($nilai);

        return $lokasi === '' ? null : $lokasi;
    }

    private function teks(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        return $teks === '' ? null : $teks;
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(AgendaAkademik $item): array
    {
        $jenisUjian = $item->jenis_ujian;
        $tahun = $this->periode($item);
        $mulai = $item->tanggal_mulai;
        $selesai = $item->tanggal_selesai;

        $tanggalLabel = $mulai === null
            ? '-'
            : ($selesai !== null && $selesai->greaterThan($mulai)
                ? $mulai->translatedFormat('d M Y').' – '.$selesai->translatedFormat('d M Y')
                : $mulai->translatedFormat('D, d M Y'));

        return [
            'id' => $item->getKey(),
            'judul' => $item->judul,
            'jenis_ujian' => $jenisUjian?->value ?? '',
            'jenis_label' => $jenisUjian?->label() ?? 'Ujian',
            'tahun' => $tahun?->label() ?? '-',
            'semester' => $tahun?->semester ? ucfirst($tahun->semester->value) : '-',
            'tanggal' => $mulai?->toDateString() ?? '',
            'tanggal_label' => $tanggalLabel,
            'waktu_mulai' => $this->jamLabel($item->waktu_mulai),
            'waktu_selesai' => $this->jamLabel($item->waktu_selesai),
            'lokasi' => $item->lokasi,
            'deskripsi' => $item->deskripsi,
            'dipublikasikan' => $item->dipublikasikan_pada !== null,
            'publikasi_label' => $item->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Draft',
        ];
    }

    private function jamLabel(mixed $value): string
    {
        if (! is_string($value)) {
            return '-';
        }

        $jam = substr($value, 0, 5);

        return $jam === '' ? '-' : $jam;
    }
}
