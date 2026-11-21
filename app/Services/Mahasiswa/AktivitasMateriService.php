<?php

namespace App\Services\Mahasiswa;

use App\Enums\JenisAktivitasMateri;
use App\Models\AktivitasMateri;
use Illuminate\Support\Collection;

/**
 * Pencatatan aktivitas baca/unduh materi.
 *
 * Aktivitas ini sengaja dipisah dari progres penyelesaian: membuka atau
 * mengunduh tidak pernah menandai materi sebagai selesai.
 */
class AktivitasMateriService
{
    /**
     * Catat satu kejadian aktivitas yang sudah berhasil divalidasi pemanggil.
     */
    public function catat(
        int $materiId,
        int $pesertaId,
        JenisAktivitasMateri $jenis,
    ): AktivitasMateri {
        return AktivitasMateri::query()->create([
            'materi_pembelajaran_id' => $materiId,
            'peserta_mata_kuliah_id' => $pesertaId,
            'jenis' => $jenis->value,
        ]);
    }

    /**
     * Ringkasan aktivitas satu materi untuk satu peserta.
     *
     * @return array{dibuka: int, diunduh: int, terakhir_dibuka: ?string, terakhir_diunduh: ?string}
     */
    public function ringkasan(int $materiId, int $pesertaId): array
    {
        $aktivitas = AktivitasMateri::query()
            ->where('materi_pembelajaran_id', $materiId)
            ->where('peserta_mata_kuliah_id', $pesertaId)
            ->orderByDesc('created_at')
            ->get();

        return [
            'dibuka' => $aktivitas->where('jenis', JenisAktivitasMateri::Dibuka->value)->count(),
            'diunduh' => $aktivitas->where('jenis', JenisAktivitasMateri::Diunduh->value)->count(),
            'terakhir_dibuka' => $aktivitas->firstWhere('jenis', JenisAktivitasMateri::Dibuka->value)?->created_at?->translatedFormat('d M Y H:i'),
            'terakhir_diunduh' => $aktivitas->firstWhere('jenis', JenisAktivitasMateri::Diunduh->value)?->created_at?->translatedFormat('d M Y H:i'),
        ];
    }

    /**
     * Ringkasan aktivitas untuk banyak materi sekaligus (menghindari N+1).
     *
     * @param  Collection<int, int>  $materiIds
     * @return Collection<int, array{dibuka: int, diunduh: int}>
     */
    public function ringkasanBanyak(int $pesertaId, Collection $materiIds): Collection
    {
        if ($materiIds->isEmpty()) {
            return collect();
        }

        return AktivitasMateri::query()
            ->selectRaw('materi_pembelajaran_id, jenis, COUNT(*) AS jumlah')
            ->where('peserta_mata_kuliah_id', $pesertaId)
            ->whereIn('materi_pembelajaran_id', $materiIds->all())
            ->groupBy('materi_pembelajaran_id', 'jenis')
            ->get()
            ->groupBy('materi_pembelajaran_id')
            ->map(function (Collection $rows): array {
                return [
                    'dibuka' => (int) ($rows->firstWhere('jenis', JenisAktivitasMateri::Dibuka->value)?->jumlah ?? 0),
                    'diunduh' => (int) ($rows->firstWhere('jenis', JenisAktivitasMateri::Diunduh->value)?->jumlah ?? 0),
                ];
            });
    }
}
