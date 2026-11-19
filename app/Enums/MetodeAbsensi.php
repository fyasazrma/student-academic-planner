<?php

namespace App\Enums;

enum MetodeAbsensi: string
{
    case Mandiri = 'mandiri';
    case Otomatis = 'otomatis';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Mandiri => 'Mandiri',
            self::Otomatis => 'Otomatis',
            self::Manual => 'Manual',
        };
    }
}
