<?php

namespace App\Services\Dosen;

use App\Enums\StatusPeserta;
use App\Enums\StatusTugasMahasiswa;
use App\Support\KelayakanPeserta;
use App\Support\StatusTugasResolver;
use App\Models\KelasMataKuliah;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\Tugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data pengumpulan tugas untuk dosen.
 *
 * Fase ini bersifat read-only. Setiap aksi diawali scoped lookup kelas milik
 * dosen, lalu tugas di dalam kelas tersebut, lalu pengumpulan yang benar-benar
 * terkait dengan tugas dan peserta kelas yang sama.
 */
class PengumpulanTugasService
{
    public function __construct(private readonly TugasService $tugasService)
    {
    }

    /**
     * Resolver pengumpulan milik tugas dan kelas yang diberikan.
     */
    public function pengumpulanMilikTugasKelas(
        KelasMataKuliah $kelas,
        Tugas $tugas,
        mixed $pengumpulanId,
    ): PengumpulanTugas {
        return PengumpulanTugas::query()
            ->whereKey(is_numeric($pengumpulanId) ? (int) $pengumpulanId : 0)
            ->where('tugas_id', $tugas->getKey())
            ->whereHas('pesertaMataKuliah', fn ($peserta) => $peserta
                ->where('kelas_mata_kuliah_id', $kelas->getKey()))
            ->with(['tugas', 'pesertaMataKuliah.mahasiswa', 'penilai'])
            ->firstOrFail();
    }

    /**
     * @return array{
     *     kelas: KelasMataKuliah,
     *     info_tugas: array<string, mixed>,
     *     statistik: array<string, int>,
     *     peserta: Collection<int, array<string, mixed>>
     * }
     */
    public function daftar(int $dosenId, mixed $kelasId, mixed $tugasId): array
    {
        $kelas = $this->tugasService->kelasMilikDosen($dosenId, $kelasId);
        $tugas = $this->tugasService->tugasMilikKelas($kelas, $tugasId);

        $pesertaModels = $kelas->peserta()
            ->with('mahasiswa')
            ->get()
            ->sortBy(fn (PesertaMataKuliah $item): string => Str::lower((string) $item->mahasiswa?->name))
            ->values();

        $pengumpulanModels = $tugas->pengumpulan()
            ->whereIn('peserta_mata_kuliah_id', $pesertaModels->pluck('id')->all())
            ->with('penilai')
            ->get()
            ->keyBy('peserta_mata_kuliah_id');

        $peserta = $pesertaModels
            ->filter(fn (PesertaMataKuliah $item): bool => KelayakanPeserta::berhak($item->status)
                || $pengumpulanModels->has($item->getKey()))
            ->map(fn (PesertaMataKuliah $item): array => $this->barisPeserta(
                $item,
                $pengumpulanModels->get($item->getKey()),
                $kelas,
                $tugas,
            ))
            ->values();

        return [
            'kelas' => $kelas,
            'info_tugas' => $this->infoTugas($tugas),
            'statistik' => [
                'jumlah_peserta' => $peserta->count(),
                'sudah_mengumpulkan' => $peserta->where('sudah_mengumpulkan', true)->count(),
                'belum_mengumpulkan' => $peserta->where('sudah_mengumpulkan', false)->count(),
                'belum_dinilai' => $peserta
                    ->filter(fn (array $baris): bool => $baris['sudah_mengumpulkan'] && ! $baris['sudah_dinilai'])
                    ->count(),
                'sudah_dinilai' => $peserta->where('sudah_dinilai', true)->count(),
                'terlambat' => $peserta->where('terlambat', true)->count(),
                'belum_dikumpulkan' => $peserta
                    ->filter(fn (array $baris): bool => ! $baris['sudah_mengumpulkan']
                        && $baris['status'] === StatusTugasMahasiswa::BelumDikumpulkan->value)
                    ->count(),
                'terlambat_belum_mengumpulkan' => $peserta
                    ->filter(fn (array $baris): bool => ! $baris['sudah_mengumpulkan']
                        && $baris['status'] === StatusTugasMahasiswa::Terlambat->value)
                    ->count(),
            ],
            'peserta' => $peserta,
        ];
    }

