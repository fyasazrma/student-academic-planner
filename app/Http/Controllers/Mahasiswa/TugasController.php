<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    /**
     * Display tasks and submissions belonging to the authenticated student.
     */
    public function index(Request $request): View
    {
        return view('mahasiswa.tugas.index', $this->dashboardData->buildTasks($request));
    }
}
