<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MataKuliahController as AdminMataKuliahController;
use App\Http\Controllers\Admin\TahunAkademikController as AdminTahunAkademikController;
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
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/mata-kuliah', [AdminMataKuliahController::class, 'index'])->name('admin.mata-kuliah.index');
    Route::get('/admin/mata-kuliah/create', [AdminMataKuliahController::class, 'create'])->name('admin.mata-kuliah.create');
    Route::post('/admin/mata-kuliah', [AdminMataKuliahController::class, 'store'])->name('admin.mata-kuliah.store');
    Route::get('/admin/mata-kuliah/{mata_kuliah}/edit', [AdminMataKuliahController::class, 'edit'])
        ->whereNumber('mata_kuliah')
        ->name('admin.mata-kuliah.edit');
    Route::match(['put', 'patch'], '/admin/mata-kuliah/{mata_kuliah}', [AdminMataKuliahController::class, 'update'])
        ->whereNumber('mata_kuliah')
        ->name('admin.mata-kuliah.update');
    Route::post('/admin/mata-kuliah/{mata_kuliah}/status', [AdminMataKuliahController::class, 'ubahStatus'])
        ->whereNumber('mata_kuliah')
        ->name('admin.mata-kuliah.status');
    Route::delete('/admin/mata-kuliah/{mata_kuliah}', [AdminMataKuliahController::class, 'destroy'])
        ->whereNumber('mata_kuliah')
        ->name('admin.mata-kuliah.destroy');

    Route::get('/admin/tahun-akademik', [AdminTahunAkademikController::class, 'index'])->name('admin.tahun-akademik.index');
    Route::get('/admin/tahun-akademik/create', [AdminTahunAkademikController::class, 'create'])->name('admin.tahun-akademik.create');
    Route::post('/admin/tahun-akademik', [AdminTahunAkademikController::class, 'store'])->name('admin.tahun-akademik.store');
    Route::get('/admin/tahun-akademik/{tahun_akademik}/edit', [AdminTahunAkademikController::class, 'edit'])
        ->whereNumber('tahun_akademik')
        ->name('admin.tahun-akademik.edit');
    Route::match(['put', 'patch'], '/admin/tahun-akademik/{tahun_akademik}', [AdminTahunAkademikController::class, 'update'])
        ->whereNumber('tahun_akademik')
        ->name('admin.tahun-akademik.update');
    Route::post('/admin/tahun-akademik/{tahun_akademik}/status', [AdminTahunAkademikController::class, 'ubahStatus'])
        ->whereNumber('tahun_akademik')
        ->name('admin.tahun-akademik.status');
    Route::delete('/admin/tahun-akademik/{tahun_akademik}', [AdminTahunAkademikController::class, 'destroy'])
        ->whereNumber('tahun_akademik')
        ->name('admin.tahun-akademik.destroy');
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
