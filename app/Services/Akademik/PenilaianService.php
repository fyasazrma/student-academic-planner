<?php

namespace App\Services\Akademik;

use App\Enums\KomponenNilai;
use App\Enums\StatusNilaiAkhir;
use App\Enums\StatusPeserta;
use App\Models\KelasMataKuliah;
use App\Models\Nilai;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\RiwayatPerubahanNilai;
use App\Models\User;
use App\Services\Dosen\TugasService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pengelolaan nilai akademik yang terikat pada kelas milik dosen.
 *
 * Setiap aksi diawali scoped lookup kelas berdasarkan dosen_id, lalu peserta
 * diverifikasi benar-benar terdaftar pada kelas tersebut. Hanya UTS dan UAS
 * yang dapat ditulis; nilai tugas tetap dibaca dari pengumpulan_tugas.nilai.
 */
class PenilaianService
{
    /** @var array<int, KomponenNilai> */
    public const KOMPONEN_DITULIS = [KomponenNilai::Uts, KomponenNilai::Uas];

    public const KOMPONEN_KONFIGURASI = 'konfigurasi_tugas';

    public function __construct(
        private readonly NilaiAkhirService $akhir,
        private readonly TugasService $tugasService,
    ) {
    }

    public function kelasMilikDosen(int $dosenId, mixed $kelasId): KelasMataKuliah
    {
        return $this->tugasService->kelasMilikDosen($dosenId, $kelasId);
    }

    public function pesertaMilikKelas(KelasMataKuliah $kelas, mixed $pesertaId): PesertaMataKuliah
    {
        return PesertaMataKuliah::query()
            ->whereKey(is_numeric($pesertaId) ? (int) $pesertaId : 0)
            ->where('kelas_mata_kuliah_id', $kelas->getKey())
            ->with('mahasiswa')
            ->firstOrFail();
    }

    /**
     * @return array{berhasil: bool, pesan: string, nilai: ?Nilai, komponen: ?KomponenNilai}
     */
    public function simpan(
        int $dosenId,
        mixed $kelasId,
        mixed $pesertaId,
        KomponenNilai $komponen,
        string $nilai,
        ?string $alasan,
        User $actor,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $peserta = $this->pesertaMilikKelas($kelas, $pesertaId);

        if (! in_array($komponen, self::KOMPONEN_DITULIS, true)) {
            return [
                'berhasil' => false,
                'pesan' => 'Komponen ini tidak dapat ditulis melalui fitur penilaian.',
                'nilai' => null,
                'komponen' => $komponen,
            ];
        }

        try {
            $model = DB::transaction(function () use ($peserta, $komponen, $nilai, $alasan, $actor): Nilai {
                $model = Nilai::query()
                    ->where('peserta_mata_kuliah_id', $peserta->getKey())
                    ->where('komponen', $komponen->value)
                    ->lockForUpdate()
                    ->first();

                $sebelum = $model?->nilai;

                if ($model === null) {
                    $model = new Nilai();
                    $model->pesertaMataKuliah()->associate($peserta);
                }

                $model->forceFill([
                    'komponen' => $komponen,
                    'nilai' => $this->akhir->formatNilai($nilai),
                    'dinilai_pada' => now(),
                    'keterangan' => $alasan,
                ]);
                $model->penilai()->associate($actor);
                $model->save();

                $this->catatPerubahan(
                    $model,
                    $peserta,
                    $komponen->value,
                    $sebelum,
                    $model->nilai,
                    $actor,
                    $alasan,
                );

                return $model;
            });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan nilai akademik', [
                'peserta_id' => $peserta->getKey(),
                'komponen' => $komponen->value,
                'error' => $exception->getMessage(),
            ]);

