@props([
    'calendar' => [],
    'currentView' => 'month',
    'activeFilters' => [],
    'filterOptions' => [],
    'routeName' => 'mahasiswa.dashboard',
])

@php
    $tab = request('tab') === 'timeline' ? 'timeline' : 'calendar';
    $custom = request()->except(['view', 'date', 'tab', 'jenis']);
    $base = $custom + [
        'date' => request('date'),
        'tab' => $tab,
        'jenis' => request('jenis'),
    ];

    $link = function (array $overrides) use ($base, $routeName): string {
        $params = array_filter(array_merge($base, $overrides), fn ($value): bool => $value !== null && $value !== '' && $value !== []);

        return route($routeName, $params);
    };

    $resetLink = fn (): string => route($routeName, array_filter($custom + [
        'date' => request('date'),
        'tab' => $tab,
        'view' => $currentView,
    ], fn ($value): bool => $value !== null && $value !== ''));

    $toneByType = [
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
    $textByType = [
        'tugas' => 'text-agenda-tugas',
        'deadline' => 'text-agenda-terlambat',
        'ujian' => 'text-agenda-ujian',
        'uts' => 'text-agenda-uts',
        'uas' => 'text-agenda-uas',
        'kuliah' => 'text-agenda-kuliah',
        'akademik' => 'text-agenda-akademik',
        'pengumuman' => 'text-agenda-pengumuman',
        'materi' => 'text-agenda-materi',
    ];
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-full bg-soft p-1" role="tablist" aria-label="Tampilan kalender">
                <a
                    href="{{ $link(['view' => 'month']) }}"
                    role="tab"
                    @class([
                        'rounded-full px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                        'bg-sidebar text-white shadow-card' => $currentView === 'month',
                        'text-muted hover:text-text' => $currentView !== 'month',
                    ])
                    @if ($currentView === 'month') aria-selected="true" @endif
                >Bulan</a>
                <a
                    href="{{ $link(['view' => 'week']) }}"
                    role="tab"
                    @class([
                        'rounded-full px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                        'bg-sidebar text-white shadow-card' => $currentView === 'week',
                        'text-muted hover:text-text' => $currentView !== 'week',
                    ])
                    @if ($currentView === 'week') aria-selected="true" @endif
                >Minggu</a>
            </div>

            <div class="flex items-center gap-1">
                <a
                    href="{{ $link(['view' => $currentView, 'date' => $calendar['previous']]) }}"
                    class="grid size-8 place-items-center rounded-full bg-card text-muted shadow-card transition hover:bg-soft hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    aria-label="Periode sebelumnya"
                >
                    <x-icon name="chevron-left" class="size-4" />
                </a>
                <span class="min-w-[132px] px-2 text-center text-sm font-semibold capitalize text-text">{{ $calendar['label'] }}</span>
                <a
                    href="{{ $link(['view' => $currentView, 'date' => $calendar['next']]) }}"
                    class="grid size-8 place-items-center rounded-full bg-card text-muted shadow-card transition hover:bg-soft hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    aria-label="Periode berikutnya"
                >
                    <x-icon name="chevron-right" class="size-4" />
                </a>
            </div>

            <a
                href="{{ $link(['view' => $currentView, 'date' => today()->toDateString()]) }}"
                class="inline-flex h-8 items-center rounded-full bg-card px-3 text-xs font-semibold text-text shadow-card transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >Hari Ini</a>
        </div>

        <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="view" value="{{ $currentView }}">
            <input type="hidden" name="date" value="{{ request('date') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @foreach ($custom as $customKey => $customValue)
                @if (! is_array($customValue))
                    <input type="hidden" name="{{ $customKey }}" value="{{ $customValue }}">
                @endif
            @endforeach
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted">
                <x-icon name="filter" class="size-4" />
                Filter
            </span>
            @foreach ($filterOptions as $key => $option)
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-card px-3 py-1.5 text-[11px] font-medium text-text shadow-card transition hover:bg-soft">
                    <input
                        type="checkbox"
                        name="jenis[]"
                        value="{{ $key }}"
                        @if (in_array($key, $activeFilters, true)) checked @endif
                        class="size-3.5 rounded border-border text-agenda-tugas focus:ring-primary"
                    >
                    @if (($option['shape'] ?? 'titik') === 'rentang')
                        <span class="h-1.5 w-4 rounded-full {{ $toneByType[$key] ?? 'bg-muted' }}"></span>
                    @elseif (($option['shape'] ?? 'titik') === 'berulang')
                        <x-icon name="repeat" class="size-3 {{ $textByType[$key] ?? 'text-muted' }}" />
                    @else
                        <span class="size-2 rounded-full {{ $toneByType[$key] ?? 'bg-muted' }}"></span>
                    @endif
                    {{ $option['label'] }}
                </label>
            @endforeach
            <button type="submit" class="inline-flex h-8 items-center rounded-full bg-sidebar px-3 text-[11px] font-semibold text-white transition hover:bg-sidebar-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Terapkan</button>
            @if ($activeFilters !== [])
                <a href="{{ $resetLink() }}" class="text-[11px] font-semibold text-muted underline-offset-2 hover:text-text hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-border pt-3">
        <span class="text-[11px] font-semibold uppercase tracking-wide text-muted">Legenda</span>
        @foreach ($filterOptions as $key => $option)
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-text">
                @if (($option['shape'] ?? 'titik') === 'rentang')
                    <span class="h-1.5 w-4 rounded-full {{ $toneByType[$key] ?? 'bg-muted' }}"></span>
                @elseif (($option['shape'] ?? 'titik') === 'berulang')
                    <x-icon name="repeat" class="size-3.5 {{ $textByType[$key] ?? 'text-muted' }}" />
                @else
                    <span class="size-2.5 rounded-full {{ $toneByType[$key] ?? 'bg-muted' }}"></span>
                @endif
                {{ $option['label'] }}
            </span>
        @endforeach
    </div>
</div>
