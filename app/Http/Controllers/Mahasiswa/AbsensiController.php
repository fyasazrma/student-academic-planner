<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\AbsensiRequest;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RekapAbsensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiService $absensi,
        private readonly RekapAbsensiService $rekap,
    ) {
    }

    public function index(AbsensiRequest $request): View
    {
        $user = $request->user();
        $id = (int) $user->getAuthIdentifier();

        return view('mahasiswa.absensi.index', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            'sesi' => $this->absensi->daftarSesiMahasiswa($id),
            'rekap' => $this->rekap->rekapMahasiswa($id),
        ]);
    }

    public function simpan(AbsensiRequest $request, mixed $sesi): RedirectResponse
    {
        $hasil = $this->absensi->isiMandiri(
            (int) $request->user()->getAuthIdentifier(),
            $sesi,
        );

        return redirect()
            ->route('mahasiswa.absensi.index')
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function riwayat(AbsensiRequest $request): View
    {
        $user = $request->user();
        $id = (int) $user->getAuthIdentifier();

        return view('mahasiswa.absensi.riwayat', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            'riwayat' => $this->absensi->riwayatPribadi($id),
        ]);
    }

    public function rekap(AbsensiRequest $request): View
    {
        $user = $request->user();
        $id = (int) $user->getAuthIdentifier();

        return view('mahasiswa.absensi.rekap', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            'rekap' => $this->rekap->rekapMahasiswa($id),
        ]);
    }

}
