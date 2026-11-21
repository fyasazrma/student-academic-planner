<x-app-layout title="Dashboard Dosen" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-5 ui-stagger">
        <x-page-header
            title="Selamat datang, {{ $user->name ?? 'Dosen' }} 👋"
            eyebrow="Dashboard dosen"
            :description="'Ringkasan kelas yang Anda ampu. '.$today->translatedFormat('l, d F Y').'.'"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex h-10 items-center gap-2 rounded-full bg-card px-4 text-xs font-semibold text-text shadow-card">
                        <x-icon name="calendar" class="size-4 text-agenda-tugas" />
                        {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
                    </span>
                    @if ($user->nidn)
                        <span class="inline-flex h-10 items-center gap-2 rounded-full bg-card px-4 text-xs font-semibold text-text shadow-card">
                            <x-icon name="user" class="size-4 text-agenda-tugas" />
                            NIDN {{ $user->nidn }}
                        </span>
                    @endif
                </div>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan kelas" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                label="Kelas diampu"
                :value="$stats['kelas_diampu']"
                :hint="$stats['kelas_diampu'] === 0 ? 'Belum ada kelas dibuat' : 'Total seluruh kelas milik Anda'"
                icon="book"
                tone="kuliah"
            />
            <x-stat-card
                label="Pendaftaran buka"
                :value="$stats['pendaftaran_buka']"
                :hint="$stats['pendaftaran_buka'] === 0 ? 'Tidak ada yang menerima pendaftaran' : 'Kelas siap diikuti mahasiswa'"
                icon="check"
                tone="kuliah"
            />
            <x-stat-card
                label="Peserta aktif"
                :value="$stats['peserta_aktif']"
                hint="Dari seluruh kelas yang diampu"
                icon="user"
                tone="tugas"
            />
            <x-stat-card
                label="Jadwal kuliah"
                :value="$stats['jadwal_kuliah']"
                hint="Jadwal berulang pada kelas Anda"
                icon="calendar"
                tone="akademik"
            />
        </section>

        <x-dosen.calendar-panel
            :calendar="$calendar"
            :timeline="$timeline"
            :current-view="$currentView"
            :active-filters="$activeFilters"
            :filter-options="$filterOptions"
            :today="$today"
            route-name="dosen.dashboard"
        />

        <section class="rounded-card bg-card p-5 shadow-card" aria-label="Kelas yang diampu">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-text">Kelas yang Anda ampu</h2>
                    <p class="mt-1 text-xs leading-5 text-muted">Status pendaftaran, periode aktif, jumlah peserta, dan jadwal terdekat.</p>
                </div>
                <a
                    href="{{ route('dosen.mata-kuliah.index') }}"
                    class="inline-flex h-10 w-fit items-center gap-1.5 rounded-full bg-card px-4 text-xs font-semibold text-text shadow-card transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Kelola kelas
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            </div>

            @if ($ringkasanKelas === [])
                <div class="mt-5">
                    <x-empty-state
                        title="Belum ada kelas"
                        description="Buat kelas pertama melalui menu Mata Kuliah Saya untuk melihat ringkasan pengampu."
                        icon="book"
                    />
                </div>
            @else
                <div class="mt-5 overflow-x-auto rounded-control bg-card shadow-card">
                    <table class="w-full min-w-[820px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah</th>
                                <th scope="col" class="px-4 py-3">Kelas</th>
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Peserta</th>
                                <th scope="col" class="px-4 py-3">Jadwal terdekat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($ringkasanKelas as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-text">{{ $item['nama_mata_kuliah'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $item['kode_mata_kuliah'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a
                                            href="{{ route('dosen.mata-kuliah.show', $item['id']) }}"
                                            class="font-semibold text-agenda-tugas underline-offset-2 transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                        >{{ $item['kode_kelas'] }}</a>
                                        @if ($item['nama_kelas'])
                                            <p class="mt-0.5 text-[11px] text-muted">{{ $item['nama_kelas'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-text">{{ $item['tahun_akademik'] }}</p>
                                        <x-status-badge
                                            class="mt-1"
                                            :label="$item['periode_label']"
                                            :tone="$item['periode_label'] === 'Aktif' ? 'selesai' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['status_label']"
                                            :tone="$item['status_pendaftaran'] === 'buka' ? 'selesai' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['jumlah_peserta_aktif'] }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['jadwal_terdekat_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <x-slot:rightPanel>
        <x-dosen.ringkasan-panel
            :notifications="$notifications"
            :deadlines="$deadlines"
            :next-schedules="$nextSchedules"
            :unread-count="$unreadCount"
            :absensi-ringkasan="$absensiRingkasan"
            :tugas-ringkasan="$tugasRingkasan"
        />
    </x-slot:rightPanel>
</x-app-layout>
