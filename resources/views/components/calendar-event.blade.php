@props([
    'event' => [],
])

@php
    $tones = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-uts',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
        'materi' => 'bg-agenda-materi',
    ];
    $textTones = [
        'tugas' => 'text-agenda-tugas',
        'deadline' => 'text-agenda-terlambat',
        'ujian' => 'text-agenda-uts',
        'kuliah' => 'text-agenda-kuliah',
        'akademik' => 'text-agenda-akademik',
        'pengumuman' => 'text-agenda-pengumuman',
        'materi' => 'text-agenda-materi',
    ];
@endphp

<button
    type="button"
    data-agenda-trigger="{{ $event['id'] }}"
    class="flex w-full items-center gap-1.5 rounded-md bg-soft px-1.5 py-1 text-left text-[10px] font-semibold leading-tight text-text transition hover:bg-card hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas sm:text-[11px]"
    title="{{ $event['label'] }}: {{ $event['judul'] }} ({{ $event['tanggal_label'] }})"
>
    @if (($event['kind'] ?? 'point') === 'recurring')
        <x-icon name="repeat" class="h-3 w-3 shrink-0 {{ $textTones[$event['jenis']] ?? 'text-muted' }}" />
    @elseif (($event['kind'] ?? 'point') === 'range')
        <span class="h-1 w-3 shrink-0 rounded-full {{ $tones[$event['jenis']] ?? 'bg-muted' }}"></span>
    @else
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $tones[$event['jenis']] ?? 'bg-muted' }}"></span>
    @endif
    <span class="truncate">
        <span class="sr-only">{{ $event['label'] }}: </span>{{ $event['judul'] }}
    </span>
</button>
