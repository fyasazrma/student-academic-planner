<?php

namespace App\Models;

use App\Enums\StatusAbsensi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identitas peserta dan pencatat absensi sengaja tidak mass-assignable.
 * Tetapkan lewat alur absensi yang sudah diotorisasi (relasi atau forceFill).
 */
#[Table('absensi')]
#[Fillable([
    'sesi_absensi_id',
    'status',
    'keterangan',
    'metode',
    'dicatat_pada',
])]
class Absensi extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusAbsensi::class,
            'dicatat_pada' => 'datetime',
        ];
    }

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_absensi_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
