<?php

namespace App\Services\Calendar;

use App\Enums\JenisAgenda;
use App\Enums\JenisUjian;
use App\Models\AgendaAkademik;
use App\Models\JadwalKuliah;
use App\Models\MateriPembelajaran;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Membentuk satu event logis per aktivitas dari data sumber yang sudah dimuat
 * oleh service role. Tidak ada baris database baru yang dibuat di sini.
 */
final class CalendarEventFactory
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

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function tasks(Collection $tasks): Collection
    {
        return $tasks->map(function (array $task): array {
            $published = $task['dipublikasikan_at'] === null
                ? null
                : Carbon::parse($task['dipublikasikan_at']);
            $deadline = Carbon::parse($task['deadline_at']);

            $base = [
                'id' => 'tugas-'.$task['id'],
                'source' => 'tugas',
                'source_id' => (int) $task['id'],
                'judul' => $task['judul'],
                'mata_kuliah' => $task['mata_kuliah'] ?? 'Mata kuliah tidak tersedia',
                'kelas' => isset($task['kode_kelas']) ? 'Kelas '.$task['kode_kelas'] : null,
                'lokasi' => null,
                'deskripsi' => $task['deskripsi'] ?? null,
                'status' => $task['status'] ?? null,
                'status_label' => $task['status_label'] ?? null,
                'publikasi_label' => $task['dipublikasikan_label'] ?? 'Tidak tersedia',
                'recurrence' => null,
            ];

            if ($published !== null && $published->copy()->startOfDay()->lessThanOrEqualTo($deadline->copy()->startOfDay())) {
                return $base + [
                    'jenis' => CalendarCategory::TUGAS,
                    'label' => CalendarCategory::label(CalendarCategory::TUGAS),
                    'kind' => 'range',
                    'start' => $published->copy(),
                    'end' => $deadline->copy(),
                    'tanggal_label' => $published->translatedFormat('d M Y H:i').' – '.$deadline->translatedFormat('d M Y H:i'),
                    'waktu_label' => $published->format('H:i').' – '.$deadline->format('H:i'),
                    'awal_label' => $task['dipublikasikan_label'],
                    'akhir_label' => $task['deadline_label'],
                    'awal_label_name' => 'Tanggal dipublikasikan',
                    'akhir_label_name' => 'Deadline',
                    'urutan' => $published->format('YmdHi'),
                    'markers' => [
                        ['type' => 'publikasi', 'at' => $published->copy(), 'label' => 'Tugas dipublikasikan'],
                        ['type' => 'deadline', 'at' => $deadline->copy(), 'label' => 'Deadline tugas'],
                    ],
                ];
            }

            return $base + [
                'jenis' => CalendarCategory::DEADLINE,
                'label' => 'Deadline tugas',
                'kind' => 'point',
                'start' => $deadline->copy(),
                'end' => null,
                'tanggal_label' => $deadline->translatedFormat('D, d M Y H:i'),
                'waktu_label' => $deadline->format('H:i'),
                'awal_label' => 'Tidak tersedia',
                'akhir_label' => $task['deadline_label'],
                'awal_label_name' => 'Tanggal dipublikasikan',
                'akhir_label_name' => 'Deadline',
                'urutan' => $deadline->format('YmdHi'),
                'markers' => [
                    ['type' => 'deadline', 'at' => $deadline->copy(), 'label' => 'Deadline tugas'],
                ],
            ];
        })->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AgendaAkademik>  $agendas
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function agendas(Collection $agendas): Collection
    {
        return $agendas->map(function (AgendaAkademik $agenda): array {
            $start = $agenda->tanggal_mulai->copy()->startOfDay();
            $end = ($agenda->tanggal_selesai ?? $agenda->tanggal_mulai)->copy()->startOfDay();
            $jenis = $this->agendaCategory($agenda->jenis, $agenda->jenis_ujian);
            $waktu = $agenda->waktu_mulai
                ? $this->timeLabel($agenda->waktu_mulai).($agenda->waktu_selesai ? ' – '.$this->timeLabel($agenda->waktu_selesai) : '')
                : null;

            $adaSelesai = $agenda->tanggal_selesai !== null && $end->greaterThan($start);
            $isRange = $jenis === CalendarCategory::AKADEMIK && $adaSelesai;
            $tanggalLabel = $adaSelesai
                ? $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y')
                : $start->translatedFormat('D, d M Y');

            return [
                'id' => 'agenda-'.$agenda->id,
                'source' => 'agenda',
                'source_id' => (int) $agenda->id,
                'jenis' => $jenis,
                'label' => CalendarCategory::label($jenis),
                'kind' => $isRange ? 'range' : 'point',
                'judul' => $agenda->judul,
                'mata_kuliah' => $agenda->kelasMataKuliah?->mataKuliah?->nama ?? 'Agenda institusi',
                'kelas' => $agenda->kelasMataKuliah ? 'Kelas '.$agenda->kelasMataKuliah->kode_kelas : null,
                'start' => $start,
                'end' => $isRange ? $end : null,
                'tanggal_label' => $tanggalLabel,
                'waktu_label' => $waktu,
                'awal_label' => $start->translatedFormat('d M Y'),
                'akhir_label' => $adaSelesai
                    ? $end->translatedFormat('d M Y')
                    : $start->translatedFormat('d M Y'),
                'awal_label_name' => 'Tanggal mulai',
                'akhir_label_name' => $adaSelesai ? 'Tanggal selesai' : 'Tanggal agenda',
                'publikasi_label' => $agenda->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Tidak tersedia',
                'lokasi' => $agenda->lokasi,
                'deskripsi' => $agenda->deskripsi,
                'status_label' => null,
                'urutan' => $start->format('Ymd').($agenda->waktu_mulai ? $this->timeLabel($agenda->waktu_mulai, 'Hi') : '0000'),
                'recurrence' => null,
                'markers' => $isRange
                    ? [
                        ['type' => 'mulai', 'at' => $start->copy(), 'label' => 'Tanggal mulai'],
                        ['type' => 'selesai', 'at' => $end->copy(), 'label' => 'Tanggal selesai'],
                    ]
                    : [
                        ['type' => 'agenda', 'at' => $start->copy(), 'label' => 'Tanggal agenda'],
                    ],
            ];
        })->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, JadwalKuliah>  $schedules
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function schedules(Collection $schedules): Collection
    {
        return $schedules->map(function (JadwalKuliah $schedule): array {
            $class = $schedule->kelasMataKuliah;
            $day = self::HARI[$schedule->hari] ?? 'Tidak diketahui';
            $time = $this->timeLabel($schedule->jam_mulai).' – '.$this->timeLabel($schedule->jam_selesai);
            $windowStart = $class?->tahunAkademik?->tanggal_mulai;
            $windowEnd = $class?->tahunAkademik?->tanggal_selesai;

            return [
                'id' => 'jadwal-'.$schedule->id,
                'source' => 'jadwal',
                'source_id' => (int) $schedule->id,
                'jenis' => CalendarCategory::KULIAH,
                'label' => ucfirst($schedule->jenis?->value ?? 'kuliah'),
                'kind' => 'recurring',
                'judul' => $class?->mataKuliah?->nama ?? 'Jadwal kuliah',
                'mata_kuliah' => $class?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                'kelas' => $class ? 'Kelas '.$class->kode_kelas : null,
                'start' => null,
                'end' => null,
                'tanggal_label' => 'Setiap '.$day,
                'waktu_label' => $time,
                'awal_label' => $day.', '.$this->timeLabel($schedule->jam_mulai),
                'akhir_label' => $this->timeLabel($schedule->jam_selesai),
                'awal_label_name' => 'Hari / jam mulai',
                'akhir_label_name' => 'Jam selesai',
                'publikasi_label' => 'Tidak tersedia',
                'lokasi' => $schedule->ruang,
                'deskripsi' => $class ? 'Kelas '.$class->kode_kelas : null,
                'status_label' => null,
                'urutan' => str_pad((string) $schedule->hari, 2, '0', STR_PAD_LEFT).$this->timeLabel($schedule->jam_mulai, 'Hi'),
                'recurrence' => [
                    'type' => 'weekly',
                    'by_day' => (int) $schedule->hari,
                    'window_start' => $windowStart?->copy()->startOfDay(),
                    'window_end' => $windowEnd?->copy()->endOfDay(),
                ],
                'markers' => [],
            ];
        })->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MateriPembelajaran>  $materials
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function materials(Collection $materials): Collection
    {
        return $materials
            ->filter(fn (MateriPembelajaran $material): bool => $material->sudahDipublikasikan())
            ->map(function (MateriPembelajaran $material): array {
                $published = $material->dipublikasikan_pada->copy();
                $label = $published->translatedFormat('d M Y H:i');

                return [
                    'id' => 'materi-'.$material->id,
                    'source' => 'materi',
                    'source_id' => (int) $material->id,
                    'jenis' => CalendarCategory::MATERI,
                    'label' => CalendarCategory::label(CalendarCategory::MATERI),
                    'kind' => 'point',
                    'judul' => $material->judul,
                    'mata_kuliah' => $material->kelasMataKuliah?->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia',
                    'kelas' => $material->kelasMataKuliah ? 'Kelas '.$material->kelasMataKuliah->kode_kelas : null,
                    'start' => $published->copy(),
                    'end' => null,
                    'tanggal_label' => $published->translatedFormat('D, d M Y'),
                    'waktu_label' => $published->format('H:i'),
                    'awal_label' => $label,
                    'akhir_label' => 'Tidak tersedia',
                    'awal_label_name' => 'Tanggal publikasi',
                    'akhir_label_name' => 'Tanggal selesai',
                    'publikasi_label' => $label,
                    'lokasi' => $material->file_name,
                    'deskripsi' => $material->deskripsi,
                    'status_label' => 'Materi terpublikasi',
                    'urutan' => $published->format('YmdHi'),
                    'recurrence' => null,
                    'markers' => [
                        ['type' => 'publikasi', 'at' => $published->copy(), 'label' => 'Materi dipublikasikan'],
                    ],
                ];
            })->values();
    }

    /**
     * Data rata untuk modal detail. Tidak mengirim objek Carbon ke JSON.
     *
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function detailPayload(array $event): array
    {
        return [
            'id' => $event['id'],
            'source' => $event['source'],
            'jenis' => $event['jenis'],
            'label' => $event['label'],
            'kind' => $event['kind'],
            'judul' => $event['judul'],
            'mata_kuliah' => $event['mata_kuliah'],
            'tanggal_label' => $event['tanggal_label'],
            'waktu_label' => $event['waktu_label'],
            'awal_label' => $event['awal_label'],
            'akhir_label' => $event['akhir_label'],
            'awal_label_name' => $event['awal_label_name'],
            'akhir_label_name' => $event['akhir_label_name'],
            'publikasi_label' => $event['publikasi_label'],
            'lokasi' => $event['lokasi'],
            'status_label' => $event['status_label'],
            'deskripsi' => $event['deskripsi'],
        ];
    }

    public function agendaCategory(JenisAgenda $jenis, ?JenisUjian $jenisUjian = null): string
    {
        return match ($jenis) {
            JenisAgenda::Ujian => match ($jenisUjian) {
                JenisUjian::Uts => CalendarCategory::UTS,
                JenisUjian::Uas => CalendarCategory::UAS,
                null => CalendarCategory::UJIAN,
            },
            JenisAgenda::Pengumuman => CalendarCategory::PENGUMUMAN,
            JenisAgenda::Akademik => CalendarCategory::AKADEMIK,
        };
    }

    public function timeLabel(mixed $value, string $format = 'H:i'): string
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
}
