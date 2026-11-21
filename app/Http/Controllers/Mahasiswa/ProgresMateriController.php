<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\ProgresMateriRequest;
use App\Services\Mahasiswa\MateriPembelajaranService;
use App\Services\Mahasiswa\ProgresMateriService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Progres materi dan target otomatis mahasiswa.
 */
class ProgresMateriController extends Controller
{
    public function __construct(
        private readonly ProgresMateriService $service,
        private readonly MateriPembelajaranService $materi,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = (int) $user->getAuthIdentifier();

        return view('mahasiswa.progres.index', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            'ringkasan' => $this->service->ringkasanOtomatis($userId),
            'daftarMateri' => $this->materi->daftarMateri($userId),
        ]);
    }

    public function show(ProgresMateriRequest $request, mixed $kelas, mixed $materi): View
    {
        $user = $request->user();
        $data = $this->service->detail(
            (int) $user->getAuthIdentifier(),
            (int) $kelas,
            (int) $materi,
        );
        $data['user'] = $user;
        $data['unreadCount'] = $this->unreadCount();

        return view('mahasiswa.materi.show', $data);
    }

    public function selesai(ProgresMateriRequest $request, mixed $kelas, mixed $materi): RedirectResponse
    {
        $this->service->tandaiSelesai(
            (int) $request->user()->getAuthIdentifier(),
            (int) $kelas,
            (int) $materi,
        );

        return back()->with('status', 'Materi ditandai sudah dipelajari.');
    }

    public function batalkan(ProgresMateriRequest $request, mixed $kelas, mixed $materi): RedirectResponse
    {
        $this->service->batalkanSelesai(
            (int) $request->user()->getAuthIdentifier(),
            (int) $kelas,
            (int) $materi,
        );

        return back()->with('status', 'Status penyelesaian materi dibatalkan.');
    }

}
