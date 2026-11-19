<x-app-layout title="Mata Kuliah" :user="$user" :unread-count="$unreadCount">
    @php
        $labelStatusPeserta = fn (?string $status): string => match ($status) {
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'mengulang' => 'Mengulang',
            default => 'Tidak diketahui',
        };
    @endphp

    <x-page-header
        title="Mata Kuliah"
        eyebrow="Akademik mahasiswa"
        description="Kelola kelas yang Anda ikuti dan temukan kelas lain yang masih menerima peserta."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text">
                <x-icon name="book" class="h-4 w-4 text-agenda-tugas" />
                {{ count($kelasSaya) }} kelas diikuti
            </span>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div role="status" class="mt-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
            {{ session('status') }}
        </div>
    @endif

    {{-- Kelas Saya --}}
    <section class="mt-8" aria-labelledby="kelas-saya-judul">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 id="kelas-saya-judul" class="text-lg font-bold tracking-tight text-text">Kelas Saya</h3>
                <p class="mt-1 text-xs leading-5 text-muted">Hanya kelas dengan pendaftaran aktif yang ditampilkan.</p>
            </div>
        </div>

        <div class="mt-4">
            @if ($kelasSaya === [])
                <x-empty-state
                    title="Belum ada kelas diikuti"
                    description="Cari kelas di bawah ini dan gunakan kunci gabung dari dosen untuk bergabung."
                    icon="book"
                />
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($kelasSaya as $pendaftaran)
                        <article class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-agenda-tugas">
                                        {{ $pendaftaran['kode_mata_kuliah'] ?? '-' }}
                                    </p>
                                    <h4 class="mt-1 truncate text-base font-bold text-text">
                                        {{ $pendaftaran['nama_mata_kuliah'] ?? 'Mata kuliah tidak tersedia' }}
                                    </h4>
                                </div>
                                <x-status-badge
                                    :label="$labelStatusPeserta($pendaftaran['status_peserta'] ?? null)"
                                    tone="selesai"
                                />
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-3 text-xs">
                                <div>
                                    <dt class="font-medium text-muted">Kode kelas</dt>
                                    <dd class="mt-0.5 font-semibold text-text">{{ $pendaftaran['kode_kelas'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted">Nama kelas</dt>
                                    <dd class="mt-0.5 font-semibold text-text">{{ $pendaftaran['nama_kelas'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted">Dosen</dt>
                                    <dd class="mt-0.5 font-semibold text-text">{{ $pendaftaran['nama_dosen'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted">SKS</dt>
                                    <dd class="mt-0.5 font-semibold text-text">{{ $pendaftaran['sks'] ?? '-' }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="font-medium text-muted">Tahun akademik</dt>
                                    <dd class="mt-0.5 font-semibold text-text">
                                        {{ $pendaftaran['tahun_akademik'] ?? 'Tidak tersedia' }}
                                        @if (! empty($pendaftaran['semester']))
                                            <span class="text-muted">({{ ucfirst($pendaftaran['semester']) }})</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 border-t border-border pt-3">
                                <a
                                    href="{{ route('mahasiswa.materi.index', ['kelas' => $pendaftaran['kelas_id']]) }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    <x-icon name="file" class="h-3.5 w-3.5 text-agenda-tugas" />
                                    Lihat Materi
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Cari Kelas --}}
    <section class="mt-10" aria-labelledby="cari-kelas-judul">
        <h3 id="cari-kelas-judul" class="text-lg font-bold tracking-tight text-text">Cari Kelas</h3>
        <p class="mt-1 text-xs leading-5 text-muted">Cari berdasarkan nama atau kode mata kuliah, maupun kode kelas.</p>

        <form method="GET" action="{{ route('mahasiswa.mata-kuliah.cari') }}" class="mt-4" role="search">
            <label for="q" class="sr-only">Kata kunci pencarian kelas</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                    <input
                        id="q"
                        type="search"
                        name="q"
                        value="{{ $kataKunci }}"
                        maxlength="100"
                        placeholder="Contoh: Pemrograman atau K001"
                        autocomplete="off"
                        class="w-full rounded-xl border border-border bg-card py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                    >
                </div>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="search" class="h-4 w-4" />
                    Cari kelas
                </button>
            </div>
        </form>

        @if ($kelas !== null)
            <div class="mt-6">
                @if ($kelas->isEmpty())
                    <x-empty-state
                        title="Tidak ada kelas ditemukan"
                        description="Coba kata kunci lain atau lihat seluruh kelas yang tersedia."
                        icon="search"
                    >
                        <a
                            href="{{ route('mahasiswa.mata-kuliah.cari') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Lihat semua kelas
                        </a>
                    </x-empty-state>
                @else
                    <p class="text-xs font-semibold text-muted" role="status">
                        Menampilkan {{ $paginasi['dari'] }}–{{ $paginasi['sampai'] }} dari {{ $paginasi['total'] }} kelas
                    </p>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($kelas as $kandidat)
                            <article class="flex flex-col rounded-2xl border border-border bg-card p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-agenda-tugas">
                                            {{ $kandidat['kode_mata_kuliah'] ?: '-' }}
                                        </p>
                                        <h4 class="mt-1 text-base font-bold leading-6 text-text">
                                            {{ $kandidat['nama_mata_kuliah'] }}
                                        </h4>
                                    </div>
                                    @if ($kandidat['masih_menerima_peserta'])
                                        <x-status-badge label="Menerima peserta" tone="selesai" />
                                    @else
                                        <x-status-badge label="Kapasitas penuh" tone="deadline" />
                                    @endif
                                </div>

                                <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-3 text-xs">
                                    <div>
                                        <dt class="font-medium text-muted">Kode kelas</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $kandidat['kode_kelas'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-muted">Nama kelas</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $kandidat['nama_kelas'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-muted">Dosen</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $kandidat['dosen'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-muted">SKS</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $kandidat['sks'] ?? '-' }}</dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="font-medium text-muted">Tahun akademik</dt>
                                        <dd class="mt-0.5 font-semibold text-text">
                                            {{ $kandidat['tahun_akademik'] }}
                                            @if (! empty($kandidat['semester']))
                                                <span class="text-muted">({{ ucfirst($kandidat['semester']) }})</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="font-medium text-muted">Kapasitas</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $kandidat['kapasitas_label'] }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-5 flex items-center justify-between gap-3 border-t border-border pt-4">
                                    <span class="text-[11px] font-medium text-muted">Pendaftaran dibuka</span>
                                    @if ($kandidat['masih_menerima_peserta'])
                                        <button
                                            type="button"
                                            data-gabung-trigger
                                            data-gabung-label="{{ $kandidat['kode_mata_kuliah'] }} — {{ $kandidat['nama_kelas'] }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-agenda-tugas px-3 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                                        >
                                            Gabung
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            disabled
                                            title="Kapasitas kelas sudah penuh"
                                            class="inline-flex cursor-not-allowed items-center justify-center rounded-xl bg-soft px-3 py-2 text-xs font-bold text-muted"
                                        >
                                            Gabung
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($kelas->hasPages())
                        <nav class="mt-6" aria-label="Paginasi hasil pencarian kelas">
                            {{ $kelas->links() }}
                        </nav>
                    @endif
                @endif
            </div>
        @endif
    </section>

    @push('modals')
        <dialog
            id="dialog-gabung"
            @error('kode_gabung') data-gabung-error @enderror
            class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-border bg-card p-0 text-text shadow-xl backdrop:bg-sidebar/50"
            aria-labelledby="dialog-gabung-judul"
        >
            <form method="POST" action="{{ route('mahasiswa.mata-kuliah.gabung') }}" class="p-5 sm:p-6">
                @csrf
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-agenda-tugas">Gabung kelas</p>
                        <h3 id="dialog-gabung-judul" class="mt-1 text-lg font-bold text-text">Masukkan kunci gabung</h3>
                    </div>
                    <button
                        type="button"
                        data-gabung-close
                        class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        aria-label="Tutup dialog gabung"
                    >
                        <x-icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <p class="mt-3 text-sm leading-6 text-muted">
                    Masukkan kunci yang diberikan dosen untuk bergabung ke
                    <span class="font-semibold text-text" data-gabung-label>Kelas terpilih</span>.
                </p>

                <div class="mt-5">
                    <label for="kode_gabung" class="block text-sm font-semibold text-text">Kunci gabung</label>
                    <input
                        id="kode_gabung"
                        type="text"
                        name="kode_gabung"
                        value=""
                        required
                        maxlength="32"
                        autocomplete="off"
                        spellcheck="false"
                        @error('kode_gabung') aria-invalid="true" aria-describedby="kode_gabung_error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('kode_gabung') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('kode_gabung')
                        <p id="kode_gabung_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        data-gabung-close
                        class="rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-agenda-tugas px-4 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                    >
                        Gabung kelas
                    </button>
                </div>
            </form>
        </dialog>
    @endpush
</x-app-layout>
