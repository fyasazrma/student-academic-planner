<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\KomponenNilai;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\NilaiKelasRequest;
use App\Http\Requests\Dosen\PerbaruiKonfigurasiNilaiRequest;
use App\Http\Requests\Dosen\RiwayatNilaiRequest;
use App\Http\Requests\Dosen\SimpanNilaiRequest;
use App\Services\Akademik\PenilaianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function __construct(private readonly PenilaianService $service)
    {
    }

    public function index(NilaiKelasRequest $request): View
    {
        $daftar = $this->service->daftarKelas((int) $request->user()->getAuthIdentifier());

        return view('dosen.nilai.index', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $daftar,
            'belum_dikonfigurasi' => $daftar
                ->filter(fn (array $item): bool => $item['gunakan_tugas_label'] === 'Belum ditentukan')
                ->count(),
        ]);
    }

    public function kelas(NilaiKelasRequest $request, mixed $kelas): View
    {
        $milik = $this->service->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);

        return view('dosen.nilai.kelas', [
            'user' => $request->user(),
            'unreadCount' => 0,
            ...$this->service->rekapKelas($milik),
        ]);
    }

    public function konfigurasi(
        PerbaruiKonfigurasiNilaiRequest $request,
        mixed $kelas,
    ): RedirectResponse {
        $hasil = $this->service->aturGunakanTugas(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $request->boolean('gunakan_tugas'),
            $request->validated('alasan'),
            $request->user(),
        );

        return redirect()
            ->route('dosen.nilai.kelas', $kelas)
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function simpan(
        SimpanNilaiRequest $request,
        mixed $kelas,
        mixed $peserta,
        mixed $komponen,
    ): RedirectResponse {
        $hasil = $this->service->simpan(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $peserta,
            KomponenNilai::from((string) $komponen),
            (string) $request->validated('nilai'),
            $request->validated('alasan'),
            $request->user(),
        );

        if (! $hasil['berhasil']) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['nilai' => $hasil['pesan']]);
        }

        return redirect()
            ->route('dosen.nilai.kelas', $kelas)
            ->with('status', $hasil['pesan']);
    }

    public function riwayat(RiwayatNilaiRequest $request, mixed $kelas, mixed $peserta): View
    {
        $milik = $this->service->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);
        $pesertaMilik = $this->service->pesertaMilikKelas($milik, $peserta);

        return view('dosen.nilai.riwayat', [
            'user' => $request->user(),
            'unreadCount' => 0,
            'kelas' => $milik,
            'peserta' => $pesertaMilik,
            'riwayat' => $this->service->riwayat($milik, $pesertaMilik),
        ]);
    }
}
