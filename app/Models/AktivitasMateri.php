<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('aktivitas_materi')]
#[Fillable([
    'materi_pembelajaran_id',
    'peserta_mata_kuliah_id',
    'jenis',
])]
class AktivitasMateri extends Model
{
    public function materi(): BelongsTo
    {
        return $this->belongsTo(MateriPembelajaran::class, 'materi_pembelajaran_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }
}
