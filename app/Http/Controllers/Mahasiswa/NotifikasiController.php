<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Services\Mahasiswa\DashboardDataService;
use App\Services\Notifikasi\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardData,
        private readonly NotifikasiService $notifikasi,
    ) {
    }

    /**
     * Display all notifications belonging to the authenticated student.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Notifikasi::class);

        return view('mahasiswa.notifikasi.index', $this->dashboardData->buildNotifications($request));
    }

    /**
     * Mark one of the student's own notifications as read.
     */
    public function tandaiDibaca(Request $request, int $notifikasi): RedirectResponse
    {
        $record = Notifikasi::untukUser((int) $request->user()->getAuthIdentifier())
            ->whereKey($notifikasi)
            ->firstOrFail();
        $this->authorize('tandaiDibaca', $record);

        $this->dashboardData->tandaiNotifikasiDibaca($request->user()->id, $notifikasi);

        $status = $this->dashboardData->notificationStatusFilter($request->input('status'));
        $params = $status === 'semua' ? [] : ['status' => $status];

        return redirect()
            ->route('mahasiswa.notifikasi', $params)
            ->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Mark all of the student's own notifications as read.
     */
    public function tandaiSemuaDibaca(Request $request): RedirectResponse
    {
        $jumlah = $this->notifikasi->tandaiSemuaDibaca((int) $request->user()->getAuthIdentifier());

        return redirect()->route('mahasiswa.notifikasi')
            ->with('status', $jumlah > 0
                ? $jumlah.' notifikasi ditandai sudah dibaca.'
                : 'Tidak ada notifikasi yang perlu ditandai dibaca.');
    }
}
