<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerbaruiTahunAkademikRequest;
use App\Http\Requests\Admin\SimpanTahunAkademikRequest;
use App\Http\Requests\Admin\UbahStatusTahunAkademikRequest;
use App\Models\TahunAkademik;
use App\Services\Admin\AdminTahunAkademikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    public function __construct(private readonly AdminTahunAkademikService $service)
    {
    }

    /**
     * Daftar tahun akademik dengan jumlah kelas yang memakai setiap periode.
     */
    public function index(Request $request): View
    {
        return view('admin.tahun-akademik.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'tahunAkademik' => $this->service->daftar(),
        ]);
    }

    /**
     * Formulir tambah periode. Status awal ditentukan service, bukan request.
     */
    public function create(): View
    {
        return view('admin.tahun-akademik.create', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'data' => $this->service->dataFormulir(),
            'tahunAkademikId' => null,
            'mode' => 'create',
            'memilikiKelas' => false,
        ]);
    }

    /**
     * Simpan tahun akademik baru dalam kondisi nonaktif.
     */
    public function store(SimpanTahunAkademikRequest $request): RedirectResponse
    {
        $hasil = $this->service->simpan($request->validated());

        if (! $hasil['berhasil']) {
            $field = $hasil['field'] ?? 'tahun_mulai';

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$field => $hasil['pesan']]);
        }

        return redirect()
            ->route('admin.tahun-akademik.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Formulir edit. Identitas periode terkunci bila sudah dipakai kelas.
     */
    public function edit(TahunAkademik $tahunAkademik): View
    {
        return view('admin.tahun-akademik.edit', [
            'user' => request()->user(),
            'unreadCount' => 0,
            'data' => $this->service->dataFormulir($tahunAkademik),
            'tahunAkademikId' => $tahunAkademik->getKey(),
            'mode' => 'edit',
            'memilikiKelas' => $tahunAkademik->kelasMataKuliah()->exists(),
        ]);
    }

    /**
     * Perbarui tanggal periode tanpa mengubah status atau identitas yang terkunci.
     */
    public function update(PerbaruiTahunAkademikRequest $request, TahunAkademik $tahunAkademik): RedirectResponse
    {
        $hasil = $this->service->perbarui($tahunAkademik, $request->validated());

        if (! $hasil['berhasil']) {
            $field = $hasil['field'] ?? 'tahun_mulai';

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([$field => $hasil['pesan']]);
        }

        return redirect()
            ->route('admin.tahun-akademik.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Satu-satunya endpoint yang boleh mengubah status aktif.
     */
    public function ubahStatus(
        UbahStatusTahunAkademikRequest $request,
        TahunAkademik $tahunAkademik,
    ): RedirectResponse {
        $hasil = $this->service->ubahStatus(
            $tahunAkademik,
            (bool) $request->validated('aktif'),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('admin.tahun-akademik.index')
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('admin.tahun-akademik.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Hapus hanya jika tidak ada kelas yang memakai periode ini.
     */
    public function destroy(TahunAkademik $tahunAkademik): RedirectResponse
    {
        $hasil = $this->service->hapus($tahunAkademik);

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('admin.tahun-akademik.index')
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route('admin.tahun-akademik.index')
            ->with('status', $hasil['pesan']);
    }
}
