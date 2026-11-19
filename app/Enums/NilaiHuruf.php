<?php

namespace App\Enums;

use InvalidArgumentException;

/**
 * Skala nilai akhir dan bobot mutu yang dipakai IP/IPK.
 * Batas rentang dan bobot mutu merupakan keputusan akademik yang ditetapkan.
 */
enum NilaiHuruf: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    public static function dariAngka(float $nilai): self
    {
        if ($nilai < 0 || $nilai > 100) {
            throw new InvalidArgumentException('Nilai akhir harus berada pada rentang 0–100.');
        }

        return match (true) {
            $nilai >= 80 => self::A,
            $nilai >= 70 => self::B,
            $nilai >= 60 => self::C,
            $nilai >= 50 => self::D,
            default => self::E,
        };
    }

    public function bobotMutu(): float
    {
        return match ($this) {
            self::A => 4.0,
            self::B => 3.0,
            self::C => 2.0,
            self::D => 1.0,
            self::E => 0.0,
        };
    }

    public function bobotMutuLabel(): string
    {
        return number_format($this->bobotMutu(), 2, '.', '');
    }
}
