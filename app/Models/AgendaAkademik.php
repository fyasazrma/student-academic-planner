<?php

namespace App\Models;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('agenda_akademik')]
#[Fillable([
    'kelas_mata_kuliah_id',
    'tahun_akademik_id',
    'judul',
    'deskripsi',
    'jenis',
    'jenis_ujian',
    'tanggal_mulai',
    'tanggal_selesai',
    'waktu_mulai',
    'waktu_selesai',
    'lokasi',
    'dipublikasikan_pada',
])]
class AgendaAkademik extends Model
{
    protected function casts(): array
    {
        return [
            'jenis' => JenisAgenda::class,
            'jenis_ujian' => JenisUjian::class,
            'tahun_akademik_id' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'dipublikasikan_pada' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function kelasMataKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMataKuliah::class, 'kelas_mata_kuliah_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function scopeDipublikasikan(Builder $query): Builder
    {
        return $query->whereNotNull('dipublikasikan_pada')
            ->where('dipublikasikan_pada', '<=', now());
    }

    public function scopeInstitusi(Builder $query): Builder
    {
        return $query->whereNull('kelas_mata_kuliah_id');
    }

    public function sudahDipublikasikan(): bool
    {
        return $this->dipublikasikan_pada !== null
            && $this->dipublikasikan_pada->lte(now());
    }
}
