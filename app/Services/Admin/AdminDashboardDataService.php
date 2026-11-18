<?php

namespace App\Services\Admin;

use App\Enums\JenisAgenda;
use App\Enums\UserRole;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminDashboardDataService
{
    /** @var array<int, string> */
    private const HARI = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /** @var array<string, array{label: string, token: string}> */
    private const JENIS = [
        'akademik' => ['label' => 'Agenda akademik', 'token' => 'agenda-akademik'],
        'ujian' => ['label' => 'Ujian', 'token' => 'agenda-uts'],
        'pengumuman' => ['label' => 'Pengumuman', 'token' => 'agenda-pengumuman'],
        'kuliah' => ['label' => 'Jadwal kuliah', 'token' => 'agenda-kuliah'],
    ];

    /**
     * Build all data needed by the admin dashboard from real database rows.
     *
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $view = $request->query('view') === 'week' ? 'week' : 'month';
        $date = $this->parseDate($request->query('date'));
        $filters = $this->parseFilters($request->query('jenis'));
        $today = today();
        $tahunAktif = TahunAkademik::aktif()->first();

        $statistik = [
            'mahasiswa_aktif' => User::query()
                ->where('role', UserRole::Mahasiswa->value)
                ->where('is_active', true)
                ->count(),
            'dosen_aktif' => User::query()
                ->where('role', UserRole::Dosen->value)
                ->where('is_active', true)
                ->count(),
            'mata_kuliah_aktif' => MataKuliah::aktif()->count(),
            'kelas_periode_aktif' => $tahunAktif === null
                ? 0
                : KelasMataKuliah::query()->where('tahun_akademik_id', $tahunAktif->id)->count(),
        ];

        $agendaMendatang = AgendaAkademik::institusi()
            ->dipublikasikan()
            ->where('tanggal_mulai', '>=', $today->toDateString())
            ->orderBy('tanggal_mulai')
            ->orderBy('waktu_mulai')
            ->limit(5)
            ->get()
            ->map(fn (AgendaAkademik $agenda): array => [
                'id' => $agenda->id,
                'judul' => $agenda->judul,
                'jenis_label' => self::JENIS[$this->jenisKey($agenda->jenis)]['label'],
                'tanggal_label' => $agenda->tanggal_mulai->translatedFormat('D, d M Y'),
                'waktu_label' => $agenda->waktu_mulai
                    ? $this->timeLabel($agenda->waktu_mulai).($agenda->waktu_selesai ? ' – '.$this->timeLabel($agenda->waktu_selesai) : '')
                    : null,
                'lokasi' => $agenda->lokasi,
                'konteks' => 'Agenda institusi',
            ])
            ->all();

        [$start, $end] = $this->periode($view, $date);

        $agendaKalender = AgendaAkademik::institusi()
            ->dipublikasikan()
            ->where('tanggal_mulai', '<=', $end->toDateString())
            ->orderBy('tanggal_mulai')
            ->get();

        $jadwalKalender = $tahunAktif === null
            ? collect()
            : JadwalKuliah::query()
                ->whereHas('kelasMataKuliah', fn ($query) => $query->where('tahun_akademik_id', $tahunAktif->id))
                ->with('kelasMataKuliah.mataKuliah')
                ->get();

        $events = $this->calendarEvents($agendaKalender, $jadwalKalender, $start, $end);

        return [
            'user' => $user,
            'unreadCount' => 0,
            'statistik' => $statistik,
            'tahunAkademikAktif' => $tahunAktif,
            'agendaMendatang' => $agendaMendatang,
            'perubahanTerbaru' => $this->perubahanTerbaru(),
            'calendar' => $this->calendar($events, $view, $date, $start, $end, $filters),
            'currentView' => $view,
            'activeFilters' => $filters,
            'filterOptions' => self::JENIS,
            'today' => $today,
        ];
    }

    /**
     * Recent master-data updates. This is not an audit log: only updated_at is available.
     *
     * @return array<int, array<string, mixed>>
     */
    private function perubahanTerbaru(): array
    {
        $mataKuliah = MataKuliah::query()
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (MataKuliah $item): array => [
                'id' => $item->id,
                'jenis' => 'Mata kuliah',
                'judul' => $item->nama,
                'keterangan' => $item->kode,
                'waktu' => $item->updated_at,
                'waktu_label' => $item->updated_at?->translatedFormat('d M Y H:i') ?? '-',
            ]);

        $tahunAkademik = TahunAkademik::query()
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (TahunAkademik $item): array => [
                'id' => $item->id,
                'jenis' => 'Tahun akademik',
                'judul' => $item->label(),
                'keterangan' => $item->aktif ? 'Aktif' : 'Nonaktif',
                'waktu' => $item->updated_at,
                'waktu_label' => $item->updated_at?->translatedFormat('d M Y H:i') ?? '-',
            ]);

        return $mataKuliah
            ->concat($tahunAkademik)
            ->sortByDesc(fn (array $item): int => $item['waktu']?->timestamp ?? 0)
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periode(string $view, Carbon $date): array
    {
        $start = $view === 'week'
            ? $date->copy()->startOfWeek(Carbon::MONDAY)
            : $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $view === 'week'
            ? $date->copy()->endOfWeek(Carbon::MONDAY)
            : $date->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        return [$start, $end];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $agendas,
        Collection $schedules,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $events = collect();

        foreach ($agendas as $agenda) {
            $agendaStart = $agenda->tanggal_mulai->copy()->startOfDay();
            $agendaEnd = ($agenda->tanggal_selesai ?? $agenda->tanggal_mulai)->copy()->endOfDay();
            $jenis = $this->jenisKey($agenda->jenis);

            for ($cursor = $agendaStart->copy(); $cursor->lessThanOrEqualTo($agendaEnd) && $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                if ($cursor->lessThan($start)) {
                    continue;
                }

                $events->push([
                    'id' => 'agenda-'.$agenda->id.'-'.$cursor->toDateString(),
                    'jenis' => $jenis,
                    'label' => self::JENIS[$jenis]['label'],
                    'judul' => $agenda->judul,
                    'mata_kuliah' => 'Agenda institusi',
                    'tanggal' => $cursor->toDateString(),
                    'tanggal_label' => $cursor->translatedFormat('D, d M Y'),
                    'waktu_label' => $agenda->waktu_mulai
                        ? $this->timeLabel($agenda->waktu_mulai).($agenda->waktu_selesai ? ' – '.$this->timeLabel($agenda->waktu_selesai) : '')
                        : null,
                    'awal_label' => $agenda->tanggal_mulai->translatedFormat('d M Y'),
                    'akhir_label' => ($agenda->tanggal_selesai ?? $agenda->tanggal_mulai)->translatedFormat('d M Y'),
                    'awal_label_name' => 'Tanggal mulai',
                    'akhir_label_name' => 'Tanggal selesai',
                    'publikasi_label' => $agenda->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Tidak tersedia',
                    'lokasi' => $agenda->lokasi,
                    'deskripsi' => $agenda->deskripsi,
                    'status_label' => null,
                    'urutan' => $cursor->format('Ymd').($agenda->waktu_mulai ? $this->timeLabel($agenda->waktu_mulai, 'Hi') : '0000'),
                ]);
            }
        }

        foreach ($schedules as $jadwal) {
            $kelas = $jadwal->kelasMataKuliah;

            for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                if ($cursor->dayOfWeek !== $jadwal->hari) {
                    continue;
                }

                $events->push([
                    'id' => 'jadwal-'.$jadwal->id.'-'.$cursor->toDateString(),
                    'jenis' => 'kuliah',
                    'label' => ucfirst($jadwal->jenis?->value ?? 'kuliah'),
                    'judul' => $kelas?->mataKuliah?->nama ?? 'Jadwal kuliah',
                    'mata_kuliah' => $kelas?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'tanggal' => $cursor->toDateString(),
                    'tanggal_label' => $cursor->translatedFormat('D, d M Y'),
                    'waktu_label' => $this->timeLabel($jadwal->jam_mulai).' – '.$this->timeLabel($jadwal->jam_selesai),
                    'awal_label' => self::HARI[$jadwal->hari].', '.$this->timeLabel($jadwal->jam_mulai),
                    'akhir_label' => $this->timeLabel($jadwal->jam_selesai),
                    'awal_label_name' => 'Hari / jam mulai',
                    'akhir_label_name' => 'Jam selesai',
                    'publikasi_label' => 'Tidak tersedia',
                    'lokasi' => $jadwal->ruang,
                    'deskripsi' => 'Kelas '.$kelas?->kode_kelas,
                    'status_label' => null,
                    'urutan' => $cursor->format('Ymd').$this->timeLabel($jadwal->jam_mulai, 'Hi'),
                ]);
            }
        }

        return $events->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $events
     * @return array<string, mixed>
     */
    private function calendar(
        Collection $events,
        string $view,
        Carbon $date,
        Carbon $start,
        Carbon $end,
        array $filters,
    ): array {
        $days = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $days[] = $cursor->copy();
        }

        $filtered = $filters === []
            ? $events
            : $events->filter(fn (array $event): bool => in_array($event['jenis'], $filters, true))->values();

        return [
            'days' => $days,
            'focusDate' => $date->format('Y-m'),
            'view' => $view,
            'events' => $filtered,
            'eventsByDate' => $filtered->groupBy('tanggal'),
            'agendaList' => $filtered->sortBy(fn (array $event): string => $event['tanggal'].'|'.$event['urutan'])->values(),
            'label' => $view === 'week'
                ? $start->translatedFormat('d M').' – '.$end->translatedFormat('d M Y')
                : $date->translatedFormat('F Y'),
            'previous' => $view === 'week'
                ? $date->copy()->subWeek()->toDateString()
                : $date->copy()->subMonth()->toDateString(),
            'next' => $view === 'week'
                ? $date->copy()->addWeek()->toDateString()
                : $date->copy()->addMonth()->toDateString(),
        ];
    }

    private function jenisKey(JenisAgenda $jenis): string
    {
        return match ($jenis) {
            JenisAgenda::Akademik => 'akademik',
            JenisAgenda::Ujian => 'ujian',
            JenisAgenda::Pengumuman => 'pengumuman',
        };
    }

    private function timeLabel(mixed $value, string $format = 'H:i'): string
    {
        if (! is_string($value) || $value === '') {
            return '-';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $value)->format($format);
        } catch (\Throwable) {
            return Carbon::parse($value)->format($format);
        }
    }

    private function parseDate(mixed $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $parsed = Carbon::createFromFormat('!Y-m-d', $value);

            if ($parsed instanceof Carbon && $parsed->toDateString() === $value) {
                return $parsed;
            }
        }

        return today();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function parseFilters(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($item): string => is_string($item) ? $item : '', $value),
            fn (string $item): bool => array_key_exists($item, self::JENIS),
        ));
    }
}
