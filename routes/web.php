<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JadwalKuliahController as AdminJadwalKuliahController;
use App\Http\Controllers\Admin\JadwalUjianController as AdminJadwalUjianController;
use App\Http\Controllers\Admin\MataKuliahController as AdminMataKuliahController;
use App\Http\Controllers\Admin\TahunAkademikController as AdminTahunAkademikController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\JadwalKuliahController as DosenJadwalKuliahController;
use App\Http\Controllers\Dosen\MataKuliahController as DosenMataKuliahController;
use App\Http\Controllers\Dosen\MateriPembelajaranController as DosenMateriPembelajaranController;
use App\Http\Controllers\Dosen\NilaiController as DosenNilaiController;
use App\Http\Controllers\Dosen\PengumpulanTugasController as DosenPengumpulanTugasController;
use App\Http\Controllers\Dosen\TugasController as DosenTugasController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\KalenderController;
use App\Http\Controllers\Mahasiswa\MataKuliahController;
use App\Http\Controllers\Mahasiswa\MateriPembelajaranController as MahasiswaMateriPembelajaranController;
use App\Http\Controllers\Mahasiswa\NilaiController as MahasiswaNilaiController;
use App\Http\Controllers\Mahasiswa\NotifikasiController;
use App\Http\Controllers\Mahasiswa\PengumpulanTugasController as MahasiswaPengumpulanTugasController;
use App\Http\Controllers\Mahasiswa\TugasController;
use App\Http\Controllers\ProfilController;
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

    Route::get('/admin/jadwal-kuliah', [AdminJadwalKuliahController::class, 'index'])->name('admin.jadwal-kuliah.index');
    Route::get('/admin/jadwal-kuliah/create', [AdminJadwalKuliahController::class, 'create'])->name('admin.jadwal-kuliah.create');
    Route::post('/admin/jadwal-kuliah', [AdminJadwalKuliahController::class, 'store'])->name('admin.jadwal-kuliah.store');
    Route::get('/admin/jadwal-kuliah/{jadwal}/edit', [AdminJadwalKuliahController::class, 'edit'])
        ->whereNumber('jadwal')
        ->name('admin.jadwal-kuliah.edit');
    Route::match(['put', 'patch'], '/admin/jadwal-kuliah/{jadwal}', [AdminJadwalKuliahController::class, 'update'])
        ->whereNumber('jadwal')
        ->name('admin.jadwal-kuliah.update');
    Route::delete('/admin/jadwal-kuliah/{jadwal}', [AdminJadwalKuliahController::class, 'destroy'])
        ->whereNumber('jadwal')
        ->name('admin.jadwal-kuliah.destroy');

    Route::get('/admin/jadwal-ujian', [AdminJadwalUjianController::class, 'index'])->name('admin.jadwal-ujian.index');
    Route::get('/admin/jadwal-ujian/create', [AdminJadwalUjianController::class, 'create'])->name('admin.jadwal-ujian.create');
    Route::post('/admin/jadwal-ujian', [AdminJadwalUjianController::class, 'store'])->name('admin.jadwal-ujian.store');
    Route::get('/admin/jadwal-ujian/{ujian}/edit', [AdminJadwalUjianController::class, 'edit'])
        ->whereNumber('ujian')
        ->name('admin.jadwal-ujian.edit');
    Route::match(['put', 'patch'], '/admin/jadwal-ujian/{ujian}', [AdminJadwalUjianController::class, 'update'])
        ->whereNumber('ujian')
        ->name('admin.jadwal-ujian.update');
    Route::delete('/admin/jadwal-ujian/{ujian}', [AdminJadwalUjianController::class, 'destroy'])
        ->whereNumber('ujian')
        ->name('admin.jadwal-ujian.destroy');

    foreach (['pengguna' => 'admin.pengguna', 'mahasiswa' => 'admin.mahasiswa', 'dosen' => 'admin.dosen'] as $segmen => $prefiks) {
        Route::get('/admin/'.$segmen, [AdminUserController::class, 'index'])->name($prefiks.'.index');
        Route::get('/admin/'.$segmen.'/create', [AdminUserController::class, 'create'])->name($prefiks.'.create');
        Route::post('/admin/'.$segmen, [AdminUserController::class, 'store'])->name($prefiks.'.store');
        Route::get('/admin/'.$segmen.'/{user}', [AdminUserController::class, 'show'])
            ->whereNumber('user')
            ->name($prefiks.'.show');
        Route::get('/admin/'.$segmen.'/{user}/edit', [AdminUserController::class, 'edit'])
            ->whereNumber('user')
            ->name($prefiks.'.edit');
        Route::match(['put', 'patch'], '/admin/'.$segmen.'/{user}', [AdminUserController::class, 'update'])
            ->whereNumber('user')
            ->name($prefiks.'.update');
        Route::post('/admin/'.$segmen.'/{user}/status', [AdminUserController::class, 'ubahStatus'])
            ->whereNumber('user')
            ->name($prefiks.'.status');
    }
});

