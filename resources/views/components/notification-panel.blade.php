@props([
    'notifications' => collect(),
    'deadlines' => [],
    'nextSchedules' => [],
    'unreadCount' => 0,
])

@php
    $toneByType = [
        'tugas' => 'bg-agenda-tugas',
        'deadline' => 'bg-agenda-terlambat',
        'ujian' => 'bg-agenda-ujian',
        'uts' => 'bg-agenda-uts',
        'uas' => 'bg-agenda-uas',
        'kuliah' => 'bg-agenda-kuliah',
        'akademik' => 'bg-agenda-akademik',
        'pengumuman' => 'bg-agenda-pengumuman',
    ];
@endphp

<aside id="panel-notifikasi" class="space-y-4">
    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-icon name="bell" class="h-5 w-5 text-agenda-tugas" />
                <h3 class="text-sm font-bold text-text">Notifikasi terbaru</h3>
            </div>
            @if ($unreadCount > 0)
                <x-status-badge :label="$unreadCount.' belum dibaca'" tone="deadline" />
            @endif
        </div>

        <a
            href="{{ route('mahasiswa.notifikasi') }}"
            class="mt-3 inline-flex text-[11px] font-semibold text-agenda-tugas underline-offset-2 transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >Lihat semua notifikasi</a>

        <div class="mt-4 space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $link = $notification->tautanInternal();
                @endphp
                <article class="rounded-xl border border-border bg-soft p-3">
                    <div class="flex items-start gap-2">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->sudahDibaca() ? 'bg-border' : 'bg-agenda-tugas' }}"></span>
                        <div class="min-w-0 flex-1">
                            @if ($link !== null)
                                <a href="{{ $link }}" class="text-xs font-bold text-text hover:text-agenda-tugas hover:underline">{{ $notification->judul }}</a>
                            @else
                                <p class="text-xs font-bold text-text">{{ $notification->judul }}</p>
                            @endif
                            <p class="mt-1 line-clamp-2 text-[11px] leading-4 text-muted">{{ $notification->pesan }}</p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-muted">
                                <span class="uppercase tracking-wide">{{ $notification->tipe }}</span>
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state title="Belum ada notifikasi" description="Notifikasi akademik akan muncul di sini." icon="bell" class="border-0 bg-transparent px-0 py-6" />
            @endforelse
        </div>
    </section>

    <section id="tugas-terdekat" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-center gap-2">
            <x-icon name="clipboard" class="h-5 w-5 text-agenda-terlambat" />
            <h3 class="text-sm font-bold text-text">Deadline terdekat</h3>
        </div>

        <a
            href="{{ route('mahasiswa.tugas') }}"
            class="mt-3 inline-flex text-[11px] font-semibold text-agenda-tugas underline-offset-2 transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >Lihat semua tugas</a>

        <div class="mt-4 space-y-3">
            @forelse ($deadlines as $task)
                <article class="rounded-xl border border-border p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-text">{{ $task['judul'] }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-muted">{{ $task['mata_kuliah'] }}</p>
                        </div>
                        <x-status-badge
                            :label="$task['status_label']"
                            :tone="$task['terlambat'] ? 'deadline' : ($task['mendekati_deadline'] ? 'peringatan' : 'tugas')"
                        />
                    </div>
                    <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-medium text-text">
                        <x-icon name="clock" class="h-3.5 w-3.5 text-muted" />
                        {{ $task['deadline_label'] }}
                    </p>
                </article>
            @empty
                <x-empty-state title="Tidak ada deadline aktif" description="Tugas dengan deadline mendatang akan ditampilkan di sini." icon="check" class="border-0 bg-transparent px-0 py-6" />
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-center gap-2">
            <x-icon name="calendar" class="h-5 w-5 text-agenda-kuliah" />
            <h3 class="text-sm font-bold text-text">Jadwal berikutnya</h3>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($nextSchedules as $schedule)
                <article class="flex gap-3 rounded-xl border border-border p-3">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $toneByType[$schedule['jenis']] ?? 'bg-muted' }}"></span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-xs font-bold text-text">{{ $schedule['judul'] }}</p>
                            <x-status-badge :label="$schedule['label']" :tone="$schedule['jenis']" />
                        </div>
                        <p class="mt-1 text-[11px] font-medium text-text">{{ $schedule['tanggal_label'] }} · {{ $schedule['waktu_label'] ?? 'Waktu belum tersedia' }}</p>
                        @if ($schedule['lokasi'])
                            <p class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-muted">
                                <x-icon name="map-pin" class="h-3 w-3" />
                                {{ $schedule['lokasi'] }}
                            </p>
                        @endif
                    </div>
                </article>
            @empty
                <x-empty-state title="Belum ada jadwal" description="Jadwal kuliah dan ujian akan muncul di sini." icon="calendar" class="border-0 bg-transparent px-0 py-6" />
            @endforelse
        </div>
    </section>
</aside>
