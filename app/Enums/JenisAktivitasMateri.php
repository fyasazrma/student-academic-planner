<?php

namespace App\Enums;

/**
 * Aktivitas akses materi. Membuka/mengunduh TIDAK menjadi bukti penyelesaian.
 */
enum JenisAktivitasMateri: string
{
    case Dibuka = 'dibuka';
    case Diunduh = 'diunduh';

    public function label(): string
    {
        return match ($this) {
            self::Dibuka => 'Dibuka',
            self::Diunduh => 'Diunduh',
        };
    }
}