Route::middleware(['auth', 'active', 'role:dosen'])->group(function (): void {
    Route::get('/dosen/dashboard', [DosenDashboardController::class, 'index'])->name('dosen.dashboard');

    Route::get('/dosen/profil', [ProfilController::class, 'show'])->name('dosen.profil');
    Route::get('/dosen/profil/edit', [ProfilController::class, 'edit'])->name('dosen.profil.edit');
    Route::match(['put', 'patch'], '/dosen/profil', [ProfilController::class, 'update'])->name('dosen.profil.update');
    Route::post('/dosen/profil/password', [ProfilController::class, 'ubahPassword'])->name('dosen.profil.password');

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

    Route::get('/dosen/mata-kuliah/{kelas}/tugas', [DosenTugasController::class, 'index'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.tugas.index');
    Route::get('/dosen/mata-kuliah/{kelas}/tugas/create', [DosenTugasController::class, 'create'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.tugas.create');
    Route::post('/dosen/mata-kuliah/{kelas}/tugas', [DosenTugasController::class, 'store'])
        ->whereNumber('kelas')
        ->name('dosen.mata-kuliah.tugas.store');
    Route::get('/dosen/mata-kuliah/{kelas}/tugas/{tugas}/edit', [DosenTugasController::class, 'edit'])
        ->whereNumber(['kelas', 'tugas'])
        ->name('dosen.mata-kuliah.tugas.edit');
    Route::match(['put', 'patch'], '/dosen/mata-kuliah/{kelas}/tugas/{tugas}', [DosenTugasController::class, 'update'])
        ->whereNumber(['kelas', 'tugas'])
        ->name('dosen.mata-kuliah.tugas.update');
    Route::delete('/dosen/mata-kuliah/{kelas}/tugas/{tugas}', [DosenTugasController::class, 'destroy'])
        ->whereNumber(['kelas', 'tugas'])
        ->name('dosen.mata-kuliah.tugas.destroy');
    Route::get('/dosen/mata-kuliah/{kelas}/tugas/{tugas}/download', [DosenTugasController::class, 'download'])
        ->whereNumber(['kelas', 'tugas'])
        ->name('dosen.mata-kuliah.tugas.download');

    Route::get('/dosen/mata-kuliah/{kelas}/tugas/{tugas}/pengumpulan', [DosenPengumpulanTugasController::class, 'index'])
        ->whereNumber(['kelas', 'tugas'])
        ->name('dosen.mata-kuliah.tugas.pengumpulan.index');
    Route::get('/dosen/mata-kuliah/{kelas}/tugas/{tugas}/pengumpulan/{pengumpulan}', [DosenPengumpulanTugasController::class, 'show'])
        ->whereNumber(['kelas', 'tugas', 'pengumpulan'])
        ->name('dosen.mata-kuliah.tugas.pengumpulan.show');

    Route::get('/dosen/nilai', [DosenNilaiController::class, 'index'])->name('dosen.nilai.index');
    Route::get('/dosen/nilai/{kelas}', [DosenNilaiController::class, 'kelas'])
        ->whereNumber('kelas')
        ->name('dosen.nilai.kelas');
    Route::post('/dosen/nilai/{kelas}/konfigurasi', [DosenNilaiController::class, 'konfigurasi'])
        ->whereNumber('kelas')
        ->name('dosen.nilai.konfigurasi');
    Route::post('/dosen/nilai/{kelas}/peserta/{peserta}/{komponen}', [DosenNilaiController::class, 'simpan'])
        ->whereNumber(['kelas', 'peserta'])
        ->where('komponen', 'uts|uas')
        ->name('dosen.nilai.simpan');
    Route::get('/dosen/nilai/{kelas}/peserta/{peserta}/riwayat', [DosenNilaiController::class, 'riwayat'])
        ->whereNumber(['kelas', 'peserta'])
        ->name('dosen.nilai.riwayat');
});

Route::middleware(['auth', 'active', 'role:mahasiswa'])->group(function (): void {
    Route::get('/mahasiswa/dashboard', [MahasiswaDashboardController::class, 'index'])->name('mahasiswa.dashboard');

    Route::get('/mahasiswa/profil', [ProfilController::class, 'show'])->name('mahasiswa.profil');
    Route::get('/mahasiswa/profil/edit', [ProfilController::class, 'edit'])->name('mahasiswa.profil.edit');
    Route::match(['put', 'patch'], '/mahasiswa/profil', [ProfilController::class, 'update'])->name('mahasiswa.profil.update');
    Route::post('/mahasiswa/profil/password', [ProfilController::class, 'ubahPassword'])->name('mahasiswa.profil.password');

    Route::get('/mahasiswa/kalender', [KalenderController::class, 'index'])->name('mahasiswa.kalender');
    Route::get('/mahasiswa/tugas', [TugasController::class, 'index'])->name('mahasiswa.tugas');
    Route::get('/mahasiswa/tugas/{tugas}/pengumpulan', [MahasiswaPengumpulanTugasController::class, 'form'])
        ->whereNumber('tugas')
        ->name('mahasiswa.tugas.pengumpulan');
    Route::post('/mahasiswa/tugas/{tugas}/pengumpulan', [MahasiswaPengumpulanTugasController::class, 'simpan'])
        ->whereNumber('tugas')
        ->name('mahasiswa.tugas.pengumpulan.simpan');
    Route::get('/mahasiswa/tugas/{tugas}/pengumpulan/unduh', [MahasiswaPengumpulanTugasController::class, 'unduh'])
        ->whereNumber('tugas')
        ->name('mahasiswa.tugas.pengumpulan.unduh');
    Route::get('/mahasiswa/mata-kuliah', [MataKuliahController::class, 'index'])->name('mahasiswa.mata-kuliah');
    Route::get('/mahasiswa/mata-kuliah/cari', [MataKuliahController::class, 'cari'])->name('mahasiswa.mata-kuliah.cari');
    Route::post('/mahasiswa/mata-kuliah/gabung', [MataKuliahController::class, 'gabung'])
        ->middleware('throttle:gabung-kelas')
        ->name('mahasiswa.mata-kuliah.gabung');
    Route::get('/mahasiswa/materi', [MahasiswaMateriPembelajaranController::class, 'index'])
        ->name('mahasiswa.materi.index');
    Route::get('/mahasiswa/materi/{kelas}/{materi}/unduh', [MahasiswaMateriPembelajaranController::class, 'download'])
        ->whereNumber(['kelas', 'materi'])
        ->name('mahasiswa.materi.unduh');
    Route::get('/mahasiswa/notifikasi', [NotifikasiController::class, 'index'])->name('mahasiswa.notifikasi');
    Route::post('/mahasiswa/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'tandaiDibaca'])
        ->whereNumber('notifikasi')
        ->name('mahasiswa.notifikasi.baca');
    Route::get('/mahasiswa/nilai', [MahasiswaNilaiController::class, 'index'])->name('mahasiswa.nilai');
    Route::get('/mahasiswa/nilai/{mata_kuliah}', [MahasiswaNilaiController::class, 'show'])
        ->whereNumber('mata_kuliah')
        ->name('mahasiswa.nilai.show');
});
