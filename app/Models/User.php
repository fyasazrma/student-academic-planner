<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Table('users')]
#[Fillable([
    'name',
    'email',
    'password',
    'nim',
    'nidn',
    'program_studi',
    'angkatan',
    'no_telepon',
    'foto_path',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /**
     * Trait Notifiable tetap diperlukan agar notifikasi email bawaan Laravel
     * (termasuk reset password) dapat dikirim melalui channel mail.
     *
     * Jangan memakai channel database atau relasi `notifications()` bawaan;
     * notifikasi akademik memakai relasi `notifikasi()` ke tabel `notifikasi`.
     *
     * @use HasFactory<UserFactory>
     */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function kelasDiajar(): HasMany
    {
        return $this->hasMany(KelasMataKuliah::class, 'dosen_id');
    }

    public function pendaftaranKelas(): HasMany
    {
        return $this->hasMany(PesertaMataKuliah::class, 'mahasiswa_id');
    }

    public function materiDibuat(): HasMany
    {
        return $this->hasMany(MateriPembelajaran::class, 'dibuat_oleh');
    }

    public function tugasDibuat(): HasMany
    {
        return $this->hasMany(Tugas::class, 'dibuat_oleh');
    }

    public function pengumpulanDinilai(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'dinilai_oleh');
    }

    public function agendaDibuat(): HasMany
    {
        return $this->hasMany(AgendaAkademik::class, 'dibuat_oleh');
    }

    public function sesiAbsensiDibuat(): HasMany
    {
        return $this->hasMany(SesiAbsensi::class, 'dibuat_oleh');
    }

    public function absensiDicatat(): HasMany
    {
        return $this->hasMany(Absensi::class, 'dicatat_oleh');
    }

    public function nilaiDinilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'dinilai_oleh');
    }

    public function targetBelajar(): HasMany
    {
        return $this->hasMany(TargetBelajar::class, 'mahasiswa_id');
    }

    /**
     * Notifikasi akademik internal aplikasi.
     *
     * Gunakan model/relasi ini untuk notifikasi dalam aplikasi. Jangan memakai
     * channel `database` bawaan Laravel atau relasi `notifications()`, karena
     * tabel `notifications` tidak dibuat. Channel `mail` (termasuk reset
     * password) tetap aman dan tidak memerlukan tabel tersebut.
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function pengumpulanTugas(): HasManyThrough
    {
        return $this->hasManyThrough(
            PengumpulanTugas::class,
            PesertaMataKuliah::class,
            'mahasiswa_id',
            'peserta_mata_kuliah_id',
        );
    }

    public function progresMateri(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProgresMateri::class,
            PesertaMataKuliah::class,
            'mahasiswa_id',
            'peserta_mata_kuliah_id',
        );
    }

    public function nilaiDiterima(): HasManyThrough
    {
        return $this->hasManyThrough(
            Nilai::class,
            PesertaMataKuliah::class,
            'mahasiswa_id',
            'peserta_mata_kuliah_id',
        );
    }

    public function absensi(): HasManyThrough
    {
        return $this->hasManyThrough(
            Absensi::class,
            PesertaMataKuliah::class,
            'mahasiswa_id',
            'peserta_mata_kuliah_id',
        );
    }

    /**
     * Get the dashboard route name for the user's role.
     */
    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Dosen => 'dosen.dashboard',
            UserRole::Mahasiswa => 'mahasiswa.dashboard',
        };
    }

    public function isMahasiswa(): bool
    {
        return $this->role === UserRole::Mahasiswa;
    }

    public function isDosen(): bool
    {
        return $this->role === UserRole::Dosen;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
