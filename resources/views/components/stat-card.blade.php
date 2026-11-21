@props([
    'label' => '',
    'value' => 0,
    'hint' => '',
    'icon' => 'info',
    'tone' => 'tugas',
])

@php
    $tones = [
        'tugas' => 'bg-tile-lavender text-sidebar',
        'deadline' => 'bg-tile-peach text-badge-text',
        'ujian' => 'bg-tile-sky text-sidebar',
        'uts' => 'bg-tile-sky text-sidebar',
        'uas' => 'bg-tile-sky text-sidebar',
        'kuliah' => 'bg-tile-lime text-primary-text',
        'akademik' => 'bg-tile-rose text-badge-text',
        'pengumuman' => 'bg-tile-sand text-badge-text',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card bg-card p-5 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-start gap-3">
            <div class="grid size-11 shrink-0 place-items-center rounded-tile {{ $tones[$tone] ?? $tones['tugas'] }}">
                <x-icon :name="$icon" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium text-muted">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold leading-none text-text">{{ $value }}</p>
                @if ($hint !== '')
                    <p class="mt-2 text-[11px] leading-4 text-muted">{{ $hint }}</p>
                @endif
            </div>
        </div>
        @if (isset($visual) && trim((string) $visual) !== '')
            <div class="shrink-0">{{ $visual }}</div>
        @endif
    </div>
</div>
