<?php

namespace App\Services\Notifikasi;

use App\Enums\JenisNotifikasi;
use App\Enums\UserRole;
use App\Models\KelasMataKuliah;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\User;
use App\Support\KelayakanPeserta;
use App\Support\TautanNotifikasi;
use Illuminate\Support\Facades\DB;

/**
 * Penulis tunggal notifikasi akademik in-app.
 *
 * Notifikasi memakai tabel `notifikasi` kustom (bukan tabel `notifications`
 * bawaan Laravel). Pemilik dan status baca ditetapkan server-side; duplikasi
 * dicegah berdasarkan kunci sumber yang stabil. Penerima selalu diturunkan
 * dari role dan relasi kelas, bukan dari data request.
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
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($userIds, UserRole::Mahasiswa, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    public function kirimKeSemuaMahasiswa(
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($this->semuaPengguna(UserRole::Mahasiswa), UserRole::Mahasiswa, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    public function kirimKeKelas(
        int $kelasId,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        $ids = PesertaMataKuliah::query()
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
            ->pluck('mahasiswa_id');

        return $this->kirimKeMahasiswa($ids, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    /**
     * @param  iterable<int>  $userIds
     */
    public function kirimKeDosen(
        iterable $userIds,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($userIds, UserRole::Dosen, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    public function kirimKeSemuaDosen(
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($this->semuaPengguna(UserRole::Dosen), UserRole::Dosen, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    /**
     * Dosen pengampu kelas. Dosen yang menjadi aktor dilewati agar tidak
     * menerima notifikasi atas tindakannya sendiri.
     */
    public function kirimKeDosenPengampu(
        int $kelasId,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        $dosenId = KelasMataKuliah::query()
            ->whereKey($kelasId)
            ->value('dosen_id');

        if ($dosenId === null) {
            return 0;
        }

        return $this->kirimKeDosen([(int) $dosenId], $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    /**
     * @param  iterable<int>  $userIds
     */
    public function kirimKeAdmin(
        iterable $userIds,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($userIds, UserRole::Admin, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
    }

    public function kirimKeSemuaAdmin(
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        return $this->kirimKeRole($this->semuaPengguna(UserRole::Admin), UserRole::Admin, $tipe, $judul, $pesan, $tautan, $sumber, $kecualiUserId);
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
     * @param  iterable<int>  $userIds
     */
    private function kirimKeRole(
        iterable $userIds,
        UserRole $role,
        JenisNotifikasi $tipe,
        string $judul,
        string $pesan,
        ?string $tautan,
        string $sumber,
        ?int $kecualiUserId = null,
    ): int {
        $ids = collect($userIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== $kecualiUserId)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $penerima = User::query()
            ->whereIn('id', $ids->all())
            ->where('role', $role->value)
            ->where('is_active', true)
            ->pluck('id');

        if ($penerima->isEmpty()) {
            return 0;
        }

        $tautanAman = TautanNotifikasi::valid($tautan, $role);

        return DB::transaction(function () use ($penerima, $role, $tipe, $judul, $pesan, $tautanAman, $sumber): int {
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
                    'data' => ['sumber' => $sumber, 'peran' => $role->value],
                    'tautan' => $tautanAman,
                    'dibaca_pada' => null,
                ])->save();

                $dibuat++;
            }

            return $dibuat;
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function semuaPengguna(UserRole $role): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('role', $role->value)
            ->where('is_active', true)
            ->pluck('id');
    }
}
