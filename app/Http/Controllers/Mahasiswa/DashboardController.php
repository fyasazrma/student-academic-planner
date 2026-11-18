<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    /**
     * Display the academic dashboard for the authenticated student.
     */
    public function index(Request $request): View
    {
        return view('mahasiswa.dashboard', $this->dashboardData->build($request));
    }
}