            return [
                'berhasil' => false,
                'pesan' => 'Nilai gagal disimpan. Silakan coba lagi.',
                'nilai' => null,
                'komponen' => $komponen,
            ];
        }

        return [
            'berhasil' => true,
            'pesan' => $model->wasRecentlyCreated ? 'Nilai berhasil ditambahkan.' : 'Nilai berhasil diperbarui.',
            'nilai' => $model,
            'komponen' => $komponen,
        ];
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function aturGunakanTugas(
        int $dosenId,
        mixed $kelasId,
        bool $gunakanTugas,
        ?string $alasan,
        User $actor,
    ): array {
        $kelas = $this->kelasMilikDosen($dosenId, $kelasId);
        $sebelum = $kelas->gunakanTugas();

        if ($sebelum === $gunakanTugas) {
            return ['berhasil' => true, 'pesan' => 'Konfigurasi komponen tugas sudah sesuai.'];
        }

        try {
            DB::transaction(function () use ($kelas, $gunakanTugas, $sebelum, $alasan, $actor): void {
                $kelas->forceFill(['gunakan_tugas' => $gunakanTugas])->save();

                $this->catatPerubahan(
                    null,
                    null,
                    self::KOMPONEN_KONFIGURASI,
                    $sebelum === null ? null : ($sebelum ? '1' : '0'),
                    $gunakanTugas ? '1' : '0',
                    $actor,
                    $alasan ?: 'Perubahan konfigurasi komponen tugas',
                );
            });
        } catch (Throwable $exception) {
            Log::error('Gagal mengubah konfigurasi komponen tugas', [
                'kelas_id' => $kelas->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return ['berhasil' => false, 'pesan' => 'Konfigurasi gagal disimpan. Silakan coba lagi.'];
        }

        return ['berhasil' => true, 'pesan' => 'Konfigurasi komponen tugas berhasil disimpan.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function detailNilai(PesertaMataKuliah $peserta): array
    {
        $kelas = $peserta->kelasMataKuliah;
        $uts = $this->nilaiKomponen($peserta, KomponenNilai::Uts);
        $uas = $this->nilaiKomponen($peserta, KomponenNilai::Uas);
        $tugas = $this->nilaiTugas($peserta);

        $hasil = $this->akhir->hitung(
            $uts,
            $uas,
            $tugas,
            $kelas?->gunakanTugas(),
        );

        return [
            'uts' => $uts,
            'uas' => $uas,
            'tugas' => $tugas,
            'uts_label' => $this->akhir->formatNilai($uts),
            'uas_label' => $this->akhir->formatNilai($uas),
            'tugas_label' => $this->akhir->formatNilai($tugas),
            'status' => $hasil['status']->value,
            'status_label' => $hasil['status']->label(),
            'alasan' => $hasil['alasan'],
            'alasan_label' => $this->akhir->alasanLabel($hasil['alasan']),
            'akhir' => $hasil['nilai_akhir'],
            'akhir_label' => $hasil['nilai_akhir'] ?? '-',
            'huruf' => $hasil['nilai_huruf']?->value,
            'bobot_mutu' => $hasil['bobot_mutu'],
            'bobot_mutu_label' => $hasil['bobot_mutu'] === null
                ? '-'
                : number_format($hasil['bobot_mutu'], 2, '.', ''),
        ];
    }

    public function nilaiKomponen(PesertaMataKuliah $peserta, KomponenNilai $komponen): ?string
    {
        if ($peserta->relationLoaded('nilai')) {
            $model = $peserta->nilai->first(
                fn (Nilai $item): bool => $item->komponen === $komponen,
            );
        } else {
            $model = $peserta->nilai()
                ->where('komponen', $komponen->value)
                ->first();
        }

        return $model?->nilai;
    }

    /**
     * Nilai tugas dibaca dari pengumpulan milik peserta pada kelas yang sama.
     * Baris dari kelas lain tidak pernah ikut dirata-ratakan.
     */
    public function nilaiTugas(PesertaMataKuliah $peserta): ?string
    {
        if ($peserta->relationLoaded('pengumpulanTugas')) {
            $kumpulan = $peserta->pengumpulanTugas;
        } else {
            $kumpulan = $peserta->pengumpulanTugas()
                ->with('tugas')
                ->get();
        }

        $kumpulan = $kumpulan->filter(function (PengumpulanTugas $item) use ($peserta): bool {
            if (! $item->relationLoaded('tugas')) {
                return true;
            }

            return $item->tugas === null
                || (int) $item->tugas->kelas_mata_kuliah_id === (int) $peserta->kelas_mata_kuliah_id;
        });

        return $this->akhir->agregasiNilaiTugas($kumpulan);
    }

    /**
     * @return array{kelas: KelasMataKuliah, total: int, lengkap: int, belum_lengkap: int, baris: Collection<int, array<string, mixed>>}
     */
    public function rekapKelas(KelasMataKuliah $kelas): array
    {
        $kelas->loadMissing(['mataKuliah', 'tahunAkademik']);

        $models = $kelas->peserta()
            ->with(['mahasiswa', 'nilai', 'pengumpulanTugas.tugas'])
            ->get()
            ->sortBy(fn (PesertaMataKuliah $item): string => Str::lower((string) $item->mahasiswa?->name))
            ->values();

        $baris = $models
            ->filter(fn (PesertaMataKuliah $item): bool => $item->status === StatusPeserta::Aktif
                || $item->nilai->isNotEmpty()
                || $item->pengumpulanTugas->isNotEmpty())
            ->map(fn (PesertaMataKuliah $item): array => $this->barisRekap($item, $kelas))
            ->values();

        return [
            'kelas' => $kelas,
            'total' => $baris->count(),
            'lengkap' => $baris->where('status', StatusNilaiAkhir::Lengkap->value)->count(),
            'belum_lengkap' => $baris->where('status', StatusNilaiAkhir::BelumLengkap->value)->count(),
            'baris' => $baris,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function daftarKelas(int $dosenId): Collection
    {
        $kelas = KelasMataKuliah::query()
            ->where('dosen_id', $dosenId)
            ->with([
                'mataKuliah',
                'tahunAkademik',
                'peserta.nilai',
                'peserta.pengumpulanTugas.tugas',
            ])
            ->orderByDesc('id')
            ->get();

        return $kelas->map(function (KelasMataKuliah $item): array {
            $rekap = $this->rekapKelas($item);

            return [
                'id' => $item->getKey(),
                'kode_kelas' => $item->kode_kelas,
                'nama_mata_kuliah' => $item->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'kode_mata_kuliah' => $item->mataKuliah?->kode ?? '-',
                'sks' => $item->mataKuliah?->sks,
                'periode' => $item->tahunAkademik?->label() ?? '-',
                'gunakan_tugas_label' => match ($item->gunakanTugas()) {
                    true => 'Tugas digunakan',
                    false => 'Tugas tidak digunakan',
                    null => 'Belum ditentukan',
                },
                'total' => $rekap['total'],
                'lengkap' => $rekap['lengkap'],
                'belum_lengkap' => $rekap['belum_lengkap'],
                'url' => route('dosen.nilai.kelas', $item->getKey()),
            ];
        });
    }

    /**
     * @return Collection<int, RiwayatPerubahanNilai>
     */
    public function riwayat(KelasMataKuliah $kelas, PesertaMataKuliah $peserta): Collection
    {
        if ((int) $peserta->kelas_mata_kuliah_id !== (int) $kelas->getKey()) {
            abort(404);
        }

        return RiwayatPerubahanNilai::query()
            ->where('peserta_mata_kuliah_id', $peserta->getKey())
            ->whereIn('komponen', array_map(
                fn (KomponenNilai $item): string => $item->value,
                self::KOMPONEN_DITULIS,
            ))
            ->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function barisRekap(PesertaMataKuliah $peserta, KelasMataKuliah $kelas): array
    {
        $detail = $this->detailNilai($peserta);

        return [
            'peserta_id' => $peserta->getKey(),
            'nama' => $peserta->mahasiswa?->name ?? 'Mahasiswa tidak tersedia',
            'nim' => $peserta->mahasiswa?->nim ?? '-',
            'status_peserta' => $peserta->status?->value ?? '-',
            'arsip' => $peserta->status !== StatusPeserta::Aktif,
            'uts_label' => $detail['uts_label'],
            'uas_label' => $detail['uas_label'],
            'tugas_label' => $detail['tugas_label'],
            'akhir_label' => $detail['akhir_label'],
            'huruf' => $detail['huruf'],
            'bobot_mutu_label' => $detail['bobot_mutu_label'],
            'status' => $detail['status'],
            'status_label' => $detail['status_label'],
            'alasan_label' => $detail['alasan_label'],
            'riwayat_url' => route('dosen.nilai.riwayat', [$kelas->getKey(), $peserta->getKey()]),
        ];
    }

    private function catatPerubahan(
        ?Nilai $nilai,
        ?PesertaMataKuliah $peserta,
        string $komponen,
        mixed $sebelum,
        mixed $sesudah,
        User $actor,
        ?string $alasan,
    ): void {
        $riwayat = new RiwayatPerubahanNilai();
        $riwayat->forceFill([
            'nilai_id' => $nilai?->getKey(),
            'peserta_mata_kuliah_id' => $peserta?->getKey(),
            'komponen' => $komponen,
            'nilai_sebelum' => $sebelum,
            'nilai_sesudah' => $sesudah,
            'alasan' => $alasan,
        ]);
        $riwayat->actor()->associate($actor);
        $riwayat->save();
    }
}
