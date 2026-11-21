<?php

namespace App\Enums;

/**
 * Status target belajar mahasiswa.
 *
 * Nilai `belum_selesai` dipertahankan karena merupakan default schema lama;
 * tampilannya dibaca sebagai "Belum dimulai". Status efektif dihitung ulang
 * oleh service (bukan dipercaya dari browser).
 */
enum StatusTargetBelajar: string
{
    case BelumDimulai = 'belum_selesai';
    case Berlangsung = 'berlangsung';
    case Selesai = 'selesai';
    case Terlambat = 'terlambat';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::BelumDimulai => 'Belum dimulai',
            self::Berlangsung => 'Berlangsung',
            self::Selesai => 'Selesai',
            self::Terlambat => 'Terlambat',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BelumDimulai => 'netral',
            self::Berlangsung => 'kuliah',
            self::Selesai => 'selesai',
            self::Terlambat => 'deadline',
            self::Dibatalkan => 'ditutup',
        };
    }

    /**
     * Status yang boleh dikirim sebagai filter daftar target.
     *
     * @return array<int, string>
     */
    public static function nilaiFilter(): array
    {
        return array_column(self::cases(), 'value');
    }
}
