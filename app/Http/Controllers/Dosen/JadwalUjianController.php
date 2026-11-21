<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Services\Dosen\DashboardDataService;
use App\Services\JadwalUjian\JadwalUjianPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalUjianController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardData,
        private readonly JadwalUjianPageService $jadwalUjian,
    ) {
    }

    /**
     * Dosen hanya melihat jadwal ujian institusional dan kelas yang diampu.
     */
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        return view('dosen.jadwal-ujian.index', $this->jadwalUjian->build(
            $request,
            $this->dashboardData->classIds($userId),
            $request->user(),
            $this->dashboardData->unreadCount($userId),
            'dosen.jadwal-ujian',
        ));
    }
}
