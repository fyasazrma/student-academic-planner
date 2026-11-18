<x-app-layout title="Kalender Akademik" :user="$user" :unread-count="$unreadCount">
    <x-page-header
        title="Kalender Akademik"
        eyebrow="Akademik mahasiswa"
        description="Lihat seluruh agenda, jadwal kuliah, tugas, dan ujian dari kelas yang Anda ikuti dalam satu tampilan."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text">
                <x-icon name="calendar" class="h-4 w-4 text-agenda-tugas" />
                {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        <x-mahasiswa.calendar-panel
            :calendar="$calendar"
            :timeline="$timeline"
            :current-view="$currentView"
            :active-filters="$activeFilters"
            :filter-options="$filterOptions"
            :today="$today"
            route-name="mahasiswa.kalender"
        />
    </div>
</x-app-layout>
