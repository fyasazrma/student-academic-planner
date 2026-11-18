<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KalenderController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    /**
     * Display the dedicated academic calendar for the authenticated student.
     */
    public function index(Request $request): View
    {
        return view('mahasiswa.kalender.index', $this->dashboardData->buildCalendar($request));
    }
}
