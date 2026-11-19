<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Aturan bisnis pengelolaan akun pengguna admin.
 *
 * Profil mahasiswa dan dosen tersimpan pada baris `users` yang sama, sehingga
 * tidak ada tabel profil terpisah yang perlu disinkronkan. Role dan status
 * akun sengaja tidak pernah diterima dari request.
 */
class UserManagementService
{
    public const PER_HALAMAN = 10;

    /**
     * @param  array<string, mixed>  $mentah
     * @return array{q: string, role: ?UserRole, status: string}
     */
    public function normalisasiFilter(array $mentah): array
    {
        $q = $mentah['q'] ?? '';
        $q = is_string($q) ? trim($q) : '';

        $roleString = $mentah['role'] ?? '';
        $role = is_string($roleString) ? UserRole::tryFrom($roleString) : null;

        $status = $mentah['status'] ?? '';
        $status = is_string($status) && in_array($status, ['aktif', 'nonaktif'], true) ? $status : '';

        return [
            'q' => mb_substr($q, 0, 100),
            'role' => $role,
            'status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $mentah
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function daftar(array $mentah, ?UserRole $roleTetap = null): LengthAwarePaginator
    {
        $filter = $this->normalisasiFilter($mentah);
        $role = $roleTetap ?? $filter['role'];

        $query = User::query()
            ->when($filter['q'] !== '', function ($pencarian) use ($filter): void {
                $pencarian->where(function ($cari) use ($filter): void {
                    $cari->where('name', 'like', '%'.$filter['q'].'%')
                        ->orWhere('email', 'like', '%'.$filter['q'].'%')
                        ->orWhere('nim', 'like', '%'.$filter['q'].'%')
                        ->orWhere('nidn', 'like', '%'.$filter['q'].'%');
                });
            })
            ->when($role !== null, fn ($pencarian) => $pencarian->where('role', $role->value))
            ->when($filter['status'] !== '', fn ($pencarian) => $pencarian->where('is_active', $filter['status'] === 'aktif'))
            ->orderBy('name')
            ->orderBy('id');

        $paginator = $query->paginate(self::PER_HALAMAN);
        $paginator->appends(array_filter([
            'q' => $filter['q'] !== '' ? $filter['q'] : null,
            'role' => $roleTetap === null && $filter['role'] !== null ? $filter['role']->value : null,
            'status' => $filter['status'] !== '' ? $filter['status'] : null,
        ]));

        return $paginator->through(fn (User $user): array => $this->baris($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $user): array
    {
        $user->loadCount([
            'kelasDiajar as jumlah_kelas_diajar',
            'pendaftaranKelas as jumlah_pendaftaran',
            'materiDibuat as jumlah_materi',
            'tugasDibuat as jumlah_tugas',
        ]);

        return $this->baris($user, true);
    }

    /**
     * Nilai formulir eksplisit. Tidak pernah menyertakan role, status, atau password.
     *
     * @return array<string, mixed>
     */
    public function dataFormulir(?User $user = null, ?UserRole $roleTetap = null): array
    {
        $role = $roleTetap ?? $user?->role;

        return [
            'role' => $role?->value ?? '',
            'name' => $user?->name ?? '',
            'email' => $user?->email ?? '',
            'nim' => $user?->nim ?? '',
            'nidn' => $user?->nidn ?? '',
            'program_studi' => $user?->program_studi ?? '',
            'angkatan' => $user?->angkatan ?? '',
            'no_telepon' => $user?->no_telepon ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, user: ?User, kode: string}
     */
    public function simpan(array $data): array
    {
        $role = UserRole::from((string) $data['role']);

        $user = new User();
        $user->forceFill([
            'role' => $role,
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'password' => Hash::make((string) $data['password']),
            'nim' => $role === UserRole::Mahasiswa ? ($data['nim'] ?? null) : null,
            'nidn' => $role === UserRole::Dosen ? ($data['nidn'] ?? null) : null,
            'program_studi' => $this->opsional($data['program_studi'] ?? null),
            'angkatan' => $role === UserRole::Mahasiswa && ! empty($data['angkatan'])
                ? (int) $data['angkatan']
                : null,
            'no_telepon' => $this->opsional($data['no_telepon'] ?? null),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        DB::transaction(fn () => $user->save());

        return [
            'berhasil' => true,
            'kode' => 'berhasil',
            'pesan' => 'Akun '.self::labelRole($role).' '.$user->name.' berhasil dibuat.',
            'user' => $user,
        ];
    }

    /**
     * Role dan status tidak pernah disentuh oleh metode ini.
     *
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, user: ?User, kode: string}
     */
    public function perbarui(User $user, array $data): array
    {
        $user->fill([
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'program_studi' => $this->opsional($data['program_studi'] ?? null),
            'angkatan' => $user->isMahasiswa() && ! empty($data['angkatan'])
                ? (int) $data['angkatan']
                : null,
            'no_telepon' => $this->opsional($data['no_telepon'] ?? null),
        ]);

        if ($user->isMahasiswa() && array_key_exists('nim', $data)) {
            $user->nim = $this->opsional($data['nim']);
        }

        if ($user->isDosen() && array_key_exists('nidn', $data)) {
            $user->nidn = $this->opsional($data['nidn']);
        }

        DB::transaction(fn () => $user->save());

        return [
            'berhasil' => true,
            'kode' => 'berhasil',
            'pesan' => 'Data akun '.$user->name.' berhasil diperbarui.',
            'user' => $user,
        ];
    }

    /**
     * Satu-satunya jalur perubahan status akun.
     *
     * @return array{berhasil: bool, pesan: string, kode: string}
     */
    public function ubahStatus(User $user, bool $aktif, int $actorId): array
    {
        if ($user->getKey() === $actorId) {
            return [
                'berhasil' => false,
                'kode' => 'akun_sendiri',
                'pesan' => 'Anda tidak dapat mengubah status akun yang sedang digunakan sendiri.',
            ];
        }

        if ($user->isAdmin()) {
            return [
                'berhasil' => false,
                'kode' => 'akun_admin',
                'pesan' => 'Status akun admin tidak dapat diubah melalui halaman ini.',
            ];
        }

        if ($user->is_active === $aktif) {
            return [
                'berhasil' => false,
                'kode' => 'tidak_berubah',
                'pesan' => 'Status akun tidak berubah.',
            ];
        }

        DB::transaction(function () use ($user, $aktif): void {
            $terkunci = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $terkunci->forceFill(['is_active' => $aktif])->save();
            $user->forceFill(['is_active' => $aktif]);
        });

        return [
            'berhasil' => true,
            'kode' => 'berhasil',
            'pesan' => $aktif
                ? 'Akun '.$user->name.' berhasil diaktifkan.'
                : 'Akun '.$user->name.' berhasil dinonaktifkan. Data akademiknya tetap dipertahankan.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(User $user, bool $lengkap = false): array
    {
        $baris = [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => self::labelRole($user->role),
            'is_active' => (bool) $user->is_active,
            'status_label' => $user->is_active ? 'Aktif' : 'Nonaktif',
            'nim' => $user->nim,
            'nidn' => $user->nidn,
            'program_studi' => $user->program_studi,
            'angkatan' => $user->angkatan,
            'no_telepon' => $user->no_telepon,
            'created_at' => $user->created_at?->translatedFormat('d M Y H:i') ?? '-',
        ];

        if ($lengkap) {
            $baris['jumlah_kelas_diajar'] = (int) ($user->jumlah_kelas_diajar ?? 0);
            $baris['jumlah_pendaftaran'] = (int) ($user->jumlah_pendaftaran ?? 0);
            $baris['jumlah_materi'] = (int) ($user->jumlah_materi ?? 0);
            $baris['jumlah_tugas'] = (int) ($user->jumlah_tugas ?? 0);
        }

        return $baris;
    }

    private function opsional(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    public static function labelRole(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'Admin',
            UserRole::Dosen => 'Dosen',
            UserRole::Mahasiswa => 'Mahasiswa',
        };
    }
}
