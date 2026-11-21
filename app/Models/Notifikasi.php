<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\TautanNotifikasi;

/**
 * Notifikasi aplikasi memakai tabel `notifikasi`, bukan tabel `notifications`
 * bawaan Laravel. Pemilik notifikasi ditetapkan lewat relasi atau forceFill.
 */
#[Table('notifikasi')]
#[Fillable([
    'tipe',
    'judul',
    'pesan',
    'data',
    'tautan',
    'dibaca_pada',
])]
class Notifikasi extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'dibaca_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_pada');
    }

    public function scopeUntukUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function sudahDibaca(): bool
    {
        return $this->dibaca_pada !== null;
    }

    /**
     * Return a safe internal path for the recipient's role, or null when the
     * stored link is external, obfuscated, or belongs to another role area.
     *
     * Validation is shared with NotifikasiService so writing and reading apply
     * exactly the same rules. The recipient role is stored with new records;
     * older records fall back to the owner's role and then to mahasiswa.
     */
    public function tautanInternal(): ?string
    {
        $peran = is_array($this->data) && isset($this->data['peran'])
            ? (string) $this->data['peran']
            : ($this->user?->role?->value ?? 'mahasiswa');

        return TautanNotifikasi::valid($this->tautan, $peran);
    }
}
