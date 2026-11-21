@props([
    'eyebrow' => '',
    'title' => '',
    'description' => '',
    'align' => 'left',
    'inverted' => false,
])

<div data-reveal-group @class([
    'max-w-3xl',
    'mx-auto text-center' => $align === 'center',
])>
    @if ($eyebrow !== '')
        <p data-reveal @class([
            'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.14em]',
            'bg-primary text-primary-text' => $inverted,
            'bg-tile-lime text-primary-text' => ! $inverted,
        ])>{{ $eyebrow }}</p>
    @endif

    <h2 data-reveal @class([
        'mt-5 text-3xl font-bold leading-tight tracking-tight sm:text-4xl',
        'text-white' => $inverted,
        'text-text' => ! $inverted,
    ])>{{ $title }}</h2>

    @if ($description !== '')
        <p data-reveal @class([
            'mt-4 text-base leading-7',
            'text-white/75' => $inverted,
            'text-muted' => ! $inverted,
        ])>{{ $description }}</p>
    @endif
</div>
