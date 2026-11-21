<?php

namespace App\Services\Mahasiswa;

use App\Enums\JenisAktivitasMateri;
use App\Enums\StatusPeserta;
use App\Enums\StatusProgres;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Progres penyelesaian materi dan target otomatis mahasiswa.
 *
 * Target otomatis tidak disalin ke tabel target_belajar; angkanya dihitung
 * langsung dari materi terpublikasi dan progres penyelesaian.
 */
class ProgresMateriService
{
    public function __construct(
        private readonly MateriPembelajaranService $materiService,
        private readonly AktivitasMateriService $aktivitas,
    ) {
    }

    /**
     * Detail materi sekaligus pencatatan aktivitas "dibuka" setelah akses
     * berhasil divalidasi. Membuka tidak mengubah status penyelesaian.
     *
     * @return array<string, mixed>
     */
    public function detail(int $userId, int $kelasId, int $materiId): array
    {
        [$pendaftaran, $materi] = $this->akses($userId, $kelasId, $materiId);

        $this->aktivitas->catat($materi->id, $pendaftaran->id, JenisAktivitasMateri::Dibuka);

        $progres = $this->progres($pendaftaran->id, $materi->id);
        $status = $progres?->status ?? StatusProgres::BelumDimulai;

        return [
            'user' => null,
            'materi' => [
                'id' => $materi->id,
                'kelas_id' => $materi->kelas_mata_kuliah_id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'file_name' => $materi->file_name,
                'file_mime' => $materi->file_mime,
                'pertemuan_ke' => $materi->pertemuan_ke,
                'dipublikasikan_label' => $materi->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? '-',
                'kode_mata_kuliah' => $materi->kelasMataKuliah?->mataKuliah?->kode ?? '-',
                'nama_mata_kuliah' => $materi->kelasMataKuliah?->mataKuliah?->nama ?? '-',
                'nama_kelas' => $materi->kelasMataKuliah?->nama_kelas ?? '-',
                'kode_kelas' => $materi->kelasMataKuliah?->kode_kelas ?? '-',
                'nama_dosen' => $materi->kelasMataKuliah?->dosen?->name ?? '-',
            ],
            'progres' => [
                'status' => $status->value,
                'status_label' => $this->labelStatus($status),
                'status_tone' => $this->toneStatus($status),
                'persentase' => $progres?->progres ?? 0,
                'selesai' => $status === StatusProgres::Selesai,
                'selesai_label' => $progres?->selesai_pada?->translatedFormat('d M Y H:i') ?? '-',
            ],
            'aktivitas' => $this->aktivitas->ringkasan($materi->id, $pendaftaran->id),
        ];
    }

    public function tandaiSelesai(int $userId, int $kelasId, int $materiId): ProgresMateri
    {
        return DB::transaction(function () use ($userId, $kelasId, $materiId): ProgresMateri {
            [$pendaftaran, $materi] = $this->akses($userId, $kelasId, $materiId);

            $progres = $this->progres($pendaftaran->id, $materi->id);

            if ($progres === null) {
                $progres = new ProgresMateri();
                $progres->forceFill([
                    'materi_pembelajaran_id' => $materi->id,
                    'peserta_mata_kuliah_id' => $pendaftaran->id,
                ]);
            }

            $progres->forceFill([
                'status' => StatusProgres::Selesai,
                'progres' => 100,
                'selesai_pada' => now(),
            ])->save();

            return $progres;
        });
    }

    public function batalkanSelesai(int $userId, int $kelasId, int $materiId): ?ProgresMateri
    {
        return DB::transaction(function () use ($userId, $kelasId, $materiId): ?ProgresMateri {
            [$pendaftaran, $materi] = $this->akses($userId, $kelasId, $materiId);

            $progres = $this->progres($pendaftaran->id, $materi->id);

            if ($progres === null || $progres->status !== StatusProgres::Selesai) {
                return $progres;
            }

            // Baris progres dipertahankan sebagai riwayat, bukan dihapus.
            $progres->forceFill([
                'status' => StatusProgres::BelumDimulai,
                'progres' => 0,
                'selesai_pada' => null,
            ])->save();

            return $progres;
        });
    }

