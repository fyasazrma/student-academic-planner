<x-app-layout title="Dashboard Mahasiswa" :user="$user" :unread-count="$unreadCount">
    @php
        $jam = (int) now()->format('H');
        $sapaan = match (true) {
            $jam < 11 => 'Selamat pagi',
            $jam < 15 => 'Selamat siang',
            $jam < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $persenProgres = max(0, min(100, (float) ($progresOtomatis['persentase'] ?? 0)));
        $lingkaranProgres = 2 * pi() * 16;
        $offsetProgres = $lingkaranProgres * (1 - $persenProgres / 100);
    @endphp

    <div class="space-y-5 ui-stagger">
        <x-page-header
            :title="$sapaan.', '.($user->name ?? 'Mahasiswa').' 👋'"
            eyebrow="Dashboard mahasiswa"
            :description="'Ringkasan aktivitas akademik Anda. '.$today->translatedFormat('l, d F Y').'.'"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex h-10 items-center gap-2 rounded-full bg-card px-4 text-xs font-semibold text-text shadow-card">
                        <x-icon name="calendar" class="size-4 text-agenda-tugas" />
                        {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
                    </span>
                    @if ($user->nim)
                        <span class="inline-flex h-10 items-center gap-2 rounded-full bg-card px-4 text-xs font-semibold text-text shadow-card">
                            <x-icon name="user" class="size-4 text-agenda-tugas" />
                            NIM {{ $user->nim }}
                        </span>
                    @endif
                </div>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan akademik" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                label="Tugas belum dikumpulkan"
                :value="$stats['belum_dikumpulkan']"
                :hint="($stats['belum_dikumpulkan'] === 0 ? 'Semua tugas sudah dikumpulkan' : 'Perlu ditindaklanjuti')"
                icon="clipboard"
                tone="tugas"
            />
            <x-stat-card
                label="Mendekati deadline"
                :value="$stats['mendekati_deadline']"
                hint="Dalam 7 hari ke depan"
                icon="clock"
                tone="deadline"
            />
            <x-stat-card
                label="Ujian mendatang"
                :value="$stats['ujian_mendatang']"
                hint="Agenda ujian yang dipublikasikan"
                icon="exam"
                tone="ujian"
            />
            <x-stat-card
                :label="'Progres materi ('.$stats['progres_selesai'].'/'.$stats['progres_total'].')'"
                :value="number_format((float) $stats['progres_persen'], 2, ',', '.').'%'"
                hint="Materi selesai dari kelas Anda"
                icon="book"
                tone="kuliah"
            >
                <x-slot:visual>
                    <x-chart.donut
                        :value="$persenProgres"
                        label="Progres materi"
                        :size="36"
                        :stroke="4"
                    />
                </x-slot:visual>
            </x-stat-card>
        </section>

        <x-mahasiswa.calendar-panel
            :calendar="$calendar"
            :timeline="$timeline"
            :current-view="$currentView"
            :active-filters="$activeFilters"
            :filter-options="$filterOptions"
            :today="$today"
            route-name="mahasiswa.dashboard"
        />

        <section aria-label="Ringkasan belajar" class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <article class="rounded-card bg-card p-5 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Target belajar</p>
                        <p class="mt-2 text-sm font-bold text-text">{{ $targetRingkasan['aktif'] }} target aktif · {{ $targetRingkasan['selesai'] }} selesai</p>
                        @if ($targetRingkasan['terlambat'] > 0)
                            <p class="mt-1 text-[11px] font-semibold text-agenda-terlambat">{{ $targetRingkasan['terlambat'] }} target terlambat</p>
                        @endif
                    </div>
                    <a href="{{ route('mahasiswa.target.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-full bg-soft px-3 text-[11px] font-semibold text-text transition hover:bg-border focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        Kelola <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                    </a>
                </div>
                @if (count($targetRingkasan['terdekat']) > 0)
                    <ul class="mt-4 space-y-2">
                        @foreach ($targetRingkasan['terdekat'] as $target)
                            <li class="flex items-center justify-between gap-2 text-xs">
                                <a href="{{ route('mahasiswa.target.show', $target['id']) }}" class="min-w-0 truncate font-semibold text-text transition hover:text-agenda-tugas focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                    {{ $target['judul'] }}
                                </a>
                                <span class="shrink-0 text-[11px] text-muted">{{ $target['deadline_label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-xs text-muted">Belum ada target dengan deadline dalam 7 hari ke depan.</p>
                @endif
            </article>

            <article class="rounded-card bg-card p-5 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Progres materi otomatis</p>
                        <p class="mt-2 text-sm font-bold text-text">{{ $progresOtomatis['selesai'] }}/{{ $progresOtomatis['total'] }} materi selesai</p>
                        <p class="mt-1 text-[11px] font-semibold text-muted">{{ number_format($progresOtomatis['persentase'], 2, ',', '.') }}% dari seluruh kelas aktif</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative size-10 shrink-0">
                            <svg class="size-10 -rotate-90" viewBox="0 0 40 40" aria-hidden="true">
                                <circle cx="20" cy="20" r="16" fill="none" stroke="currentColor" class="text-border" stroke-width="4" />
                                <circle
                                    cx="20"
                                    cy="20"
                                    r="16"
                                    fill="none"
                                    stroke="currentColor"
                                    class="text-progress transition-[stroke-dashoffset] duration-500"
                                    stroke-width="4"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $lingkaranProgres }}"
                                    stroke-dashoffset="{{ $offsetProgres }}"
                                />
                            </svg>
                            <span class="absolute inset-0 grid place-items-center text-[10px] font-bold text-text">{{ (int) round($persenProgres) }}%</span>
                        </div>
                        <a href="{{ route('mahasiswa.progres-materi.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-full bg-soft px-3 text-[11px] font-semibold text-text transition hover:bg-border focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            Detail <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
                @if (count($progresOtomatis['belum_selesai']) > 0)
                    <ul class="mt-4 space-y-2">
                        @foreach ($progresOtomatis['belum_selesai'] as $materi)
                            <li>
                                <a href="{{ route('mahasiswa.materi.show', [$materi['kelas_id'], $materi['id']]) }}" class="flex items-center gap-2 rounded-control bg-surface px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    <x-icon name="file" class="h-3.5 w-3.5 shrink-0 text-agenda-tugas" />
                                    <span class="min-w-0 truncate">{{ $materi['judul'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-xs text-muted">Belum ada materi yang perlu diselesaikan.</p>
                @endif
            </article>
        </section>
    </div>

    <x-slot:rightPanel>
        <x-notification-panel
            :notifications="$notifications"
            :deadlines="$deadlines"
            :next-schedules="$nextSchedules"
            :unread-count="$unreadCount"
        />
    </x-slot:rightPanel>
</x-app-layout>
