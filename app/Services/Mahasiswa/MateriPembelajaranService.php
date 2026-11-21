<?php

namespace App\Services\Mahasiswa;

use App\Enums\JenisAktivitasMateri;
use App\Enums\StatusPeserta;
use App\Support\KelayakanPeserta;
use App\Enums\StatusProgres;
use App\Models\MateriPembelajaran;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use App\Services\Dosen\MateriFileService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akses dan pembacaan materi perkuliahan untuk mahasiswa.
 *
 * Seluruh akses diverifikasi berdasarkan $userId akun login dan status
 * peserta aktif pada kelas mata kuliah terkait. Mahasiswa tidak dapat mengakses
 * materi draft, materi terjadwal di masa depan, atau materi dari kelas yang
 * tidak diikutinya.
 */
class MateriPembelajaranService
{
    public function __construct(
        private readonly MateriFileService $file,
        private readonly AktivitasMateriService $aktivitas,
    ) {
    }

    /**
     * Ambil seluruh pendaftaran kelas mahasiswa dengan status peserta aktif.
     *
     * @return Collection<int, PesertaMataKuliah>
     */
    public function kelasAktifMahasiswa(int $userId): Collection
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.dosen',
                'kelasMataKuliah.tahunAkademik',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Ambil daftar materi terpublikasi beserta ringkasan progres mahasiswa.
     *
     * @return array{
     *     kelasAktif: array<int, array<string, mixed>>,
     *     selectedKelas: ?array<string, mixed>,
     *     materi: Collection<int, array<string, mixed>>,
     *     stats: array{total: int, selesai: int, belum_selesai: int, persentase: int}
     * }
     */
    public function daftarMateri(int $userId, ?int $kelasId = null): array
    {
        $pendaftaranList = $this->kelasAktifMahasiswa($userId);

        if ($kelasId !== null) {
            $pendaftaranTerpilih = $pendaftaranList->first(
                fn (PesertaMataKuliah $p): bool => (int) $p->kelas_mata_kuliah_id === $kelasId
            );

            if ($pendaftaranTerpilih === null) {
                abort(Response::HTTP_NOT_FOUND);
            }

            $targetKelasIds = [$kelasId];
        } else {
            $targetKelasIds = $pendaftaranList->pluck('kelas_mata_kuliah_id')->all();
        }

        if ($pendaftaranList->isEmpty()) {
            return [
                'kelasAktif' => [],
                'selectedKelas' => null,
                'materi' => collect(),
                'stats' => [
                    'total' => 0,
                    'selesai' => 0,
                    'belum_selesai' => 0,
                    'persentase' => 0,
                ],
            ];
        }

        $kelasOptions = $pendaftaranList->map(function (PesertaMataKuliah $pendaftaran): array {
            $kelas = $pendaftaran->kelasMataKuliah;

            return [
                'id' => $kelas->id,
                'kode_kelas' => $kelas->kode_kelas,
                'nama_kelas' => $kelas->nama_kelas,
                'kode_mata_kuliah' => $kelas->mataKuliah?->kode ?? '',
                'nama_mata_kuliah' => $kelas->mataKuliah?->nama ?? '',
                'nama_dosen' => $kelas->dosen?->name ?? 'Dosen tidak tersedia',
                'label' => sprintf(
                    '%s — %s (%s)',
                    $kelas->mataKuliah?->nama ?? 'Mata Kuliah',
                    $kelas->nama_kelas,
                    $kelas->kode_kelas
                ),
            ];
        })->values()->all();

        $selectedKelas = null;

        if ($kelasId !== null) {
            $matchedKey = array_search($kelasId, array_column($kelasOptions, 'id'), true);
            $selectedKelas = $matchedKey !== false ? $kelasOptions[$matchedKey] : null;
        }

        $pesertaIds = $pendaftaranList->pluck('id')->all();

        $progresByMateri = ProgresMateri::query()
            ->whereIn('peserta_mata_kuliah_id', $pesertaIds)
            ->get()
            ->keyBy('materi_pembelajaran_id');

        $materiCollection = MateriPembelajaran::query()
            ->whereIn('kelas_mata_kuliah_id', $targetKelasIds)
            ->dipublikasikan()
            ->with(['kelasMataKuliah.mataKuliah', 'kelasMataKuliah.dosen'])
            ->orderByDesc('pertemuan_ke')
            ->orderByDesc('dipublikasikan_pada')
            ->orderByDesc('id')
            ->get();

        $totalMateri = $materiCollection->count();
        $selesaiCount = 0;

        $formattedMateri = $materiCollection->map(function (MateriPembelajaran $item) use ($progresByMateri, &$selesaiCount): array {
            $kelas = $item->kelasMataKuliah;
            $mataKuliah = $kelas?->mataKuliah;
            $progres = $progresByMateri->get($item->id);

            $statusProgres = $progres?->status ?? StatusProgres::BelumDimulai;
            $persenProgres = (int) ($progres?->progres ?? 0);
            $isSelesai = $statusProgres === StatusProgres::Selesai;

            if ($isSelesai) {
                $selesaiCount++;
            }

            return [
                'id' => $item->id,
                'kelas_id' => $item->kelas_mata_kuliah_id,
                'kode_kelas' => $kelas?->kode_kelas ?? '-',
                'nama_kelas' => $kelas?->nama_kelas ?? '-',
                'kode_mata_kuliah' => $mataKuliah?->kode ?? '-',
                'nama_mata_kuliah' => $mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'nama_dosen' => $kelas?->dosen?->name ?? 'Dosen tidak tersedia',
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'deskripsi_ringkas' => Str::limit((string) $item->deskripsi, 140),
                'pertemuan_ke' => $item->pertemuan_ke,
                'file_name' => $item->file_name,
                'file_mime' => $item->file_mime,
                'file_size_label' => $this->labelUkuran((int) $item->file_size),
                'dipublikasikan_label' => $item->dipublikasikan_pada?->format('d M Y H:i') ?? '-',
                'status_progres' => $statusProgres->value,
                'status_progres_label' => match ($statusProgres) {
                    StatusProgres::Selesai => 'Selesai',
                    StatusProgres::SedangDipelajari => 'Sedang dipelajari',
                    default => 'Belum dipelajari',
                },
                'status_progres_tone' => match ($statusProgres) {
                    StatusProgres::Selesai => 'selesai',
                    StatusProgres::SedangDipelajari => 'kuliah',
                    default => 'netral',
                },
                'persentase_progres' => $persenProgres,
            ];
        });

        $persentase = $totalMateri > 0 ? (int) round(($selesaiCount / $totalMateri) * 100) : 0;

        return [
            'kelasAktif' => $kelasOptions,
            'selectedKelas' => $selectedKelas,
            'materi' => $formattedMateri,
            'stats' => [
                'total' => $totalMateri,
                'selesai' => $selesaiCount,
                'belum_selesai' => $totalMateri - $selesaiCount,
                'persentase' => $persentase,
            ],
        ];
    }

