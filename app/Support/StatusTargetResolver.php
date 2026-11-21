<?php

namespace App\Support;

use App\Enums\StatusTargetBelajar;
use App\Models\TargetBelajar;
use Carbon\Carbon;

/**
 * Satu sumber status efektif target belajar.
 *
 * Prioritas evaluasi sesuai keputusan Batch E.1:
 * Dibatulkan → Selesai → Belum Dimulai → Terlambat → Berlangsung.
 */
final class StatusTargetResolver
{
    public static function hitung(TargetBelajar $target, ?Carbon $waktu = null): StatusTargetBelajar
    {
        $waktu = $waktu ?? now();
        $hariIni = $waktu->copy()->startOfDay();

        if ($target->status === StatusTargetBelajar::Dibatalkan) {
            return StatusTargetBelajar::Dibatalkan;
        }

        if ($target->status === StatusTargetBelajar::Selesai || self::capaianMencapaiTarget($target)) {
            return StatusTargetBelajar::Selesai;
        }

        if ($target->tanggal_mulai !== null && $target->tanggal_mulai->greaterThan($hariIni)) {
            return StatusTargetBelajar::BelumDimulai;
        }

        if ($target->deadline !== null && $target->deadline->isBefore($hariIni)) {
            return StatusTargetBelajar::Terlambat;
        }

        return StatusTargetBelajar::Berlangsung;
    }

    public static function capaianMencapaiTarget(TargetBelajar $target): bool
    {
        return $target->target_nilai !== null
            && $target->target_nilai > 0
            && (float) $target->capaian >= $target->target_nilai;
    }
}
