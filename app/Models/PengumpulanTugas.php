<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pengumpulan_tugas')]
#[Fillable([
    'tugas_id',
    'file_path',
    'file_name',
    'file_mime',
    'file_size',
    'catatan',
    'dikumpulkan_pada',
])]
class PengumpulanTugas extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'dikumpulkan_pada' => 'datetime',
            'terlambat' => 'boolean',
            'nilai' => 'decimal:2',
            'dinilai_pada' => 'datetime',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function pesertaMataKuliah(): BelongsTo
    {
        return $this->belongsTo(PesertaMataKuliah::class, 'peserta_mata_kuliah_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    /**
     * Status pengumpulan diturunkan dari data yang tersimpan.
     * `terlambat` dan nilai tidak boleh diisi langsung oleh mahasiswa.
     * Penilaian yang sudah selesai diprioritaskan di atas status keterlambatan.
     */
    protected function status(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->sudahDinilai()) {
                return 'dinilai';
            }

            if ($this->terlambat) {
                return 'terlambat';
            }

            return 'terkumpul';
        });
    }

    public function sudahDinilai(): bool
    {
        return $this->nilai !== null && $this->dinilai_pada !== null;
    }
}
