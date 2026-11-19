<?php

namespace App\Services\Dosen;

use App\Enums\JenisAgenda;
use App\Enums\StatusPendaftaranKelas;
use App\Enums\StatusPeserta;
use App\Enums\StatusSesiAbsensi;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\PesertaMataKuliah;
use App\Models\SesiAbsensi;
use App\Services\Calendar\CalendarCategory;
use App\Services\Calendar\CalendarEventFactory;
use App\Services\Calendar\CalendarViewBuilder;
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

    private const DEADLINE_DEKAT_JAM = 168;

    public function __construct(
        private readonly CalendarEventFactory $eventFactory,
        private readonly CalendarViewBuilder $viewBuilder,
    ) {
    }

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
        $materials = $this->materials($classIds);
        $exams = $agendas
            ->filter(fn (AgendaAkademik $agenda): bool => $agenda->jenis === JenisAgenda::Ujian
                && $agenda->tanggal_mulai->greaterThanOrEqualTo($today))
            ->values();
        $events = $this->calendarEvents($tasks, $agendas, $schedules, $materials);

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
            'absensiRingkasan' => $this->absensiRingkasan($classIds),
            'calendar' => $this->viewBuilder->build($events, $view, $date, $filters),
            'timeline' => $this->viewBuilder->timeline($events),
            'filterOptions' => CalendarCategory::options(CalendarCategory::MAHASISWA),
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
            ->with('kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * @param  array<int, int>  $classIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function tasks(array $classIds, bool $onlyPublished = true): Collection
    {
        if ($classIds === []) {
            return collect();
        }

        $query = Tugas::query()->whereIn('kelas_mata_kuliah_id', $classIds);

        if ($onlyPublished) {
            $query->dipublikasikan();
        }

        return $query
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
     * Ringkasan absensi hanya untuk kelas yang benar-benar diampu dosen.
     * Angka "belum mengisi" dihitung dari jumlah peserta berhak dikurangi
     * catatan yang sudah ada pada setiap sesi yang masih terbuka.
     *
     * @param  array<int, int>  $classIds
     * @return array{sesi_dibuka: int, sesi_hari_ini: int, belum_mengisi: int, url: string}
     */
    private function absensiRingkasan(array $classIds): array
    {
        $url = route('dosen.absensi.index');

        if ($classIds === []) {
            return [
                'sesi_dibuka' => 0,
                'sesi_hari_ini' => 0,
                'belum_mengisi' => 0,
                'url' => $url,
            ];
        }

        $query = SesiAbsensi::query()->whereIn('kelas_mata_kuliah_id', $classIds);
        $dibuka = (clone $query)
            ->where('status', StatusSesiAbsensi::Dibuka->value)
            ->withCount('absensi')
            ->get();

        $belumMengisi = 0;

        foreach ($dibuka as $sesi) {
            $berhak = PesertaMataKuliah::query()
                ->where('kelas_mata_kuliah_id', $sesi->kelas_mata_kuliah_id)
                ->whereIn('status', [
                    StatusPeserta::Aktif->value,
                    StatusPeserta::Mengulang->value,
                ])
                ->count();

            $belumMengisi += max(0, $berhak - (int) $sesi->absensi_count);
        }

        return [
            'sesi_dibuka' => $dibuka->count(),
            'sesi_hari_ini' => (clone $query)->whereDate('tanggal', today())->count(),
            'belum_mengisi' => $belumMengisi,
            'url' => $url,
        ];
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
     * @param  \Illuminate\Support\Collection<int, MateriPembelajaran>  $materials
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $tasks,
        Collection $agendas,
        Collection $schedules,
        Collection $materials,
    ): Collection {
        return $this->eventFactory->tasks($tasks)
            ->concat($this->eventFactory->agendas($agendas))
            ->concat($this->eventFactory->schedules($schedules))
            ->concat($this->eventFactory->materials($materials))
            ->values();
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
}
