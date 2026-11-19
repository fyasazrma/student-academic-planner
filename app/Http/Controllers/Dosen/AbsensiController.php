<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusAbsensi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\AbsensiKelasRequest;
use App\Http\Requests\Dosen\KoreksiAbsensiRequest;
use App\Http\Requests\Dosen\RiwayatAbsensiRequest;
use App\Http\Requests\Dosen\SimpanSesiAbsensiRequest;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RekapAbsensiService;
use App\Services\Absensi\SesiAbsensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(
        private readonly SesiAbsensiService $sesi,
        private readonly AbsensiService $absensi,
        private readonly RekapAbsensiService $rekap,
    ) {
    }

    public function index(AbsensiKelasRequest $request): View
    {
        return view('dosen.absensi.index', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $this->sesi->daftarKelas((int) $request->user()->getAuthIdentifier()),
        ]);
    }

    public function kelas(AbsensiKelasRequest $request, mixed $kelas): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);

        return view('dosen.absensi.kelas', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => $this->sesi->daftarSesi($milik),
            'ringkasan' => $this->rekap->rekapKelas($milik),
        ]);
    }

    public function create(AbsensiKelasRequest $request, mixed $kelas): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);

        return view('dosen.absensi.form', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => null,
            'mode' => 'buat',
            'nilai' => [
                'pertemuan_ke' => old('pertemuan_ke', ''),
                'tanggal' => old('tanggal', ''),
                'topik' => old('topik', ''),
                'waktu_mulai' => old('waktu_mulai', ''),
                'waktu_selesai' => old('waktu_selesai', ''),
            ],
        ]);
    }

    public function store(SimpanSesiAbsensiRequest $request, mixed $kelas): RedirectResponse
    {
        $hasil = $this->sesi->buat(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('dosen.absensi.kelas', $kelas)
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function show(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);
        $sesiMilik = $this->sesi->sesiMilikKelas($milik, $sesi);

        return view('dosen.absensi.sesi', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => $sesiMilik,
            'waktu_label' => $this->absensi->rentangWaktu($sesiMilik),
            'peserta' => $this->absensi->daftarAbsensiSesi($milik, $sesiMilik),
            'riwayat_url' => route('dosen.absensi.riwayat', [$milik->getKey(), $sesiMilik->getKey()]),
            'edit_url' => route('dosen.absensi.sesi.edit', [$milik->getKey(), $sesiMilik->getKey()]),
        ]);
    }

    public function edit(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);
        $sesiMilik = $this->sesi->sesiMilikKelas($milik, $sesi);

        return view('dosen.absensi.form', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => $sesiMilik,
            'mode' => 'ubah',
            'nilai' => [
                'pertemuan_ke' => old('pertemuan_ke', $sesiMilik->pertemuan_ke),
                'tanggal' => old('tanggal', $sesiMilik->tanggal?->toDateString()),
                'topik' => old('topik', $sesiMilik->topik),
                'waktu_mulai' => old('waktu_mulai', substr((string) $sesiMilik->waktu_mulai, 0, 5)),
                'waktu_selesai' => old('waktu_selesai', substr((string) $sesiMilik->waktu_selesai, 0, 5)),
            ],
        ]);
    }

    public function update(SimpanSesiAbsensiRequest $request, mixed $kelas, mixed $sesi): RedirectResponse
    {
        $hasil = $this->sesi->ubah(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $sesi,
            $request->validated(),
        );

        return redirect()
            ->route('dosen.absensi.sesi.show', [$kelas, $sesi])
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function destroy(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi): RedirectResponse
    {
        $hasil = $this->sesi->hapus(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $sesi,
        );

        return redirect()
            ->route('dosen.absensi.kelas', $kelas)
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function buka(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi): RedirectResponse
    {
        $hasil = $this->sesi->buka(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $sesi,
        );

        return redirect()
            ->route('dosen.absensi.sesi.show', [$kelas, $sesi])
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function tutup(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi): RedirectResponse
    {
        $hasil = $this->sesi->tutup(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $sesi,
        );

        return redirect()
            ->route('dosen.absensi.sesi.show', [$kelas, $sesi])
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function koreksiForm(AbsensiKelasRequest $request, mixed $kelas, mixed $sesi, mixed $peserta): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);
        $sesiMilik = $this->sesi->sesiMilikKelas($milik, $sesi);
        $pesertaMilik = PesertaMataKuliah::query()
            ->whereKey(is_numeric($peserta) ? (int) $peserta : 0)
            ->where('kelas_mata_kuliah_id', $milik->getKey())
            ->with('mahasiswa', 'absensi')
            ->firstOrFail();

        $absensi = $sesiMilik->absensi()
            ->where('peserta_mata_kuliah_id', $pesertaMilik->getKey())
            ->first();

        return view('dosen.absensi.koreksi', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => $sesiMilik,
            'peserta' => $pesertaMilik,
            'absensi' => $absensi,
            'statusSekarang' => $absensi?->status?->value,
            'statusLabel' => $this->absensi->labelStatus($absensi?->status),
            'statusOptions' => array_map(
                fn (StatusAbsensi $item): array => [
                    'value' => $item->value,
                    'label' => $this->absensi->labelStatus($item),
                ],
                StatusAbsensi::cases(),
            ),
        ]);
    }

    public function koreksi(KoreksiAbsensiRequest $request, mixed $kelas, mixed $sesi, mixed $peserta): RedirectResponse
    {
        $status = StatusAbsensi::tryFrom((string) $request->validated('status'));

        if ($status === null) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['status' => 'Status absensi tidak valid.']);
        }

        $hasil = $this->absensi->koreksi(
            (int) $request->user()->getAuthIdentifier(),
            $kelas,
            $sesi,
            $peserta,
            $status,
            (string) $request->validated('alasan'),
            $request->user(),
        );

        return redirect()
            ->route('dosen.absensi.sesi.show', [$kelas, $sesi])
            ->with($hasil['berhasil'] ? 'status' : 'error', $hasil['pesan']);
    }

    public function riwayat(RiwayatAbsensiRequest $request, mixed $kelas, mixed $sesi): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);
        $sesiMilik = $this->sesi->sesiMilikKelas($milik, $sesi);

        return view('dosen.absensi.riwayat', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            'kelas' => $milik,
            'sesi' => $sesiMilik,
            'riwayat' => $this->absensi->riwayatKoreksi($sesiMilik),
        ]);
    }

    public function rekap(AbsensiKelasRequest $request, mixed $kelas): View
    {
        $milik = $this->sesi->kelasMilikDosen((int) $request->user()->getAuthIdentifier(), $kelas);

        return view('dosen.absensi.rekap', [
            'user' => $request->user(),
            'unreadCount' => $this->unreadCount($request),
            ...$this->rekap->rekapKelas($milik),
        ]);
    }

    private function unreadCount($request): int
    {
        return Notifikasi::untukUser((int) $request->user()->getAuthIdentifier())
            ->belumDibaca()
            ->count();
    }
}
