<x-app-layout title="Materi Pembelajaran" :user="$user" :unread-count="$unreadCount">
    <x-page-header
        title="Materi Pembelajaran"
        eyebrow="Akademik mahasiswa"
        description="Akses dan unduh berkas materi perkuliahan dari kelas yang Anda ikuti."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text shadow-sm">
                <x-icon name="file" class="h-4 w-4 text-agenda-tugas" />
                {{ $stats['total'] }} materi tersedia
            </span>
        </x-slot:actions>
    </x-page-header>

    {{-- Ringkasan Statistik --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card
            label="Total Materi"
            :value="$stats['total']"
            hint="Dari seluruh kelas aktif yang Anda ikuti"
            icon="file"
            tone="tugas"
        />
        <x-stat-card
            label="Materi Selesai"
            :value="$stats['selesai']"
            :hint="$stats['persentase'] . '% materi telah dipelajari'"
            icon="check"
            tone="kuliah"
        />
        <x-stat-card
            label="Belum Selesai"
            :value="$stats['belum_selesai']"
            hint="Materi perkuliahan yang perlu dipelajari"
            icon="clock"
            tone="akademik"
        />
    </div>

    {{-- Filter Kelas --}}
    <section class="mt-8" aria-labelledby="filter-materi-judul">
        <div class="flex flex-col gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <h3 id="filter-materi-judul" class="text-sm font-bold text-text">Filter Berdasarkan Kelas</h3>
                <p class="text-xs text-muted">Pilih kelas untuk melihat materi spesifik atau tampilkan semua kelas.</p>
            </div>

            <form method="GET" action="{{ route('mahasiswa.materi.index') }}" class="flex flex-wrap items-center gap-2">
                <label for="kelas-filter" class="sr-only">Pilih kelas</label>
                <div class="relative min-w-[240px]">
                    <select
                        id="kelas-filter"
                        name="kelas"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-border bg-surface px-3 py-2 text-xs font-medium text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                    >
                        <option value="">Semua Kelas ({{ count($kelasAktif) }} kelas)</option>
                        @foreach ($kelasAktif as $k)
                            <option value="{{ $k['id'] }}" @selected($selectedKelasId === $k['id'])>
                                {{ $k['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <noscript>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-text transition hover:bg-primary-hover"
                    >
                        Terapkan
                    </button>
                </noscript>
                @if ($selectedKelasId !== null)
                    <a
                        href="{{ route('mahasiswa.materi.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-medium text-muted transition hover:bg-soft hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="close" class="h-3.5 w-3.5" />
                        <span>Reset</span>
                    </a>
                @endif
            </form>
        </div>
    </section>

    {{-- Daftar Materi --}}
    <section class="mt-6" aria-labelledby="daftar-materi-judul">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 id="daftar-materi-judul" class="text-lg font-bold tracking-tight text-text">
                    @if ($selectedKelas !== null)
                        Materi: {{ $selectedKelas['nama_mata_kuliah'] }} ({{ $selectedKelas['nama_kelas'] }})
                    @else
                        Semua Materi Pembelajaran
                    @endif
                </h3>
                <p class="mt-0.5 text-xs leading-5 text-muted">
                    Hanya materi yang telah dipublikasikan oleh dosen yang dapat diakses dan diunduh.
                </p>
            </div>
            @if ($materi->isNotEmpty())
                <span class="text-xs font-semibold text-muted">
                    {{ $materi->count() }} materi
                </span>
            @endif
        </div>

        <div class="mt-4">
            @if ($kelasAktif === [])
                <x-empty-state
                    title="Belum terdaftar di kelas manapun"
                    description="Anda belum memiliki pendaftaran kelas aktif. Bergabunglah dengan kelas terlebih dahulu untuk mengakses materi."
                    icon="book"
                >
                    <a
                        href="{{ route('mahasiswa.mata-kuliah') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <x-icon name="search" class="h-3.5 w-3.5" />
                        <span>Cari & Gabung Kelas</span>
                    </a>
                </x-empty-state>
            @elseif ($materi->isEmpty())
                <x-empty-state
                    title="Belum ada materi pembelajaran"
                    description="{{ $selectedKelas !== null ? 'Belum ada materi perkuliahan yang dipublikasikan pada kelas ini.' : 'Belum ada materi perkuliahan yang dipublikasikan dari seluruh kelas yang Anda ikuti.' }}"
                    icon="file"
                >
                    @if ($selectedKelasId !== null)
                        <a
                            href="{{ route('mahasiswa.materi.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Tampilkan semua kelas
                        </a>
                    @endif
                </x-empty-state>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($materi as $item)
                        <article class="flex flex-col justify-between rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-agenda-tugas">
                                                {{ $item['kode_mata_kuliah'] }}
                                            </span>
                                            @if ($item['pertemuan_ke'])
                                                <span class="inline-flex items-center rounded-md bg-agenda-kuliah/15 px-1.5 py-0.5 text-[10px] font-bold text-agenda-kuliah">
                                                    P{{ $item['pertemuan_ke'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="mt-1 text-base font-bold leading-snug text-text" title="{{ $item['judul'] }}">
                                            {{ $item['judul'] }}
                                        </h4>
                                        <p class="mt-0.5 truncate text-xs text-muted">
                                            {{ $item['nama_mata_kuliah'] }} • {{ $item['nama_kelas'] }}
                                        </p>
                                    </div>
                                    <x-status-badge
                                        :label="$item['status_progres_label']"
                                        :tone="$item['status_progres_tone']"
                                    />
                                </div>

                                @if (! empty($item['deskripsi']))
                                    <p class="mt-3 line-clamp-3 text-xs leading-relaxed text-muted">
                                        {{ $item['deskripsi'] }}
                                    </p>
                                @else
                                    <p class="mt-3 text-xs italic text-muted/60">
                                        Tidak ada deskripsi tambahan.
                                    </p>
                                @endif

                                <div class="mt-4 rounded-xl border border-border bg-surface p-3">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-text">
                                        <x-icon name="file" class="h-4 w-4 shrink-0 text-agenda-tugas" />
                                        <span class="truncate" title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</span>
                                    </div>
                                    <dl class="mt-2.5 grid grid-cols-2 gap-x-2 gap-y-1.5 text-[11px]">
                                        <div>
                                            <dt class="text-muted">Ukuran</dt>
                                            <dd class="font-semibold text-text">{{ $item['file_size_label'] }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-muted">Dipublikasikan</dt>
                                            <dd class="font-semibold text-text">{{ $item['dipublikasikan_label'] }}</dd>
                                        </div>
                                        <div class="col-span-2">
                                            <dt class="text-muted">Dosen Pengampu</dt>
                                            <dd class="truncate font-semibold text-text">{{ $item['nama_dosen'] }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border pt-4">
                                <a
                                    href="{{ route('mahasiswa.materi.show', [$item['kelas_id'], $item['id']]) }}"
                                    class="mb-2 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                                    aria-label="Lihat detail {{ $item['judul'] }}"
                                >
                                    <x-icon name="info" class="h-4 w-4" />
                                    <span>Lihat Detail & Progres</span>
                                </a>
                                <a
                                    href="{{ route('mahasiswa.materi.unduh', [$item['kelas_id'], $item['id']]) }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    aria-label="Unduh berkas {{ $item['judul'] }}"
                                >
                                    <x-icon name="file" class="h-4 w-4" />
                                    <span>Unduh Berkas</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-app-layout>