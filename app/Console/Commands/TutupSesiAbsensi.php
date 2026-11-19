<?php

namespace App\Console\Commands;

use App\Services\Absensi\AbsensiService;
use Illuminate\Console\Command;

class TutupSesiAbsensi extends Command
{
    protected $signature = 'absensi:tutup-otomatis';

    protected $description = 'Menutup sesi absensi yang waktu selesainya telah tercapai dan mencatat alfa otomatis';

    public function handle(AbsensiService $absensi): int
    {
        $hasil = $absensi->tutupYangJatuhTempo();

        $this->line(sprintf(
            'Sesi ditutup: %d, alfa otomatis: %d, gagal: %d.',
            $hasil['ditutup'],
            $hasil['alfa'],
            $hasil['gagal'],
        ));

        return self::SUCCESS;
    }
}
