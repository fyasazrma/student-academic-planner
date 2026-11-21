<x-app-layout title="Progres Materi" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif

        <x-page-header
            title="Progres Materi"
            eyebrow="Pembelajaran"
            description="Target penyelesaian materidi hitung otomatis dari materi terpublikasi pada kelas yang Anda ikuti."
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.materi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="file" class="h-4 w-4" /> Lihat Materi
                </a>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan progres materi" class="grid gap-3 sm:grid-cols-3">
            <x-stat-card label="Materi tersedia" :value="$ringkasan['total']" hint="Materi terpublikasi pada kelas aktif" icon="file" tone="tugas" />
            <x-stat-card label="Materi selesai" :value="$ringkasan['selesai']" hint="Ditandai sudah dipelajari" icon="check" tone="kuliah" />
            <x-stat-card label="Persentase progres" :value="number_format($ringkasan['persentase'], 2, ',', '.').'%'" hint="Rata-rata seluruh kelas aktif" icon="chart" tone="akademik" />
        </section>

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Progres per mata kuliah">
            <h3 class="text-sm font-bold text-text">Target otomatis per mata kuliah</h3>
            <p class="mt-1 text-xs leading-5 text-muted">Materi draft dan materi yang belum dipublikasikan tidak dihitung.</p>

            <div class="mt-5 space-y-4">
                @forelse ($ringkasan['kelas'] as $item)
                    <article class="rounded-xl border border-border bg-surface p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-agenda-tugas">{{ $item['kode_mata_kuliah'] }} · {{ $item['kode_kelas'] }}</p>
                                <h4 class="mt-1 text-sm font-bold text-text">{{ $item['nama_mata_kuliah'] }} — {{ $item['nama_kelas'] }}</h4>
                            </div>
                            <x-status-badge
                                :label="$item['total'] === 0 ? 'Belum ada materi' : $item['selesai'].'/'.$item['total'].' selesai'"
                                :tone="$item['total'] > 0 && $item['selesai'] === $item['total'] ? 'selesai' : 'kuliah'"
                            />
                        </div>

                        <div class="mt-3 flex items-center justify-between text-[11px] font-semibold text-muted">
                            <span>{{ number_format($item['persentase'], 2, ',', '.') }}% selesai</span>
                            <span>{{ $item['belum_selesai'] }} materi belum diselesaikan</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-border" role="progressbar" aria-valuenow="{{ $item['persentase'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-agenda-tugas" style="width: {{ min(100, $item['persentase']) }}%"></div>
                        </div>

                        @if ($item['total'] === 0)
                            <p class="mt-3 text-xs italic text-muted">Belum ada materi terpublikasi pada kelas ini.</p>
                        @else
                            <ul class="mt-3 divide-y divide-border">
                                @foreach ($item['materi'] as $materi)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                        <a
                                            href="{{ route('mahasiswa.materi.show', [$materi['kelas_id'], $materi['id']]) }}"
                                            class="min-w-0 truncate text-xs font-semibold text-text transition hover:text-agenda-tugas focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                        >
                                            {{ $materi['judul'] }}
                                        </a>
                                        <x-status-badge :label="$materi['status_label']" :tone="$materi['status_tone']" />
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @empty
                    <x-empty-state
                        title="Belum ada kelas aktif"
                        description="Gabung kelas terlebih dahulu untuk melihat target otomatis penyelesaian materi."
                        icon="book"
                    >
                        <a href="{{ route('mahasiswa.mata-kuliah') }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                            Lihat Mata Kuliah
                        </a>
                    </x-empty-state>
                @endforelse
            </div>
        </section>

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Materi belum diselesaikan">
            <h3 class="text-sm font-bold text-text">Materi yang belum diselesaikan</h3>
            @if (count($ringkasan['belum_selesai']) > 0)
                <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($ringkasan['belum_selesai'] as $materi)
                        <li>
                            <a
                                href="{{ route('mahasiswa.materi.show', [$materi['kelas_id'], $materi['id']]) }}"
                                class="flex items-center gap-2 rounded-xl border border-border bg-surface px-3 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                <x-icon name="file" class="h-4 w-4 shrink-0 text-agenda-tugas" />
                                <span class="min-w-0 truncate">{{ $materi['judul'] }} — {{ $materi['nama_mata_kuliah'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-xs text-muted">Semua materi pada kelas aktif sudah diselesaikan atau belum tersedia.</p>
            @endif
        </section>
    </div>
</x-app-layout>