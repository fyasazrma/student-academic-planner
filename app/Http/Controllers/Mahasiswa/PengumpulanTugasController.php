<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\SimpanPengumpulanRequest;
use App\Models\Notifikasi;
use App\Services\Mahasiswa\PengumpulanTugasService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Controller untuk form, penyimpanan, dan unduhan pengumpulan tugas mahasiswa.
 */
class PengumpulanTugasController extends Controller
{
    public function __construct(private readonly PengumpulanTugasService $service)
    {
    }

    public function form(Request $request, mixed $tugas): View
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        return view('mahasiswa.tugas.pengumpulan', array_merge(
            $this->service->form($userId, (int) $tugas),
            [
                'user' => $request->user(),
                'unreadCount' => Notifikasi::untukUser($userId)->belumDibaca()->count(),
            ],
        ));
    }

    public function simpan(SimpanPengumpulanRequest $request, mixed $tugas): RedirectResponse
    {
        $validated = $request->validated();
        $userId = (int) $request->user()->getAuthIdentifier();

        try {
            $this->service->simpan(
                $userId,
                (int) $tugas,
                $validated['lampiran'],
                $validated['catatan'] ?? null,
            );
        } catch (AuthorizationException $exception) {
            abort(Response::HTTP_FORBIDDEN, $exception->getMessage());
        } catch (DeadlockException | QueryException $exception) {
            report($exception);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Pengumpulan sementara gagal disimpan. Silakan coba lagi.');
        } catch (ModelNotFoundException | HttpExceptionInterface $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['lampiran' => $exception->getMessage()]);
        }

        return redirect()->route('mahasiswa.tugas')
            ->with('success', 'Pengumpulan tugas berhasil disimpan.');
    }

    public function unduh(Request $request, mixed $tugas): Response
    {
        return $this->service->unduh(
            (int) $request->user()->getAuthIdentifier(),
            (int) $tugas,
        );
    }
}
