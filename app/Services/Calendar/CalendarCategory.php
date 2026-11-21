<?php

namespace App\Services\Calendar;

/**
 * Definisi kategori kalender yang dipakai bersama oleh filter, legenda, dan event.
 */
final class CalendarCategory
{
    public const TUGAS = 'tugas';
    public const DEADLINE = 'deadline';
    public const UJIAN = 'ujian';
    public const UTS = 'uts';
    public const UAS = 'uas';
    public const KULIAH = 'kuliah';
    public const AKADEMIK = 'akademik';
    public const PENGUMUMAN = 'pengumuman';
    public const MATERI = 'materi';

    /** @var array<int, string> */
    public const MAHASISWA = [
        self::TUGAS,
        self::DEADLINE,
        self::UJIAN,
        self::UTS,
        self::UAS,
        self::KULIAH,
        self::AKADEMIK,
        self::PENGUMUMAN,
        self::MATERI,
    ];

    /** @var array<int, string> */
    public const DOSEN = [
        self::TUGAS,
        self::DEADLINE,
        self::UJIAN,
        self::UTS,
        self::UAS,
        self::KULIAH,
        self::AKADEMIK,
        self::PENGUMUMAN,
        self::MATERI,
    ];

    /** @var array<int, string> */
    public const ADMIN = [
        self::AKADEMIK,
        self::UJIAN,
        self::UTS,
        self::UAS,
        self::PENGUMUMAN,
        self::KULIAH,
        self::MATERI,
    ];

    /** @var array<string, array{label: string, token: string, shape: string}> */
    private const DEFINITIONS = [
        self::TUGAS => ['label' => 'Tugas', 'token' => 'agenda-tugas', 'shape' => 'rentang'],
        self::DEADLINE => ['label' => 'Deadline', 'token' => 'agenda-terlambat', 'shape' => 'titik'],
        self::UJIAN => ['label' => 'Ujian', 'token' => 'agenda-ujian', 'shape' => 'titik'],
        self::UTS => ['label' => 'UTS', 'token' => 'agenda-uts', 'shape' => 'titik'],
        self::UAS => ['label' => 'UAS', 'token' => 'agenda-uas', 'shape' => 'titik'],
        self::KULIAH => ['label' => 'Jadwal kuliah', 'token' => 'agenda-kuliah', 'shape' => 'berulang'],
        self::AKADEMIK => ['label' => 'Agenda akademik', 'token' => 'agenda-akademik', 'shape' => 'rentang'],
        self::PENGUMUMAN => ['label' => 'Pengumuman', 'token' => 'agenda-pengumuman', 'shape' => 'titik'],
        self::MATERI => ['label' => 'Materi pembelajaran', 'token' => 'agenda-materi', 'shape' => 'titik'],
    ];

    /**
     * @param  array<int, string>|null  $keys
     * @return array<string, array{label: string, token: string, shape: string}>
     */
    public static function options(?array $keys = null): array
    {
        $keys ??= array_keys(self::DEFINITIONS);

        $options = [];
        foreach ($keys as $key) {
            if (isset(self::DEFINITIONS[$key])) {
                $options[$key] = self::DEFINITIONS[$key];
            }
        }

        return $options;
    }

    public static function label(string $key): string
    {
        return self::DEFINITIONS[$key]['label'] ?? 'Agenda';
    }

    /**
     * Normalisasi filter dari query string. Nilai di luar whitelist diabaikan.
     *
     * @param  mixed  $value
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    public static function parseFilters(mixed $value, array $allowed): array
    {
        if (! is_array($value)) {
            return [];
        }

        $allowed = array_flip($allowed);

        return array_values(array_filter(
            array_map(fn (mixed $item): string => is_string($item) ? $item : '', $value),
            fn (string $item): bool => isset($allowed[$item]),
        ));
    }
}
