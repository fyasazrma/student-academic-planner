@props([
    'items' => [],
    'label' => 'Grafik progres',
])

@php
    $warna = [
        'bg-chart-navy',
        'bg-chart-violet',
        'bg-chart-sky',
        'bg-chart-coral',
        'bg-chart-rose',
    ];
@endphp

<figure role="img" aria-label="{{ $label }}">
    @if ($items === [])
        <x-chart.empty
            title="Belum ada data progres"
            description="Angka akan muncul setelah materi pada kelas Anda tersedia."
        />
    @else
        <ol class="space-y-4">
            @foreach ($items as $index => $item)
                @php
                    $nilai = max(0, min(100, (float) ($item['value'] ?? 0)));
                    $warnaItem = $warna[((int) ($item['colorIndex'] ?? 0)) % 5];
                    $keterangan = (string) ($item['description'] ?? '');
                    $aria = ($item['label'] ?? 'Mata kuliah').': '.($item['display'] ?? number_format($nilai, 2, ',', '.').'%');
                    if ($keterangan !== '') {
                        $aria .= ', '.$keterangan;
                    }
                @endphp
                <li class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,9rem)_minmax(0,1fr)_3.5rem] sm:items-center sm:gap-3">
                    <span class="truncate text-xs font-semibold text-text" title="{{ $item['label'] ?? '' }}">
                        {{ $item['label'] ?? 'Mata kuliah' }}
                    </span>

                    <div
                        class="group relative rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        tabindex="0"
                        role="img"
                        aria-label="{{ $aria }}"
                    >
                        <div class="h-3 rounded-full bg-chart-track">
                            <div
                                class="chart-bar h-3 rounded-full {{ $warnaItem }}"
                                style="width: {{ $nilai }}%; animation-delay: {{ $index * 60 }}ms;"
                            ></div>
                        </div>
                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded-control bg-sidebar px-3 py-2 text-xs text-white opacity-0 shadow-card transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100"
                            aria-hidden="true"
                        >
                            <span class="font-bold">{{ $item['display'] ?? number_format($nilai, 2, ',', '.').'%' }}</span>
                            @if ($keterangan !== '')
                                <span class="ml-2 text-white/80">{{ $keterangan }}</span>
                            @endif
                        </div>
                    </div>

                    <span class="text-xs font-bold text-chart-navy sm:text-right">
                        {{ $item['display'] ?? number_format($nilai, 2, ',', '.').'%' }}
                    </span>
                </li>
            @endforeach
        </ol>

        <figcaption class="sr-only">
            {{ $label }}:
            @foreach ($items as $item)
                {{ $item['label'] ?? 'Mata kuliah' }} {{ $item['display'] ?? '' }}@if (! $loop->last); @endif
            @endforeach
        </figcaption>
    @endif
</figure>
