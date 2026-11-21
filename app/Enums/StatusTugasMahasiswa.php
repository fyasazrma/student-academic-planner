<?php

namespace App\Enums;

/**
 * Status tugas mahasiswa yang dihitung dari pengumpulan, deadline, dan waktu.
 *
 * Nilai string dipertahankan mengikuti terminologi proyek yang sudah ada agar
 * data lama dan filter lama tetap terbaca.
 */
enum StatusTugasMahasiswa: string
{
    case BelumDikumpulkan = 'belum_dikumpulkan';
    case Terlambat = 'terlambat';
    case Dikumpulkan = 'terkumpul';
    case SudahDinilai = 'dinilai';

    public function label(): string
    {
        return match ($this) {
            self::BelumDikumpulkan => 'Belum dikumpulkan',
            self::Terlambat => 'Terlambat',
            self::Dikumpulkan => 'Dikumpulkan',
            self::SudahDinilai => 'Sudah dinilai',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BelumDikumpulkan => 'tugas',
            self::Terlambat => 'deadline',
            self::Dikumpulkan => 'kuliah',
            self::SudahDinilai => 'selesai',
        };
    }

    public static function dariNilai(?string $nilai): ?self
    {
        return $nilai === null ? null : self::tryFrom($nilai);
    }

    public static function labelDariNilai(?string $nilai): string
    {
        return self::dariNilai($nilai)?->label() ?? 'Belum dikumpulkan';
    }

    public static function toneDariNilai(?string $nilai): string
    {
        return self::dariNilai($nilai)?->tone() ?? 'tugas';
    }
}
