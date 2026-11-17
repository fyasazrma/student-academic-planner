<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kelas_mata_kuliah')]
#[Fillable([
    'mata_kuliah_id',
    'tahun_akademik_id',
    'kode_kelas',
    'nama_kelas',
    'kapasitas',
])]
class KelasMataKuliah extends Model
{
    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
        ];
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(PesertaMataKuliah::class, 'kelas_mata_kuliah_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKuliah::class, 'kelas_mata_kuliah_id');
    }

    public function materi(): HasMany
    {
        return $this->hasMany(MateriPembelajaran::class, 'kelas_mata_kuliah_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'kelas_mata_kuliah_id');
    }

    public function sesiAbsensi(): HasMany
    {
        return $this->hasMany(SesiAbsensi::class, 'kelas_mata_kuliah_id');
    }

    public function agenda(): HasMany
    {
        return $this->hasMany(AgendaAkademik::class, 'kelas_mata_kuliah_id');
    }
}
