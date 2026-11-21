<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy untuk notifikasi in-app.
 *
 * Middleware role masih menjadi gerbang kasar. Policy hanya memastikan resource
 * benar-benar milik pengguna; pelanggaran dikembalikan sebagai 404 mengikuti
 * pola IDOR yang sudah dipakai aplikasi.
 */
class NotifikasiPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Mahasiswa, UserRole::Dosen, UserRole::Admin], true);
    }

    public function view(User $user, Notifikasi $notifikasi): Response
    {
        return $this->milik($user, $notifikasi);
    }

    public function tandaiDibaca(User $user, Notifikasi $notifikasi): Response
    {
        return $this->milik($user, $notifikasi);
    }

    private function milik(User $user, Notifikasi $notifikasi): Response
    {
        return (int) $notifikasi->user_id === (int) $user->getAuthIdentifier()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
