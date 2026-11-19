<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MataKuliahController as AdminMataKuliahController;
use App\Http\Controllers\Admin\TahunAkademikController as AdminTahunAkademikController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\JadwalKuliahController as DosenJadwalKuliahController;
use App\Http\Controllers\Dosen\MataKuliahController as DosenMataKuliahController;
use App\Http\Controllers\Dosen\MateriPembelajaranController as DosenMateriPembelajaranController;
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
    Route::get('/dosen/dashboard', [DosenDashboardController::class, 'index'])->name('dosen.dashboard');

    Route::get('/dosen/mata-kuliah', [DosenMataKuliahController::class, 'index'])->name('dosen.mata-kuliah.index');
    Route::get('/dosen/mata-kuliah/create', [DosenMataKuliahController::class, 'create'])->name('dosen.mata-kuliah.create');
    Route::post('/dosen/mata-kuliah', [DosenMataKuliahController::class, 'store'])->name('dosen.mata-kuliah.store');
    Route::get('/dosen/mata-kuliah/{kelas}', [DosenMataKuliahController::class, 'show'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.show');
    Route::get('/dosen/mata-kuliah/{kelas}/edit', [DosenMataKuliahController::class, 'edit'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.edit');
    Route::match(['put', 'patch'], '/dosen/mata-kuliah/{kelas}', [DosenMataKuliahController::class, 'update'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.update');
    Route::post('/dosen/mata-kuliah/{kelas}/status', [DosenMataKuliahController::class, 'ubahStatus'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.status');
    Route::post('/dosen/mata-kuliah/{kelas}/kunci-gabung', [DosenMataKuliahController::class, 'kelolaKodeGabung'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.kunci');

    Route::get('/dosen/mata-kuliah/{kelas}/jadwal', [DosenJadwalKuliahController::class, 'index'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.jadwal.index');
    Route::get('/dosen/mata-kuliah/{kelas}/jadwal/create', [DosenJadwalKuliahController::class, 'create'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.jadwal.create');
    Route::post('/dosen/mata-kuliah/{kelas}/jadwal', [DosenJadwalKuliahController::class, 'store'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.jadwal.store');
    Route::get('/dosen/mata-kuliah/{kelas}/jadwal/{jadwal}/edit', [DosenJadwalKuliahController::class, 'edit'])
        ->whereNumber(['kelas', 'jadwal'])
        ->name('dosen.mata-kuliah.jadwal.edit');
    Route::match(['put', 'patch'], '/dosen/mata-kuliah/{kelas}/jadwal/{jadwal}', [DosenJadwalKuliahController::class, 'update'])
        ->whereNumber(['kelas', 'jadwal'])
        ->name('dosen.mata-kuliah.jadwal.update');
    Route::delete('/dosen/mata-kuliah/{kelas}/jadwal/{jadwal}', [DosenJadwalKuliahController::class, 'destroy'])
        ->whereNumber(['kelas', 'jadwal'])
        ->name('dosen.mata-kuliah.jadwal.destroy');

    Route::get('/dosen/mata-kuliah/{kelas}/materi', [DosenMateriPembelajaranController::class, 'index'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.materi.index');
    Route::get('/dosen/mata-kuliah/{kelas}/materi/create', [DosenMateriPembelajaranController::class, 'create'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.materi.create');
    Route::post('/dosen/mata-kuliah/{kelas}/materi', [DosenMateriPembelajaranController::class, 'store'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.materi.store');
    Route::get('/dosen/mata-kuliah/{kelas}/materi/{materi}/edit', [DosenMateriPembelajaranController::class, 'edit'])
        ->whereNumber(['kelas', 'materi'])
        ->name('dosen.mata-kuliah.materi.edit');
    Route::match(['put', 'patch'], '/dosen/mata-kuliah/{kelas}/materi/{materi}', [DosenMateriPembelajaranController::class, 'update'])
        ->whereNumber(['kelas', 'materi'])
        ->name('dosen.mata-kuliah.materi.update');
    Route::delete('/dosen/mata-kuliah/{kelas}/materi/{materi}', [DosenMateriPembelajaranController::class, 'destroy'])
        ->whereNumber(['kelas', 'materi'])
        ->name('dosen.mata-kuliah.materi.destroy');
    Route::get('/dosen/mata-kuliah/{kelas}/materi/{materi}/download', [DosenMateriPembelajaranController::class, 'download'])
        ->whereNumber(['kelas', 'materi'])
        ->name('dosen.mata-kuliah.materi.download');
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
