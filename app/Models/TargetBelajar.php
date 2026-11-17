<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('target_belajar')]
#[Fillable([
    'judul',
    'deskripsi',
    'tanggal_target',
    'status',
    'progres',
    'selesai_pada',
])]
class TargetBelajar extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal_target' => 'date',
            'progres' => 'integer',
            'selesai_pada' => 'datetime',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', 'belum_selesai');
    }

    public function sudahSelesai(): bool
    {
        return $this->status === 'selesai';
    }
}
