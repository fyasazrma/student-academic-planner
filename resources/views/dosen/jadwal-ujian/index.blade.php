<x-app-layout title="Jadwal Ujian" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Jadwal Ujian UTS & UAS"
            eyebrow="Kalender akademik"
            description="Jadwal ujian institusional dan ujian pada kelas yang Anda ampu. Seluruh perubahan jadwal dilakukan oleh administrator."
        >
            <x-slot:actions>
                <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text">
                    <x-icon name="calendar" class="h-4 w-4 text-agenda-uts" />
                    {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
                </span>
            </x-slot:actions>
        </x-page-header>

        <x-dosen.calendar-panel
            :calendar="$calendar"
            :timeline="$timeline"
            :current-view="$currentView"
            :active-filters="$activeFilters"
            :filter-options="$filterOptions"
            :today="$today"
            route-name="dosen.jadwal-ujian"
        />

        <x-jadwal-ujian.readonly-list
            :ujian="$ujian"
            :filters="$filters"
            :jenis-ujian-options="$jenisUjianOptions"
            :tahun-options="$tahunOptions"
            route-name="dosen.jadwal-ujian"
        />
    </div>
</x-app-layout>
