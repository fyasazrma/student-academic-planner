<?php

namespace App\Enums;

enum StatusNilaiAkhir: string
{
    case Lengkap = 'lengkap';
    case BelumLengkap = 'belum_lengkap';

    public function label(): string
    {
        return match ($this) {
            self::Lengkap => 'Lengkap',
            self::BelumLengkap => 'Belum lengkap',
        };
    }
}
