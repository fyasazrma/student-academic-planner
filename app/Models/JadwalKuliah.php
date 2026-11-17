<?php

namespace App\Models;

use App\Enums\JenisJadwal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('jadwal_kuliah')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'hari',
    'jam_mulai',
    'jam_selesai',
    'ruang',
    'jenis',
])]
class JadwalKuliah extends Model
{
    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'jenis' => JenisJadwal::class,
        ];
    }

    public function kelasMataKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMataKuliah::class, 'kelas_mata_kuliah_id');
    }
}
