<?php

namespace App\Services\Calendar;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menurunkan event logis menjadi tampilan kalender: hari, minggu, segmen
 * visual batang/titik/berulang, dan data untuk modal detail.
 */
final class CalendarViewBuilder
{
    private const MAX_LANES = 4;

    public function __construct(private readonly CalendarEventFactory $eventFactory)
    {
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $events
     * @param  array<int, string>  $filters
     * @return array<string, mixed>
     */
    public function build(Collection $events, string $view, Carbon $date, array $filters): array
    {
        [$start, $end] = $this->period($view, $date);
        $days = $this->days($start, $end);

        $filtered = $filters === []
            ? $events
            : $events->filter(fn (array $event): bool => in_array($event['jenis'], $filters, true))->values();

        $segments = $this->segments($filtered, $start, $end);
        $visibleIds = $segments->pluck('event_id')->unique()->all();

        $visibleEvents = $filtered
            ->filter(fn (array $event): bool => in_array($event['id'], $visibleIds, true))
            ->sortBy('urutan')
            ->values();

        $detailEvents = [];
        foreach ($events as $event) {
            $detailEvents[$event['id']] = $this->eventFactory->detailPayload($event);
        }

        return [
            'days' => $days,
            'weeks' => $this->weeks($days, $segments),
            'focusDate' => $date->format('Y-m'),
            'view' => $view,
            'events' => $visibleEvents,
            'eventsByDate' => $segments->groupBy('tanggal')->map(fn (Collection $items): Collection => $items->values()),
            'dayCounts' => $segments->groupBy('tanggal')->map(fn (Collection $items): int => $items->count()),
            'agendaList' => $visibleEvents,
            'detailEvents' => $detailEvents,
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
     * Timeline tetap memakai data sumber yang sama dengan Calendar View.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $events
     * @return array<string, mixed>
     */
    public function timeline(Collection $events): array
    {
        $dates = collect();

        foreach ($events as $event) {
            if ($event['source'] === 'tugas' && $event['start'] instanceof Carbon) {
                $dates->push($event['start']->copy()->startOfDay());
            }

            if ($this->adalahUjian($event) && $event['start'] instanceof Carbon) {
                $dates->push($event['start']->copy()->startOfDay());
            }
        }

        $dates->push(today()->copy()->startOfDay());

        /** @var Carbon $earliest */
        $earliest = $dates->min();
        /** @var Carbon $latest */
        $latest = $dates->max();

        $start = $earliest->copy()->subDay();
        $end = $latest->copy()->addDay();
        $span = max(1, $start->diffInDays($end));

        $bars = $events
            ->filter(fn (array $event): bool => $event['source'] === 'tugas' && $event['kind'] === 'range')
            ->map(function (array $event) use ($start, $span): array {
                /** @var Carbon $published */
                $published = $event['start'];
                /** @var Carbon $deadline */
                $deadline = $event['end'];
                $left = max(0, min(100, $start->diffInDays($published->copy()->startOfDay()) / $span * 100));
                $right = max(0, min(100, $start->diffInDays($deadline->copy()->startOfDay()) / $span * 100));

                return [
                    'id' => $event['source_id'],
                    'event_id' => $event['id'],
                    'jenis' => $event['jenis'],
                    'judul' => $event['judul'],
                    'mata_kuliah' => $event['mata_kuliah'],
                    'deskripsi' => $event['deskripsi'],
                    'dipublikasikan_label' => $event['awal_label'],
                    'deadline_label' => $event['akhir_label'],
                    'status' => $event['status'] ?? null,
                    'status_label' => $event['status_label'],
                    'bar_left' => round($left, 2),
                    'bar_width' => max(3, round($right - $left, 2)),
                    'deadline_marker' => round($right, 2),
                ];
            })->values()->all();

        $markers = $events
            ->filter(fn (array $event): bool => $event['start'] instanceof Carbon
                && (
                    $this->adalahUjian($event)
                    || ($event['source'] === 'tugas' && $event['jenis'] === CalendarCategory::DEADLINE)
                ))
            ->map(function (array $event) use ($start, $span): array {
                /** @var Carbon $date */
                $date = $event['start'];
                $isExam = $event['source'] === 'agenda';

                return [
                    'id' => $isExam ? 'ujian-'.$event['source_id'] : 'deadline-'.$event['source_id'],
                    'event_id' => $event['id'],
                    'jenis' => $isExam ? $event['jenis'] : CalendarCategory::DEADLINE,
                    'label' => $isExam ? $event['label'] : 'Deadline tugas',
                    'judul' => $event['judul'],
                    'mata_kuliah' => $event['mata_kuliah'],
                    'tanggal_label' => $event['tanggal_label'],
                    'posisi' => round(max(0, min(100, $start->diffInDays($date->copy()->startOfDay()) / $span * 100)), 2),
                    'waktu_label' => $event['waktu_label'],
                    'lokasi' => $event['lokasi'],
                ];
            })
            ->sortBy('posisi')
            ->values()
            ->all();

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
     * @param  array<string, mixed>  $event
     */
    private function adalahUjian(array $event): bool
    {
        return $event['source'] === 'agenda'
            && in_array($event['jenis'], [
                CalendarCategory::UJIAN,
                CalendarCategory::UTS,
                CalendarCategory::UAS,
            ], true);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(string $view, Carbon $date): array
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
     * @return \Illuminate\Support\Collection<int, Carbon>
     */
    private function days(Carbon $start, Carbon $end): Collection
    {
        $days = collect();
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $days->push($cursor->copy());
        }

        return $days;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $events
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function segments(Collection $events, Carbon $start, Carbon $end): Collection
    {
        $segments = collect();

        foreach ($events as $event) {
            if ($event['kind'] === 'recurring') {
                $this->recurringSegments($segments, $event, $start, $end);
                continue;
            }

            if (! $event['start'] instanceof Carbon) {
                continue;
            }

            $eventStart = $event['start']->copy()->startOfDay();
            $eventEnd = ($event['end'] ?? $event['start'])->copy()->startOfDay();

            if ($eventEnd->lessThan($start) || $eventStart->greaterThan($end)) {
                continue;
            }

            if ($event['kind'] === 'range') {
                $weekStart = $start->copy()->startOfWeek(Carbon::MONDAY);

                while ($weekStart->lessThanOrEqualTo($end)) {
                    $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
                    $segmentStart = $this->later($eventStart, $weekStart);
                    $segmentStart = $this->later($segmentStart, $start);
                    $segmentEnd = $this->earlier($eventEnd, $weekEnd);
                    $segmentEnd = $this->earlier($segmentEnd, $end);

                    if ($segmentStart->lessThanOrEqualTo($segmentEnd)) {
                        $segments->push($this->makeSegment($event, $segmentStart, $segmentEnd));
                    }

                    $weekStart->addWeek();
                }

                continue;
            }

            $segmentStart = $this->later($eventStart, $start);
            $segmentEnd = $this->earlier($eventEnd, $end);

            if ($segmentStart->lessThanOrEqualTo($segmentEnd)) {
                $segments->push($this->makeSegment($event, $segmentStart, $segmentEnd));
            }
        }

        return $segments->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $segments
     * @param  array<string, mixed>  $event
     */
    private function recurringSegments(Collection $segments, array $event, Carbon $start, Carbon $end): void
    {
        $recurrence = $event['recurrence'] ?? null;
        if (! is_array($recurrence) || ($recurrence['type'] ?? null) !== 'weekly') {
            return;
        }

        $windowStart = $recurrence['window_start'] instanceof Carbon
            ? $this->later($recurrence['window_start']->copy()->startOfDay(), $start)
            : $start->copy();
        $windowEnd = $recurrence['window_end'] instanceof Carbon
            ? $this->earlier($recurrence['window_end']->copy()->endOfDay(), $end)
            : $end->copy();

        if ($windowStart->greaterThan($windowEnd)) {
            return;
        }

        $day = (int) ($recurrence['by_day'] ?? -1);
        $cursor = $windowStart->copy();
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($windowEnd) && $guard < 400) {
            if ($cursor->dayOfWeek === $day) {
                $segments->push($this->makeSegment($event, $cursor->copy(), $cursor->copy()));
            }

            $cursor->addDay();
            $guard++;
        }
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function makeSegment(array $event, Carbon $segmentStart, Carbon $segmentEnd): array
    {
        $eventStart = $event['start'] instanceof Carbon ? $event['start']->copy()->startOfDay() : null;
        $eventEnd = $event['end'] instanceof Carbon ? $event['end']->copy()->startOfDay() : null;

        return [
            'event_id' => $event['id'],
            'segment_key' => $event['id'].'|'.$segmentStart->toDateString().'|'.$segmentEnd->toDateString(),
            'jenis' => $event['jenis'],
            'label' => $event['label'],
            'judul' => $event['judul'],
            'mata_kuliah' => $event['mata_kuliah'],
            'kind' => $event['kind'],
            'tanggal' => $segmentStart->toDateString(),
            'tanggal_akhir' => $segmentEnd->toDateString(),
            'tanggal_label' => $segmentStart->equalTo($segmentEnd)
                ? $segmentStart->translatedFormat('D, d M Y')
                : $segmentStart->translatedFormat('d M').' – '.$segmentEnd->translatedFormat('d M Y'),
            'waktu_label' => $event['waktu_label'],
            'start_col' => $this->column($segmentStart),
            'end_col' => $this->column($segmentEnd),
            'continues_left' => $eventStart !== null && $eventStart->lessThan($segmentStart),
            'continues_right' => $eventEnd !== null && $eventEnd->greaterThan($segmentEnd),
            'urutan' => $event['urutan'].$segmentStart->format('Ymd'),
        ];
    }

    private function column(Carbon $date): int
    {
        return ((int) $date->dayOfWeek + 6) % 7 + 1;
    }

    private function later(Carbon $left, Carbon $right): Carbon
    {
        return $left->greaterThan($right) ? $left->copy() : $right->copy();
    }

    private function earlier(Carbon $left, Carbon $right): Carbon
    {
        return $left->lessThan($right) ? $left->copy() : $right->copy();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Carbon>  $days
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $segments
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function weeks(Collection $days, Collection $segments): Collection
    {
        $weeks = collect();

        foreach ($days->chunk(7) as $weekDays) {
            /** @var Carbon $weekStart */
            $weekStart = $weekDays->first();
            $weekEnd = $weekDays->last();

            $weekSegments = $segments
                ->filter(function (array $segment) use ($weekStart, $weekEnd): bool {
                    $date = Carbon::createFromFormat('!Y-m-d', $segment['tanggal']);

                    return $date->greaterThanOrEqualTo($weekStart->copy()->startOfDay())
                        && $date->lessThanOrEqualTo($weekEnd->copy()->startOfDay());
                })
                ->values();

            $assigned = $this->assignRows($weekSegments);
            $maxRow = (int) $assigned->max('row');
            $lanes = max(1, min(self::MAX_LANES, $maxRow + 1));
            $visible = $assigned->filter(fn (array $segment): bool => $segment['row'] < $lanes)->values();

            $weeks->push([
                'days' => $weekDays->values(),
                'segments' => $assigned,
                'visible' => $visible,
                'lanes' => $lanes,
                'hidden' => $assigned->count() - $visible->count(),
            ]);
        }

        return $weeks;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $segments
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function assignRows(Collection $segments): Collection
    {
        $priority = ['range' => 0, 'recurring' => 1, 'point' => 2];
        $sorted = $segments
            ->sortBy(fn (array $segment): string => sprintf(
                '%d-%02d-%02d-%s',
                $priority[$segment['kind']] ?? 9,
                $segment['start_col'],
                $segment['end_col'],
                $segment['urutan'],
            ))
            ->values();

        $occupied = [];

        return $sorted->map(function (array $segment) use (&$occupied): array {
            $row = 0;
            while (isset($occupied[$row]) && $occupied[$row] >= $segment['start_col']) {
                $row++;
            }

            $occupied[$row] = $segment['end_col'];

            return $segment + ['row' => $row];
        })->values();
    }
}
