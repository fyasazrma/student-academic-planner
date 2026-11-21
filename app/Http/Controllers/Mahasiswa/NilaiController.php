<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\NilaiRequest;
use App\Services\Akademik\IndeksPrestasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function __construct(private readonly IndeksPrestasiService $service)
    {
    }

    public function index(NilaiRequest $request): View
    {
        $user = $request->user();

        return view('mahasiswa.nilai.index', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            ...$this->service->ringkasan(
                (int) $user->getAuthIdentifier(),
                $request->query('tahun_akademik_id'),
            ),
        ]);
    }

    public function show(NilaiRequest $request, mixed $mata_kuliah): View
    {
        $user = $request->user();
        $data = $this->service->riwayatMataKuliah(
            (int) $user->getAuthIdentifier(),
            (int) $mata_kuliah,
        );

        return view('mahasiswa.nilai.show', [
            'user' => $user,
            'unreadCount' => $this->unreadCount(),
            ...$data,
        ]);
    }

}
