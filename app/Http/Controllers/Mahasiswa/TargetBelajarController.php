<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\PerbaruiCapaianTargetRequest;
use App\Http\Requests\Mahasiswa\PerbaruiTargetBelajarRequest;
use App\Http\Requests\Mahasiswa\SimpanTargetBelajarRequest;
use App\Models\Notifikasi;
use App\Services\Mahasiswa\TargetBelajarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Target belajar pribadi mahasiswa.
 */
class TargetBelajarController extends Controller
{
    public function __construct(
        private readonly TargetBelajarService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');
        $status = is_string($status) && $status !== '' ? $status : null;
        $mataKuliahId = $request->query('mata_kuliah');
        $mataKuliahId = is_numeric($mataKuliahId) ? (int) $mataKuliahId : null;

        $data = $this->service->daftar((int) $user->getAuthIdentifier(), $status, $mataKuliahId);

        return view('mahasiswa.target.index', array_merge($data, [
            'user' => $user,
            'unreadCount' => $this->unreadCount((int) $user->getAuthIdentifier()),
            'selectedStatus' => $status,
            'selectedMataKuliahId' => $mataKuliahId,
        ]));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('mahasiswa.target.create', [
            'user' => $user,
            'unreadCount' => $this->unreadCount((int) $user->getAuthIdentifier()),
            'mataKuliah' => $this->service->mataKuliahOptions((int) $user->getAuthIdentifier()),
            'target' => null,
        ]);
    }

    public function store(SimpanTargetBelajarRequest $request): RedirectResponse
    {
        $target = $this->service->simpan(
            (int) $request->user()->getAuthIdentifier(),
            $request->validated(),
        );

        return redirect()
            ->route('mahasiswa.target.show', $target->id)
            ->with('status', 'Target belajar berhasil dibuat.');
    }

    public function show(Request $request, mixed $target): View
    {
        $user = $request->user();
        $data = $this->service->daftarSatu((int) $user->getAuthIdentifier(), (int) $target);

        return view('mahasiswa.target.show', array_merge($data, [
            'user' => $user,
            'unreadCount' => $this->unreadCount((int) $user->getAuthIdentifier()),
        ]));
    }

    public function edit(Request $request, mixed $target): View
    {
        $user = $request->user();

        return view('mahasiswa.target.edit', [
            'user' => $user,
            'unreadCount' => $this->unreadCount((int) $user->getAuthIdentifier()),
            'mataKuliah' => $this->service->mataKuliahOptions((int) $user->getAuthIdentifier()),
            'target' => $this->service->targetMilik((int) $user->getAuthIdentifier(), (int) $target),
        ]);
    }

    public function update(PerbaruiTargetBelajarRequest $request, mixed $target): RedirectResponse
    {
        $this->service->perbarui(
            (int) $request->user()->getAuthIdentifier(),
            (int) $target,
            $request->validated(),
        );

        return redirect()
            ->route('mahasiswa.target.show', (int) $target)
            ->with('status', 'Target belajar berhasil diperbarui.');
    }

    public function destroy(Request $request, mixed $target): RedirectResponse
    {
        $this->service->hapus((int) $request->user()->getAuthIdentifier(), (int) $target);

        return redirect()
            ->route('mahasiswa.target.index')
            ->with('status', 'Target belajar berhasil dihapus.');
    }

    public function capaian(PerbaruiCapaianTargetRequest $request, mixed $target): RedirectResponse
    {
        try {
            $this->service->perbaruiCapaian(
                (int) $request->user()->getAuthIdentifier(),
                (int) $target,
                (float) $request->validated('capaian'),
            );

            return back()->with('status', 'Capaian target berhasil diperbarui.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function selesai(Request $request, mixed $target): RedirectResponse
    {
        try {
            $this->service->tandaiSelesai((int) $request->user()->getAuthIdentifier(), (int) $target);

            return back()->with('status', 'Target ditandai selesai.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function batalkanSelesai(Request $request, mixed $target): RedirectResponse
    {
        $this->service->batalkanSelesai((int) $request->user()->getAuthIdentifier(), (int) $target);

        return back()->with('status', 'Status selesai dibatalkan.');
    }

    public function batalkan(Request $request, mixed $target): RedirectResponse
    {
        $this->service->batalkan((int) $request->user()->getAuthIdentifier(), (int) $target);

        return back()->with('status', 'Target dibatalkan.');
    }

    private function unreadCount(int $userId): int
    {
        return Notifikasi::untukUser($userId)->belumDibaca()->count();
    }
}
