<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HapusJadwalKuliahRequest;
use App\Http\Requests\Admin\SimpanJadwalKuliahRequest;
use App\Services\Admin\JadwalKuliahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalKuliahController extends Controller
{
    public function __construct(private readonly JadwalKuliahService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.jadwal-kuliah.index', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'jadwal' => $this->service->daftar($filters),
            'filters' => $filters,
            'hariOptions' => $this->service->opsiHari(),
            'jenisOptions' => $this->service->opsiJenis(),
            'tahunOptions' => $this->service->opsiTahunAkademik(),
            'semesterOptions' => $this->service->opsiSemester(),
            'dosenOptions' => $this->service->opsiDosen(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.jadwal-kuliah.create', $this->formData($request, null));
    }

    public function store(SimpanJadwalKuliahRequest $request): RedirectResponse
    {
        $hasil = $this->service->simpan($request->validated());

        if (! $hasil['berhasil']) {
            return redirect()->back()->withInput()->withErrors([$hasil['field'] ?? 'jam_mulai' => $hasil['pesan']]);
        }

        return redirect()->route('admin.jadwal-kuliah.index')->with('status', $hasil['pesan']);
    }

    public function edit(Request $request, mixed $jadwal): View
    {
        return view('admin.jadwal-kuliah.edit', $this->formData($request, $this->service->temukan($jadwal)));
    }

    public function update(SimpanJadwalKuliahRequest $request, mixed $jadwal): RedirectResponse
    {
        $hasil = $this->service->perbarui($jadwal, $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()->back()->withInput()->withErrors([$hasil['field'] ?? 'jam_mulai' => $hasil['pesan']]);
        }

        return redirect()->route('admin.jadwal-kuliah.index')->with('status', $hasil['pesan']);
    }

    public function destroy(HapusJadwalKuliahRequest $request, mixed $jadwal): RedirectResponse
    {
        $hasil = $this->service->hapus($jadwal);

        return redirect()->route('admin.jadwal-kuliah.index')->with('status', $hasil['pesan']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, mixed $jadwal): array
    {
        return [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'jadwal' => $jadwal,
            'hariOptions' => $this->service->opsiHari(),
            'jenisOptions' => $this->service->opsiJenis(),
            'kelasOptions' => $this->service->opsiKelas(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        $filter = [
            'q' => '',
            'tahun_akademik' => '',
            'semester' => '',
            'hari' => '',
            'dosen' => '',
        ];

        foreach ($filter as $key => $unused) {
            $value = $request->query($key);
            if (is_string($value)) {
                $filter[$key] = $key === 'q' ? trim($value) : $value;
            }
        }

        return $filter;
    }
}
