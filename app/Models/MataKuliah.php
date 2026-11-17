<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('mata_kuliah')]
#[Fillable([
    'kode',
    'nama',
    'sks',
    'deskripsi',
    'program_studi',
    'semester',
    'aktif',
])]
class MataKuliah extends Model
{
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'semester' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function kelasMataKuliah(): HasMany
    {
        return $this->hasMany(KelasMataKuliah::class, 'mata_kuliah_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
