<?php

namespace App\Services\Mahasiswa;

use App\Services\Dosen\MateriFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pengelolaan lampiran pengumpulan tugas mahasiswa pada private disk lokal.
 *
 * Kebijakan format, MIME, tanda tangan berkas, dan ukuran mengikuti
 * MateriFileService. Path dibuat sepenuhnya oleh server dengan prefix
 * pengumpulan/{kelas}/ sehingga berkas tidak dapat diakses lintas kelas.
 */
class PengumpulanFileService
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
     * Simpan lampiran dan kembalikan metadata hanya setelah penyimpanan sukses.
     *
     * Jika Storage::put() gagal atau hanya menulis sebagian, path milik berkas
     * ini sendiri dibersihkan lebih dulu. File lama tidak pernah disentuh di sini.
     *
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
            Log::error('Gagal membaca lampiran pengumpulan yang diunggah.');

            throw new RuntimeException('Lampiran pengumpulan gagal dibaca.');
        }

        try {
            $tersimpan = Storage::disk(self::DISK)->put($path, $isi, 'private');
        } catch (Throwable $exception) {
            $this->hapusTulisanParsial($path, $kelasId);
            Log::error('Gagal menyimpan lampiran pengumpulan', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Lampiran pengumpulan gagal disimpan.', 0, $exception);
        }

        if (! $tersimpan) {
            $this->hapusTulisanParsial($path, $kelasId);
            Log::error('Gagal menyimpan lampiran pengumpulan', ['path' => $path]);

            throw new RuntimeException('Lampiran pengumpulan gagal disimpan.');
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
            Log::warning('Path lampiran pengumpulan ditolak saat penghapusan.', [
                'path' => $path,
                'kelas_id' => $kelasId,
            ]);

            return false;
        }

        try {
            return Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus lampiran pengumpulan', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function pathMilikKelas(string $path, int $kelasId): bool
    {
        return $this->pathValid($path) && str_starts_with($path, 'pengumpulan/'.$kelasId.'/');
    }

    public function unduh(string $path, string $namaFile, ?string $mime): Response
    {
        if (! $this->pathValid($path) || ! Storage::disk(self::DISK)->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk(self::DISK)->download($path, $namaFile, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function hapusTulisanParsial(string $path, int $kelasId): void
    {
        try {
            if ($this->pathMilikKelas($path, $kelasId)) {
                Storage::disk(self::DISK)->delete($path);
            }
        } catch (Throwable $exception) {
            Log::warning('Gagal membersihkan tulisan parsial lampiran pengumpulan.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
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
            'pengumpulan/%d/%s/%s/%s.%s',
            $kelasId,
            now()->format('Y'),
            now()->format('m'),
            (string) Str::uuid(),
            $ekstensi,
        );
    }

    private function pathValid(string $path): bool
    {
        return str_starts_with($path, 'pengumpulan/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0");
    }
}
