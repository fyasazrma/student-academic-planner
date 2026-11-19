<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit nilai. Identitas nilai, peserta, dan actor sengaja tidak mass-assignable
 * agar hanya alur penilaian terotorisasi yang dapat menuliskannya.
 */
#[Table('riwayat_perubahan_nilai')]
#[Fillable([
    'komponen',
    'nilai_sebelum',
    'nilai_sesudah',
    'alasan',
])]
class RiwayatPerubahanNilai extends Model
{
    protected function casts(): array
    {
        return [
            'nilai_sebelum' => 'decimal:2',
            'nilai_sesudah' => 'decimal:2',
        ];
    }

    public function nilai(): BelongsTo
    {
        return $this->belongsTo(Nilai::class, 'nilai_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
