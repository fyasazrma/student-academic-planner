@props([
    'value' => 0,
    'label' => 'Progres',
    'size' => 40,
    'stroke' => 4,
    'caption' => '',
])

@php
    $persen = max(0, min(100, (float) $value));
    $radius = 16;
    $keliling = 2 * pi() * $radius;
    $offset = $keliling * (1 - $persen / 100);
    $labelAngka = number_format($persen, 2, ',', '.');
@endphp

<figure
    class="inline-flex items-center gap-2"
    role="img"
    aria-label="{{ $label }}: {{ $labelAngka }}%"
>
    <span class="relative inline-grid shrink-0 place-items-center" style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;">
        <svg
            class="-rotate-90"
            viewBox="0 0 40 40"
            style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;"
            aria-hidden="true"
        >
            <circle cx="20" cy="20" r="{{ $radius }}" fill="none" class="stroke-chart-track" stroke-width="{{ (int) $stroke }}" />
            <circle
                cx="20"
                cy="20"
                r="{{ $radius }}"
                fill="none"
                class="chart-ring stroke-chart-lime"
                stroke-width="{{ (int) $stroke }}"
                stroke-linecap="round"
                stroke-dasharray="{{ $keliling }}"
                stroke-dashoffset="{{ $offset }}"
            />
        </svg>
        <span class="absolute text-[10px] font-bold text-chart-navy">{{ (int) round($persen) }}%</span>
    </span>
    @if ($caption !== '')
        <figcaption class="text-xs font-semibold text-text">{{ $caption }}</figcaption>
    @endif
    <span class="sr-only">{{ $label }}: {{ $labelAngka }}%</span>
</figure>
