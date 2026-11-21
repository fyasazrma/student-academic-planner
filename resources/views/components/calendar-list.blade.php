@props([
    'events' => [],
    'emptyTitle' => 'Tidak ada agenda',
    'emptyDescription' => 'Tidak ada agenda pada periode ini untuk akun Anda.',
])

@php
    $toneByJenis = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-ujian',
        'uts' => 'bg-agenda-uts',
        'uas' => 'bg-agenda-uas',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
        'materi' => 'bg-agenda-materi',
    ];
@endphp

@if (count($events) === 0)
    <x-empty-state :title="$emptyTitle" :description="$emptyDescription" icon="calendar" />
@else
    <div class="space-y-2">
        @foreach ($events as $event)
            <button
                type="button"
                data-agenda-trigger="{{ $event['id'] }}"
                class="flex w-full items-start gap-3 rounded-control bg-soft p-3 text-left transition hover:bg-card hover:shadow-card focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                <span class="mt-1 grid size-4 shrink-0 place-items-center {{ $toneByJenis[$event['jenis']] ?? 'bg-muted' }} rounded-full">
                    @if (($event['kind'] ?? 'point') === 'recurring')
                        <x-icon name="repeat" class="size-3 text-white" />
                    @elseif (($event['kind'] ?? 'point') === 'range')
                        <span class="h-0.5 w-2 rounded-full bg-white"></span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-muted">
                        {{ $event['label'] }} · {{ $event['tanggal_label'] }}
                        @if (($event['kind'] ?? 'point') === 'recurring')
                            · Berulang
                        @elseif (($event['kind'] ?? 'point') === 'range')
                            · Rentang
                        @endif
                    </span>
                    <span class="mt-0.5 block text-sm font-semibold text-text">{{ $event['judul'] }}</span>
                    <span class="mt-0.5 block text-[11px] text-muted">{{ $event['mata_kuliah'] }}</span>
                </span>
            </button>
        @endforeach
    </div>
@endif
