<?php

namespace App\Services\JadwalUjian;

use App\Enums\JenisAgenda;
use App\Models\AgendaAkademik;
use App\Models\TahunAkademik;
use App\Services\Calendar\CalendarCategory;
use App\Services\Calendar\CalendarEventFactory;
use App\Services\Calendar\CalendarViewBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Data halaman jadwal ujian untuk dosen dan mahasiswa (read-only).
 *
 * Sumber datanya tetap satu: agenda_akademik dengan jenis = ujian. Daftar,
 * kalender, dan timeline dibentuk dari koleksi yang sama sehingga tidak ada
 * event duplikat atau penafsiran ulang judul.
 */
final class JadwalUjianPageService
{
    /** @var array<int, string> */
    private const EXAM_FILTERS = [
        CalendarCategory::UJIAN,
        CalendarCategory::UTS,
        CalendarCategory::UAS,
    ];

    public function __construct(
        private readonly CalendarEventFactory $eventFactory,
        private readonly CalendarViewBuilder $viewBuilder,
    ) {
    }

    /**
     * @param  array<int, int>  $classIds
     * @return array<string, mixed>
     */
    public function build(Request $request, array $classIds, mixed $user, int $unreadCount, string $routeName): array
    {
        Carbon::setLocale('id');

        $view = $request->query('view') === 'week' ? 'week' : 'month';
        $date = $this->parseDate($request->query('date'));
        $jenisUjian = $this->parseJenisUjian($request->query('jenis_ujian'));
        $tahunId = $this->parseTahunId($request->query('tahun_akademik'));
        $filters = $this->parseFilters($request->query('jenis'), $jenisUjian);

        $query = AgendaAkademik::query()
            ->where('jenis', JenisAgenda::Ujian->value)
            ->dipublikasikan()
            ->with([
                'tahunAkademik',
                'kelasMataKuliah.mataKuliah',
                'kelasMataKuliah.tahunAkademik',
            ])
            ->where(function ($group) use ($classIds): void {
                $group->whereNull('kelas_mata_kuliah_id');

                if ($classIds !== []) {
                    $group->orWhereIn('kelas_mata_kuliah_id', $classIds);
                }
            })
            ->orderBy('tanggal_mulai')
            ->orderBy('waktu_mulai')
            ->orderBy('id');

        $agendas = $query->get()->filter(function (AgendaAkademik $agenda) use ($jenisUjian, $tahunId): bool {
            if ($jenisUjian !== '' && $agenda->jenis_ujian?->value !== $jenisUjian) {
                return false;
            }

            if ($tahunId !== null) {
                $periodeId = $agenda->tahunAkademik?->getKey()
                    ?? $agenda->kelasMataKuliah?->tahunAkademik?->getKey();

                if ($periodeId !== $tahunId) {
                    return false;
                }
            }

            return true;
        })->values();

        $events = $this->eventFactory->agendas($agendas);

        return [
            'user' => $user,
            'unreadCount' => $unreadCount,
            'tahunAkademikAktif' => TahunAkademik::aktif()->first(),
            'ujian' => $agendas->map(fn (AgendaAkademik $agenda): array => $this->baris($agenda))->all(),
            'filters' => [
                'jenis_ujian' => $jenisUjian,
                'tahun_akademik' => $tahunId === null || $tahunId === 0 ? '' : (string) $tahunId,
            ],
            'jenisUjianOptions' => [
                'uts' => 'UTS — Ujian Tengah Semester',
                'uas' => 'UAS — Ujian Akhir Semester',
            ],
            'tahunOptions' => $this->opsiTahunAkademik(),
            'calendar' => $this->viewBuilder->build($events, $view, $date, $filters),
            'timeline' => $this->viewBuilder->timeline($events),
            'currentView' => $view,
            'activeFilters' => $filters,
            'filterOptions' => CalendarCategory::options(self::EXAM_FILTERS),
            'today' => today(),
            'routeName' => $routeName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(AgendaAkademik $agenda): array
    {
        $kategori = $this->eventFactory->agendaCategory($agenda->jenis, $agenda->jenis_ujian);
        $mulai = $agenda->tanggal_mulai;
        $selesai = $agenda->tanggal_selesai;
        $tanggal = $selesai !== null && $selesai->greaterThan($mulai)
            ? $mulai->translatedFormat('d M Y').' – '.$selesai->translatedFormat('d M Y')
            : $mulai->translatedFormat('D, d M Y');
        $waktu = $agenda->waktu_mulai
            ? $this->eventFactory->timeLabel($agenda->waktu_mulai)
                .($agenda->waktu_selesai ? ' – '.$this->eventFactory->timeLabel($agenda->waktu_selesai) : '')
            : '-';
        $periode = $agenda->tahunAkademik ?? $agenda->kelasMataKuliah?->tahunAkademik;

        return [
            'id' => $agenda->getKey(),
            'judul' => $agenda->judul,
            'kategori' => $kategori,
            'label' => CalendarCategory::label($kategori),
            'konteks' => $agenda->kelasMataKuliah
                ? 'Kelas '.$agenda->kelasMataKuliah->kode_kelas
                : 'Agenda institusi',
            'tahun' => $periode?->label() ?? '-',
            'tanggal_label' => $tanggal,
            'waktu_label' => $waktu,
            'lokasi' => $agenda->lokasi,
        ];
    }

    private function parseJenisUjian(mixed $value): string
    {
        return in_array($value, ['uts', 'uas'], true) ? (string) $value : '';
    }

    private function parseTahunId(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return TahunAkademik::query()->whereKey((int) $value)->exists() ? (int) $value : 0;
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function parseFilters(mixed $value, string $jenisUjian): array
    {
        $filters = CalendarCategory::parseFilters($value, self::EXAM_FILTERS);

        if ($filters === [] && $jenisUjian !== '') {
            $filters = [$jenisUjian === 'uts' ? CalendarCategory::UTS : CalendarCategory::UAS];
        }

        return $filters;
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
     * @return array<int, string>
     */
    private function opsiTahunAkademik(): array
    {
        return TahunAkademik::query()
            ->orderByDesc('tahun_mulai')
            ->orderByDesc('semester')
            ->get()
            ->mapWithKeys(fn (TahunAkademik $tahun): array => [$tahun->getKey() => $tahun->label()])
            ->all();
    }
}
