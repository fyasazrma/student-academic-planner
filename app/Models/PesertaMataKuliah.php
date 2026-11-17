<?php

namespace App\Models;

use App\Enums\StatusPeserta;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('peserta_mata_kuliah')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'status',
    'tanggal_daftar',
])]
class PesertaMataKuliah extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusPeserta::class,
            'tanggal_daftar' => 'date',
        ];
    }

    public function kelasMataKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMataKuliah::class, 'kelas_mata_kuliah_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'peserta_mata_kuliah_id');
    }

    public function progresMateri(): HasMany
    {
        return $this->hasMany(ProgresMateri::class, 'peserta_mata_kuliah_id');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'peserta_mata_kuliah_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'peserta_mata_kuliah_id');
    }
}
