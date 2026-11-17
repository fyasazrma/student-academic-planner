<?php

namespace App\Enums;

enum StatusPeserta: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
    case Mengulang = 'mengulang';
}