    /**
     * Download berkas materi pembelajaran.
     *
     * Memastikan mahasiswa terdaftar aktif pada kelas dan materi telah dipublikasikan.
     */
    public function unduh(int $userId, int $kelasId, int $materiId): Response
    {
        /** @var PesertaMataKuliah $pendaftaran */
        $pendaftaran = PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
            ->firstOrFail();

        $materi = MateriPembelajaran::query()
            ->whereKey($materiId)
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->dipublikasikan()
            ->firstOrFail();

        if (! $this->file->pathMilikKelas($materi->file_path, $kelasId)) {
            abort(404);
        }

        if (! Storage::disk(MateriFileService::DISK)->exists($materi->file_path)) {
            abort(404);
        }

        // Aktivitas hanya dicatat jika berkas benar-benar tersedia dan akses sah.
        $this->aktivitas->catat($materi->id, $pendaftaran->id, JenisAktivitasMateri::Diunduh);

        return $this->file->unduh($materi->file_path, $materi->file_name, $materi->file_mime);
    }

    private function labelUkuran(int $byte): string
    {
        if ($byte >= 1048576) {
            return number_format($byte / 1048576, 1, ',', '.').' MB';
        }

        if ($byte >= 1024) {
            return (string) round($byte / 1024).' KB';
        }

        return $byte.' B';
    }
}
