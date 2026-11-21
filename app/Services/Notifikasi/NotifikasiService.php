<?php

namespace App\Services\Notifikasi;

use App\Enums\JenisNotifikasi;
use App\Enums\StatusPeserta;
use App\Enums\UserRole;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Penulis tunggal notifikasi akademik in-app.
 *
 * Notifikasi memakai tabel `notifikasi` kustom (bukan tabel `notifications`
 * bawaan Laravel). Pemilik dan status baca ditetapkan server-side; duplikasi
 * dicegah berdasarkan kunci sumber yang stabil.
 */
class NotifikasiService
{
    /**
     * @param  iterable<int>  $userIds
     */
    public function kirimKeMahasiswa(
        iterable $userIds,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
    ): int {
        $ids = collect($userIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $penerima = User::query()
            ->whereIn('id', $ids->all())
            ->where('role', UserRole::Mahasiswa->value)
            ->where('is_active', true)
            ->pluck('id');

        if ($penerima->isEmpty()) {
            return 0;
        }

        $tautanAman = $this->tautanAman($tautan);

        return DB::transaction(function () use ($penerima, $tipe, $judul, $pesan, $tautanAman, $sumber): int {
            $dibuat = 0;

            foreach ($penerima as $userId) {
                $sudahAda = Notifikasi::untukUser((int) $userId)
                    ->where('tipe', $tipe->value)
                    ->where('data->sumber', $sumber)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                $notifikasi = new Notifikasi();
                $notifikasi->forceFill([
                    'user_id' => (int) $userId,
                    'tipe' => $tipe->value,
                    'judul' => $judul,
                    'pesan' => $pesan,
                    'data' => ['sumber' => $sumber],
                    'tautan' => $tautanAman,
                    'dibaca_pada' => null,
                ])->save();

                $dibuat++;
            }

            return $dibuat;
        });
    }

    public function kirimKeSemuaMahasiswa(
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
    ): int {
        $ids = User::query()
            ->where('role', UserRole::Mahasiswa->value)
            ->where('is_active', true)
            ->pluck('id');

        return $this->kirimKeMahasiswa($ids, $tipe, $judul, $pesan, $tautan, $sumber);
    }

    public function kirimKeKelas(
        int $kelasId,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
    ): int {
        $ids = PesertaMataKuliah::query()
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->where('status', StatusPeserta::Aktif->value)
            ->pluck('mahasiswa_id');

        return $this->kirimKeMahasiswa($ids, $tipe, $judul, $pesan, $tautan, $sumber);
    }

    public function tandaiSemuaDibaca(int $userId): int
    {
        return DB::transaction(fn (): int => Notifikasi::untukUser($userId)
            ->belumDibaca()
            ->update(['dibaca_pada' => now()]));
    }

    /**
     * Hapus notifikasi yang sumbernya sudah tidak lagi tersedia.
     */
    public function hapusBersumber(string $sumber): int
    {
        return DB::transaction(fn (): int => Notifikasi::query()
            ->where('data->sumber', $sumber)
            ->delete());
    }

    /**
     * Hanya path internal area mahasiswa yang disimpan. Tautan eksternal,
     * protocol-relative, atau hasil obfuskasi ditolak sejak penulisan.
     */
    private function tautanAman(?string $tautan): ?string
    {
        if ($tautan === null || $tautan === '') {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F\\\\@]/', $tautan) === 1) {
            return null;
        }

        $bagian = parse_url($tautan);
        if (! is_array($bagian) || isset($bagian['scheme']) || isset($bagian['host']) || isset($bagian['user']) || isset($bagian['pass'])) {
            return null;
        }

        $path = $bagian['path'] ?? '';
        if ($path !== '/mahasiswa' && ! str_starts_with($path, '/mahasiswa/')) {
            return null;
        }

        if (str_contains($path, '..') || str_contains($tautan, '//')) {
            return null;
        }

        if (rawurldecode(rawurldecode($path)) !== $path) {
            return null;
        }

        return $path.(isset($bagian['query']) ? '?'.$bagian['query'] : '');
    }
}
