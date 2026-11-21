<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengumumanRequest;
use App\Services\Pengumuman\PengumumanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function __construct(private readonly PengumumanService $service)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.pengumuman.index', $this->dataIndex($request));
    }

    public function create(Request $request): View
    {
        return view('admin.pengumuman.form', $this->dataForm($request, null));
    }

    public function store(PengumumanRequest $request): RedirectResponse
    {
        $record = $this->service->simpan(
            UserRole::Admin->value,
            (int) $request->user()->getAuthIdentifier(),
            $request->validated(),
        );

        return redirect()->route('admin.pengumuman.index')
            ->with('status', 'Pengumuman berhasil disimpan.');
    }

    public function show(Request $request, int $pengumuman): View
    {
        return view('admin.pengumuman.show', [
            ...$this->dataIndex($request),
            'pengumuman' => $this->service->temukan(
                UserRole::Admin->value,
                (int) $request->user()->getAuthIdentifier(),
                $pengumuman,
            ),
        ]);
    }

    public function edit(Request $request, int $pengumuman): View
    {
        return view('admin.pengumuman.form', $this->dataForm(
            $request,
            $this->service->temukan(UserRole::Admin->value, (int) $request->user()->getAuthIdentifier(), $pengumuman),
        ));
    }

    public function update(PengumumanRequest $request, int $pengumuman): RedirectResponse
    {
        $this->service->simpan(
            UserRole::Admin->value,
            (int) $request->user()->getAuthIdentifier(),
            $request->validated(),
            $pengumuman,
        );

        return redirect()->route('admin.pengumuman.index')
            ->with('status', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Request $request, int $pengumuman): RedirectResponse
    {
        $this->service->hapus(
            UserRole::Admin->value,
            (int) $request->user()->getAuthIdentifier(),
            $pengumuman,
        );

        return redirect()->route('admin.pengumuman.index')
            ->with('status', 'Pengumuman berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataIndex(Request $request): array
    {
        return [
            'user' => $request->user(),
            'unreadCount' => 0,
            'pengumuman' => $this->service->daftar(
                UserRole::Admin->value,
                (int) $request->user()->getAuthIdentifier(),
                $this->filters($request),
            ),
            'filters' => $this->filters($request),
            'statusOptions' => [
                'semua' => 'Semua status',
                'draft' => 'Draft',
                'terbit' => 'Terbit',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Request $request, mixed $record): array
    {
        return [
            'user' => $request->user(),
            'unreadCount' => 0,
            'pengumuman' => $record,
            'statusOptions' => [
                'draft' => 'Draft',
                'terbit' => 'Terbit langsung',
                'terjadwal' => 'Terjadwal',
            ],
            'kelasOptions' => \App\Models\KelasMataKuliah::query()
                ->with('mataKuliah')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn ($kelas): array => [
                    $kelas->getKey() => $kelas->mataKuliah?->nama.' — '.$kelas->nama_kelas,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        $filters = ['q' => '', 'status' => 'semua'];
        foreach ($filters as $key => $unused) {
            $value = $request->query($key);
            if (is_string($value)) {
                $filters[$key] = $key === 'q' ? trim($value) : $value;
            }
        }

        return $filters;
    }
}
