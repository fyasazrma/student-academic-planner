<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusPendaftaranKelas;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\KodeGabungKelasRequest;
use App\Http\Requests\Dosen\PerbaruiKelasMataKuliahRequest;
use App\Http\Requests\Dosen\SimpanKelasMataKuliahRequest;
use App\Http\Requests\Dosen\UbahStatusPendaftaranKelasRequest;
use App\Models\KelasMataKuliah;
use App\Services\Dosen\MataKuliahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    public function __construct(private readonly MataKuliahService $service)
    {
    }

    /**
     * Daftar seluruh kelas milik dosen, termasuk kelas historis.
     */
    public function index(Request $request): View
    {
        return view('dosen.mata-kuliah.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $this->service->daftar((int) $request->user()->getAuthIdentifier(), $request->query('q')),
            'kataKunci' => is_string($request->query('q')) ? trim($request->query('q')) : '',
        ]);
    }

    public function create(): View
    {
        return view('dosen.mata-kuliah.create', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'data' => $this->service->dataFormulir(),
            'mode' => 'create',
            'mataKuliahOptions' => $this->service->opsiMataKuliah(),
            'tahunAkademikOptions' => $this->service->opsiTahunAkademik(),
        ]);
    }

    public function store(SimpanKelasMataKuliahRequest $request): RedirectResponse
    {
        $hasil = $this->service->simpan(
            (int) $request->user()->getAuthIdentifier(),
            $request->validated(),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$hasil['field'] ?? 'kode_kelas' => $hasil['pesan']]);
        }

        return redirect()
            ->route('dosen.mata-kuliah.show', $hasil['kelas']->id)
            ->with('status', $hasil['pesan']);
    }

    public function show(mixed $kelas): View
    {
        $milik = $this->kelasMilikDosen($kelas);

        return view('dosen.mata-kuliah.show', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'kelas' => $milik,
            'detail' => $this->service->detail($milik),
        ]);
    }

    public function edit(mixed $kelas): View
    {
        $milik = $this->kelasMilikDosen($kelas);

        return view('dosen.mata-kuliah.edit', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'kelas' => $milik,
            'detail' => $this->service->detail($milik),
            'data' => $this->service->dataFormulir($milik),
            'mode' => 'edit',
        ]);
    }

    public function update(
        PerbaruiKelasMataKuliahRequest $request,
        mixed $kelas,
    ): RedirectResponse {
        $milik = $this->kelasMilikDosen($kelas);
        $hasil = $this->service->perbarui(
            (int) $request->user()->getAuthIdentifier(),
            $milik,
            $request->validated(),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$hasil['field'] ?? 'nama_kelas' => $hasil['pesan']]);
        }

        return redirect()
            ->route('dosen.mata-kuliah.show', $milik->id)
            ->with('status', $hasil['pesan']);
    }

    public function ubahStatus(
        UbahStatusPendaftaranKelasRequest $request,
        mixed $kelas,
    ): RedirectResponse {
        $milik = $this->kelasMilikDosen($kelas);
        $status = StatusPendaftaranKelas::tryFrom((string) $request->validated('status_pendaftaran'));

        if ($status === null) {
            return redirect()
                ->route('dosen.mata-kuliah.show', $milik->id)
                ->with('error', 'Status pendaftaran tidak dikenal.');
        }

        $hasil = $this->service->ubahStatusPendaftaran(
            (int) $request->user()->getAuthIdentifier(),
            $milik,
            $status,
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('dosen.mata-kuliah.show', $milik->id)
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('dosen.mata-kuliah.show', $milik->id)
            ->with('status', $hasil['pesan']);
    }

    public function kelolaKodeGabung(
        KodeGabungKelasRequest $request,
        mixed $kelas,
    ): RedirectResponse {
        $milik = $this->kelasMilikDosen($kelas);
        $hasil = $this->service->kelolaKodeGabung(
            (int) $request->user()->getAuthIdentifier(),
            $milik,
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('dosen.mata-kuliah.show', $milik->id)
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('dosen.mata-kuliah.show', $milik->id)
            ->with('status', $hasil['pesan'])
            ->with('kode_gabung', $hasil['kode_gabung'])
            ->with('kode_gabung_jenis', $hasil['jenis']);
    }

    /**
     * Semua aksi kelas memakai resolver scoped. ID kelas dari URL tidak pernah
     * dipakai tanpa syarat dosen_id = pengguna yang login.
     */
    private function kelasMilikDosen(mixed $id): KelasMataKuliah
    {
        return $this->service->milikDosen((int) auth()->id(), $id);
    }
}
