<?php

namespace App\Enums;

enum StatusPublikasi: string
{
    case Draft = 'draft';
    case Terbit = 'terbit';
    case Terjadwal = 'terjadwal';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Terbit => 'Terbit',
            self::Terjadwal => 'Terjadwal',
        };
    }
}
