<?php

namespace App\Services\Mahasiswa;

use App\Enums\JenisAgenda;
use App\Enums\StatusPeserta;
use App\Enums\StatusTugasMahasiswa;
use App\Support\KelayakanPeserta;
use App\Support\StatusTugasResolver;
use App\Services\Calendar\CalendarCategory;
use App\Services\Calendar\CalendarEventFactory;
use App\Services\Calendar\CalendarViewBuilder;
use App\Models\AgendaAkademik;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
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

    private const DEADLINE_DEKAT_JAM = 168;

    /** @var array<string, string> */
    private const TASK_STATUS_FILTERS = [
        'semua' => 'Semua',
        'belum_dikumpulkan' => 'Belum dikumpulkan',
        'mendekati_deadline' => 'Mendekati deadline',
        'terlambat' => 'Terlambat',
        'terkumpul' => 'Dikumpulkan',
        'dinilai' => 'Sudah dinilai',
    ];

    /** @var array<string, string> */
    private const NOTIFICATION_STATUS_FILTERS = [
        'semua' => 'Semua',
        'belum_dibaca' => 'Belum dibaca',
        'sudah_dibaca' => 'Sudah dibaca',
    ];

    public function __construct(
        private readonly CalendarEventFactory $eventFactory,
        private readonly CalendarViewBuilder $viewBuilder,
        private readonly TargetBelajarService $targetBelajar,
        private readonly ProgresMateriService $progresMateri,
    ) {
    }

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
        $materials = $this->materials($classIds);
        $events = $this->calendarEvents($tasks, $agendas, $registrations, $materials);
        $targetRingkasan = $this->targetBelajar->ringkasan((int) $user->getAuthIdentifier());
        $progresOtomatis = $this->progresMateri->ringkasanOtomatis((int) $user->getAuthIdentifier());

        $stats = [
            'belum_dikumpulkan' => $tasks->filter(fn (array $task): bool => $task['status'] === StatusTugasMahasiswa::BelumDikumpulkan->value)->count(),
            'terlambat' => $tasks->filter(fn (array $task): bool => $task['status'] === StatusTugasMahasiswa::Terlambat->value)->count(),
            'mendekati_deadline' => $tasks->filter(fn (array $task): bool => $task['mendekati_deadline'])->count(),
            'ujian_mendatang' => $exams->filter(fn (AgendaAkademik $agenda): bool => $agenda->tanggal_mulai->greaterThanOrEqualTo(today()))->count(),
            'progres_selesai' => $progresOtomatis['selesai'],
            'progres_total' => $progresOtomatis['total'],
            'progres_persen' => $progresOtomatis['persentase'],
            'target_aktif' => $targetRingkasan['aktif'],
            'target_selesai' => $targetRingkasan['selesai'],
        ];

        $notifications = Notifikasi::untukUser($user->id)->latest()->limit(5)->get();
        $unreadCount = Notifikasi::untukUser($user->id)->belumDibaca()->count();

        $calendar = $this->viewBuilder->build($events, $view, $date, $filters);
        $timeline = $this->viewBuilder->timeline($events);
        $deadlines = $tasks
            ->filter(fn (array $task): bool => ! $task['sudah_dikumpulkan'] && Carbon::parse($task['deadline_at'])->greaterThanOrEqualTo(now()))
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
            'filterOptions' => CalendarCategory::options(CalendarCategory::MAHASISWA),
            'activeFilters' => $filters,
            'currentView' => $view,
            'today' => today(),
            'targetRingkasan' => $targetRingkasan,
            'progresOtomatis' => $progresOtomatis,
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
        $materials = $this->materials($classIds);
        $events = $this->calendarEvents($tasks, $agendas, $registrations, $materials);

        return [
            'user' => $user,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'calendar' => $this->viewBuilder->build($events, $view, $date, $filters),
            'timeline' => $this->viewBuilder->timeline($events),
            'filterOptions' => CalendarCategory::options(CalendarCategory::MAHASISWA),
            'activeFilters' => $filters,
            'currentView' => $view,
            'today' => today(),
            'unreadCount' => Notifikasi::untukUser($user->id)->belumDibaca()->count(),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function classIds(int $mahasiswaId): array
    {
        return $this->registrations($mahasiswaId)
            ->pluck('kelas_mata_kuliah_id')
            ->all();
    }

    public function unreadCount(int $mahasiswaId): int
    {
        return Notifikasi::untukUser($mahasiswaId)->belumDibaca()->count();
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
                ->filter(fn (array $task): bool => $status === 'mendekati_deadline'
                    ? $task['mendekati_deadline']
                    : $task['status'] === $status)
                ->count();
        }

        $filtered = $activeStatus === 'semua'
            ? $tasks
            : $tasks->filter(fn (array $task): bool => $activeStatus === 'mendekati_deadline'
                ? $task['mendekati_deadline']
                : $task['status'] === $activeStatus)->values();

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
            'notifications' => $query->latest()->paginate(10)->withQueryString(),
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
            ->whereIn('status', KelayakanPeserta::nilaiStatusBerhak())
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
    private function tasks(array $classIds, array $registrationIds, bool $onlyPublished = true): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        $query = Tugas::query()->whereIn('kelas_mata_kuliah_id', $classIds);

        if ($onlyPublished) {
            $query->dipublikasikan();
        }

        $models = $query
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
            $isLate = StatusTugasResolver::sudahLewatDeadline($deadline);
            $hoursToDeadline = now()->diffInHours($deadline, false);
            $deadlineDitutup = $isLate && ! $task->izinkan_terlambat;
            $bisaDikumpulkan = ! $deadlineDitutup
                && ($submission === null || ! $submission->sudahDinilai());

            $statusTugas = StatusTugasResolver::tentukan($submission, $deadline);
            $status = $statusTugas->value;
            $mendekatiDeadline = $submission === null
                && ! $isLate
                && $hoursToDeadline <= self::DEADLINE_DEKAT_JAM;

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
                'status_label' => $statusTugas->label(),
                'status_tone' => $statusTugas->tone(),
                'sudah_dikumpulkan' => $submission !== null,
                'bisa_dikumpulkan' => $bisaDikumpulkan,
                'pengumpulan' => $submission === null ? null : [
                    'id' => $submission->id,
                    'dikumpulkan_label' => $submission->dikumpulkan_pada?->translatedFormat('d M Y H:i') ?? 'Tidak tersedia',
                    'file_nama' => $submission->file_name,
                    'unduh_url' => route('mahasiswa.tugas.pengumpulan.unduh', $task->id),
                    'bisa_resubmit' => ! $submission->sudahDinilai(),
                    'catatan' => $submission->catatan,
                    'nilai' => $submission->nilai !== null ? (string) $submission->nilai : null,
                    'umpan_balik' => $submission->umpan_balik,
                    'terlambat' => (bool) $submission->terlambat,
                ],
                'mendekati_deadline' => $mendekatiDeadline,
                'terlambat' => $statusTugas === StatusTugasMahasiswa::Terlambat,
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
            ->with(['tahunAkademik', 'kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik']);

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
     * @return \Illuminate\Support\Collection<int, MateriPembelajaran>
     */
    private function materials(array $classIds): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        return MateriPembelajaran::dipublikasikan()
            ->whereIn('kelas_mata_kuliah_id', $classIds)
            ->with('kelasMataKuliah.mataKuliah')
            ->orderBy('dipublikasikan_pada')
            ->get();
    }

    /**
     * Satu event logis per aktivitas, dibentuk dari data sumber role yang sudah
     * dibatasi oleh hak akses.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, PesertaMataKuliah>  $registrations
     * @param  \Illuminate\Support\Collection<int, MateriPembelajaran>  $materials
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $tasks,
        Collection $agendas,
        Collection $registrations,
        Collection $materials,
    ): Collection {
        $events = $this->eventFactory->tasks($tasks)
            ->concat($this->eventFactory->agendas($agendas))
            ->concat($this->eventFactory->materials($materials));

        $schedules = $registrations
            ->flatMap(fn (PesertaMataKuliah $registration): Collection => $registration->kelasMataKuliah?->jadwal ?? collect())
            ->values();

        return $events->concat($this->eventFactory->schedules($schedules))->values();
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

            $kategori = $this->eventFactory->agendaCategory($exam->jenis, $exam->jenis_ujian);

            $items[] = [
                'jenis' => $kategori,
                'label' => CalendarCategory::label($kategori),
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

        return CalendarCategory::parseFilters($value, CalendarCategory::MAHASISWA);
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
