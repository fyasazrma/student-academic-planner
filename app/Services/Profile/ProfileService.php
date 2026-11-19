<?php

namespace App\Services\Profile;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Profil mahasiswa dan dosen berada pada baris `users` milik pengguna yang
 * sedang login. Service ini tidak pernah menerima ID pengguna lain, role,
 * status akun, NIM/NIDN, atau relasi kelas dari request.
 */
class ProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function dataProfil(User $user): array
    {
        $user->loadCount([
            'pendaftaranKelas as jumlah_kelas_diikuti',
            'kelasDiajar as jumlah_kelas_diajar',
            'materiDibuat as jumlah_materi',
            'tugasDibuat as jumlah_tugas',
        ]);

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->isDosen() ? 'Dosen' : 'Mahasiswa',
            'nim' => $user->nim,
            'nidn' => $user->nidn,
            'program_studi' => $user->program_studi,
            'angkatan' => $user->angkatan,
            'no_telepon' => $user->no_telepon,
            'email_verified_at' => $user->email_verified_at?->translatedFormat('d M Y H:i') ?? '-',
            'created_at' => $user->created_at?->translatedFormat('d M Y H:i') ?? '-',
            'jumlah_kelas_diikuti' => (int) ($user->jumlah_kelas_diikuti ?? 0),
            'jumlah_kelas_diajar' => (int) ($user->jumlah_kelas_diajar ?? 0),
            'jumlah_materi' => (int) ($user->jumlah_materi ?? 0),
            'jumlah_tugas' => (int) ($user->jumlah_tugas ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, kode: string, field: ?string}
     */
    public function perbarui(User $user, array $data): array
    {
        $user->fill([
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'no_telepon' => $this->opsional($data['no_telepon'] ?? null),
        ]);

        DB::transaction(fn () => $user->save());

        return [
            'berhasil' => true,
            'kode' => 'berhasil',
            'field' => null,
            'pesan' => 'Profil berhasil diperbarui.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, pesan: string, kode: string, field: ?string}
     */
    public function ubahPassword(User $user, array $data): array
    {
        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            return [
                'berhasil' => false,
                'kode' => 'password_saat_ini_salah',
                'field' => 'current_password',
                'pesan' => 'Password saat ini tidak sesuai.',
            ];
        }

        $user->forceFill(['password' => Hash::make((string) $data['password'])]);

        DB::transaction(fn () => $user->save());

        return [
            'berhasil' => true,
            'kode' => 'berhasil',
            'field' => null,
            'pesan' => 'Password berhasil diubah.',
        ];
    }

    public function routeName(User $user): string
    {
        return $user->role === UserRole::Dosen ? 'dosen.profil' : 'mahasiswa.profil';
    }

    private function opsional(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
