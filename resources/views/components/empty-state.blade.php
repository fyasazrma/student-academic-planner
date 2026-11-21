@props([
    'title' => 'Belum ada data',
    'description' => '',
    'icon' => 'info',
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-card text-center '.($compact ? 'bg-soft px-4 py-4' : 'bg-soft px-5 py-10')]) }}>
    <div class="grid {{ $compact ? 'size-8' : 'size-11' }} shrink-0 place-items-center rounded-tile bg-card text-muted shadow-card">
        <x-icon :name="$icon" class="{{ $compact ? 'size-4' : 'size-5' }}" />
    </div>
    <p class="{{ $compact ? 'mt-2' : 'mt-4' }} text-sm font-semibold text-text">{{ $title }}</p>
    @if ($description !== '')
        <p class="{{ $compact ? 'mt-1 leading-4' : 'mt-1 leading-5' }} max-w-sm text-xs text-muted">{{ $description }}</p>
    @endif
    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
