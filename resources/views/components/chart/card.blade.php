@props([
    'title' => '',
    'subtitle' => '',
])

<section {{ $attributes->merge(['class' => 'rounded-card bg-card p-5 shadow-card']) }}>
    @if ($title !== '')
        <header class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-text">{{ $title }}</h3>
                @if ($subtitle !== '')
                    <p class="mt-1 text-xs text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($action) && trim((string) $action) !== '')
                <div class="shrink-0">{{ $action }}</div>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
