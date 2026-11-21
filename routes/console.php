<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Penutupan sesi tidak boleh bergantung pada pengunjung halaman. Scheduler
 * memanggil command setiap menit; service bersifat idempotent dan aman bila
 * dua proses bertemu pada sesi yang sama.
 */
Schedule::command('absensi:tutup-otomatis')
    ->timezone('Asia/Jakarta')
    ->everyMinute()
    ->withoutOverlapping();

/*
 * Pengumuman terjadwal menjadi terlihat otomatis saat waktunya tiba karena query
 * mahasiswa memfilter dipublikasikan_pada <= now(). Command ini memastikan
 * notifikasi ikut dikirim tepat saat publikasi dan aman dijalankan berulang.
 */
Schedule::command('pengumuman:terbitkan-terjadwal')
    ->timezone('Asia/Jakarta')
    ->everyMinute()
    ->withoutOverlapping();
