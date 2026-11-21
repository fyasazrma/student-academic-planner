<x-app-layout title="Dashboard Admin" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Dashboard admin"
            eyebrow="Ringkasan institusi"
            :description="'Ringkasan data akademik institusi. '.$today->translatedFormat('l, d F Y').'.'"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 font-semibold text-text">
                        <x-icon name="calendar" class="h-4 w-4 text-agenda-tugas" />
                        {{ $tahunAkademikAktif?->label() ?? 'Tahun akademik belum ditetapkan' }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 font-semibold text-text">
                        <x-icon name="user" class="h-4 w-4 text-agenda-tugas" />
                        {{ $user?->name ?? 'Administrator' }}
                    </span>
                    <a
                        href="{{ route('admin.jadwal-ujian.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <x-icon name="exam" class="h-4 w-4" />
                        Kelola jadwal ujian
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan data akademik" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                label="Mahasiswa aktif"
                :value="$statistik['mahasiswa_aktif']"
                hint="Akun mahasiswa berstatus aktif"
                icon="user"
                tone="tugas"
            />
            <x-stat-card
                label="Dosen aktif"
                :value="$statistik['dosen_aktif']"
                hint="Akun dosen berstatus aktif"
                icon="user"
                tone="kuliah"
            />
            <x-stat-card
                label="Mata kuliah aktif"
                :value="$statistik['mata_kuliah_aktif']"
                hint="Data induk yang dapat digunakan"
                icon="book"
                tone="akademik"
            />
            <x-stat-card
                label="Kelas periode aktif"
                :value="$statistik['kelas_periode_aktif']"
                :hint="$tahunAkademikAktif ? 'Pada '.$tahunAkademikAktif->label() : 'Tidak ada periode aktif'"
                icon="dashboard"
                tone="ujian"
            />
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <x-admin.calendar-panel
                :calendar="$calendar"
                :timeline="$timeline"
                :current-view="$currentView"
                :active-filters="$activeFilters"
                :filter-options="$filterOptions"
                :today="$today"
                route-name="admin.dashboard"
            />

            <aside class="space-y-4">
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <x-icon name="calendar" class="h-5 w-5 text-agenda-uts" />
                        <h3 class="text-sm font-bold text-text">Agenda mendatang</h3>
                    </div>

                    @if ($agendaMendatang === [])
                        <x-empty-state
                            class="mt-4"
                            title="Belum ada agenda mendatang"
                            description="Agenda institusi yang dipublikasikan akan tampil di sini."
                            icon="calendar"
                        />
                    @else
                        <ul class="mt-4 space-y-3">
                            @foreach ($agendaMendatang as $agenda)
                                <li class="rounded-xl border border-border bg-soft p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-muted">
                                        {{ $agenda['jenis_label'] }} · {{ $agenda['tanggal_label'] }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-text">{{ $agenda['judul'] }}</p>
                                    @if ($agenda['waktu_label'])
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $agenda['waktu_label'] }}</p>
                                    @endif
                                    <p class="mt-0.5 text-[11px] text-muted">
                                        {{ $agenda['konteks'] }}{{ $agenda['lokasi'] ? ' · '.$agenda['lokasi'] : '' }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <x-icon name="list" class="h-5 w-5 text-agenda-akademik" />
                        <h3 class="text-sm font-bold text-text">Perubahan data terbaru</h3>
                    </div>
                    <p class="mt-1 text-[11px] leading-5 text-muted">Berdasarkan waktu pembaruan data induk, bukan catatan audit terpisah.</p>

                    @if ($perubahanTerbaru === [])
                        <x-empty-state
                            class="mt-4"
                            title="Belum ada perubahan data"
                            description="Perubahan pada mata kuliah dan tahun akademik akan tampil di sini."
                            icon="list"
                        />
                    @else
                        <ul class="mt-4 space-y-3">
                            @foreach ($perubahanTerbaru as $perubahan)
                                <li class="flex items-start gap-3 rounded-xl border border-border bg-soft p-3">
                                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-agenda-akademik"></span>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-muted">
                                            {{ $perubahan['jenis'] }} · {{ $perubahan['waktu_label'] }}
                                        </p>
                                        <p class="mt-1 truncate text-sm font-semibold text-text">{{ $perubahan['judul'] }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-muted">{{ $perubahan['keterangan'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>