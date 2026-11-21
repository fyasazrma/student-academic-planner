@props([
    'calendar' => [],
    'today' => null,
])

@php
    $today = $today ?? today();
    $weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $toneByJenis = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-ujian',
        'uts' => 'bg-agenda-uts',
        'uas' => 'bg-agenda-uas',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
        'materi' => 'bg-agenda-materi',
    ];
    $textByJenis = [
        'kuliah' => 'text-primary-text',
        'pengumuman' => 'text-primary-text',
    ];
@endphp

<div class="hidden overflow-hidden rounded-xl border border-border sm:block">
    <div class="grid grid-cols-7 border-b border-border bg-soft">
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
                        'min-h-[132px] border-r border-border p-1.5 last:border-r-0',
                        'bg-soft/50' => $outside,
                        'bg-card' => ! $outside,
                    ])>
                        <span @class([
                            'flex h-6 w-6 items-center justify-center rounded-lg text-[11px] font-semibold',
                            'bg-sidebar text-white' => $isToday,
                            'text-muted' => $outside && ! $isToday,
                            'text-text' => ! $outside && ! $isToday,
                        ])>{{ $day->format('d') }}</span>
                    </div>
                @endforeach
            </div>

            @if (($week['visible'] ?? []) !== [])
                <div
                    class="pointer-events-none absolute inset-x-0 top-9 grid grid-cols-7"
                    style="grid-template-rows: repeat({{ max(1, (int) ($week['lanes'] ?? 1)) }}, 24px);"
                >
                    @foreach ($week['visible'] as $segment)
                        <button
                            type="button"
                            data-agenda-trigger="{{ $segment['event_id'] }}"
                            class="pointer-events-auto mx-0.5 flex h-5 min-w-0 items-center gap-1 px-1.5 text-left text-[10px] font-semibold leading-none shadow-sm transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-sidebar {{ $toneByJenis[$segment['jenis']] ?? 'bg-muted' }} {{ $textByJenis[$segment['jenis']] ?? 'text-white' }} {{ $segment['continues_left'] ? 'rounded-l-none' : 'rounded-l-full' }} {{ $segment['continues_right'] ? 'rounded-r-none' : 'rounded-r-full' }}"
                            style="grid-column: {{ (int) $segment['start_col'] }} / span {{ max(1, (int) $segment['end_col'] - (int) $segment['start_col'] + 1) }}; grid-row: {{ (int) $segment['row'] + 1 }};"
                            title="{{ $segment['label'] }}: {{ $segment['judul'] }} — {{ $segment['tanggal_label'] }} {{ $segment['waktu_label'] ?? '' }}"
                        >
                            @if (($segment['kind'] ?? 'point') === 'recurring')
                                <x-icon name="repeat" class="h-3 w-3 shrink-0" />
                            @endif
                            <span class="truncate">{{ $segment['judul'] }}</span>
                            @if (($segment['kind'] ?? 'point') === 'range' && (int) $segment['end_col'] > (int) $segment['start_col'])
                                @if (! $segment['continues_left'])
                                    <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-80" title="Tanggal dipublikasikan" aria-label="Tanggal dipublikasikan"></span>
                                @endif
                                @if (! $segment['continues_right'])
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-80" title="Deadline" aria-label="Deadline"></span>
                                @endif
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            @if (($week['hidden'] ?? 0) > 0)
                <span class="absolute bottom-1 right-2 rounded-md bg-soft px-1.5 py-0.5 text-[9px] font-semibold text-muted">+{{ $week['hidden'] }} agenda</span>
            @endif
        </div>
    @endforeach
</div>
