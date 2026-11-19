<?php

namespace App\Services\Akademik;

use App\Enums\NilaiHuruf;
use App\Enums\StatusNilaiAkhir;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Perhitungan nilai akhir murni: tidak menulis database dan tidak membaca
 * request. Seluruh aturan akademik berada di sini, bukan di Blade/JavaScript.
 *
 * Nilai dihitung dalam satuan sen (desimal dua angka) memakai integer agar
 * pembulatan setengah ke atas deterministik dan tidak dipengaruhi representasi
 * float. Bobot normalisasi memakai rasio eksak 35/90 dan 55/90, bukan 38,89%.
 */
class NilaiAkhirService
{
    public const BOBOT_UTS = 35;
    public const BOBOT_UAS = 55;
    public const BOBOT_TUGAS = 10;

    private const ALASAN_LABEL = [
        'konfigurasi_belum_ditentukan' => 'Penggunaan komponen tugas belum ditentukan dosen',
        'uts_belum_tersedia' => 'Nilai UTS belum tersedia',
        'uas_belum_tersedia' => 'Nilai UAS belum tersedia',
        'tugas_belum_lengkap' => 'Nilai tugas belum lengkap',
    ];

    /**
     * @return array{
     *     status: StatusNilaiAkhir,
     *     nilai_akhir: ?string,
     *     nilai_huruf: ?NilaiHuruf,
     *     bobot_mutu: ?float,
     *     alasan: array<int, string>
     * }
     */
    public function hitung(
        mixed $uts,
        mixed $uas,
        mixed $tugas,
        ?bool $gunakanTugas,
    ): array {
        $utsCents = $this->keCents($uts, 'Nilai UTS');
        $uasCents = $this->keCents($uas, 'Nilai UAS');
        $tugasCents = $this->keCents($tugas, 'Nilai tugas');

        $alasan = [];

        if ($gunakanTugas === null) {
            $alasan[] = 'konfigurasi_belum_ditentukan';
        }

        if ($utsCents === null) {
            $alasan[] = 'uts_belum_tersedia';
        }

        if ($uasCents === null) {
            $alasan[] = 'uas_belum_tersedia';
        }

        if ($gunakanTugas === true && $tugasCents === null) {
            $alasan[] = 'tugas_belum_lengkap';
        }

        if ($alasan !== [] || $utsCents === null || $uasCents === null) {
            return [
                'status' => StatusNilaiAkhir::BelumLengkap,
                'nilai_akhir' => null,
                'nilai_huruf' => null,
                'bobot_mutu' => null,
                'alasan' => $alasan,
            ];
        }

        if ($gunakanTugas === true) {
            if ($tugasCents === null) {
                throw new InvalidArgumentException('Nilai tugas wajib tersedia ketika komponen tugas digunakan.');
            }

            $pembilang = ($utsCents * self::BOBOT_UTS)
                + ($uasCents * self::BOBOT_UAS)
                + ($tugasCents * self::BOBOT_TUGAS);
            $akhirCents = (int) round($pembilang / 100, 0, PHP_ROUND_HALF_UP);
        } else {
            // Tugas tidak digunakan: bobot UTS/UAS dinormalisasi secara proporsional.
            $pembilang = ($utsCents * self::BOBOT_UTS) + ($uasCents * self::BOBOT_UAS);
            $akhirCents = (int) round($pembilang / (self::BOBOT_UTS + self::BOBOT_UAS), 0, PHP_ROUND_HALF_UP);
        }

        $akhir = number_format($akhirCents / 100, 2, '.', '');
        $huruf = NilaiHuruf::dariAngka((float) $akhir);

        return [
            'status' => StatusNilaiAkhir::Lengkap,
            'nilai_akhir' => $akhir,
            'nilai_huruf' => $huruf,
            'bobot_mutu' => $huruf->bobotMutu(),
            'alasan' => [],
        ];
    }

    /**
     * Rata-rata aritmetika seluruh pengumpulan yang benar-benar sudah dinilai.
     * Nilai 0 yang sudah dinilai ikut dihitung; nilai yang belum dinilai tidak.
     *
     * @param Collection<int, mixed>|iterable<mixed> $pengumpulan
     */
    public function agregasiNilaiTugas(iterable $pengumpulan): ?string
    {
        $cents = [];

        foreach ($pengumpulan as $item) {
            if ($item->nilai === null || $item->dinilai_pada === null) {
                continue;
            }

            $cents[] = $this->keCents($item->nilai, 'Nilai tugas');
        }

        if ($cents === []) {
            return null;
        }

        $rataCents = (int) round(array_sum($cents) / count($cents), 0, PHP_ROUND_HALF_UP);

        return number_format($rataCents / 100, 2, '.', '');
    }

    public function formatNilai(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        return number_format((float) $nilai, 2, '.', '');
    }

    /**
     * @param array<int, string> $alasan
     */
    public function alasanLabel(array $alasan): string
    {
        if ($alasan === []) {
            return '';
        }

        return implode('; ', array_map(
            fn (string $kode): string => self::ALASAN_LABEL[$kode] ?? $kode,
            $alasan,
        ));
    }

    private function keCents(mixed $nilai, string $nama): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (! is_numeric($nilai)) {
            throw new InvalidArgumentException($nama.' bukan angka yang valid.');
        }

        $angka = (float) $nilai;

        if ($angka < 0 || $angka > 100) {
            throw new InvalidArgumentException($nama.' harus berada pada rentang 0–100.');
        }

        return (int) round($angka * 100, 0, PHP_ROUND_HALF_UP);
    }
}
