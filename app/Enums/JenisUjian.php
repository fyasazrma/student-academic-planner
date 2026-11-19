<?php

namespace App\Enums;

enum JenisUjian: string
{
    case Uts = 'uts';
    case Uas = 'uas';

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
            self::Uts => 'UTS',
            self::Uas => 'UAS',
        };
    }
}
