<?php

namespace App\Console\Commands;

use App\Services\Pengumuman\PengumumanService;
use Illuminate\Console\Command;

class TerbitkanPengumumanTerjadwal extends Command
{
    protected $signature = 'pengumuman:terbitkan-terjadwal';

    protected $description = 'Menerbitkan pengumuman terjadwal yang sudah mencapai waktu publikasi dan mengirim notifikasinya';

    public function handle(PengumumanService $pengumuman): int
    {
        $hasil = $pengumuman->terbitkanYangJatuhTempo();

        $this->line(sprintf(
            'Pengumuman diproses: %d, notifikasi dibuat: %d, gagal: %d.',
            $hasil['diproses'],
            $hasil['notifikasi'],
            $hasil['gagal'],
        ));

        return self::SUCCESS;
    }
}
