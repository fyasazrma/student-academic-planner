<?php

namespace App\Services\Mahasiswa;

use App\Enums\JenisAgenda;
use App\Enums\StatusPeserta;
use App\Enums\StatusProgres;
use App\Models\AgendaAkademik;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\ProgresMateri;
use App\Models\TahunAkademik;
use App\Models\Tugas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardDataService
{
    /** @var array<string, string> */
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
        'tugas' => ['label' => 'Tugas', 'token' => 'agenda-tugas'],
        'deadline' => ['label' => 'Deadline', 'token' => 'agenda-terlambat'],
        'ujian' => ['label' => 'Ujian', 'token' => 'agenda-uts'],
        'kuliah' => ['label' => 'Jadwal kuliah', 'token' => 'agenda-kuliah'],
        'akademik' => ['label' => 'Agenda akademik', 'token' => 'agenda-akademik'],
        'pengumuman' => ['label' => 'Pengumuman', 'token' => 'agenda-pengumuman'],
    ];

    private const DEADLINE_DEKAT_JAM = 168;

    /** @var array<string, string> */
    private const TASK_STATUS_FILTERS = [
        'semua' => 'Semua',
        'belum_dikumpulkan' => 'Belum dikumpulkan',
        'mendekati_deadline' => 'Mendekati deadline',
        'terlambat' => 'Terlambat',
        'terkumpul' => 'Terkumpul',
        'dinilai' => 'Dinilai',
    ];

    /** @var array<string, string> */
    private const NOTIFICATION_STATUS_FILTERS = [
        'semua' => 'Semua',
        'belum_dibaca' => 'Belum dibaca',
        'sudah_dibaca' => 'Sudah dibaca',
    ];

    /**
     * Build all data needed by the student dashboard for the authenticated user.
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

        $tahunAkademikAktif = TahunAkademik::aktif()->first();
        $registrations = $this->registrations($user->id);
        $classIds = $registrations->pluck('kelas_mata_kuliah_id')->all();
        $registrationIds = $registrations->pluck('id')->all();

        $tasks = $this->tasks($classIds, $registrationIds);
        $agendas = $this->agendas($classIds);
        $exams = $agendas->where('jenis', JenisAgenda::Ujian);
        $progress = $this->materialProgress($classIds, $registrationIds);

        $stats = [
            'belum_dikumpulkan' => $tasks->filter(fn (array $task): bool => ! $task['sudah_dikumpulkan'])->count(),
            'mendekati_deadline' => $tasks->filter(fn (array $task): bool => $task['mendekati_deadline'])->count(),
            'ujian_mendatang' => $exams->filter(fn (AgendaAkademik $agenda): bool => $agenda->tanggal_mulai->greaterThanOrEqualTo(today()))->count(),
            'progres_selesai' => $progress['selesai'],
            'progres_total' => $progress['total'],
            'progres_persen' => $progress['persen'],
        ];

        $notifications = Notifikasi::untukUser($user->id)->latest()->limit(5)->get();
        $unreadCount = Notifikasi::untukUser($user->id)->belumDibaca()->count();

        $calendar = $this->calendar($view, $date, $tasks, $agendas, $registrations, $filters);
        $timeline = $this->timeline($tasks, $exams);
        $deadlines = $tasks
            ->filter(fn (array $task): bool => ! $task['sudah_dikumpulkan'] && Carbon::parse($task['deadline_at'])->isFuture())
            ->sortBy('deadline_at')
            ->take(3)
            ->values()
            ->all();
        $nextSchedules = $this->nextSchedules($registrations, $exams);

        return [
            'user' => $user,
            'tahunAkademikAktif' => $tahunAkademikAktif,
            'registrations' => $registrations,
            'tasks' => $tasks,
            'stats' => $stats,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'calendar' => $calendar,
            'timeline' => $timeline,
            'deadlines' => $deadlines,
            'nextSchedules' => $nextSchedules,
            'filterOptions' => self::JENIS,
            'activeFilters' => $filters,
            'currentView' => $view,
            'today' => today(),
        ];
    }

    /**
     * Data for the dedicated academic calendar page.
     *
     * @return array<string, mixed>
     */
    public function buildCalendar(Request $request): array
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $view = $request->query('view') === 'week' ? 'week' : 'month';
        $date = $this->parseDate($request->query('date'));
        $filters = $this->parseFilters($request->query('jenis'));

        $registrations = $this->registrations($user->id);
        $classIds = $registrations->pluck('kelas_mata_kuliah_id')->all();
        $registrationIds = $registrations->pluck('id')->all();

        $tasks = $this->tasks($classIds, $registrationIds);
        $agendas = $this->agendas($classIds);
        $exams = $agendas->where('jenis', JenisAgenda::Ujian);

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'calendar' => $this->calendar($view, $date, $tasks, $agendas, $registrations, $filters),
            'timeline' => $this->timeline($tasks, $exams),
            'filterOptions' => self::JENIS,
            'activeFilters' => $filters,
            'currentView' => $view,
            'today' => today(),
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
        ];
    }

    /**
     * Data for the dedicated task and submission page.
     *
     * @return array<string, mixed>
     */
    public function buildTasks(Request $request): array
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $activeStatus = $this->parseTaskStatus($request->query('status'));

        $registrations = $this->registrations($user->id);
        $classIds = $registrations->pluck('kelas_mata_kuliah_id')->all();
        $registrationIds = $registrations->pluck('id')->all();
        $tasks = $this->tasks($classIds, $registrationIds);

        $statusCounts = ['semua' => $tasks->count()];
        foreach (array_keys(self::TASK_STATUS_FILTERS) as $status) {
            if ($status === 'semua') {
                continue;
            }

            $statusCounts[$status] = $tasks
                ->filter(fn (array $task): bool => $task['status'] === $status)
                ->count();
        }

        $filtered = $activeStatus === 'semua'
            ? $tasks
            : $tasks->filter(fn (array $task): bool => $task['status'] === $activeStatus)->values();

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'tasks' => $filtered,
            'statusCounts' => $statusCounts,
            'activeStatus' => $activeStatus,
            'statusOptions' => self::TASK_STATUS_FILTERS,
            'today' => today(),
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
        ];
    }

    /**
     * Data for the dedicated notification page.
     *
     * @return array<string, mixed>
     */
    public function buildNotifications(Request $request): array
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $activeStatus = $this->notificationStatusFilter($request->query('status'));

        $query = Notifikasi::untukUser($user->id);
        if ($activeStatus === 'belum_dibaca') {
            $query->belumDibaca();
        } elseif ($activeStatus === 'sudah_dibaca') {
            $query->whereNotNull('dibaca_pada');
        }

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'notifications' => $query->latest()->get(),
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
            'activeStatus' => $activeStatus,
            'statusOptions' => self::NOTIFICATION_STATUS_FILTERS,
        ];
    }

    /**
     * Mark one notification as read, restricted to the given student.
     */
    public function tandaiNotifikasiDibaca(int $userId, int $notificationId): Notifikasi
    {
        $notification = Notifikasi::untukUser($userId)
            ->whereKey($notificationId)
            ->firstOrFail();

        if ($notification->dibaca_pada === null) {
            $notification->dibaca_pada = now();
            $notification->save();
        }

        return $notification;
    }

    private function registrations(int $userId): Collection
    {
        return PesertaMataKuliah::query()
            ->where('mahasiswa_id', $userId)
            ->where('status', StatusPeserta::Aktif->value)
            ->with([
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
                'kelasMataKuliah.jadwal',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $classIds
     * @param  array<int, int>  $registrationIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function tasks(array $classIds, array $registrationIds): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        $models = Tugas::dipublikasikan()
            ->whereIn('kelas_mata_kuliah_id', $classIds)
            ->with('kelasMataKuliah.mataKuliah')
            ->orderBy('deadline')
            ->get();

        $submissions = collect();

        if ($registrationIds !== [] && $models->isNotEmpty()) {
            $submissions = \App\Models\PengumpulanTugas::query()
                ->whereIn('tugas_id', $models->pluck('id')->all())
                ->whereIn('peserta_mata_kuliah_id', $registrationIds)
                ->get()
                ->keyBy('tugas_id');
        }

        return $models->map(function (Tugas $task) use ($submissions): array {
            $submission = $submissions->get($task->id);
            $published = $task->dipublikasikan_pada;
            $deadline = $task->deadline;
            $isLate = $deadline->isPast();
            $hoursToDeadline = now()->diffInHours($deadline, false);

            $status = 'belum_dikumpulkan';
            if ($submission !== null) {
                $status = $submission->status;
            } elseif ($isLate) {
                $status = 'terlambat';
            } elseif ($hoursToDeadline <= self::DEADLINE_DEKAT_JAM) {
                $status = 'mendekati_deadline';
            }

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
                'status_label' => $this->taskStatusLabel($status),
                'sudah_dikumpulkan' => $submission !== null,
                'pengumpulan' => $submission === null ? null : [
                    'dikumpulkan_label' => $submission->dikumpulkan_pada?->translatedFormat('d M Y H:i') ?? 'Tidak tersedia',
                    'file_nama' => $submission->file_name,
                    'catatan' => $submission->catatan,
                    'nilai' => $submission->nilai !== null ? (string) $submission->nilai : null,
                    'umpan_balik' => $submission->umpan_balik,
                    'terlambat' => (bool) $submission->terlambat,
                ],
                'mendekati_deadline' => $submission === null && ! $isLate && $hoursToDeadline <= self::DEADLINE_DEKAT_JAM,
                'terlambat' => ! $submission && $isLate,
            ];
        })->values();
    }

    /**
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
     * @param  array<int, int>  $classIds
     * @param  array<int, int>  $registrationIds
     * @return array{total: int, selesai: int, persen: int}
     */
    private function materialProgress(array $classIds, array $registrationIds): array
    {
        if ($classIds === [] || $registrationIds === []) {
            return ['total' => 0, 'selesai' => 0, 'persen' => 0];
        }

        $materialIds = MateriPembelajaran::dipublikasikan()
            ->whereIn('kelas_mata_kuliah_id', $classIds)
            ->pluck('id')
            ->all();

        $progressRows = ProgresMateri::query()
            ->whereIn('peserta_mata_kuliah_id', $registrationIds)
            ->whereIn('materi_pembelajaran_id', $materialIds)
            ->get();

        $total = count($materialIds);
        $selesai = $progressRows
            ->filter(fn (ProgresMateri $row): bool => $row->status === StatusProgres::Selesai)
            ->count();

        return [
            'total' => $total,
            'selesai' => $selesai,
            'persen' => $total === 0 ? 0 : (int) round($selesai / $total * 100),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, PesertaMataKuliah>  $registrations
     * @param  array<int, string>  $filters
     * @return array<string, mixed>
     */
    private function calendar(
        string $view,
        Carbon $date,
        Collection $tasks,
        Collection $agendas,
        Collection $registrations,
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

        $events = $this->calendarEvents($tasks, $agendas, $registrations, $start, $end);
        $filtered = $filters === []
            ? $events
            : $events->filter(fn (array $event): bool => in_array($event['jenis'], $filters, true))->values();
        $eventsByDate = $filtered->groupBy('tanggal');

        return [
            'days' => $days,
            'focusDate' => $date->format('Y-m'),
            'view' => $view,
            'events' => $filtered,
            'eventsByDate' => $eventsByDate,
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
     * @param  \Illuminate\Support\Collection<int, PesertaMataKuliah>  $registrations
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $tasks,
        Collection $agendas,
        Collection $registrations,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $events = collect();

        foreach ($tasks as $task) {
            foreach ([
                ['suffix' => 'publikasi', 'jenis' => 'tugas', 'tanggal' => Carbon::parse($task['dipublikasikan_at']), 'label' => 'Tugas dipublikasikan'],
                ['suffix' => 'deadline', 'jenis' => 'deadline', 'tanggal' => Carbon::parse($task['deadline_at']), 'label' => 'Deadline tugas'],
            ] as $point) {
                if ($point['tanggal']->lessThan($start) || $point['tanggal']->greaterThan($end)) {
                    continue;
                }

                $events->push([
                    'id' => 'tugas-'.$task['id'].'-'.$point['suffix'],
                    'jenis' => $point['jenis'],
                    'label' => $point['label'],
                    'judul' => $task['judul'],
                    'mata_kuliah' => $task['mata_kuliah'],
                    'tanggal' => $point['tanggal']->toDateString(),
                    'tanggal_label' => $point['tanggal']->translatedFormat('D, d M Y'),
                    'waktu_label' => $point['tanggal']->translatedFormat('H:i'),
                    'awal_label' => $task['dipublikasikan_label'],
                    'akhir_label' => $task['deadline_label'],
                    'awal_label_name' => 'Tanggal dipublikasikan',
                    'akhir_label_name' => 'Deadline',
                    'publikasi_label' => $task['dipublikasikan_label'],
                    'deskripsi' => $task['deskripsi'],
                    'status_label' => $task['status_label'],
                    'urutan' => $point['tanggal']->format('YmdHi'),
                ]);
            }
        }

        foreach ($agendas as $agenda) {
            $agendaStart = $agenda->tanggal_mulai->copy()->startOfDay();
            $agendaEnd = ($agenda->tanggal_selesai ?? $agenda->tanggal_mulai)->copy()->endOfDay();

            for ($cursor = $agendaStart->copy(); $cursor->lessThanOrEqualTo($agendaEnd) && $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                if ($cursor->lessThan($start)) {
                    continue;
                }

                $jenis = match ($agenda->jenis) {
                    JenisAgenda::Ujian => 'ujian',
                    JenisAgenda::Pengumuman => 'pengumuman',
                    JenisAgenda::Akademik => 'akademik',
                };

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

        foreach ($registrations as $registration) {
            foreach ($registration->kelasMataKuliah?->jadwal ?? [] as $jadwal) {
                for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
                    if ($cursor->dayOfWeek !== $jadwal->hari) {
                        continue;
                    }

                    $events->push([
                        'id' => 'jadwal-'.$jadwal->id.'-'.$cursor->toDateString(),
                        'jenis' => 'kuliah',
                        'label' => ucfirst($jadwal->jenis?->value ?? 'kuliah'),
                        'judul' => $registration->kelasMataKuliah?->mataKuliah?->nama ?? 'Jadwal kuliah',
                        'mata_kuliah' => $registration->kelasMataKuliah?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                        'tanggal' => $cursor->toDateString(),
                        'tanggal_label' => $cursor->translatedFormat('D, d M Y'),
                        'waktu_label' => $this->timeLabel($jadwal->jam_mulai).' – '.$this->timeLabel($jadwal->jam_selesai),
                        'awal_label' => self::HARI[$jadwal->hari].', '.$this->timeLabel($jadwal->jam_mulai),
                        'akhir_label' => $this->timeLabel($jadwal->jam_selesai),
                        'awal_label_name' => 'Hari / jam mulai',
                        'akhir_label_name' => 'Jam selesai',
                        'publikasi_label' => 'Tidak tersedia',
                        'lokasi' => $jadwal->ruang,
                        'deskripsi' => 'Kelas '.$registration->kelasMataKuliah?->kode_kelas,
                        'status_label' => null,
                        'urutan' => $cursor->format('Ymd').$this->timeLabel($jadwal->jam_mulai, 'Hi'),
                    ]);
                }
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

    /**
     * @param  \Illuminate\Support\Collection<int, PesertaMataKuliah>  $registrations
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $exams
     * @return array<int, array<string, mixed>>
     */
    private function nextSchedules(Collection $registrations, Collection $exams): array
    {
        $items = [];

        foreach ($registrations as $registration) {
            foreach ($registration->kelasMataKuliah?->jadwal ?? [] as $jadwal) {
                for ($offset = 0; $offset <= 14; $offset++) {
                    $date = today()->addDays($offset);

                    if ($date->dayOfWeek !== $jadwal->hari) {
                        continue;
                    }

                    $items[] = [
                        'jenis' => 'kuliah',
                        'label' => 'Kuliah',
                        'judul' => $registration->kelasMataKuliah?->mataKuliah?->nama ?? 'Jadwal kuliah',
                        'tanggal' => $date->toDateString(),
                        'tanggal_label' => $date->translatedFormat('D, d M'),
                        'waktu_label' => $this->timeLabel($jadwal->jam_mulai).' – '.$this->timeLabel($jadwal->jam_selesai),
                        'lokasi' => $jadwal->ruang,
                        'urutan' => $date->format('Ymd').$this->timeLabel($jadwal->jam_mulai, 'Hi'),
                    ];

                    break;
                }
            }
        }

        foreach ($exams as $exam) {
            if ($exam->tanggal_mulai->isPast()) {
                continue;
            }

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

    private function parseTaskStatus(mixed $value): string
    {
        return is_string($value) && array_key_exists($value, self::TASK_STATUS_FILTERS)
            ? $value
            : 'semua';
    }

    /**
     * Normalize a notification status filter through the available whitelist.
     * Unknown or malformed input falls back to "semua" so it cannot affect the query.
     */
    public function notificationStatusFilter(mixed $value): string
    {
        return is_string($value) && array_key_exists($value, self::NOTIFICATION_STATUS_FILTERS)
            ? $value
            : 'semua';
    }

    private function taskStatusLabel(string $status): string
    {
        return match ($status) {
            'dinilai' => 'Dinilai',
            'terkumpul' => 'Terkumpul',
            'terlambat' => 'Terlambat',
            'mendekati_deadline' => 'Mendekati deadline',
            default => 'Belum dikumpulkan',
        };
    }
}
