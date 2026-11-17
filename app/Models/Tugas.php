<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tugas')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'judul',
    'deskripsi',
    'lampiran_path',
    'lampiran_nama',
    'lampiran_mime',
    'lampiran_size',
    'dipublikasikan_pada',
    'deadline',
    'nilai_maksimal',
    'izinkan_terlambat',
])]
class Tugas extends Model
{
    protected function casts(): array
    {
        return [
            'lampiran_size' => 'integer',
            'dipublikasikan_pada' => 'datetime',
            'deadline' => 'datetime',
            'nilai_maksimal' => 'decimal:2',
            'izinkan_terlambat' => 'boolean',
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

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'tugas_id');
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

    public function sudahMelewatiDeadline(): bool
    {
        return $this->deadline->isPast();
    }
}
