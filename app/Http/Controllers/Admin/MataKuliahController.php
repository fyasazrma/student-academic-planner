<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerbaruiMataKuliahRequest;
use App\Http\Requests\Admin\SimpanMataKuliahRequest;
use App\Http\Requests\Admin\UbahStatusMataKuliahRequest;
use App\Models\MataKuliah;
use App\Services\Admin\AdminMataKuliahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    public function __construct(private readonly AdminMataKuliahService $service)
    {
    }

    /**
     * Daftar data induk mata kuliah dengan pencarian dan pagination.
     */
    public function index(Request $request): View
    {
        $kataKunci = $request->query('q');
        $kataKunci = is_string($kataKunci) ? trim($kataKunci) : '';

        return view('admin.mata-kuliah.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'mataKuliah' => $this->service->daftar($kataKunci),
            'kataKunci' => $kataKunci,
        ]);
    }

    /**
     * Formulir tambah mata kuliah baru.
     */
    public function create(): View
    {
        return view('admin.mata-kuliah.create', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'data' => $this->service->dataFormulir(),
            'mataKuliahId' => null,
            'mode' => 'create',
        ]);
    }

    /**
     * Simpan mata kuliah baru. Status awal ditentukan service, bukan request.
     */
    public function store(SimpanMataKuliahRequest $request): RedirectResponse
    {
        $hasil = $this->service->simpan($request->validated());

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['kode' => $hasil['pesan']]);
        }

        return redirect()
            ->route('admin.mata-kuliah.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Formulir edit mata kuliah. Status tidak tersedia di formulir ini.
     */
    public function edit(MataKuliah $mataKuliah): View
    {
        return view('admin.mata-kuliah.edit', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'data' => $this->service->dataFormulir($mataKuliah),
            'mataKuliahId' => $mataKuliah->getKey(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Perbarui data induk tanpa mengubah status atau relasi kelas.
     */
    public function update(PerbaruiMataKuliahRequest $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $hasil = $this->service->perbarui($mataKuliah, $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['kode' => $hasil['pesan']]);
        }

        return redirect()
            ->route('admin.mata-kuliah.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Satu-satunya endpoint yang boleh mengubah status aktif.
     */
    public function ubahStatus(
        UbahStatusMataKuliahRequest $request,
        MataKuliah $mataKuliah,
    ): RedirectResponse {
        $hasil = $this->service->ubahStatus(
            $mataKuliah,
            (bool) $request->validated('aktif'),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('admin.mata-kuliah.index')
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('admin.mata-kuliah.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Hapus hanya jika tidak ada kelas yang merujuk ke mata kuliah ini.
     */
    public function destroy(MataKuliah $mataKuliah): RedirectResponse
    {
        $hasil = $this->service->hapus($mataKuliah);

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('admin.mata-kuliah.index')
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('admin.mata-kuliah.index')
            ->with('status', $hasil['pesan']);
    }
}
