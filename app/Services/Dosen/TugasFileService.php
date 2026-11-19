<?php

namespace App\Services\Dosen;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pengelolaan lampiran tugas pada private disk lokal.
 *
 * Kebijakan format, MIME, tanda tangan berkas, dan ukuran mengikuti
 * MateriFileService agar konsisten dengan unggahan materi. Path dibuat
 * sepenuhnya oleh server dengan prefix tugas/{kelas}/ sehingga lampiran
 * tidak dapat diakses lintas kelas.
 */
class TugasFileService
{
    public const DISK = 'local';

    public function __construct(private readonly MateriFileService $berkas)
    {
    }

    /**
     * @return array<int, string>
     */
    public function ekstensiDiizinkan(): array
    {
        return $this->berkas->ekstensiDiizinkan();
    }

    public function validasiBerkas(UploadedFile $file): ?string
    {
        return $this->berkas->validasiBerkas($file);
    }

    /**
     * @return array{path: string, file_name: string, file_mime: string, file_size: int}
     */
    public function simpan(UploadedFile $file, int $kelasId): array
    {
        $pesan = $this->validasiBerkas($file);

        if ($pesan !== null) {
            throw new RuntimeException($pesan);
        }

        $ekstensi = strtolower($file->getClientOriginalExtension());
        $path = $this->pathUntuk($kelasId, $ekstensi);
        $isi = @file_get_contents((string) $file->getRealPath());

        if ($isi === false) {
            Log::error('Gagal membaca lampiran tugas yang diunggah.');

            throw new RuntimeException('Lampiran tugas gagal dibaca.');
        }

        try {
            $tersimpan = Storage::disk(self::DISK)->put($path, $isi, 'private');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan lampiran tugas', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new RuntimeException('Lampiran tugas gagal disimpan.', 0, $exception);
        }

        if (! $tersimpan) {
            Log::error('Gagal menyimpan lampiran tugas', ['path' => $path]);

            throw new RuntimeException('Lampiran tugas gagal disimpan.');
        }

        return [
            'path' => $path,
            'file_name' => $this->namaAsli($file),
            'file_mime' => $this->mime($file),
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function hapus(?string $path, int $kelasId): bool
    {
        if ($path === null || $path === '' || ! $this->pathMilikKelas($path, $kelasId)) {
            Log::warning('Path lampiran tugas ditolak saat penghapusan.', [
                'path' => $path,
                'kelas_id' => $kelasId,
            ]);

            return false;
        }

        try {
            return Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus lampiran tugas', ['path' => $path, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    public function pathMilikKelas(string $path, int $kelasId): bool
    {
        return $this->pathValid($path) && str_starts_with($path, 'tugas/'.$kelasId.'/');
    }

    public function unduh(string $path, string $namaFile, ?string $mime): Response
    {
        if (! $this->pathValid($path) || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        return Storage::disk(self::DISK)->download($path, $namaFile, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function mime(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return strtolower((string) $file->getMimeType());
        }

        $mime = finfo_file($finfo, $path);

        return strtolower(trim(explode(';', is_string($mime) ? $mime : '')[0]));
    }

    private function namaAsli(UploadedFile $file): string
    {
        $nama = str_replace('\\', '/', (string) $file->getClientOriginalName());
        $nama = basename($nama);
        $nama = preg_replace('/[\x00-\x1F\x7F]+/u', '', $nama) ?? '';
        $nama = trim($nama);

        if ($nama === '' || $nama === '.' || $nama === '..') {
            $nama = 'lampiran';
        }

        return mb_substr($nama, 0, 255);
    }

    private function pathUntuk(int $kelasId, string $ekstensi): string
    {
        return sprintf(
            'tugas/%d/%s/%s/%s.%s',
            $kelasId,
            now()->format('Y'),
            now()->format('m'),
            (string) Str::uuid(),
            $ekstensi,
        );
    }

    private function pathValid(string $path): bool
    {
        return str_starts_with($path, 'tugas/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0");
    }
}
