<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\JadwalUjian\JadwalUjianPageService;
use App\Services\Mahasiswa\DashboardDataService;
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
     * Mahasiswa hanya melihat jadwal ujian institusional dan kelas yang diikuti.
     */
    public function index(Request $request): View
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        return view('mahasiswa.jadwal-ujian.index', $this->jadwalUjian->build(
            $request,
            $this->dashboardData->classIds($userId),
            $request->user(),
            $this->dashboardData->unreadCount($userId),
            'mahasiswa.jadwal-ujian',
        ));
    }
}
