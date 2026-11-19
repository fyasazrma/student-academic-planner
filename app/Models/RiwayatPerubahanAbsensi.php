<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit koreksi absensi. Identitas absensi, peserta, sesi, dan actor sengaja
 * tidak mass-assignable agar hanya alur koreksi terotorisasi yang menuliskan.
 */
#[Table('riwayat_perubahan_absensi')]
#[Fillable([
    'status_sebelum',
    'status_sesudah',
    'alasan',
])]
class RiwayatPerubahanAbsensi extends Model
{
    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class, 'absensi_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_absensi_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
