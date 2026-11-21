@props([
    'class' => 'w-full',
])

@php
    $toneClasses = [
        'tugas' => 'bg-tile-lavender text-text',
        'kuliah' => 'bg-tile-lime text-primary-text',
        'uts' => 'bg-tile-sky text-text',
        'uas' => 'bg-tile-sky text-text',
        'pengumuman' => 'bg-tile-sand text-text',
        'deadline' => 'bg-tile-rose text-text',
    ];
    $dotClasses = [
        'tugas' => 'bg-agenda-tugas',
        'kuliah' => 'bg-agenda-kuliah',
        'uts' => 'bg-agenda-uts',
        'uas' => 'bg-agenda-uas',
        'pengumuman' => 'bg-agenda-pengumuman',
        'deadline' => 'bg-agenda-terlambat',
    ];
    $events = [
        3 => ['label' => 'Tugas', 'tone' => 'tugas'],
        6 => ['label' => 'Kuliah', 'tone' => 'kuliah'],
        8 => ['label' => 'Kuliah', 'tone' => 'kuliah'],
        10 => ['label' => 'Deadline', 'tone' => 'deadline'],
        13 => ['label' => 'Kuliah', 'tone' => 'kuliah'],
        14 => ['label' => 'UTS', 'tone' => 'uts'],
        15 => ['label' => 'Kuliah', 'tone' => 'kuliah'],
        20 => ['label' => 'Pengumuman', 'tone' => 'pengumuman'],
        22 => ['label' => 'Kuliah', 'tone' => 'kuliah'],
        28 => ['label' => 'UAS', 'tone' => 'uas'],
    ];
    $cells = array_pad(array_merge(array_fill(0, 3, null), range(1, 31)), 35, null);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-panel bg-card p-4 shadow-card sm:p-6 '.$class]) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-muted">Calendar View</p>
            <p class="mt-1 text-lg font-bold text-text">Oktober 2026</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-soft px-3 py-1.5 text-[11px] font-semibold text-muted">Contoh tampilan</span>
            <span class="grid size-8 place-items-center rounded-full bg-soft text-text" aria-hidden="true"><x-icon name="chevron-left" class="size-4" /></span>
            <span class="grid size-8 place-items-center rounded-full bg-soft text-text" aria-hidden="true"><x-icon name="chevron-right" class="size-4" /></span>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wide text-muted sm:gap-2 sm:text-xs">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
            <span>{{ $day }}</span>
        @endforeach
    </div>

    <div class="mt-2 grid grid-cols-7 gap-1 sm:gap-2">
        @foreach ($cells as $day)
            <div class="min-w-0 rounded-control bg-soft/60 p-1 sm:p-1.5">
                @if ($day !== null)
                    <span @class([
                        'grid size-5 place-items-center rounded-full text-[10px] font-bold sm:size-6 sm:text-xs',
                        'bg-primary text-primary-text' => $day === 16,
                        'text-text' => $day !== 16,
                    ])>{{ $day }}</span>

                    @if (isset($events[$day]))
                        @php($event = $events[$day])
                        <span @class([
                            'mt-1 flex items-center gap-1 rounded-full px-1 py-0.5 text-[9px] font-semibold sm:px-1.5 sm:text-[10px]',
                            $toneClasses[$event['tone']],
                        ])>
                            <span @class(['size-1.5 shrink-0 rounded-full', $dotClasses[$event['tone']]])></span>
                            <span class="hidden truncate sm:block">{{ $event['label'] }}</span>
                        </span>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 border-t border-border pt-4 text-[11px] font-medium text-muted">
        @foreach ([
            ['Tugas', 'bg-agenda-tugas'],
            ['Kuliah', 'bg-agenda-kuliah'],
            ['UTS / UAS', 'bg-agenda-uts'],
            ['Pengumuman', 'bg-agenda-pengumuman'],
        ] as $legend)
            <span class="inline-flex items-center gap-1.5">
                <span @class(['size-2 rounded-full', $legend[1]])></span>{{ $legend[0] }}
            </span>
        @endforeach
    </div>
</div>
