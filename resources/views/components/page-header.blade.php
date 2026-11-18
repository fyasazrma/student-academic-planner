@props([
    'title' => '',
    'eyebrow' => 'Dashboard',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-agenda-tugas">{{ $eyebrow }}</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ $title }}</h2>
        @if ($description !== '')
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
