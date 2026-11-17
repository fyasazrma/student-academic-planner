<?php

namespace App\Models;

use App\Enums\StatusSesiAbsensi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('sesi_absensi')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'pertemuan_ke',
    'tanggal',
    'topik',
    'waktu_mulai',
    'waktu_selesai',
    'status',
])]
class SesiAbsensi extends Model
{
    protected function casts(): array
    {
        return [
            'pertemuan_ke' => 'integer',
            'tanggal' => 'date',
            'status' => StatusSesiAbsensi::class,
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

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'sesi_absensi_id');
    }

    public function scopeDibuka(Builder $query): Builder
    {
        return $query->where('status', StatusSesiAbsensi::Dibuka->value);
    }

    public function sedangDibuka(): bool
    {
        return $this->status === StatusSesiAbsensi::Dibuka;
    }
}