    /**
     * @return array{
     *     kelas: KelasMataKuliah,
     *     info_tugas: array<string, mixed>,
     *     pengumpulan: array<string, mixed>
     * }
     */
    public function detail(int $dosenId, mixed $kelasId, mixed $tugasId, mixed $pengumpulanId): array
    {
        $kelas = $this->tugasService->kelasMilikDosen($dosenId, $kelasId);
        $tugas = $this->tugasService->tugasMilikKelas($kelas, $tugasId);
        $pengumpulan = $this->pengumpulanMilikTugasKelas($kelas, $tugas, $pengumpulanId);
        $peserta = $pengumpulan->pesertaMataKuliah;
        $sudahDinilai = $pengumpulan->sudahDinilai();
        $arsip = $peserta !== null && ! KelayakanPeserta::berhak($peserta->status);

        return [
            'kelas' => $kelas,
            'info_tugas' => $this->infoTugas($tugas),
            'pengumpulan' => [
                'id' => $pengumpulan->getKey(),
                'nama' => $peserta?->mahasiswa?->name ?? 'Mahasiswa tidak tersedia',
                'nim' => $peserta?->mahasiswa?->nim ?? '-',
                'status_peserta_label' => $this->labelStatusPeserta($peserta?->status),
                'arsip' => $arsip,
                'status_label' => $this->labelStatus($pengumpulan->status),
                'status_tone' => $this->toneStatus($pengumpulan->status),
                'dikumpulkan_label' => $pengumpulan->dikumpulkan_pada?->translatedFormat('d M Y H:i') ?? '-',
                'terlambat' => (bool) $pengumpulan->terlambat,
                'catatan' => $pengumpulan->catatan,
                'lampiran_nama' => $pengumpulan->file_name,
                'lampiran_label' => $pengumpulan->file_name === null
                    ? 'Tidak ada lampiran'
                    : $pengumpulan->file_name.' ('.$this->labelUkuran((int) $pengumpulan->file_size).')',
                'lampiran_mime' => $pengumpulan->file_mime,
                'sudah_dinilai' => $sudahDinilai,
                'nilai_label' => $sudahDinilai
                    ? $this->labelNilai($pengumpulan->nilai, $tugas)
                    : null,
                'umpan_balik' => $pengumpulan->umpan_balik,
                'penilai' => $pengumpulan->penilai?->name,
                'dinilai_pada_label' => $pengumpulan->dinilai_pada?->translatedFormat('d M Y H:i'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function barisPeserta(
        PesertaMataKuliah $peserta,
        ?PengumpulanTugas $pengumpulan,
        KelasMataKuliah $kelas,
        Tugas $tugas,
    ): array {
        $sudahMengumpulkan = $pengumpulan !== null;
        $sudahDinilai = $sudahMengumpulkan && $pengumpulan->sudahDinilai();
        $status = $sudahMengumpulkan
            ? $pengumpulan->status
            : StatusTugasResolver::tentukan(null, $tugas->deadline)->value;
        $arsip = ! KelayakanPeserta::berhak($peserta->status) && $sudahMengumpulkan;

        return [
            'peserta_id' => $peserta->getKey(),
            'nama' => $peserta->mahasiswa?->name ?? 'Mahasiswa tidak tersedia',
            'nim' => $peserta->mahasiswa?->nim ?? '-',
            'status_peserta' => $peserta->status?->value ?? '-',
            'status_peserta_label' => $this->labelStatusPeserta($peserta->status),
            'arsip' => $arsip,
            'sudah_mengumpulkan' => $sudahMengumpulkan,
            'sudah_dinilai' => $sudahDinilai,
            'terlambat' => $sudahMengumpulkan && (bool) $pengumpulan->terlambat,
            'status' => $status,
            'status_label' => $sudahMengumpulkan ? $this->labelStatus($status) : StatusTugasMahasiswa::labelDariNilai($status),
            'status_tone' => $sudahMengumpulkan ? $this->toneStatus($status) : StatusTugasMahasiswa::toneDariNilai($status),
            'dikumpulkan_label' => $pengumpulan?->dikumpulkan_pada?->translatedFormat('d M Y H:i') ?? '-',
            'lampiran_nama' => $pengumpulan?->file_name,
            'nilai_label' => $sudahDinilai
                ? $this->labelNilai($pengumpulan->nilai, $tugas)
                : null,
            'detail_url' => $sudahMengumpulkan
                ? route('dosen.mata-kuliah.tugas.pengumpulan.show', [
                    $kelas->getKey(),
                    $tugas->getKey(),
                    $pengumpulan->getKey(),
                ])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function infoTugas(Tugas $tugas): array
    {
        return [
            'id' => $tugas->getKey(),
            'judul' => $tugas->judul,
            'deskripsi_ringkas' => Str::limit((string) $tugas->deskripsi, 180),
            'publikasi_label' => $tugas->dipublikasikan_pada?->translatedFormat('d M Y H:i')
                ?? 'Belum dipublikasikan',
            'deadline_label' => $tugas->deadline->translatedFormat('d M Y H:i'),
            'nilai_maksimal' => $this->labelNilaiMaksimal($tugas),
        ];
    }

    private function labelNilai(mixed $nilai, Tugas $tugas): string
    {
        return number_format((float) $nilai, 2, '.', '').' / '.$this->labelNilaiMaksimal($tugas);
    }

    private function labelNilaiMaksimal(Tugas $tugas): string
    {
        $label = number_format((float) $tugas->nilai_maksimal, 2, '.', '');

        return str_ends_with($label, '.00') ? substr($label, 0, -3) : $label;
    }

    private function labelStatus(string $status): string
    {
        return match ($status) {
            'dinilai' => 'Sudah dinilai',
            'terlambat' => 'Terlambat',
            'terkumpul' => 'Terkumpul',
            default => 'Belum mengumpulkan',
        };
    }

    private function toneStatus(string $status): string
    {
        return match ($status) {
            'dinilai' => 'selesai',
            'terlambat' => 'deadline',
            'terkumpul' => 'tugas',
            default => 'netral',
        };
    }

    private function labelStatusPeserta(?StatusPeserta $status): string
    {
        return match ($status) {
            StatusPeserta::Aktif => 'Aktif',
            StatusPeserta::Nonaktif => 'Nonaktif',
            StatusPeserta::Mengulang => 'Mengulang',
            default => '-',
        };
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
