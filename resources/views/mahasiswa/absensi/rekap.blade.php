<x-app-layout title="Rekap Kehadiran" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        <x-page-header
            title="Rekap Kehadiran"
            eyebrow="Kehadiran mahasiswa"
            description="Rekap per mata kuliah yang Anda ikuti. Hanya sesi yang sudah ditutup yang dihitung, dengan persentase hadir / sesi ditutup × 100%."
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke absensi
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($rekap->isEmpty())
            <x-empty-state
                title="Belum ada rekap kehadiran"
                description="Rekap tampil untuk mata kuliah yang masih Anda ikuti sebagai peserta aktif atau mengulang."
                icon="chart"
            />
        @else
            <section aria-label="Rekap kehadiran per mata kuliah" class="grid gap-4 lg:grid-cols-2">
                @foreach ($rekap as $item)
                    <article class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['kode_mata_kuliah'] }} · {{ $item['kode_kelas'] }} · {{ $item['periode'] }}</p>
                            </div>
                            <x-status-badge :label="$item['persentase_label']" :tone="$item['persentase'] >= 75 ? 'hadir' : ($item['persentase'] >= 50 ? 'peringatan' : 'alfa')" />
                        </div>

                        <div class="mt-4 grid grid-cols-5 gap-2 rounded-xl bg-soft p-3 text-center">
                            <div>
                                <p class="text-[11px] text-muted">Sesi</p>
                                <p class="mt-1 text-sm font-bold text-text">{{ $item['total_sesi_ditutup'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-muted">Hadir</p>
                                <p class="mt-1 text-sm font-bold text-text">{{ $item['hadir'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-muted">Izin</p>
                                <p class="mt-1 text-sm font-bold text-text">{{ $item['izin'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-muted">Sakit</p>
                                <p class="mt-1 text-sm font-bold text-text">{{ $item['sakit'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-muted">Alfa</p>
                                <p class="mt-1 text-sm font-bold text-text">{{ $item['alfa'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
