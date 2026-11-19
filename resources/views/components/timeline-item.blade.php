@props([
    'bar' => [],
])

@php
    $barTone = match ($bar['status'] ?? null) {
        'terlambat' => 'bg-agenda-terlambat',
        'dinilai', 'terkumpul' => 'bg-success',
        'mendekati_deadline' => 'bg-agenda-pengumuman',
        default => 'bg-agenda-tugas',
    };
@endphp

<div
    class="rounded-xl border border-border bg-card p-4 transition hover:border-agenda-tugas/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
    role="button"
    tabindex="0"
    data-agenda-trigger="{{ $bar['event_id'] }}"
    aria-label="Lihat detail tugas {{ $bar['judul'] }}"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-bold text-text">{{ $bar['judul'] }}</p>
                <x-status-badge
                    :label="$bar['status_label']"
                    :tone="$bar['status'] === 'terlambat' ? 'deadline' : ($bar['status'] === 'dinilai' || $bar['status'] === 'terkumpul' ? 'selesai' : ($bar['status'] === 'mendekati_deadline' ? 'peringatan' : 'tugas'))"
                />
            </div>
            <p class="mt-1 text-xs text-muted">{{ $bar['mata_kuliah'] }}</p>
        </div>
        <p class="shrink-0 text-[11px] font-medium text-muted">{{ $bar['dipublikasikan_label'] }} → {{ $bar['deadline_label'] }}</p>
    </div>

    <div class="relative mt-5 h-12">
        <div class="absolute inset-x-0 top-5 h-2 rounded-full bg-soft"></div>
        <div class="absolute top-5 h-2 rounded-full {{ $barTone }}" style="left: {{ $bar['bar_left'] }}%; width: {{ $bar['bar_width'] }}%;"></div>
        <div class="absolute top-[14px] h-4 w-4 -translate-x-1/2 rounded-full border-2 border-card bg-agenda-tugas shadow" style="left: {{ $bar['bar_left'] }}%;" title="Dipublikasikan: {{ $bar['dipublikasikan_label'] }}"></div>
        <div class="absolute top-[14px] h-4 w-4 -translate-x-1/2 rotate-45 border-2 border-card bg-agenda-terlambat shadow" style="left: {{ $bar['deadline_marker'] }}%;" title="Deadline: {{ $bar['deadline_label'] }}"></div>
    </div>

    <div class="mt-1 flex justify-between text-[10px] font-semibold text-muted">
        <span>● Publikasi</span>
        <span>◆ Deadline</span>
    </div>
</div>
