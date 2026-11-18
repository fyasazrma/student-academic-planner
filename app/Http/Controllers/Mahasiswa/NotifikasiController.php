<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\DashboardDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    /**
     * Display all notifications belonging to the authenticated student.
     */
    public function index(Request $request): View
    {
        return view('mahasiswa.notifikasi.index', $this->dashboardData->buildNotifications($request));
    }

    /**
     * Mark one of the student's own notifications as read.
     */
    public function tandaiDibaca(Request $request, int $notifikasi): RedirectResponse
    {
        $this->dashboardData->tandaiNotifikasiDibaca($request->user()->id, $notifikasi);

        $status = $this->dashboardData->notificationStatusFilter($request->input('status'));
        $params = $status === 'semua' ? [] : ['status' => $status];

        return redirect()
            ->route('mahasiswa.notifikasi', $params)
            ->with('status', 'Notifikasi ditandai sudah dibaca.');
    }
}
