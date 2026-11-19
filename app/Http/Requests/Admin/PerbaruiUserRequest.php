<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class PerbaruiUserRequest extends UserRequest
{
    /**
     * Admin lain tidak boleh diubah identitasnya dari halaman manajemen.
     * Akun sendiri tetap dapat diperbarui.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $target = $this->route('user');

        if (! $target instanceof User) {
            return true;
        }

        // Halaman data mahasiswa/dosen hanya boleh mengubah akun dengan role
        // yang sesuai. Pemeriksaan dilakukan di sini agar validasi field
        // spesifik role tidak menghasilkan error sebelum scoped lookup 404.
        $peranTetap = $this->peranTetap();
        if ($peranTetap !== null && $target->role !== $peranTetap) {
            abort(404);
        }

        if ($target->isAdmin() && $target->getKey() !== $this->user()?->getKey()) {
            return false;
        }

        return true;
    }
}
