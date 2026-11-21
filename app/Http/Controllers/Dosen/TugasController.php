<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\HapusTugasRequest;
use App\Http\Requests\Dosen\PerbaruiTugasRequest;
use App\Http\Requests\Dosen\SimpanTugasRequest;
use App\Http\Requests\Dosen\UnduhLampiranTugasRequest;
use App\Services\Dosen\TugasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TugasController extends Controller
{
    public function __construct(private readonly TugasService $service)
    {
    }

    public function index(Request $request, mixed $kelas): View
    {
        $data = $this->service->daftar($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.tugas.index', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $data['kelas'],
            'tugas' => $data['tugas'],
        ]);
    }

    public function create(Request $request, mixed $kelas): View
    {
        $milik = $this->service->kelasMilikDosen($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.tugas.create', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $milik,
            'data' => $this->service->dataFormulir(),
        ]);
    }

    public function store(SimpanTugasRequest $request, mixed $kelas): RedirectResponse
    {
        $hasil = $this->service->simpan(
            $this->dosenId($request),
            $kelas,
            $request->validated(),
            $request->file('lampiran'),
            $request->user(),
        );

        return $this->arahkan($hasil, 'dosen.mata-kuliah.tugas.index');
    }

    public function edit(Request $request, mixed $kelas, mixed $tugas): View
    {
        $milik = $this->service->kelasMilikDosen($this->dosenId($request), $kelas);
        $tugasMilik = $this->service->tugasMilikKelas($milik, $tugas);

        return view('dosen.mata-kuliah.tugas.edit', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $milik,
            'tugas' => $tugasMilik,
            'data' => $this->service->dataFormulir($tugasMilik),
        ]);
    }

    public function update(
        PerbaruiTugasRequest $request,
        mixed $kelas,
        mixed $tugas,
    ): RedirectResponse {
        $hasil = $this->service->perbarui(
            $this->dosenId($request),
            $kelas,
            $tugas,
            $request->validated(),
            $request->file('lampiran'),
        );

        return $this->arahkan($hasil, 'dosen.mata-kuliah.tugas.index');
    }

    public function destroy(
        HapusTugasRequest $request,
        mixed $kelas,
        mixed $tugas,
    ): RedirectResponse {
        $hasil = $this->service->hapus($this->dosenId($request), $kelas, $tugas);
        $redirect = redirect()->route('dosen.mata-kuliah.tugas.index', $hasil['kelas']->getKey());

        return $hasil['berhasil']
            ? $redirect->with('status', $hasil['pesan'])
            : $redirect->with('error', $hasil['pesan']);
    }

    public function download(
        UnduhLampiranTugasRequest $request,
        mixed $kelas,
        mixed $tugas,
    ): Response {
        return $this->service->unduh($this->dosenId($request), $kelas, $tugas);
    }

    /**
     * @param array{berhasil: bool, pesan: string, field: ?string, kelas: \App\Models\KelasMataKuliah} $hasil
     */
    private function arahkan(array $hasil, string $routeName): RedirectResponse
    {
        if (! $hasil['berhasil']) {
            $redirect = redirect()->back()->withInput();

            return $hasil['field'] !== null
                ? $redirect->withErrors([$hasil['field'] => $hasil['pesan']])
                : $redirect->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route($routeName, $hasil['kelas']->getKey())
            ->with('status', $hasil['pesan']);
    }

    private function dosenId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }
}
