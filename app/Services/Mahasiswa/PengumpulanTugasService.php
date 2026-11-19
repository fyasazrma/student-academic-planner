<?php

namespace App\Services\Mahasiswa;

use App\Enums\StatusPeserta;
use App\Models\PengumpulanTugas;
use App\Models\PesertaMataKuliah;
use App\Models\Tugas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Alur pengumpulan tugas mahasiswa.
 *
 * Retry dikelola eksplisit di luar DB::transaction(). Satu percobaan selalu
 * berarti satu eksekusi callback, sehingga efek sistem berkas tidak dijalankan
 * ulang oleh retry internal Laravel. Lock dibuka urutan tugas lalu peserta,
 * dan identitas peserta serta kelas selalu diturunkan dari auth + tugas.
 */
class PengumpulanTugasService
{
    private const MAX_PERCOBAAN = 3;

    public function __construct(private readonly PengumpulanFileService $file)
    {
    }

    /**
     * Data untuk halaman form pengumpulan.
     *
     * @return array<string, mixed>
     */
    public function form(int $userId, int $tugasId): array
    {
        $tugas = Tugas::query()
            ->whereKey($tugasId)
            ->dipublikasikan()
            ->with('kelasMataKuliah.mataKuliah')
            ->firstOrFail();

        $kelasId = (int) $tugas->kelas_mata_kuliah_id;
        $peserta = $this->pesertaAktif($userId, $kelasId)->firstOrFail();

        $pengumpulan = PengumpulanTugas::query()
            ->where('tugas_id', $tugas->getKey())
            ->where('peserta_mata_kuliah_id', $peserta->getKey())
            ->first();

        $sudahDinilai = $pengumpulan !== null && $pengumpulan->sudahDinilai();
        $deadlineDitutup = $tugas->sudahMelewatiDeadline() && ! $tugas->izinkan_terlambat;
        $alasanTerkunci = match (true) {
            $sudahDinilai => 'Pengumpulan yang sudah dinilai tidak dapat diubah.',
            $deadlineDitutup => 'Deadline telah lewat dan keterlambatan tidak diizinkan.',
            default => null,
        };

        $unduhUrl = null;

        if ($pengumpulan !== null
            && is_string($pengumpulan->file_path)
            && $this->file->pathMilikKelas($pengumpulan->file_path, $kelasId)) {
            $unduhUrl = route('mahasiswa.tugas.pengumpulan.unduh', $tugas->getKey());
        }

        return [
            'tugas' => $tugas,
            'kelas' => $tugas->kelasMataKuliah,
            'namaMataKuliah' => $tugas->kelasMataKuliah?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
            'pengumpulan' => $pengumpulan,
            'ekstensiDiizinkan' => $this->file->ekstensiDiizinkan(),
            'bisa_dikumpulkan' => ! $sudahDinilai && ! $deadlineDitutup,
            'alasan_terkunci' => $alasanTerkunci,
            'unduh_url' => $unduhUrl,
        ];
    }

