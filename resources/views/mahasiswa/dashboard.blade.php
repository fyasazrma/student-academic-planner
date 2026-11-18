<x-app-layout title="Dashboard Mahasiswa" :user="$user" :unread-count="$unreadCount">
    @php
        $jam = (int) now()->format('H');
        $sapaan = match (true) {
            $jam < 11 => 'Selamat pagi',
            $jam < 15 => 'Selamat siang',
            $jam < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            :title="$sapaan.', '.($user->name ?? 'Mahasiswa')"
            eyebrow="Dashboard mahasiswa"
            :description="'Ringkasan aktivitas akademik Anda. '.$today->translatedFormat('l, d F Y').'.'"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 font-semibold text-text">
                        <x-icon name="calendar" class="h-4 w-4 text-agenda-tugas" />
                        {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
                    </span>
                    @if ($user->nim)
                        <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 font-semibold text-text">
                            <x-icon name="user" class="h-4 w-4 text-agenda-tugas" />
                            NIM {{ $user->nim }}
                        </span>
                    @endif
                </div>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan akademik" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
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
                :value="$stats['progres_persen'].'%'"
                hint="Materi selesai dari kelas Anda"
                icon="book"
                tone="kuliah"
            />
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <x-mahasiswa.calendar-panel
                :calendar="$calendar"
                :timeline="$timeline"
                :current-view="$currentView"
                :active-filters="$activeFilters"
                :filter-options="$filterOptions"
                :today="$today"
                route-name="mahasiswa.dashboard"
            />

            <x-notification-panel
                :notifications="$notifications"
                :deadlines="$deadlines"
                :next-schedules="$nextSchedules"
                :unread-count="$unreadCount"
            />
        </div>
    </div>
</x-app-layout>
