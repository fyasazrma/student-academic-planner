<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Services\Dosen\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardData)
    {
    }

    /**
     * Ringkasan akademik dosen berdasarkan data kelas yang benar-benar diampu.
     */
    public function index(Request $request): View
    {
        return view('dosen.dashboard', $this->dashboardData->build($request));
    }
}
