<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profil\PerbaruiProfilRequest;
use App\Http\Requests\Profil\UbahPasswordRequest;
use App\Models\User;
use App\Services\Profile\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil selalu diambil dari pengguna yang sedang login. Tidak ada route
 * yang menerima ID pengguna lain.
 */
class ProfilController extends Controller
{
    public function __construct(private readonly ProfileService $service)
    {
    }

    public function show(Request $request): View
    {
        $user = $this->user($request);

        return view($this->viewPrefix($user).'.show', [
            'user' => $user,
            'unreadCount' => 0,
            'profil' => $this->service->dataProfil($user),
            'routePrefix' => $this->routePrefix($user),
            'peranLabel' => $user->isDosen() ? 'Dosen' : 'Mahasiswa',
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $this->user($request);

        return view($this->viewPrefix($user).'.edit', [
            'user' => $user,
            'unreadCount' => 0,
            'profil' => $this->service->dataProfil($user),
            'routePrefix' => $this->routePrefix($user),
            'peranLabel' => $user->isDosen() ? 'Dosen' : 'Mahasiswa',
        ]);
    }

    public function update(PerbaruiProfilRequest $request): RedirectResponse
    {
        $hasil = $this->service->perbarui($this->user($request), $request->validated());

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => $hasil['pesan']]);
        }

        return redirect()
            ->route($this->routePrefix($this->user($request)))
            ->with('status', $hasil['pesan']);
    }

    public function ubahPassword(UbahPasswordRequest $request): RedirectResponse
    {
        $user = $this->user($request);
        $hasil = $this->service->ubahPassword($user, $request->validated());

        if (! $hasil['berhasil']) {
            $redirect = redirect()->back()->withInput();

            return $hasil['field'] !== null
                ? $redirect->withErrors([$hasil['field'] => $hasil['pesan']])
                : $redirect->with('error', $hasil['pesan']);
        }

        return redirect()
            ->route($this->routePrefix($user))
            ->with('status', $hasil['pesan']);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function routePrefix(User $user): string
    {
        return $user->isDosen() ? 'dosen.profil' : 'mahasiswa.profil';
    }

    private function viewPrefix(User $user): string
    {
        return $user->isDosen() ? 'dosen.profil' : 'mahasiswa.profil';
    }
}
