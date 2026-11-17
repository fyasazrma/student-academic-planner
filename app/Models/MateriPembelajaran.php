<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('materi_pembelajaran')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'judul',
    'deskripsi',
    'file_path',
    'file_name',
    'file_mime',
    'file_size',
    'pertemuan_ke',
    'dipublikasikan_pada',
])]
class MateriPembelajaran extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'pertemuan_ke' => 'integer',
            'dipublikasikan_pada' => 'datetime',
        ];
    }

    public function kelasMataKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMataKuliah::class, 'kelas_mata_kuliah_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function progres(): HasMany
    {
        return $this->hasMany(ProgresMateri::class, 'materi_pembelajaran_id');
    }

    public function scopeDipublikasikan(Builder $query): Builder
    {
        return $query->whereNotNull('dipublikasikan_pada')
            ->where('dipublikasikan_pada', '<=', now());
    }

    public function sudahDipublikasikan(): bool
    {
        return $this->dipublikasikan_pada !== null
            && $this->dipublikasikan_pada->lte(now());
    }
}
