@props([
    'title' => '',
    'eyebrow' => '',
    'description' => '',
])

@php
    // Label kecil hanya dipakai bila membawa konteks data nyata, misalnya kode kelas atau pertemuan.
    $showEyebrow = is_string($eyebrow)
        && $eyebrow !== ''
        && (str_contains($eyebrow, '—') || preg_match('/\d/', $eyebrow) === 1);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between sm:gap-6']) }}>
    <div class="min-w-0">
        @if ($showEyebrow)
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-agenda-tugas">{{ $eyebrow }}</p>
        @endif
        <h1 @class([
            'text-2xl font-bold tracking-tight text-text lg:text-3xl',
            'mt-2' => $showEyebrow,
        ])>{{ $title }}</h1>
        @if ($description !== '')
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
