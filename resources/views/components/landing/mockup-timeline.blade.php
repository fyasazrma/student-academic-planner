@props([
    'class' => 'w-full',
])

@php
    $tasks = [
        ['title' => 'Laporan Praktikum', 'course' => 'Praktikum Database', 'left' => '0%', 'width' => '28%', 'tone' => 'bg-tile-lavender', 'status' => 'Sedang dikerjakan', 'pub' => '3 Okt', 'due' => '10 Okt'],
        ['title' => 'Ringkasan Materi', 'course' => 'Sistem Informasi', 'left' => '20%', 'width' => '36%', 'tone' => 'bg-soft', 'status' => 'Belum dikerjakan', 'pub' => '8 Okt', 'due' => '17 Okt'],
        ['title' => 'Latihan Soal UTS', 'course' => 'Algoritma', 'left' => '8%', 'width' => '28%', 'tone' => 'bg-tile-lime', 'status' => 'Sudah dikumpulkan', 'pub' => '5 Okt', 'due' => '12 Okt'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-panel bg-card p-4 shadow-card sm:p-6 '.$class]) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-muted">Timeline View</p>
            <p class="mt-1 text-lg font-bold text-text">Rentang tugas &amp; ujian</p>
        </div>
        <span class="rounded-full bg-soft px-3 py-1.5 text-[11px] font-semibold text-muted">Contoh tampilan</span>
    </div>

    <div class="mt-6 hidden sm:block">
        <div class="relative h-5 text-[10px] font-semibold text-muted">
            @foreach ([['0%', '3 Okt'], ['16%', '7 Okt'], ['28%', '10 Okt'], ['44%', '14 Okt'], ['56%', '17 Okt'], ['72%', '21 Okt'], ['84%', '24 Okt']] as $tick)
                <span class="absolute -translate-x-1/2" style="left: {{ $tick[0] }}">{{ $tick[1] }}</span>
            @endforeach
            <span class="absolute right-0">28 Okt</span>
        </div>

        <div class="relative mt-2 border-t border-border pt-3">
            @foreach ($tasks as $task)
                <div class="relative mb-3 h-12 rounded-control bg-soft/60">
                    <div @class(['absolute top-1.5 flex h-9 items-center rounded-full px-3', $task['tone']]) style="left: {{ $task['left'] }}; width: {{ $task['width'] }}">
                        <span class="truncate text-[11px] font-bold text-text">{{ $task['title'] }}</span>
                    </div>
                    <span class="absolute top-[15px] size-3 rounded-full border-2 border-primary bg-card" style="left: {{ $task['left'] }}" title="Dipublikasikan {{ $task['pub'] }}"></span>
                    <span class="absolute top-[15px] size-3 rounded-full border-2 border-text bg-card" style="left: calc({{ $task['left'] }} + {{ $task['width'] }} - 12px)" title="Deadline {{ $task['due'] }}"></span>
                </div>
            @endforeach

            <div class="relative h-10 border-t border-dashed border-border pt-2">
                <span class="absolute top-0 flex -translate-x-1/2 items-center gap-1 rounded-full bg-tile-sky px-2 py-1 text-[10px] font-bold text-text" style="left: 44%">
                    <span class="size-1.5 rounded-full bg-agenda-uts"></span>UTS
                </span>
                <span class="absolute right-0 top-0 flex items-center gap-1 rounded-full bg-tile-sky px-2 py-1 text-[10px] font-bold text-text">
                    <span class="size-1.5 rounded-full bg-agenda-uas"></span>UAS
                </span>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-[11px] font-medium text-muted">
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full border-2 border-primary bg-card"></span>Titik publikasi</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full border-2 border-text bg-card"></span>Titik deadline</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-agenda-uts"></span>Ujian (titik)</span>
        </div>
    </div>

    <div class="mt-4 space-y-3 sm:hidden">
        @foreach ($tasks as $task)
            <div @class(['rounded-control p-3', $task['tone']])>
                <p class="text-sm font-bold text-text">{{ $task['title'] }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ $task['course'] }} · {{ $task['status'] }}</p>
                <p class="mt-2 text-[11px] font-semibold text-text">Dipublikasikan {{ $task['pub'] }} · Deadline {{ $task['due'] }}</p>
            </div>
        @endforeach
        <div class="grid grid-cols-2 gap-2">
            <div class="rounded-control bg-tile-sky p-3 text-xs font-bold text-text">UTS · 14 Okt</div>
            <div class="rounded-control bg-tile-sky p-3 text-xs font-bold text-text">UAS · 28 Okt</div>
        </div>
    </div>
</div>
