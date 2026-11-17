<?php

namespace App\Models;

use App\Enums\Semester;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tahun_akademik')]
#[Fillable([
    'tahun_mulai',
    'tahun_selesai',
    'semester',
    'tanggal_mulai',
    'tanggal_selesai',
    'aktif',
])]
class TahunAkademik extends Model
{
    protected function casts(): array
    {
        return [
            'tahun_mulai' => 'integer',
            'tahun_selesai' => 'integer',
            'semester' => Semester::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function kelasMataKuliah(): HasMany
    {
        return $this->hasMany(KelasMataKuliah::class, 'tahun_akademik_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function label(): string
    {
        return sprintf(
            '%d/%d %s',
            $this->tahun_mulai,
            $this->tahun_selesai,
            ucfirst($this->semester->value),
        );
    }
}
