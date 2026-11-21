<?php

namespace App\Services\Notifikasi;

use App\Models\Notifikasi;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Data halaman notifikasi untuk ketiga role.
 *
 * Semua query dibatasi oleh user yang sedang login, sehingga halaman Admin,
 * Dosen, dan Mahasiswa tidak mungkin menampilkan notifikasi pihak lain.
 */
class NotifikasiHalamanService
{
    /** @var array<string, string> */
    private const STATUS_OPTIONS = [
        'semua' => 'Semua',
        'belum_dibaca' => 'Belum dibaca',
        'sudah_dibaca' => 'Sudah dibaca',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $user = $request->user();
        $status = $this->statusFilter($request->query('status'));

        $query = Notifikasi::untukUser((int) $user->getAuthIdentifier());
        if ($status === 'belum_dibaca') {
            $query->belumDibaca();
        } elseif ($status === 'sudah_dibaca') {
            $query->whereNotNull('dibaca_pada');
        }

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'notifications' => $query->latest()->paginate(10)->withQueryString(),
            'unreadCount' => Notifikasi::untukUser((int) $user->getAuthIdentifier())
                ->belumDibaca()
                ->count(),
            'activeStatus' => $status,
            'statusOptions' => self::STATUS_OPTIONS,
            'peran' => $user->role->value,
        ];
    }

    public function temukan(int $userId, int $notifikasiId): Notifikasi
    {
        return Notifikasi::untukUser($userId)
            ->whereKey($notifikasiId)
            ->firstOrFail();
    }

    public function tandaiDibaca(Notifikasi $notifikasi): Notifikasi
    {
        if ($notifikasi->dibaca_pada === null) {
            $notifikasi->dibaca_pada = now();
            $notifikasi->save();
        }

        return $notifikasi;
    }

    private function statusFilter(mixed $value): string
    {
        return is_string($value) && array_key_exists($value, self::STATUS_OPTIONS)
            ? $value
            : 'semua';
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: string, label: string}>
     */
    public static function opsiStatus(): Collection
    {
        return collect(self::STATUS_OPTIONS)
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label]);
    }
}
