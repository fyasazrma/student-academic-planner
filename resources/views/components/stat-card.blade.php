@props([
    'label' => '',
    'value' => 0,
    'hint' => '',
    'icon' => 'info',
    'tone' => 'tugas',
])

@php
    $tones = [
        'tugas' => 'bg-agenda-tugas/12 text-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat/12 text-agenda-terlambat',
        'ujian' => 'bg-agenda-uts/15 text-agenda-uts',
        'kuliah' => 'bg-agenda-kuliah/15 text-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik/15 text-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman/15 text-agenda-pengumuman',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5']) }}>
    <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['tugas'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold leading-none text-text">{{ $value }}</p>
            @if ($hint !== '')
                <p class="mt-2 text-[11px] leading-4 text-muted">{{ $hint }}</p>
            @endif
        </div>
    </div>
</div>
