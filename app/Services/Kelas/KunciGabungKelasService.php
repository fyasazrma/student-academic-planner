<?php

namespace App\Services\Kelas;

use App\Models\KelasMataKuliah;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use SensitiveParameter;

/**
 * Mengelola kunci gabung kelas.
 *
 * Kunci asli hanya dikembalikan oleh `terapkan()` dan `rotasi()` agar bisa
 * ditampilkan sekali oleh pemanggil. Database hanya menyimpan hash SHA-256.
 * Kunci tidak memiliki masa kedaluwarsa otomatis dan hanya berubah jika
 * dosen/admin melakukan rotasi secara eksplisit.
 */
class KunciGabungKelasService
{
    /** 16 byte = 128 bit entropi. */
    private const JUMLAH_BYTE = 16;

    private const MAKS_PERCOBAAN = 5;

    /**
     * Buat kunci acak kriptografis. Hanya dipanggil oleh operasi buat/rotasi.
     */
    public function buatKunci(): string
    {
        return strtoupper(bin2hex(random_bytes(self::JUMLAH_BYTE)));
    }

    /**
     * Normalisasi kunci sebelum hashing maupun verifikasi.
     */
    public function normalisasi(#[SensitiveParameter] string $kunci): string
    {
        return strtoupper(trim($kunci));
    }

    /**
     * Hash kunci yang sudah dinormalisasi. Kunci asli tidak disimpan.
     */
    public function hash(#[SensitiveParameter] string $kunci): string
    {
        return hash('sha256', $this->normalisasi($kunci));
    }

    /**
     * Pasang kunci pertama kali. Hanya boleh jika kelas belum memiliki kunci.
     */
    public function terapkan(KelasMataKuliah $kelas): string
    {
        [$kunci] = $this->pasang($kelas, false);

        return $kunci;
    }

    /**
     * Rotasi kunci atas tindakan eksplisit. Kunci lama langsung tidak berlaku.
     */
    public function rotasi(KelasMataKuliah $kelas): string
    {
        [$kunci] = $this->pasang($kelas, true);

        return $kunci;
    }

    /**
     * Verifikasi selalu membaca hash terbaru dari database berdasarkan ID kelas,
     * sehingga instance Eloquent yang stale tidak bisa menerima kunci lama.
     */
    public function verifikasi(KelasMataKuliah $kelas, #[SensitiveParameter] string $kunci): bool
    {
        if (! $kelas->exists) {
            return false;
        }

        $hash = KelasMataKuliah::query()
            ->whereKey($kelas->getKey())
            ->value('kode_gabung_hash');

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        return hash_equals($hash, $this->hash($kunci));
    }

    /**
     * Pasang atau rotasi kunci secara atomik.
     *
     * Baris kelas dikunci dengan lockForUpdate() sehingga dua rotasi bersamaan
     * diproses berurutan. Nilai yang dikembalikan adalah hash yang benar-benar
     * tersimpan pada transaksi yang berhasil di-commit.
     *
     * @return array{0: string, 1: string} Kunci asli dan hash tersimpan
     */
    private function pasang(KelasMataKuliah $kelas, bool $rotasi): array
    {
        if (! $kelas->exists) {
            throw new LogicException('Kelas harus disimpan sebelum kunci gabung dibuat.');
        }

        [$kunci, $hash] = DB::transaction(function () use ($kelas, $rotasi): array {
            $terkunci = KelasMataKuliah::query()
                ->whereKey($kelas->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hashLama = $terkunci->kode_gabung_hash;

            if ($rotasi && (! is_string($hashLama) || $hashLama === '')) {
                throw new LogicException('Kelas belum memiliki kunci. Gunakan terapkan untuk membuat kunci pertama.');
            }

            if (! $rotasi && is_string($hashLama) && $hashLama !== '') {
                throw new LogicException('Kelas sudah memiliki kunci. Gunakan rotasi untuk menggantinya.');
            }

            for ($percobaan = 0; $percobaan < self::MAKS_PERCOBAAN; $percobaan++) {
                $kunci = $this->buatKunci();
                $hash = $this->hash($kunci);

                // Kurangi kemungkinan bentrok; constraint unik tetap menjadi penentu akhir.
                if (KelasMataKuliah::query()->where('kode_gabung_hash', $hash)->exists()) {
                    continue;
                }

                try {
                    $terkunci->forceFill(['kode_gabung_hash' => $hash])->save();

                    // Pastikan nilai yang akan dikembalikan benar-benar tersimpan.
                    $tersimpan = KelasMataKuliah::query()
                        ->whereKey($terkunci->getKey())
                        ->value('kode_gabung_hash');

                    if (! is_string($tersimpan) || ! hash_equals($hash, $tersimpan)) {
                        throw new RuntimeException('Hash kunci gabung gagal disimpan.');
                    }

                    return [$kunci, $hash];
                } catch (QueryException $exception) {
                    if (! $this->bentrokKodeGabungUnik($exception)) {
                        throw $exception;
                    }
                }
            }

            throw new RuntimeException('Gagal membuat kunci gabung kelas yang unik.');
        });

        // Sinkronkan instance pemanggil tanpa menulis ke database lagi.
        $kelas->forceFill(['kode_gabung_hash' => $hash]);

        return [$kunci, $hash];
    }

    /**
     * Hanya anggap sebagai collision jika pelanggaran unik memang terjadi pada
     * kolom kode_gabung_hash. Integrity constraint lain harus tetap dilempar.
     */
    private function bentrokKodeGabungUnik(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        $kodeDriver = (string) ($exception->errorInfo[1] ?? '');
        $pesan = strtolower($exception->getMessage());

        if (! str_contains($pesan, 'kode_gabung_hash')) {
            return false;
        }

        // MySQL/MariaDB = 1062, SQLite = 19 (SQLITE_CONSTRAINT).
        if ($kodeDriver === '1062' || $kodeDriver === '19') {
            return true;
        }

        return str_contains($pesan, 'unique') || str_contains($pesan, 'duplicate');
    }
}
