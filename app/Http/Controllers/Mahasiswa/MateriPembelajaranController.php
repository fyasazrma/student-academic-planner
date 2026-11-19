<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Services\Mahasiswa\MateriPembelajaranService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller untuk melihat dan mengunduh materi pembelajaran mahasiswa.
 */
class MateriPembelajaranController extends Controller
{
    public function __construct(
        private readonly MateriPembelajaranService $service,
    ) {
    }

    /**
     * Tampilkan daftar materi terpublikasi dari kelas yang diikuti.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $kelasId = $request->query('kelas');
        $kelasId = is_numeric($kelasId) ? (int) $kelasId : null;

        $data = $this->service->daftarMateri($user->id, $kelasId);

        return view('mahasiswa.materi.index', [
            'user' => $user,
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
            'kelasAktif' => $data['kelasAktif'],
            'selectedKelasId' => $kelasId,
            'selectedKelas' => $data['selectedKelas'],
            'materi' => $data['materi'],
            'stats' => $data['stats'],
        ]);
    }

    /**
     * Unduh file materi pembelajaran terpublikasi.
     */
    public function download(Request $request, int $kelas, int $materi): Response
    {
        return $this->service->unduh($request->user()->id, $kelas, $materi);
    }
}
