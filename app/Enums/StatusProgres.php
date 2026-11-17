<?php

namespace App\Enums;

enum StatusProgres: string
{
    case BelumDimulai = 'belum_dimulai';
    case SedangDipelajari = 'sedang_dipelajari';
    case Selesai = 'selesai';
}
