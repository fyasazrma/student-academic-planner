@props([
    'event' => [],
])

@php
    $dots = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-uts',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
    ];
@endphp

<button
    type="button"
    data-agenda-trigger="{{ $event['id'] }}"
    class="flex w-full items-center gap-1.5 rounded-md bg-soft px-1.5 py-1 text-left text-[10px] font-semibold leading-tight text-text transition hover:bg-card hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas sm:text-[11px]"
    title="{{ $event['label'] }}: {{ $event['judul'] }}"
>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dots[$event['jenis']] ?? 'bg-muted' }}"></span>
    <span class="truncate">
        <span class="sr-only">{{ $event['label'] }}: </span>{{ $event['judul'] }}
    </span>
</button>
