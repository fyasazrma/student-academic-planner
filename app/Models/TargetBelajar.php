<?php

namespace App\Models;

use App\Enums\StatusTargetBelajar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('target_belajar')]
#[Fillable([
    'judul',
    'deskripsi',
    'mata_kuliah_id',
    'satuan',
    'target_nilai',
    'capaian',
    'tanggal_target',
    'tanggal_mulai',
    'deadline',
    'status',
    'progres',
    'selesai_pada',
])]
class TargetBelajar extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusTargetBelajar::class,
            'tanggal_target' => 'date',
            'tanggal_mulai' => 'date',
            'deadline' => 'date',
            'target_nilai' => 'float',
            'capaian' => 'float',
            'progres' => 'integer',
            'selesai_pada' => 'datetime',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', StatusTargetBelajar::BelumDimulai->value);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            StatusTargetBelajar::Selesai->value,
            StatusTargetBelajar::Dibatalkan->value,
        ]);
    }

    public function sudahSelesai(): bool
    {
        return $this->status === StatusTargetBelajar::Selesai;
    }
}
