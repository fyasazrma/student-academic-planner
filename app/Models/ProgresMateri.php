<?php

namespace App\Models;

use App\Enums\StatusProgres;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('progres_materi')]
#[Fillable([
    'materi_pembelajaran_id',
    'status',
    'progres',
    'selesai_pada',
])]
class ProgresMateri extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusProgres::class,
            'progres' => 'integer',
            'selesai_pada' => 'datetime',
        ];
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(MateriPembelajaran::class, 'materi_pembelajaran_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }
}
