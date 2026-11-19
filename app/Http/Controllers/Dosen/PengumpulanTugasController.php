<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\PengumpulanTugasRequest;
use App\Services\Dosen\PengumpulanTugasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumpulanTugasController extends Controller
{
    public function __construct(private readonly PengumpulanTugasService $service)
    {
    }

    public function index(PengumpulanTugasRequest $request, mixed $kelas, mixed $tugas): View
    {
        $data = $this->service->daftar($this->dosenId($request), $kelas, $tugas);

        return view('dosen.mata-kuliah.tugas.pengumpulan.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $data['kelas'],
            'infoTugas' => $data['info_tugas'],
            'statistik' => $data['statistik'],
            'peserta' => $data['peserta'],
        ]);
    }

    public function show(
        PengumpulanTugasRequest $request,
        mixed $kelas,
        mixed $tugas,
        mixed $pengumpulan,
    ): View {
        $data = $this->service->detail($this->dosenId($request), $kelas, $tugas, $pengumpulan);

        return view('dosen.mata-kuliah.tugas.pengumpulan.show', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $data['kelas'],
            'infoTugas' => $data['info_tugas'],
            'pengumpulan' => $data['pengumpulan'],
        ]);
    }

    private function dosenId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }
}
