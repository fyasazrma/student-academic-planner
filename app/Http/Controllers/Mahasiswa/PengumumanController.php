<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Pengumuman\PengumumanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function __construct(private readonly PengumumanService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = ['q' => ''];
        $q = $request->query('q');
        if (is_string($q)) {
            $filters['q'] = trim($q);
        }

        return view('mahasiswa.pengumuman.index', [
            'user' => $request->user(),
            'unreadCount' => \App\Models\Notifikasi::untukUser((int) $request->user()->getAuthIdentifier())
                ->belumDibaca()->count(),
            'pengumuman' => $this->service->daftarMahasiswa((int) $request->user()->getAuthIdentifier(), $filters),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $pengumuman): View
    {
        return view('mahasiswa.pengumuman.show', [
            'user' => $request->user(),
            'unreadCount' => \App\Models\Notifikasi::untukUser((int) $request->user()->getAuthIdentifier())
                ->belumDibaca()->count(),
            'pengumuman' => $this->service->temukanUntukMahasiswa(
                (int) $request->user()->getAuthIdentifier(),
                $pengumuman,
            ),
        ]);
    }
}
