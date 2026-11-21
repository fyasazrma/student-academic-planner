<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\HapusJadwalKuliahRequest;
use App\Http\Requests\Dosen\SimpanJadwalKuliahRequest;
use App\Services\Dosen\JadwalKuliahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalKuliahController extends Controller
{
    public function __construct(private readonly JadwalKuliahService $service)
    {
    }

    public function index(Request $request, mixed $kelas): View
    {
        $data = $this->service->daftar($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.jadwal.index', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $data['kelas'],
            'jadwal' => $data['jadwal'],
        ]);
    }

    public function create(Request $request, mixed $kelas): View
    {
        return view('dosen.mata-kuliah.jadwal.create', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $this->service->kelasMilikDosen($this->dosenId($request), $kelas),
            'jadwal' => null,
            'hariOptions' => $this->service->opsiHari(),
            'jenisOptions' => $this->service->opsiJenis(),
        ]);
    }

    public function store(SimpanJadwalKuliahRequest $request, mixed $kelas): RedirectResponse
    {
        $hasil = $this->service->simpan($this->dosenId($request), $kelas, $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$hasil['field'] ?? 'jam_mulai' => $hasil['pesan']]);
        }

        return redirect()
            ->route('dosen.mata-kuliah.jadwal.index', $hasil['kelas']->getKey())
            ->with('status', $hasil['pesan']);
    }

    public function edit(Request $request, mixed $kelas, mixed $jadwal): View
    {
        $milik = $this->service->kelasMilikDosen($this->dosenId($request), $kelas);

        return view('dosen.mata-kuliah.jadwal.edit', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'kelas' => $milik,
            'jadwal' => $this->service->jadwalMilikKelas($milik, $jadwal),
            'hariOptions' => $this->service->opsiHari(),
            'jenisOptions' => $this->service->opsiJenis(),
        ]);
    }

    public function update(
        SimpanJadwalKuliahRequest $request,
        mixed $kelas,
        mixed $jadwal,
    ): RedirectResponse {
        $hasil = $this->service->perbarui(
            $this->dosenId($request),
            $kelas,
            $jadwal,
            $request->validated(),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$hasil['field'] ?? 'jam_mulai' => $hasil['pesan']]);
        }

        return redirect()
            ->route('dosen.mata-kuliah.jadwal.index', $hasil['kelas']->getKey())
            ->with('status', $hasil['pesan']);
    }

    public function destroy(
        HapusJadwalKuliahRequest $request,
        mixed $kelas,
        mixed $jadwal,
    ): RedirectResponse {
        $hasil = $this->service->hapus($this->dosenId($request), $kelas, $jadwal);

        return redirect()
            ->route('dosen.mata-kuliah.jadwal.index', $hasil['kelas']->getKey())
            ->with('status', $hasil['pesan']);
    }

    private function dosenId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }
}
