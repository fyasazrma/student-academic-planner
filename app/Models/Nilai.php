<?php

namespace App\Models;

use App\Enums\KomponenNilai;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai, identitas peserta, dan penilai sengaja tidak mass-assignable.
 * Tetapkan lewat alur penilaian yang sudah diotorisasi (relasi atau forceFill).
 */
#[Table('nilai')]
#[Fillable([
    'komponen',
    'bobot',
    'keterangan',
])]
class Nilai extends Model
{
    protected function casts(): array
    {
        return [
            'komponen' => KomponenNilai::class,
            'nilai' => 'decimal:2',
            'bobot' => 'decimal:2',
            'dinilai_pada' => 'datetime',
        ];
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }
}
