@props([
    'deadlines' => [],
    'nextSchedules' => [],
])

@php
    $miniRoute = request()->route()?->getName() ?? 'mahasiswa.dashboard';
    $miniParams = request()->except(['date']);
    $miniLink = fn (string $date): string => route($miniRoute, array_filter($miniParams + [
        'date' => $date,
    ], fn ($value): bool => $value !== null && $value !== ''));

    $requestedDate = request('date');
    $miniFocus = today();
    if (is_string($requestedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
        $miniFocus = \Illuminate\Support\Carbon::parse($requestedDate)->startOfDay();
    }

    $miniStart = $miniFocus->copy()->startOfMonth()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $miniEnd = $miniFocus->copy()->endOfMonth()->endOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $miniToday = today()->toDateString();

    $toneByJenis = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-ujian',
        'uts' => 'bg-agenda-uts',
        'uas' => 'bg-agenda-uas',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
    ];

    $miniMarks = [];
    foreach ($deadlines as $task) {
        $date = $task['deadline_tanggal'] ?? null;
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $miniMarks[$date][] = [
                'tone' => 'bg-agenda-terlambat',
                'label' => 'Deadline: '.($task['judul'] ?? 'Tugas'),
            ];
        }
    }
    foreach ($nextSchedules as $schedule) {
        $date = $schedule['tanggal'] ?? null;
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $miniMarks[$date][] = [
                'tone' => $toneByJenis[$schedule['jenis'] ?? ''] ?? 'bg-agenda-akademik',
                'label' => ($schedule['label'] ?? 'Agenda').': '.($schedule['judul'] ?? ''),
            ];
        }
    }
@endphp

<section id="mini-kalender" class="rounded-card bg-card p-5 shadow-card" aria-label="Navigator kalender">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <x-icon name="calendar" class="size-5 text-agenda-tugas" />
            <h3 class="text-sm font-bold text-text">Kalender</h3>
        </div>
        <div class="flex items-center gap-1">
            <a
                href="{{ $miniLink($miniFocus->copy()->startOfMonth()->subMonth()->toDateString()) }}"
                class="grid size-7 place-items-center rounded-full bg-soft text-muted transition hover:bg-card hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                aria-label="Bulan sebelumnya"
            >
                <x-icon name="chevron-left" class="size-3.5" />
            </a>
            <a
                href="{{ $miniLink($miniToday) }}"
                class="inline-flex h-7 items-center rounded-full bg-soft px-2.5 text-[10px] font-semibold text-text transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >Hari ini</a>
            <a
                href="{{ $miniLink($miniFocus->copy()->startOfMonth()->addMonth()->toDateString()) }}"
                class="grid size-7 place-items-center rounded-full bg-soft text-muted transition hover:bg-card hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                aria-label="Bulan berikutnya"
            >
                <x-icon name="chevron-right" class="size-3.5" />
            </a>
        </div>
    </div>

    <p class="mt-3 text-xs font-bold capitalize text-text">{{ $miniFocus->translatedFormat('F Y') }}</p>

    <div class="mt-2 grid grid-cols-7 gap-0.5 text-center text-[9px] font-bold uppercase tracking-wide text-muted">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
            <span>{{ $weekday }}</span>
        @endforeach
    </div>

    <div class="mt-1 grid grid-cols-7 gap-0.5">
        @for ($day = $miniStart->copy(); $day->lte($miniEnd); $day->addDay())
            @php
                $dateKey = $day->toDateString();
                $outside = $day->format('Y-m') !== $miniFocus->format('Y-m');
                $isToday = $dateKey === $miniToday;
                $isSelected = $dateKey === $requestedDate;
                $marks = $miniMarks[$dateKey] ?? [];
            @endphp
            <a
                href="{{ $miniLink($dateKey) }}"
                class="flex min-h-8 flex-col items-center justify-center gap-0.5 rounded-control py-1 transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $outside ? 'text-muted/60' : 'text-text' }} {{ $isSelected && ! $isToday ? 'ring-2 ring-inset ring-sidebar' : '' }}"
                aria-label="{{ $day->translatedFormat('l, d F Y') }}{{ $marks !== [] ? ', '.count($marks).' agenda' : '' }}"
                title="{{ $marks !== [] ? implode(' · ', array_column($marks, 'label')) : $day->translatedFormat('l, d F Y') }}"
            >
                <span @class([
                    'grid size-6 place-items-center rounded-full text-[11px] font-semibold',
                    'bg-primary text-primary-text' => $isToday,
                ])>{{ $day->format('d') }}</span>
                <span class="flex h-1.5 items-center gap-0.5" aria-hidden="true">
                    @foreach (array_slice($marks, 0, 3) as $mark)
                        <span class="size-1 rounded-full {{ $mark['tone'] }}"></span>
                    @endforeach
                </span>
            </a>
        @endfor
    </div>

    <p class="mt-3 text-[10px] leading-4 text-muted">
        Pilih tanggal untuk membuka kalender utama pada tanggal tersebut.
    </p>
</section>