    /**
     * Ringkasan target otomatis per mata kuliah.
     *
     * @return array{kelas: array<int, array<string, mixed>>, total: int, selesai: int, persentase: float, belum_selesai: array<int, array<string, mixed>>}
     */
    public function ringkasanOtomatis(int $userId): array
    {
        $pendaftaranList = $this->materiService->kelasAktifMahasiswa($userId);

        if ($pendaftaranList->isEmpty()) {
            return [
                'kelas' => [],
                'total' => 0,
                'selesai' => 0,
                'persentase' => 0.0,
                'belum_selesai' => [],
            ];
        }

        $kelasIds = $pendaftaranList->pluck('kelas_mata_kuliah_id')->all();
        $pendaftaranById = $pendaftaranList->keyBy('id');

        $materiList = MateriPembelajaran::query()
            ->whereIn('kelas_mata_kuliah_id', $kelasIds)
            ->dipublikasikan()
            ->with('kelasMataKuliah.mataKuliah')
            ->orderByDesc('pertemuan_ke')
            ->orderByDesc('dipublikasikan_pada')
            ->get();

        $progresByKunci = ProgresMateri::query()
            ->whereIn('peserta_mata_kuliah_id', $pendaftaranById->keys()->all())
            ->get()
            ->keyBy(fn (ProgresMateri $row): string => $this->kunci($row->peserta_mata_kuliah_id, $row->materi_pembelajaran_id));

        $materiPerKelas = $materiList->groupBy('kelas_mata_kuliah_id');
        $total = 0;
        $selesaiTotal = 0;
        $belumSelesai = [];

        $kelasRingkasan = $pendaftaranList->map(function (PesertaMataKuliah $pendaftaran) use (
            $materiPerKelas,
            $progresByKunci,
            &$total,
            &$selesaiTotal,
            &$belumSelesai,
        ): array {
            $materiKelas = $materiPerKelas->get($pendaftaran->kelas_mata_kuliah_id, collect());
            $selesaiKelas = 0;

            $materiFormatted = $materiKelas->map(function (MateriPembelajaran $materi) use (
                $pendaftaran,
                $progresByKunci,
                &$selesaiKelas,
                &$belumSelesai,
            ): array {
                $progres = $progresByKunci->get($this->kunci($pendaftaran->id, $materi->id));
                $status = $progres?->status ?? StatusProgres::BelumDimulai;
                $selesai = $status === StatusProgres::Selesai;

                if ($selesai) {
                    $selesaiKelas++;
                } else {
                    $belumSelesai[] = [
                        'id' => $materi->id,
                        'kelas_id' => $materi->kelas_mata_kuliah_id,
                        'judul' => $materi->judul,
                        'nama_mata_kuliah' => $materi->kelasMataKuliah?->mataKuliah?->nama ?? '-',
                    ];
                }

                return [
                    'id' => $materi->id,
                    'kelas_id' => $materi->kelas_mata_kuliah_id,
                    'judul' => $materi->judul,
                    'status' => $status->value,
                    'status_label' => $this->labelStatus($status),
                    'status_tone' => $this->toneStatus($status),
                    'selesai' => $selesai,
                ];
            })->values()->all();

            $jumlah = $materiKelas->count();
            $total += $jumlah;
            $selesaiTotal += $selesaiKelas;

            return [
                'kelas_id' => $pendaftaran->kelas_mata_kuliah_id,
                'nama_mata_kuliah' => $pendaftaran->kelasMataKuliah?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'kode_mata_kuliah' => $pendaftaran->kelasMataKuliah?->mataKuliah?->kode ?? '-',
                'nama_kelas' => $pendaftaran->kelasMataKuliah?->nama_kelas ?? '-',
                'kode_kelas' => $pendaftaran->kelasMataKuliah?->kode_kelas ?? '-',
                'total' => $jumlah,
                'selesai' => $selesaiKelas,
                'belum_selesai' => $jumlah - $selesaiKelas,
                'persentase' => $jumlah > 0 ? round($selesaiKelas / $jumlah * 100, 2) : 0.0,
                'materi' => $materiFormatted,
            ];
        })->values()->all();

        return [
            'kelas' => $kelasRingkasan,
            'total' => $total,
            'selesai' => $selesaiTotal,
            'persentase' => $total > 0 ? round($selesaiTotal / $total * 100, 2) : 0.0,
            'belum_selesai' => collect($belumSelesai)->take(5)->values()->all(),
        ];
    }

    /**
     * @return array{PesertaMataKuliah, MateriPembelajaran}
     */
    private function akses(int $userId, int $kelasId, int $materiId): array
    {
        /** @var PesertaMataKuliah $pendaftaran */
        $pendaftaran = PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->where('status', StatusPeserta::Aktif->value)
            ->with('kelasMataKuliah.mataKuliah')
            ->firstOrFail();

        /** @var MateriPembelajaran $materi */
        $materi = MateriPembelajaran::query()
            ->whereKey($materiId)
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->dipublikasikan()
            ->with(['kelasMataKuliah.mataKuliah', 'kelasMataKuliah.dosen'])
            ->firstOrFail();

        return [$pendaftaran, $materi];
    }

    private function progres(int $pendaftaranId, int $materiId): ?ProgresMateri
    {
        return ProgresMateri::query()
            ->where('peserta_mata_kuliah_id', $pendaftaranId)
            ->where('materi_pembelajaran_id', $materiId)
            ->first();
    }

    private function kunci(int $pendaftaranId, int $materiId): string
    {
        return $pendaftaranId.':'.$materiId;
    }

    private function labelStatus(StatusProgres $status): string
    {
        return match ($status) {
            StatusProgres::Selesai => 'Selesai',
            StatusProgres::SedangDipelajari => 'Sedang dipelajari',
            default => 'Belum dipelajari',
        };
    }

    private function toneStatus(StatusProgres $status): string
    {
        return match ($status) {
            StatusProgres::Selesai => 'selesai',
            StatusProgres::SedangDipelajari => 'kuliah',
            default => 'netral',
        };
    }
}
