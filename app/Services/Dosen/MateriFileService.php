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
 * Pengelolaan file materi pada private disk lokal.
 *
 * Nama file asli hanya dipakai sebagai informasi tampilan. Path penyimpanan
 * dibuat sepenuhnya oleh server agar tidak ada input pengguna yang masuk ke
 * filesystem path.
 */
class MateriFileService
{
    public const DISK = 'local';

    public const UKURAN_MAKSIMAL_KB = 10240;

    private const PDF_SIGNATURE = '%PDF-';

    private const OLE_SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    private const ZIP_SIGNATURE = "PK\x03\x04";

    private const ZIP_KOSONG_SIGNATURE = "PK\x05\x06";

    /**
     * MIME hasil deteksi konten yang diterima per ekstensi.
     *
     * Legacy Office (doc/xls/ppt) bisa terdeteksi sebagai application/octet-stream
     * pada beberapa versi libmagic. Penerimaan MIME generik tersebut hanya
     * terjadi setelah tanda tangan OLE dan nama stream spesifik format cocok.
     *
     * @var array<string, array<int, string>>
     */
    private const MIME_PER_EKSTENSI = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/cdfv2', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/cdfv2', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/cdfv2', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
    ];

    /**
     * @var array<string, string>
     */
    private const PENANDA_OOXML = [
        'docx' => 'word/',
        'xlsx' => 'xl/',
        'pptx' => 'ppt/',
    ];

    /**
     * @var array<string, string>
     */
    private const PENANDA_OLE = [
        'doc' => 'WordDocument',
        'xls' => 'Workbook',
        'ppt' => 'PowerPoint Document',
    ];

    /**
     * @return array<int, string>
     */
    public function ekstensiDiizinkan(): array
    {
        return array_keys(self::MIME_PER_EKSTENSI);
    }

    public function validasiBerkas(UploadedFile $file): ?string
    {
        $ukuran = $file->getSize();

        if ($ukuran === null || $ukuran <= 0) {
            return 'File kosong tidak boleh diunggah.';
        }

        if ($ukuran > self::UKURAN_MAKSIMAL_KB * 1024) {
            return 'Ukuran file maksimal 10 MB.';
        }

        $ekstensi = $this->ekstensi($file);

        if (! array_key_exists($ekstensi, self::MIME_PER_EKSTENSI)) {
            return 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, atau ZIP.';
        }

        $mime = $this->mime($file);

        if (! in_array($mime, self::MIME_PER_EKSTENSI[$ekstensi], true)) {
            return 'Jenis file tidak sesuai dengan ekstensi yang digunakan.';
        }

        if (! $this->strukturSesuai($file, $ekstensi)) {
            return 'Isi file tidak sesuai dengan format yang dinyatakan.';
        }

        return null;
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

        $path = $this->pathUntuk($kelasId, $this->ekstensi($file));
        $isi = @file_get_contents((string) $file->getRealPath());

        if ($isi === false) {
            Log::error('Gagal membaca file materi yang diunggah.');

            throw new RuntimeException('File materi gagal dibaca.');
        }

        try {
            $tersimpan = Storage::disk(self::DISK)->put($path, $isi, 'private');
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan file materi', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new RuntimeException('File materi gagal disimpan.', 0, $exception);
        }

        if (! $tersimpan) {
            Log::error('Gagal menyimpan file materi', ['path' => $path]);

            throw new RuntimeException('File materi gagal disimpan.');
        }

        return [
            'path' => $path,
            'file_name' => $this->namaAsli($file),
            'file_mime' => $this->mime($file),
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function hapus(?string $path): bool
    {
        if ($path === null || $path === '' || ! $this->pathValid($path)) {
            Log::warning('Path file materi ditolak saat penghapusan.');

            return false;
        }

        try {
            return Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $exception) {
            Log::error('Gagal menghapus file materi', ['path' => $path, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    public function pathMilikKelas(string $path, int $kelasId): bool
    {
        return $this->pathValid($path) && str_starts_with($path, 'materi/'.$kelasId.'/');
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

    private function ekstensi(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension());
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

    private function strukturSesuai(UploadedFile $file, string $ekstensi): bool
    {
        $isi = @file_get_contents((string) $file->getRealPath());

        if ($isi === false) {
            return false;
        }

        if ($ekstensi === 'pdf') {
            return str_starts_with($isi, self::PDF_SIGNATURE);
        }

        if ($ekstensi === 'txt') {
            return ! str_contains($isi, "\0");
        }

        if ($ekstensi === 'zip') {
            return str_starts_with($isi, self::ZIP_SIGNATURE)
                || str_starts_with($isi, self::ZIP_KOSONG_SIGNATURE);
        }

        if (isset(self::PENANDA_OOXML[$ekstensi])) {
            return str_starts_with($isi, self::ZIP_SIGNATURE)
                && str_contains($isi, '[Content_Types].xml')
                && str_contains($isi, self::PENANDA_OOXML[$ekstensi]);
        }

        if (isset(self::PENANDA_OLE[$ekstensi])) {
            $stream = mb_convert_encoding(self::PENANDA_OLE[$ekstensi], 'UTF-16LE', 'UTF-8');

            return str_starts_with($isi, self::OLE_SIGNATURE) && str_contains($isi, $stream);
        }

        return false;
    }

    private function namaAsli(UploadedFile $file): string
    {
        $nama = str_replace('\\', '/', (string) $file->getClientOriginalName());
        $nama = basename($nama);
        $nama = preg_replace('/[\x00-\x1F\x7F]+/u', '', $nama) ?? '';
        $nama = trim($nama);

        if ($nama === '' || $nama === '.' || $nama === '..') {
            $nama = 'materi';
        }

        return mb_substr($nama, 0, 255);
    }

    private function pathUntuk(int $kelasId, string $ekstensi): string
    {
        return sprintf(
            'materi/%d/%s/%s/%s.%s',
            $kelasId,
            now()->format('Y'),
            now()->format('m'),
            (string) Str::uuid(),
            $ekstensi,
        );
    }

    private function pathValid(string $path): bool
    {
        return str_starts_with($path, 'materi/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0");
    }
}
