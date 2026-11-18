<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the temporary admin dashboard.
     */
    public function admin(Request $request): View
    {
        return $this->render($request, 'Dashboard Admin');
    }

    /**
     * Display the temporary lecturer dashboard.
     */
    public function dosen(Request $request): View
    {
        return $this->render($request, 'Dashboard Dosen');
    }

    /**
     * Display the temporary student dashboard.
     */
    public function mahasiswa(Request $request): View
    {
        return $this->render($request, 'Dashboard Mahasiswa');
    }

    /**
     * Render the shared placeholder dashboard.
     */
    private function render(Request $request, string $title): View
    {
        return view('dashboard', [
            'dashboardTitle' => $title,
            'user' => $request->user(),
        ]);
    }
}
