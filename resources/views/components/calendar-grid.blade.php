@props([
    'calendar' => [],
    'today' => null,
    'compact' => false,
])

@php
    $today = $today ?? today();
    $weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $stripByJenis = [
        'tugas' => 'border-agenda-tugas',
        'deadline' => 'border-agenda-terlambat',
        'ujian' => 'border-agenda-ujian',
        'uts' => 'border-agenda-uts',
        'uas' => 'border-agenda-uas',
        'kuliah' => 'border-agenda-kuliah',
        'akademik' => 'border-agenda-akademik',
        'pengumuman' => 'border-agenda-pengumuman',
        'materi' => 'border-agenda-materi',
    ];
    $tintByJenis = [
        'tugas' => 'bg-agenda-tugas/12',
        'deadline' => 'bg-agenda-terlambat/12',
        'ujian' => 'bg-agenda-ujian/12',
        'uts' => 'bg-agenda-uts/12',
        'uas' => 'bg-agenda-uas/12',
        'kuliah' => 'bg-agenda-kuliah/12',
        'akademik' => 'bg-agenda-akademik/12',
        'pengumuman' => 'bg-agenda-pengumuman/12',
        'materi' => 'bg-agenda-materi/12',
    ];
@endphp

<div class="hidden overflow-hidden rounded-control bg-card shadow-card sm:block">
    <div class="grid grid-cols-7 bg-soft">
        @foreach ($weekdays as $weekday)
            <div class="px-2 py-2.5 text-center text-[11px] font-bold uppercase tracking-wide text-muted">{{ $weekday }}</div>
        @endforeach
    </div>

    @foreach ($calendar['weeks'] ?? [] as $week)
        <div class="relative border-b border-border last:border-b-0">
            <div class="grid grid-cols-7">
                @foreach ($week['days'] as $day)
                    @php
                        $isToday = $day->toDateString() === $today->toDateString();
                        $outside = ($calendar['view'] ?? 'month') === 'month'
                            && $day->format('Y-m') !== ($calendar['focusDate'] ?? '');
                    @endphp
                    <div @class([
                        $compact ? 'min-h-24 border-r border-border p-1.5 last:border-r-0' : 'min-h-[132px] border-r border-border p-1.5 last:border-r-0',
                        'bg-soft/60' => $outside,
                        'bg-card' => ! $outside,
                    ])>
                        <span @class([
                            'flex size-6 items-center justify-center rounded-full text-[11px] font-semibold',
                            'bg-primary text-primary-text' => $isToday,
                            'text-muted' => $outside && ! $isToday,
                            'text-text' => ! $outside && ! $isToday,
                        ])>{{ $day->format('d') }}</span>
                    </div>
                @endforeach
            </div>

            @if (($week['visible'] ?? []) !== [])
                <div
                    class="pointer-events-none absolute inset-x-0 {{ $compact ? 'top-8' : 'top-9' }} grid grid-cols-7"
                    style="grid-template-rows: repeat({{ max(1, (int) ($week['lanes'] ?? 1)) }}, {{ $compact ? '20px' : '24px' }});"
                >
                    @foreach ($week['visible'] as $segment)
                        <button
                            type="button"
                            data-agenda-trigger="{{ $segment['event_id'] }}"
                            class="pointer-events-auto mx-0.5 flex h-5 min-w-0 items-center gap-1 border-l-4 px-1.5 text-left text-[10px] font-semibold leading-none text-text shadow-card transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $stripByJenis[$segment['jenis']] ?? 'border-border' }} {{ $tintByJenis[$segment['jenis']] ?? 'bg-soft' }} {{ $segment['continues_left'] ? 'rounded-l-none' : 'rounded-l-full' }} {{ $segment['continues_right'] ? 'rounded-r-none' : 'rounded-r-full' }}"
                            style="grid-column: {{ (int) $segment['start_col'] }} / span {{ max(1, (int) $segment['end_col'] - (int) $segment['start_col'] + 1) }}; grid-row: {{ (int) $segment['row'] + 1 }};"
                            title="{{ $segment['label'] }}: {{ $segment['judul'] }} — {{ $segment['tanggal_label'] }} {{ $segment['waktu_label'] ?? '' }}"
                        >
                            @if (($segment['kind'] ?? 'point') === 'recurring')
                                <x-icon name="repeat" class="size-3 shrink-0 text-sidebar" />
                            @endif
                            <span class="truncate">{{ $segment['judul'] }}</span>
                            @if (($segment['kind'] ?? 'point') === 'range' && (int) $segment['end_col'] > (int) $segment['start_col'])
                                @if (! $segment['continues_left'])
                                    <span class="ml-auto size-2 shrink-0 rounded-full border border-sidebar" title="Tanggal dipublikasikan" aria-label="Tanggal dipublikasikan"></span>
                                @endif
                                @if (! $segment['continues_right'])
                                    <span class="size-2 shrink-0 rotate-45 rounded-[2px] bg-sidebar" title="Deadline" aria-label="Deadline"></span>
                                @endif
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            @if (($week['hidden'] ?? 0) > 0)
                <span class="absolute bottom-1 right-2 rounded-full bg-card px-2 py-0.5 text-[9px] font-semibold text-muted shadow-card">+{{ $week['hidden'] }} agenda</span>
            @endif
        </div>
    @endforeach
</div>
