<?php

namespace App\Services\Admin;

use App\Enums\JenisAgenda;
use App\Enums\UserRole;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\KelasMataKuliah;
use App\Models\MataKuliah;
use App\Models\MateriPembelajaran;
use App\Models\Notifikasi;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Calendar\CalendarCategory;
use App\Services\Calendar\CalendarEventFactory;
use App\Services\Calendar\CalendarViewBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminDashboardDataService
{
    public function __construct(
        private readonly CalendarEventFactory $eventFactory,
        private readonly CalendarViewBuilder $viewBuilder,
    ) {
    }

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
                'jenis_label' => CalendarCategory::label(
                    $this->eventFactory->agendaCategory($agenda->jenis, $agenda->jenis_ujian)
                ),
                'tanggal_label' => $agenda->tanggal_mulai->translatedFormat('D, d M Y'),
                'waktu_label' => $agenda->waktu_mulai
                    ? $this->timeLabel($agenda->waktu_mulai).($agenda->waktu_selesai ? ' – '.$this->timeLabel($agenda->waktu_selesai) : '')
                    : null,
                'lokasi' => $agenda->lokasi,
                'konteks' => 'Agenda institusi',
            ])
            ->all();

        [$start, $end] = $this->periode($view, $date);

        $tahunAktifId = $tahunAktif?->getKey() ?? 0;

        $agendaKalender = AgendaAkademik::query()
            ->where(function ($query) use ($tahunAktifId): void {
                $query->whereNull('kelas_mata_kuliah_id')
                    ->orWhere(function ($ujian) use ($tahunAktifId): void {
                        $ujian->where('jenis', JenisAgenda::Ujian->value)
                            ->whereNotNull('jenis_ujian')
                            ->whereHas(
                                'kelasMataKuliah',
                                fn ($kelas) => $kelas->where('tahun_akademik_id', $tahunAktifId),
                            );
                    });
            })
            ->dipublikasikan()
            ->where('tanggal_mulai', '<=', $end->toDateString())
            ->with(['tahunAkademik', 'kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik'])
            ->orderBy('tanggal_mulai')
            ->orderBy('waktu_mulai')
            ->get();

        $jadwalKalender = $tahunAktif === null
            ? collect()
            : JadwalKuliah::query()
                ->whereHas('kelasMataKuliah', fn ($query) => $query->where('tahun_akademik_id', $tahunAktif->id))
                ->with('kelasMataKuliah.mataKuliah', 'kelasMataKuliah.tahunAkademik')
                ->get();

        $materiKalender = $tahunAktif === null
            ? collect()
            : MateriPembelajaran::dipublikasikan()
                ->whereHas('kelasMataKuliah', fn ($query) => $query->where('tahun_akademik_id', $tahunAktif->id))
                ->with('kelasMataKuliah.mataKuliah')
                ->orderBy('dipublikasikan_pada')
                ->get();

        $events = $this->calendarEvents($agendaKalender, $jadwalKalender, $materiKalender);

        return [
            'user' => $user,
            'unreadCount' => Notifikasi::untukUser((int) $user->getAuthIdentifier())
                ->belumDibaca()
                ->count(),
            'statistik' => $statistik,
            'tahunAkademikAktif' => $tahunAktif,
            'agendaMendatang' => $agendaMendatang,
            'perubahanTerbaru' => $this->perubahanTerbaru(),
            'calendar' => $this->viewBuilder->build($events, $view, $date, $filters),
            'timeline' => $this->viewBuilder->timeline($events),
            'currentView' => $view,
            'activeFilters' => $filters,
            'filterOptions' => CalendarCategory::options(CalendarCategory::ADMIN),
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
            ? $date->copy()->endOfWeek(Carbon::SUNDAY)
            : $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return [$start, $end];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @param  \Illuminate\Support\Collection<int, MateriPembelajaran>  $materials
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function calendarEvents(
        Collection $agendas,
        Collection $schedules,
        Collection $materials,
    ): Collection {
        return $this->eventFactory->agendas($agendas)
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

        return CalendarCategory::parseFilters($value, CalendarCategory::ADMIN);
    }
}
