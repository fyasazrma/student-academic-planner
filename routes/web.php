<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\KalenderController;
use App\Http\Controllers\Mahasiswa\MataKuliahController;
use App\Http\Controllers\Mahasiswa\NotifikasiController;
use App\Http\Controllers\Mahasiswa\TugasController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'active', 'role:admin'])->group(function (): void {
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
});

Route::middleware(['auth', 'active', 'role:dosen'])->group(function (): void {
    Route::get('/dosen/dashboard', [DashboardController::class, 'dosen'])->name('dosen.dashboard');
});

Route::middleware(['auth', 'active', 'role:mahasiswa'])->group(function (): void {
    Route::get('/mahasiswa/dashboard', [MahasiswaDashboardController::class, 'index'])->name('mahasiswa.dashboard');
    Route::get('/mahasiswa/kalender', [KalenderController::class, 'index'])->name('mahasiswa.kalender');
    Route::get('/mahasiswa/tugas', [TugasController::class, 'index'])->name('mahasiswa.tugas');
    Route::get('/mahasiswa/mata-kuliah', [MataKuliahController::class, 'index'])->name('mahasiswa.mata-kuliah');
    Route::get('/mahasiswa/mata-kuliah/cari', [MataKuliahController::class, 'cari'])->name('mahasiswa.mata-kuliah.cari');
    Route::post('/mahasiswa/mata-kuliah/gabung', [MataKuliahController::class, 'gabung'])
        ->middleware('throttle:gabung-kelas')
        ->name('mahasiswa.mata-kuliah.gabung');
    Route::get('/mahasiswa/notifikasi', [NotifikasiController::class, 'index'])->name('mahasiswa.notifikasi');
    Route::post('/mahasiswa/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'tandaiDibaca'])
        ->whereNumber('notifikasi')
        ->name('mahasiswa.notifikasi.baca');
});
