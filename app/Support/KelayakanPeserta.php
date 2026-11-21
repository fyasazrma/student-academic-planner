<?php

namespace App\Support;

use App\Enums\StatusPeserta;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu aturan kelayakan keanggotaan kelas untuk seluruh aplikasi.
 *
 * Peserta Aktif dan Mengulang sama-sama berhak memakai fitur kelas.
 * Nonaktif hanya memiliki riwayat, bukan hak melakukan aktivitas baru.
 *
 * Catatan: aturan ini sengaja tidak dipakai untuk penghitungan kapasitas kelas,
 * karena kapasitas adalah aturan pendaftaran Batch A/B dan tidak diubah Batch E.1.
 */
final class KelayakanPeserta
{
    /** @var array<int, StatusPeserta> */
    public const STATUSES_BERHAK = [
        StatusPeserta::Aktif,
        StatusPeserta::Mengulang,
    ];

    /**
     * @return array<int, string>
     */
    public static function nilaiStatusBerhak(): array
    {
        return array_map(
            static fn (StatusPeserta $status): string => $status->value,
            self::STATUSES_BERHAK,
        );
    }

    public static function berhak(StatusPeserta|string|null $status): bool
    {
        if ($status instanceof StatusPeserta) {
            return in_array($status, self::STATUSES_BERHAK, true);
        }

        return in_array($status, self::nilaiStatusBerhak(), true);
    }

    public static function scopeBerhak(Builder $query, string $column = 'status'): Builder
    {
        return $query->whereIn($column, self::nilaiStatusBerhak());
    }
}
