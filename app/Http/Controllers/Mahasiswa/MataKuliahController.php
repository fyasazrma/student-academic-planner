<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\CariKelasRequest;
use App\Http\Requests\Mahasiswa\GabungKelasRequest;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Services\Mahasiswa\MataKuliahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Endpoint HTTP untuk kelas milik mahasiswa dan penggabungan kelas.
 *
 * Service adalah satu-satunya sumber data dan keputusan. Controller hanya
 * memetakan hasilnya ke data publik eksplisit untuk Blade, tidak pernah
 * mengirim model Eloquent mentah, hash/kunci gabung, atau atribut akun dosen.
 */
class MataKuliahController extends Controller
{
    public function __construct(private readonly MataKuliahService $service)
    {
    }

    /**
     * Tampilkan kelas yang diikuti mahasiswa yang sedang login.
     */
    public function index(Request $request): View
    {
        return view('mahasiswa.mata-kuliah.index', array_merge($this->dataBersama($request, ''), [
            'kelas' => null,
            'paginasi' => null,
        ]));
    }

    /**
     * Tampilkan hasil pencarian memakai query yang sudah ada di service.
     */
    public function cari(CariKelasRequest $request): View
    {
        $validated = $request->validated();
        $hasil = $this->service->cariKelas($validated);
        $kataKunci = is_string($validated['q'] ?? null) ? $validated['q'] : '';

        return view('mahasiswa.mata-kuliah.index', array_merge($this->dataBersama($request, $kataKunci), [
            'kelas' => $hasil,
            'paginasi' => [
                'total' => $hasil->total(),
                'per_halaman' => $hasil->perPage(),
                'halaman_saat_ini' => $hasil->currentPage(),
                'halaman_terakhir' => $hasil->lastPage(),
                'dari' => $hasil->firstItem(),
                'sampai' => $hasil->lastItem(),
            ],
        ]));
    }

    /**
     * Gabungkan mahasiswa memakai identitas dari akun yang login.
     */
    public function gabung(GabungKelasRequest $request): RedirectResponse
    {
        $hasil = $this->service->gabung(
            $request->user()->id,
            (string) $request->validated('kode_gabung'),
        );

        if ($hasil['berhasil']) {
            return redirect()
                ->route('mahasiswa.mata-kuliah')
                ->with('status', $hasil['pesan']);
        }

        return $this->tanggapiGagal($request, $hasil['kode'], $hasil['pesan']);
    }

    /**
     * Data shell yang dipakai kedua aksi: identitas, jumlah notifikasi,
     * kelas milik mahasiswa, dan kata kunci formulir.
     *
     * @return array<string, mixed>
     */
    private function dataBersama(Request $request, string $kataKunci): array
    {
        $user = $request->user();

        $kelasSaya = $this->service->kelasSaya($user->id)
            ->map(function (PesertaMataKuliah $pendaftaran): array {
                $kelas = $pendaftaran->kelasMataKuliah;
                $tahun = $kelas?->tahunAkademik;
                $mataKuliah = $kelas?->mataKuliah;

                return [
                    'pendaftaran_id' => $pendaftaran->id,
                    'status_peserta' => $pendaftaran->status?->value,
                    'tanggal_daftar' => $pendaftaran->tanggal_daftar?->toDateString(),
                    'kelas_id' => $kelas?->id,
                    'kode_kelas' => $kelas?->kode_kelas,
                    'nama_kelas' => $kelas?->nama_kelas,
                    'kode_mata_kuliah' => $mataKuliah?->kode,
                    'nama_mata_kuliah' => $mataKuliah?->nama,
                    'sks' => $mataKuliah?->sks,
                    // Hanya nama dosen yang dipakai; atribut akun tidak dikirim.
                    'nama_dosen' => $kelas?->dosen?->name ?? 'Dosen tidak tersedia',
                    'tahun_akademik' => $tahun?->label() ?? 'Tidak tersedia',
                    'semester' => $tahun?->semester?->value,
                ];
            })
            ->values()
            ->all();

        return [
            'user' => $user,
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
            'kelasSaya' => $kelasSaya,
            'kataKunci' => $kataKunci,
        ];
    }

    /**
     * Kunci salah menjadi error pada field; kegagalan lain menjadi flash status.
     * Kunci gabung sengaja tidak pernah dikembalikan ke old input.
     */
    private function tanggapiGagal(Request $request, string $kode, string $pesan): RedirectResponse
    {
        if ($kode === MataKuliahService::HASIL_KUNCI_TIDAK_VALID) {
            return redirect()
                ->back()
                ->withErrors(['kode_gabung' => $pesan])
                ->withInput($request->except('kode_gabung', '_token', '_method'));
        }

        return redirect()->back()->with('status', $pesan);
    }
}
