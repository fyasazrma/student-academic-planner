@props([
    'title' => 'Belum ada data',
    'description' => '',
])

<figure
    {{ $attributes->merge(['class' => 'flex min-h-20 max-h-[120px] flex-col items-center justify-center rounded-control bg-soft px-4 py-4 text-center']) }}
    role="img"
    aria-label="{{ $title }}"
>
    <x-icon name="info" class="size-5 shrink-0 text-muted" />
    <figcaption class="mt-2 text-xs font-semibold text-text">{{ $title }}</figcaption>
    @if ($description !== '')
        <p class="mt-1 text-[11px] leading-4 text-muted">{{ $description }}</p>
    @endif
</figure>
