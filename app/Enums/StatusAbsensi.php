<?php

namespace App\Enums;

enum StatusAbsensi: string
{
    case Hadir = 'hadir';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Alfa = 'alfa';
}
