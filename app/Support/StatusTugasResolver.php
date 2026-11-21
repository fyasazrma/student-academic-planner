<?php

namespace App\Support;

use App\Enums\StatusTugasMahasiswa;
use App\Models\PengumpulanTugas;
use Carbon\Carbon;

/**
 * Satu sumber perhitungan status tugas mahasiswa.
 *
 * Status turunan tidak disimpan: penilaian lebih diutamakan, lalu keberadaan
 * pengumpulan, baru keterlambatan terhadap deadline. Perbandingan waktu selalu
 * memakai timezone aplikasi (Asia/Jakarta) melalui Carbon.
 */
final class StatusTugasResolver
{
    public static function tentukan(
        ?PengumpulanTugas $pengumpulan,
        Carbon $deadline,
        ?Carbon $waktu = null,
    ): StatusTugasMahasiswa {
        $waktu = $waktu ?? now();

        if ($pengumpulan !== null && $pengumpulan->sudahDinilai()) {
            return StatusTugasMahasiswa::SudahDinilai;
        }

        if ($pengumpulan !== null) {
            return StatusTugasMahasiswa::Dikumpulkan;
        }

        return $deadline->lessThan($waktu)
            ? StatusTugasMahasiswa::Terlambat
            : StatusTugasMahasiswa::BelumDikumpulkan;
    }

    public static function sudahLewatDeadline(Carbon $deadline, ?Carbon $waktu = null): bool
    {
        return $deadline->lessThan($waktu ?? now());
    }
}
