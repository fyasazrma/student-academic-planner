@props([
    'title' => 'Belum ada data',
    'description' => '',
    'icon' => 'info',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-soft px-5 py-10 text-center']) }}>
    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-card text-muted shadow-sm">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>
    <p class="mt-4 text-sm font-semibold text-text">{{ $title }}</p>
    @if ($description !== '')
        <p class="mt-1 max-w-sm text-xs leading-5 text-muted">{{ $description }}</p>
    @endif
    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
