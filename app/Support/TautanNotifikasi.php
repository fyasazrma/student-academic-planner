<?php

namespace App\Support;

use App\Enums\UserRole;

/**
 * Validasi tautan internal notifikasi berdasarkan role penerima.
 *
 * Satu implementasi dipakai saat penulisan (NotifikasiService) maupun saat
 * pembacaan (Notifikasi::tautanInternal()) sehingga Dosen dan Admin tidak
 * pernah diarahkan ke area mahasiswa, sebaliknya.
 */
final class TautanNotifikasi
{
    /** @var array<string, string> */
    private const PREFIX = [
        'mahasiswa' => '/mahasiswa',
        'dosen' => '/dosen',
        'admin' => '/admin',
    ];

    public static function dariRole(UserRole|string|null $role): string
    {
        $nilai = $role instanceof UserRole ? $role->value : (string) $role;

        return self::PREFIX[$nilai] ?? 'mahasiswa';
    }

    public static function valid(?string $tautan, UserRole|string|null $role): ?string
    {
        if (! is_string($tautan) || $tautan === '') {
            return null;
        }

        $prefix = self::dariRole($role);

        $berbahaya = static function (string $nilai): bool {
            return preg_match('/[\x00-\x20\x7F\\\\]/', $nilai) === 1
                || str_starts_with($nilai, '//')
                || str_contains($nilai, '@');
        };

        if ($berbahaya($tautan) || ! str_starts_with($tautan, '/')) {
            return null;
        }

        $probe = $tautan;
        for ($index = 0; $index < 3; $index++) {
            $decoded = rawurldecode($probe);

            if ($decoded === $probe) {
                break;
            }

            if ($berbahaya($decoded) || ! str_starts_with($decoded, '/')) {
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

        if ($path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
