<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Services\Notifikasi\NotifikasiHalamanService;
use App\Services\Notifikasi\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman notifikasi bersama untuk Admin, Dosen, dan Mahasiswa.
 *
 * Route per role tetap berada di grup middleware masing-masing.
 */
class NotifikasiController extends Controller
{
    public function __construct(
        private readonly NotifikasiHalamanService $halaman,
        private readonly NotifikasiService $notifikasi,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Notifikasi::class);

        return view('notifikasi.index', $this->halaman->build($request));
    }

    public function tandaiDibaca(Request $request, int $notifikasi): RedirectResponse
    {
        $record = $this->halaman->temukan((int) $request->user()->getAuthIdentifier(), $notifikasi);
        $this->authorize('tandaiDibaca', $record);
        $this->halaman->tandaiDibaca($record);

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    public function tandaiSemuaDibaca(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Notifikasi::class);
        $this->notifikasi->tandaiSemuaDibaca((int) $request->user()->getAuthIdentifier());

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
