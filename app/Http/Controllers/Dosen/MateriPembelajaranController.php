<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\HapusMateriPembelajaranRequest;
use App\Http\Requests\Dosen\PerbaruiMateriPembelajaranRequest;
use App\Http\Requests\Dosen\SimpanMateriPembelajaranRequest;
use App\Http\Requests\Dosen\UnduhMateriPembelajaranRequest;
use App\Services\Dosen\MateriPembelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MateriPembelajaranController extends Controller
{
    public function __construct(private readonly MateriPembelajaranService $service)
    {
    }

    public function index(Request $request, mixed $kelas): View
    {
        $data = $this->service->daftar($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.materi.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $data['kelas'],
            'materi' => $data['materi'],
        ]);
    }

    public function create(Request $request, mixed $kelas): View
    {
        $milik = $this->service->kelasMilikDosen($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.materi.create', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $milik,
            'data' => $this->service->dataFormulir(),
        ]);
    }

    public function store(SimpanMateriPembelajaranRequest $request, mixed $kelas): RedirectResponse
    {
        $hasil = $this->service->simpan(
            $this->dosenId($request),
            $kelas,
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        return $this->arahkan($hasil, 'dosen.mata-kuliah.materi.index');
    }

    public function edit(Request $request, mixed $kelas, mixed $materi): View
    {
        $milik = $this->service->kelasMilikDosen($this->dosenId($request), $kelas);
        $materiMilik = $this->service->materiMilikKelas($milik, $materi);

        return view('dosen.mata-kuliah.materi.edit', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $milik,
            'materi' => $materiMilik,
            'data' => $this->service->dataFormulir($materiMilik),
        ]);
    }

    public function update(
        PerbaruiMateriPembelajaranRequest $request,
        mixed $kelas,
        mixed $materi,
    ): RedirectResponse {
        $hasil = $this->service->perbarui(
            $this->dosenId($request),
            $kelas,
            $materi,
            $request->validated(),
            $request->file('file'),
        );

        return $this->arahkan($hasil, 'dosen.mata-kuliah.materi.index');
    }

    public function destroy(
        HapusMateriPembelajaranRequest $request,
        mixed $kelas,
        mixed $materi,
    ): RedirectResponse {
        $hasil = $this->service->hapus($this->dosenId($request), $kelas, $materi);
        $redirect = redirect()->route('dosen.mata-kuliah.materi.index', $hasil['kelas']->getKey());

        return $hasil['berhasil']
            ? $redirect->with('status', $hasil['pesan'])
            : $redirect->with('error', $hasil['pesan']);
    }

    public function download(
        UnduhMateriPembelajaranRequest $request,
        mixed $kelas,
        mixed $materi,
    ): Response {
        return $this->service->unduh($this->dosenId($request), $kelas, $materi);
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
