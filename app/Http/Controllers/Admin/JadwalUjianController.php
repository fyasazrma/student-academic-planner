<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HapusJadwalUjianRequest;
use App\Http\Requests\Admin\SimpanJadwalUjianRequest;
use App\Services\Admin\JadwalUjianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalUjianController extends Controller
{
    public function __construct(private readonly JadwalUjianService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.jadwal-ujian.index', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'ujian' => $this->service->daftar($filters),
            'filters' => $filters,
            'jenisUjianOptions' => $this->service->opsiJenisUjian(),
            'tahunOptions' => $this->service->opsiTahunAkademik(),
            'semesterOptions' => $this->service->opsiSemester(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.jadwal-ujian.create', $this->formData($request, null));
    }

    public function store(SimpanJadwalUjianRequest $request): RedirectResponse
    {
        $hasil = $this->service->simpan($request->validated(), (int) $request->user()->getAuthIdentifier());

        if (! $hasil['berhasil']) {
            return redirect()->back()->withInput()->withErrors([$hasil['field'] ?? 'waktu_mulai' => $hasil['pesan']]);
        }

        return redirect()->route('admin.jadwal-ujian.index')->with('status', $hasil['pesan']);
    }

    public function edit(Request $request, mixed $ujian): View
    {
        return view('admin.jadwal-ujian.edit', $this->formData($request, $this->service->temukan($ujian)));
    }

    public function update(SimpanJadwalUjianRequest $request, mixed $ujian): RedirectResponse
    {
        $hasil = $this->service->perbarui($ujian, $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()->back()->withInput()->withErrors([$hasil['field'] ?? 'waktu_mulai' => $hasil['pesan']]);
        }

        return redirect()->route('admin.jadwal-ujian.index')->with('status', $hasil['pesan']);
    }

    public function destroy(HapusJadwalUjianRequest $request, mixed $ujian): RedirectResponse
    {
        $hasil = $this->service->hapus($ujian);

        return redirect()->route('admin.jadwal-ujian.index')->with('status', $hasil['pesan']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, mixed $ujian): array
    {
        $form = $this->service->formulir($ujian);
        $dipublikasikan = $ujian === null || $ujian->dipublikasikan_pada !== null;

        return [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount(),
            'ujian' => $ujian,
            'jenisUjianOptions' => $this->service->opsiJenisUjian(),
            'tahunOptions' => $this->service->opsiTahunAkademik(),
            'semesterOptions' => $this->service->opsiSemester(),
            'tahunValue' => old('tahun_akademik_id', $form['tahun_akademik_id']),
            'semesterValue' => old('semester', $form['semester']),
            'judulValue' => old('judul', $ujian?->judul ?? $form['judul']),
            'dipublikasikanValue' => old('dipublikasikan', $dipublikasikan ? '1' : '0'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        $filter = [
            'q' => '',
            'jenis_ujian' => '',
            'tahun_akademik' => '',
            'semester' => '',
            'tanggal' => '',
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
