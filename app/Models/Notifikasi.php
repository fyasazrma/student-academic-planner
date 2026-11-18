<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Return a safe internal path for this notification, or null when the
     * stored link is external, obfuscated, or otherwise unsafe.
     *
     * Only paths inside the student area are accepted. Raw and percent-encoded
     * control characters, backslashes, protocol-relative URLs, authority
     * components, and dot segments are rejected before the path is whitelisted.
     */
    public function tautanInternal(): ?string
    {
        $tautan = $this->tautan;

        if (! is_string($tautan) || $tautan === '') {
            return null;
        }

        $unsafe = static function (string $value): bool {
            return preg_match('/[\x00-\x20\x7F\\\\]/', $value) === 1
                || str_starts_with($value, '//')
                || str_contains($value, '@');
        };

        if ($unsafe($tautan) || ! str_starts_with($tautan, '/')) {
            return null;
        }

        $probe = $tautan;
        for ($index = 0; $index < 3; $index++) {
            $decoded = rawurldecode($probe);

            if ($decoded === $probe) {
                break;
            }

            if ($unsafe($decoded) || ! str_starts_with($decoded, '/')) {
                return null;
            }

            $probe = $decoded;
        }

        $parts = parse_url($tautan);

        if (! is_array($parts)) {
            return null;
        }

        foreach (['scheme', 'host', 'user', 'pass', 'port'] as $component) {
            if (isset($parts[$component]) && $parts[$component] !== '') {
                return null;
            }
        }

        $path = $parts['path'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return null;
            }
        }

        $decodedParts = parse_url($probe);
        $decodedPath = is_array($decodedParts) ? ($decodedParts['path'] ?? null) : null;

        if ($decodedPath !== $path) {
            return null;
        }

        if ($path !== '/mahasiswa' && ! str_starts_with($path, '/mahasiswa/')) {
            return null;
        }

        return $tautan;
    }
}
