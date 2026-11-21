<?php

namespace App\Enums;

enum JenisNotifikasi: string
{
    case Pengumuman = 'pengumuman';
    case Tugas = 'tugas';
    case Ujian = 'ujian';
    case Nilai = 'nilai';

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
            self::Pengumuman => 'Pengumuman',
            self::Tugas => 'Tugas',
            self::Ujian => 'Ujian',
            self::Nilai => 'Nilai',
        };
    }
}
