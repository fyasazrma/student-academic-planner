<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerbaruiUserRequest;
use App\Http\Requests\Admin\SimpanUserRequest;
use App\Http\Requests\Admin\UbahStatusUserRequest;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Satu controller untuk manajemen akun, data mahasiswa, dan data dosen.
 * Konteks ditentukan dari nama route, bukan dari input pengguna.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        $konteks = $this->konteks();

        return view($konteks['view'].'.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'pengguna' => $this->service->daftar($request->query(), $konteks['peran']),
            'filter' => $this->service->normalisasiFilter($request->query()),
            'roleTetap' => $konteks['peran'],
            'routePrefix' => $konteks['prefix'],
            'label' => $konteks['label'],
        ]);
    }

    public function create(Request $request): View
    {
        $konteks = $this->konteks();

        return view($konteks['view'].'.create', $this->viewData($konteks, [
            'user' => $request->user(),
            'unreadCount' => 0,
            'mode' => 'create',
            'target' => null,
            'data' => $this->service->dataFormulir(null, $konteks['peran']),
        ]));
    }

    public function store(SimpanUserRequest $request): RedirectResponse
    {
        $konteks = $this->konteks();
        $data = $request->validated();

        if ($konteks['peran'] !== null) {
            $data['role'] = $konteks['peran']->value;
        }

        $hasil = $this->service->simpan($data);

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => $hasil['pesan']]);
        }

        return redirect()
            ->route($konteks['prefix'].'.index')
            ->with('status', $hasil['pesan']);
    }

    public function show(Request $request, User $user): View
    {
        $konteks = $this->konteks();
        $target = $this->target($user);

        return view($konteks['view'].'.show', $this->viewData($konteks, [
            'user' => $request->user(),
            'unreadCount' => 0,
            'detail' => $this->service->detail($target),
        ]));
    }

    public function edit(Request $request, User $user): View
    {
        $konteks = $this->konteks();
        $target = $this->target($user);
        $this->pastikanBolehDiubah($target);

        return view($konteks['view'].'.edit', $this->viewData($konteks, [
            'user' => $request->user(),
            'unreadCount' => 0,
            'mode' => 'edit',
            'target' => $target,
            'data' => $this->service->dataFormulir($target, $konteks['peran']),
        ]));
    }

    public function update(PerbaruiUserRequest $request, User $user): RedirectResponse
    {
        $konteks = $this->konteks();
        $target = $this->target($user);
        $this->pastikanBolehDiubah($target);

        $hasil = $this->service->perbarui($target, $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => $hasil['pesan']]);
        }

        return redirect()
            ->route($konteks['prefix'].'.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * Satu-satunya endpoint yang boleh mengubah status akun.
     */
    public function ubahStatus(UbahStatusUserRequest $request, User $user): RedirectResponse
    {
        $konteks = $this->konteks();
        $target = $this->target($user);

        $hasil = $this->service->ubahStatus(
            $target,
            (bool) $request->validated('aktif'),
            (int) $request->user()->getKey(),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->route($konteks['prefix'].'.index')
                ->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route($konteks['prefix'].'.index')
            ->with('status', $hasil['pesan']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(array $konteks, array $data): array
    {
        return array_merge($data, [
            'roleTetap' => $konteks['peran'],
            'routePrefix' => $konteks['prefix'],
            'label' => $konteks['label'],
        ]);
    }

    /**
     * @return array{prefix: string, view: string, label: string, peran: ?UserRole}
     */
    private function konteks(): array
    {
        if (request()->routeIs('admin.mahasiswa.*')) {
            return [
                'prefix' => 'admin.mahasiswa',
                'view' => 'admin.mahasiswa',
                'label' => 'Mahasiswa',
                'peran' => UserRole::Mahasiswa,
            ];
        }

        if (request()->routeIs('admin.dosen.*')) {
            return [
                'prefix' => 'admin.dosen',
                'view' => 'admin.dosen',
                'label' => 'Dosen',
                'peran' => UserRole::Dosen,
            ];
        }

        return [
            'prefix' => 'admin.pengguna',
            'view' => 'admin.pengguna',
            'label' => 'Pengguna',
            'peran' => null,
        ];
    }

    private function target(User $user): User
    {
        $peran = $this->konteks()['peran'];

        if ($peran !== null && $user->role !== $peran) {
            abort(404);
        }

        return $user;
    }

    /**
     * Admin lain tidak boleh diubah identitasnya. Akun sendiri masih boleh
     * diperbarui namanya, emailnya, dan nomor teleponnya.
     */
    private function pastikanBolehDiubah(User $target): void
    {
        if ($target->isAdmin() && $target->getKey() !== request()->user()?->getKey()) {
            abort(403);
        }
    }
}