    /**
     * Simpan pengumpulan baru atau perbarui pengumpulan yang belum dinilai.
     *
     * @param  string|null  $catatan
     */
    public function simpan(int $userId, int $tugasId, \Illuminate\Http\UploadedFile $file, ?string $catatan): PengumpulanTugas
    {
        for ($attempt = 1; $attempt <= self::MAX_PERCOBAAN; $attempt++) {
            // Isolasi penuh per percobaan: tidak ada path/ID yang terbawa.
            $baruPath = null;
            $lamaPath = null;
            $metaBaru = null;
            $pesertaId = null;
            $kelasId = null;
            $sukses = false;
            $gagal = null;

            try {
                DB::transaction(function () use (
                    $file,
                    $tugasId,
                    $userId,
                    $catatan,
                    &$baruPath,
                    &$lamaPath,
                    &$metaBaru,
                    &$pesertaId,
                    &$kelasId,
                ): void {
                    // Urutan lock deterministik: tugas lebih dulu, lalu peserta.
                    $tugas = Tugas::query()
                        ->whereKey($tugasId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $tugas->sudahDipublikasikan()) {
                        abort(Response::HTTP_NOT_FOUND);
                    }

                    // Kelas berasal dari tugas yang sudah dikunci, bukan input pengguna.
                    $kelasId = (int) $tugas->kelas_mata_kuliah_id;

                    // Peserta diturunkan dari akun login dan kelas tugas, lalu dicek ulang di dalam lock.
                    $peserta = $this->pesertaAktif($userId, $kelasId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $pesertaId = (int) $peserta->getKey();

                    $existing = PengumpulanTugas::query()
                        ->where('tugas_id', $tugas->getKey())
                        ->where('peserta_mata_kuliah_id', $pesertaId)
                        ->first();

                    if ($existing !== null && $existing->sudahDinilai()) {
                        throw new AuthorizationException('Pengumpulan sudah dinilai dan tidak dapat dikirim ulang.');
                    }

                    $terlambat = $tugas->sudahMelewatiDeadline();

                    if ($terlambat && ! $tugas->izinkan_terlambat) {
                        throw ValidationException::withMessages([
                            'lampiran' => 'Deadline telah lewat dan keterlambatan tidak diizinkan.',
                        ]);
                    }

                    // Path lama hanya dicatat; penghapusan dilakukan setelah commit berhasil.
                    $lamaPath = $existing?->file_path;

                    $metaBaru = $this->file->simpan($file, $kelasId);
                    $baruPath = $metaBaru['path'];

                    $pengumpulan = $existing ?? new PengumpulanTugas();
                    $pengumpulan->fill([
                        'tugas_id' => $tugas->getKey(),
                        'file_path' => $baruPath,
                        'file_name' => $metaBaru['file_name'],
                        'file_mime' => $metaBaru['file_mime'],
                        'file_size' => $metaBaru['file_size'],
                        'catatan' => $catatan,
                        'dikumpulkan_pada' => now(),
                    ]);

                    // Kolom ini sengaja tidak fillable agar tidak bisa diset dari browser.
                    $pengumpulan->peserta_mata_kuliah_id = $pesertaId;
                    $pengumpulan->terlambat = $terlambat;
                    $pengumpulan->save();
                }); // Tanpa argumen $attempts: callback tidak diulang oleh Laravel.

                $sukses = true;
            } catch (Throwable $exception) {
                $gagal = $exception;

                // Exception utama tidak boleh ditimpa kegagalan cleanup.
                if ($baruPath !== null && $kelasId !== null) {
                    try {
                        $this->hapusFileJikaAman($baruPath, $kelasId, 'attempt ke-'.$attempt);
                    } catch (Throwable $cleanupError) {
                        Log::warning('Cleanup attempt gagal dan diabaikan.', [
                            'attempt' => $attempt,
                            'error' => $cleanupError->getMessage(),
                        ]);
                    }
                }

                $baruPath = null;
                $lamaPath = null;
                $metaBaru = null;
                $pesertaId = null;
                $kelasId = null;
            }

            if ($sukses) {
                break;
            }

            if ($this->harusRetry($gagal) && $attempt < self::MAX_PERCOBAAN) {
                continue;
            }

            if ($gagal !== null) {
                throw $gagal;
            }
        }

        // Verifikasi pasca-commit memakai koneksi write sebelum file lama dihapus.
        $final = PengumpulanTugas::query()
            ->useWritePdo()
            ->where('tugas_id', $tugasId)
            ->where('peserta_mata_kuliah_id', $pesertaId)
            ->firstOrFail();

        if ($baruPath !== null
            && $final->file_path === $baruPath
            && Storage::disk(PengumpulanFileService::DISK)->exists($baruPath)) {

            if ($lamaPath !== null && $lamaPath !== $baruPath) {
                try {
                    $this->hapusFileJikaAman($lamaPath, $kelasId, 'file lama pasca-commit');
                } catch (Throwable $cleanupError) {
                    Log::warning('Cleanup file lama gagal dan diabaikan.', [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return $final;
        }

        Log::error('Database tidak menunjuk ke file pengumpulan baru; file lama sengaja tidak dihapus.', [
            'tugas_id' => $tugasId,
            'peserta_mata_kuliah_id' => $pesertaId,
            'lama' => $lamaPath,
            'baru' => $baruPath,
        ]);

        return $final;
    }

    /**
     * Unduh lampiran pengumpulan milik mahasiswa yang login.
     */
    public function unduh(int $userId, int $tugasId): Response
    {
        $tugas = Tugas::query()
            ->whereKey($tugasId)
            ->dipublikasikan()
            ->firstOrFail();

        $kelasId = (int) $tugas->kelas_mata_kuliah_id;
        $peserta = $this->pesertaAktif($userId, $kelasId)->firstOrFail();

        $pengumpulan = PengumpulanTugas::query()
            ->where('tugas_id', $tugas->getKey())
            ->where('peserta_mata_kuliah_id', $peserta->getKey())
            ->firstOrFail();

        if (! is_string($pengumpulan->file_path)
            || ! $this->file->pathMilikKelas($pengumpulan->file_path, $kelasId)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $this->file->unduh(
            $pengumpulan->file_path,
            (string) $pengumpulan->file_name,
            $pengumpulan->file_mime,
        );
    }

    /**
     * Hapus file hanya jika path scoped dan tidak direferensikan database.
     *
     * Pemeriksaan referensi memakai koneksi write. Jika database tidak dapat
     * dihubungi, file sengaja dibiarkan dan dicatat untuk pemeriksaan lanjutan.
     * Helper ini tidak pernah melempar exception ke pemanggil.
     */
    private function hapusFileJikaAman(?string $path, ?int $kelasId, string $konteks): bool
    {
        if ($path === null || $path === '' || $kelasId === null) {
            return false;
        }

        if (! $this->file->pathMilikKelas($path, $kelasId)) {
            Log::warning('Cleanup file ditolak: path tidak sesuai kelas.', [
                'konteks' => $konteks,
                'path' => $path,
                'kelas_id' => $kelasId,
            ]);

            return false;
        }

        try {
            $direferensikan = PengumpulanTugas::query()
                ->useWritePdo()
                ->where('file_path', $path)
                ->exists();
        } catch (Throwable $exception) {
            Log::error('Cleanup dibatalkan: status referensi file tidak dapat dipastikan.', [
                'konteks' => $konteks,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($direferensikan) {
            // Bagian dari perlindungan ambiguous commit: commit bisa saja sudah
            // berhasil sebelum koneksi terputus dan exception diterima aplikasi.
            Log::warning('Cleanup dibatalkan: path masih direferensikan pengumpulan_tugas.', [
                'konteks' => $konteks,
                'path' => $path,
            ]);

            return false;
        }

        try {
            return $this->file->hapus($path, $kelasId);
        } catch (Throwable $exception) {
            Log::warning('Cleanup file gagal dan diabaikan.', [
                'konteks' => $konteks,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Hanya duplicate-key dan deadlock/lock timeout yang di-retry eksplisit.
     */
    private function harusRetry(?Throwable $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        if ($exception instanceof DeadlockException) {
            return true;
        }

        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = $this->sqlState($exception);
        $kodeDriver = $this->kodeDriver($exception);
        $pesan = strtolower($exception->getMessage());

        $duplicate = (string) $kodeDriver === '1062'
            || ($sqlState === '23000'
                && (str_contains($pesan, 'duplicate') || str_contains($pesan, '1062')));

        $deadlock = $sqlState === '40001'
            || in_array((int) $kodeDriver, [1205, 1213], true)
            || str_contains($pesan, 'deadlock')
            || str_contains($pesan, 'lock wait timeout');

        return $duplicate || $deadlock;
    }

    private function sqlState(QueryException $exception): ?string
    {
        $info = $exception->errorInfo;

        if (is_array($info) && isset($info[0]) && $info[0] !== '') {
            return strtoupper((string) $info[0]);
        }

        return null;
    }

    private function kodeDriver(QueryException $exception): int|string|null
    {
        $info = $exception->errorInfo;

        if (is_array($info) && isset($info[1])) {
            return is_numeric($info[1]) ? (int) $info[1] : (string) $info[1];
        }

        return $exception->getCode();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<PesertaMataKuliah>
     */
    private function pesertaAktif(int $userId, int $kelasId)
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->where('kelas_mata_kuliah_id', $kelasId)
            ->where('status', StatusPeserta::Aktif->value);
    }
}
