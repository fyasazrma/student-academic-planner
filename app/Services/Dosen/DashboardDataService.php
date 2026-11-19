<?php

namespace App\Services\Dosen;

use App\Enums\JenisAgenda;
use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Ringkasan dashboard dosen yang seluruhnya berasal dari data database.
 *
 * Lingkup informasi sengaja dibatasi pada kelas yang diampu, status
 * pendaftaran, periode aktif, jumlah peserta aktif, jadwal terdekat, agenda,
 * dan notifikasi milik dosen yang login.
 */
class DashboardDataService
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

    public static function labelHari(int $hari): string
    {
        return self::HARI[$hari] ?? 'Tidak diketahui';
    }

    /** @var array<string, array{label: string, token: string}> */
    private const JENIS = [
        'tugas' => ['label' => 'Tugas', 'token' => 'agenda-tugas'],
        'deadline' => ['label' => 'Deadline', 'token' => 'agenda-terlambat'],
        'ujian' => ['label' => 'Ujian', 'token' => 'agenda-uts'],
        'kuliah' => ['label' => 'Jadwal kuliah', 'token' => 'agenda-kuliah'],
        'akademik' => ['label' => 'Agenda akademik', 'token' => 'agenda-akademik'],
        'pengumuman' => ['label' => 'Pengumuman', 'token' => 'agenda-pengumuman'],
    ];

    private const DEADLINE_DEKAT_JAM = 168;

    /**
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

        $classes = $this->classes((int) $user->getAuthIdentifier());
        $classIds = $classes->pluck('id')->all();
        $schedules = $this->schedules($classIds);
        $tasks = $this->tasks($classIds);
        $agendas = $this->agendas($classIds);
        $exams = $agendas
            ->filter(fn (AgendaAkademik $agenda): bool => $agenda->jenis === JenisAgenda::Ujian
                && $agenda->tanggal_mulai->greaterThanOrEqualTo($today))
            ->values();

        $notifications = Notifikasi::untukUser((int) $user->getAuthIdentifier())
            ->latest()
            ->limit(5)
            ->get();
        $unreadCount = Notifikasi::untukUser((int) $user->getAuthIdentifier())
            ->belumDibaca()
            ->count();

        $stats = [
            'kelas_diampu' => $classes->count(),
            'pendaftaran_buka' => $classes
                ->filter(fn (KelasMataKuliah $class): bool => $class->status_pendaftaran === StatusPendaftaranKelas::Buka)
                ->count(),
            'peserta_aktif' => (int) $classes->sum('jumlah_peserta_aktif'),
            'jadwal_kuliah' => $schedules->count(),
        ];

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'unreadCount' => $unreadCount,
            'notifications' => $notifications,
            'stats' => $stats,
            'ringkasanKelas' => $this->ringkasanKelas($classes, $schedules),
            'deadlines' => $this->deadlines($tasks),
            'nextSchedules' => $this->nextSchedules($schedules, $exams),
            'calendar' => $this->calendar($view, $date, $tasks, $agendas, $schedules, $filters),
            'timeline' => $this->timeline($tasks, $exams),
            'filterOptions' => self::JENIS,
            'activeFilters' => $filters,
            'currentView' => $view,
            'today' => $today,
        ];
    }

    /**
     * Semua kelas milik dosen, termasuk kelas historis yang mata kuliah atau
     * tahun akademiknya sudah dinonaktifkan.
     *
     * @return \Illuminate\Support\Collection<int, KelasMataKuliah>
     */
    private function classes(int $dosenId): Collection
    {
        return KelasMataKuliah::query()
            ->where('dosen_id', $dosenId)
            ->with(['mataKuliah', 'tahunAkademik'])
            ->withCount([
                'peserta as jumlah_peserta_aktif' => fn ($query) => $query
                    ->where('status', StatusPeserta::Aktif->value),
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $classIds
     * @return \Illuminate\Support\Collection<int, JadwalKuliah>
     */
    private function schedules(array $classIds): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        return JadwalKuliah::query()
            ->whereIn('kelas_mata_kuliah_id', $classIds)
            ->with('kelasMataKuliah.mataKuliah')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * @param  array<int, int>  $classIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function tasks(array $classIds): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        return Tugas::dipublikasikan()
            ->whereIn('kelas_mata_kuliah_id', $classIds)
            ->with('kelasMataKuliah.mataKuliah')
            ->orderBy('deadline')
            ->get()
            ->map(function (Tugas $task): array {
                $published = $task->dipublikasikan_pada;
                $deadline = $task->deadline;
                $hoursToDeadline = now()->diffInHours($deadline, false);
                $status = $deadline->isPast()
                    ? 'terlambat'
                    : ($hoursToDeadline <= self::DEADLINE_DEKAT_JAM ? 'mendekati_deadline' : 'dibuat');

                return [
                    'id' => $task->id,
                    'judul' => $task->judul,
                    'deskripsi' => $task->deskripsi,
                    'mata_kuliah' => $task->kelasMataKuliah?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'kode_kelas' => $task->kelasMataKuliah?->kode_kelas,
                    'dipublikasikan_at' => $published?->toDateTimeString(),
                    'dipublikasikan_label' => $published?->translatedFormat('d M Y H:i') ?? 'Tidak tersedia',
                    'deadline_at' => $deadline->toDateTimeString(),
                    'deadline_label' => $deadline->translatedFormat('d M Y H:i'),
                    'deadline_tanggal' => $deadline->toDateString(),
                    'status' => $status,
                    'status_label' => match ($status) {
                        'terlambat' => 'Terlewat',
                        'mendekati_deadline' => 'Mendekati deadline',
                        default => 'Berjalan',
                    },
                ];
            })
            ->values();
    }

    /**
     * Agenda institusi dan agenda kelas yang diajar dosen.
     *
     * @param  array<int, int>  $classIds
     * @return \Illuminate\Support\Collection<int, AgendaAkademik>
     */
    private function agendas(array $classIds): Collection
    {
        $query = AgendaAkademik::dipublikasikan()
            ->with('kelasMataKuliah.mataKuliah');

        $query->where(function ($query) use ($classIds): void {
            $query->whereNull('kelas_mata_kuliah_id');

            if ($classIds !== []) {
                $query->orWhereIn('kelas_mata_kuliah_id', $classIds);
            }
        });

        return $query->orderBy('tanggal_mulai')->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, KelasMataKuliah>  $classes
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @return array<int, array<string, mixed>>
     */
    private function ringkasanKelas(Collection $classes, Collection $schedules): array
    {
        return $classes->map(function (KelasMataKuliah $class) use ($schedules): array {
            $next = $schedules
                ->where('kelas_mata_kuliah_id', $class->id)
                ->map(fn (JadwalKuliah $schedule): array => $this->nextSchedule($schedule))
                ->sortBy('urutan')
                ->first();

            $periodeAktif = $class->tahunAkademik?->aktif === true;

            return [
                'id' => $class->id,
                'kode_mata_kuliah' => $class->mataKuliah?->kode ?? '-',
                'nama_mata_kuliah' => $class->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'kode_kelas' => $class->kode_kelas,
                'nama_kelas' => $class->nama_kelas,
                'tahun_akademik' => $class->tahunAkademik?->label() ?? 'Tidak tersedia',
                'periode_label' => $periodeAktif ? 'Aktif' : 'Historis',
                'status_pendaftaran' => $class->status_pendaftaran?->value ?? StatusPendaftaranKelas::Tutup->value,
                'status_label' => $class->status_pendaftaran === StatusPendaftaranKelas::Buka ? 'Buka' : 'Tutup',
                'jumlah_peserta_aktif' => (int) ($class->jumlah_peserta_aktif ?? 0),
                'jadwal_terdekat_label' => $next['ringkas_label'] ?? 'Belum ada jadwal',
            ];
        })->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function deadlines(Collection $tasks): array
    {
        return $tasks
            ->filter(fn (array $task): bool => Carbon::parse($task['deadline_at'])->isFuture())
            ->sortBy('deadline_at')
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $exams
     * @return array<int, array<string, mixed>>
     */
    private function nextSchedules(Collection $schedules, Collection $exams): array
    {
        $items = $schedules->map(fn (JadwalKuliah $schedule): array => $this->nextSchedule($schedule))->all();

        foreach ($exams as $exam) {
            $items[] = [
                'jenis' => 'ujian',
                'label' => 'Ujian',
                'judul' => $exam->judul,
                'tanggal' => $exam->tanggal_mulai->toDateString(),
                'tanggal_label' => $exam->tanggal_mulai->translatedFormat('D, d M'),
                'waktu_label' => $exam->waktu_mulai ? $this->timeLabel($exam->waktu_mulai) : null,
                'lokasi' => $exam->lokasi,
                'urutan' => $exam->tanggal_mulai->format('Ymd').($exam->waktu_mulai ? $this->timeLabel($exam->waktu_mulai, 'Hi') : '0000'),
            ];
        }

        usort($items, fn (array $left, array $right): int => strcmp($left['urutan'], $right['urutan']));

        return array_slice($items, 0, 3);
    }

    /**
     * @return array{jenis: string, label: string, judul: string, tanggal: string, tanggal_label: string, waktu_label: string, lokasi: ?string, urutan: string, ringkas_label: string}
     */
    private function nextSchedule(JadwalKuliah $schedule): array
    {
        $today = today();
        $diff = ($schedule->hari - $today->dayOfWeek + 7) % 7;
        $date = $today->copy()->addDays($diff);
        $start = Carbon::parse($schedule->jam_mulai);
        $candidate = $date->copy()->setTime((int) $start->format('H'), (int) $start->format('i'));

        if ($candidate->lessThanOrEqualTo(now())) {
            $candidate->addWeek();
            $date = $candidate->copy()->startOfDay();
        }

        $waktu = $this->timeLabel($schedule->jam_mulai).' – '.$this->timeLabel($schedule->jam_selesai);
        $nama = $schedule->kelasMataKuliah?->mataKuliah?->nama ?? 'Jadwal kuliah';

        return [
            'jenis' => 'kuliah',
            'label' => ucfirst($schedule->jenis?->value ?? 'kuliah'),
            'judul' => $nama,
            'tanggal' => $date->toDateString(),
            'tanggal_label' => $date->translatedFormat('D, d M'),
            'waktu_label' => $waktu,
            'lokasi' => $schedule->ruang,
            'urutan' => $date->format('Ymd').$this->timeLabel($schedule->jam_mulai, 'Hi'),
            'ringkas_label' => self::HARI[$schedule->hari].', '.$waktu,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @param  array<int, string>  $filters
     * @return array<string, mixed>
     */
    private function calendar(
        string $view,
        Carbon $date,
        Collection $tasks,
        Collection $agendas,
        Collection $schedules,
        array $filters,
    ): array {
        $start = $view === 'week'
            ? $date->copy()->startOfWeek(Carbon::MONDAY)
            : $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $view === 'week'
            ? $date->copy()->endOfWeek(Carbon::MONDAY)
            : $date->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $days = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $days[] = $cursor->copy();
        }

        $events = $this->calendarEvents($tasks, $agendas, $schedules, $start, $end);
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

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $tasks,
        Collection $agendas,
        Collection $schedules,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $events = collect();

        foreach ($tasks as $task) {
            foreach ([
                ['suffix' => 'publikasi', 'jenis' => 'tugas', 'tanggal' => $task['dipublikasikan_at'], 'label' => 'Tugas dipublikasikan'],
                ['suffix' => 'deadline', 'jenis' => 'deadline', 'tanggal' => $task['deadline_at'], 'label' => 'Deadline tugas'],
            ] as $point) {
                $tanggal = Carbon::parse($point['tanggal']);

                if ($tanggal->lessThan($start) || $tanggal->greaterThan($end)) {
                    continue;
                }

                $events->push([
                    'id' => 'tugas-'.$task['id'].'-'.$point['suffix'],
                    'jenis' => $point['jenis'],
                    'label' => $point['label'],
                    'judul' => $task['judul'],
                    'mata_kuliah' => $task['mata_kuliah'],
                    'tanggal' => $tanggal->toDateString(),
                    'tanggal_label' => $tanggal->translatedFormat('D, d M Y'),
                    'waktu_label' => $tanggal->translatedFormat('H:i'),
                    'awal_label' => $task['dipublikasikan_label'],
                    'akhir_label' => $task['deadline_label'],
                    'awal_label_name' => 'Tanggal dipublikasikan',
                    'akhir_label_name' => 'Deadline',
                    'publikasi_label' => $task['dipublikasikan_label'],
                    'deskripsi' => $task['deskripsi'],
                    'status_label' => $task['status_label'],
                    'urutan' => $tanggal->format('YmdHi'),
                ]);
            }
        }

        foreach ($agendas as $agenda) {
            $agendaStart = $agenda->tanggal_mulai->copy()->startOfDay();
            $agendaEnd = ($agenda->tanggal_selesai ?? $agenda->tanggal_mulai)->copy()->endOfDay();
            $jenis = match ($agenda->jenis) {
                JenisAgenda::Ujian => 'ujian',
                JenisAgenda::Pengumuman => 'pengumuman',
                JenisAgenda::Akademik => 'akademik',
            };

            for ($cursor = $agendaStart->copy(); $cursor->lessThanOrEqualTo($agendaEnd) && $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                if ($cursor->lessThan($start)) {
                    continue;
                }

                $events->push([
                    'id' => 'agenda-'.$agenda->id.'-'.$cursor->toDateString(),
                    'jenis' => $jenis,
                    'label' => self::JENIS[$jenis]['label'],
                    'judul' => $agenda->judul,
                    'mata_kuliah' => $agenda->kelasMataKuliah?->mataKuliah?->nama ?? 'Agenda institusi',
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

        foreach ($schedules as $schedule) {
            $class = $schedule->kelasMataKuliah;

            for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                if ($cursor->dayOfWeek !== $schedule->hari) {
                    continue;
                }

                $events->push([
                    'id' => 'jadwal-'.$schedule->id.'-'.$cursor->toDateString(),
                    'jenis' => 'kuliah',
                    'label' => ucfirst($schedule->jenis?->value ?? 'kuliah'),
                    'judul' => $class?->mataKuliah?->nama ?? 'Jadwal kuliah',
                    'mata_kuliah' => $class?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'tanggal' => $cursor->toDateString(),
                    'tanggal_label' => $cursor->translatedFormat('D, d M Y'),
                    'waktu_label' => $this->timeLabel($schedule->jam_mulai).' – '.$this->timeLabel($schedule->jam_selesai),
                    'awal_label' => self::HARI[$schedule->hari].', '.$this->timeLabel($schedule->jam_mulai),
                    'akhir_label' => $this->timeLabel($schedule->jam_selesai),
                    'awal_label_name' => 'Hari / jam mulai',
                    'akhir_label_name' => 'Jam selesai',
                    'publikasi_label' => 'Tidak tersedia',
                    'lokasi' => $schedule->ruang,
                    'deskripsi' => 'Kelas '.$class?->kode_kelas,
                    'status_label' => null,
                    'urutan' => $cursor->format('Ymd').$this->timeLabel($schedule->jam_mulai, 'Hi'),
                ]);
            }
        }

        return $events->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $exams
     * @return array<string, mixed>
     */
    private function timeline(Collection $tasks, Collection $exams): array
    {
        $dates = collect();

        foreach ($tasks as $task) {
            $dates->push(Carbon::parse($task['dipublikasikan_at'])->copy()->startOfDay());
            $dates->push(Carbon::parse($task['deadline_at'])->copy()->startOfDay());
        }

        foreach ($exams as $exam) {
            $dates->push($exam->tanggal_mulai->copy()->startOfDay());
        }

        $dates->push(today()->copy()->startOfDay());

        $start = $dates->min()->copy()->subDay();
        $end = $dates->max()->copy()->addDay();
        $span = max(1, $start->diffInDays($end));

        $bars = $tasks->map(function (array $task) use ($start, $span): array {
            $published = Carbon::parse($task['dipublikasikan_at'])->startOfDay();
            $deadline = Carbon::parse($task['deadline_at'])->startOfDay();
            $left = max(0, min(100, $start->diffInDays($published) / $span * 100));
            $right = max(0, min(100, $start->diffInDays($deadline) / $span * 100));

            return $task + [
                'bar_left' => round($left, 2),
                'bar_width' => max(3, round($right - $left, 2)),
                'deadline_marker' => round($right, 2),
            ];
        })->values()->all();

        $markers = $exams->map(function (AgendaAkademik $exam) use ($start, $span): array {
            return [
                'id' => 'ujian-'.$exam->id,
                'jenis' => 'ujian',
                'judul' => $exam->judul,
                'mata_kuliah' => $exam->kelasMataKuliah?->mataKuliah?->nama ?? 'Agenda institusi',
                'tanggal_label' => $exam->tanggal_mulai->translatedFormat('d M Y'),
                'posisi' => round(max(0, min(100, $start->diffInDays($exam->tanggal_mulai) / $span * 100)), 2),
                'waktu_label' => $exam->waktu_mulai ? $this->timeLabel($exam->waktu_mulai) : null,
                'lokasi' => $exam->lokasi,
            ];
        })->values()->all();

        $ticks = [];
        for ($index = 0; $index <= 4; $index++) {
            $tickDate = $start->copy()->addDays((int) round($span * $index / 4));
            $ticks[] = [
                'posisi' => $index * 25,
                'label' => $tickDate->translatedFormat('d M'),
            ];
        }

        return [
            'bars' => $bars,
            'markers' => $markers,
            'ticks' => $ticks,
            'start_label' => $start->translatedFormat('d M Y'),
            'end_label' => $end->translatedFormat('d M Y'),
        ];
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
