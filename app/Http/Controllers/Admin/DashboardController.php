<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardDataService $dashboardData)
    {
    }

    /**
     * Display the academic overview for the authenticated administrator.
     */
    public function index(Request $request): View
    {
        return view('admin.dashboard', $this->dashboardData->build($request));
    }
}
