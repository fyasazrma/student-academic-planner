<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Jumlah notifikasi belum dibaca milik pengguna yang sedang login.
     * Angka ini dipakai shell aplikasi, sehingga harus bersumber dari data.
     */
    protected function unreadCount(): int
    {
        $userId = Auth::id();

        if ($userId === null) {
            return 0;
        }

        return Notifikasi::untukUser((int) $userId)
            ->belumDibaca()
            ->count();
    }
}
