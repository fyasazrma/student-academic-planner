<x-app-layout title="Pengumuman" :user="$user" :unread-count="$unreadCount">
    <x-page-header
        title="Pengumuman Akademik"
        eyebrow="Informasi mahasiswa"
        description="Pengumuman institusi dan pengumuman dari kelas yang Anda ikuti."
    />

    @if (session('status'))
        <div role="status" class="mt-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
    @endif

    <section class="mt-6 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar pengumuman mahasiswa">
        <form method="GET" action="{{ route('mahasiswa.pengumuman.index') }}" role="search" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="block text-xs font-semibold text-text">Cari pengumuman</label>
                <div class="relative mt-2">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                    <input id="q" type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Judul atau isi pengumuman" autocomplete="off" class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/30">
                </div>
            </div>
            <button type="submit" class="rounded-xl border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">Cari</button>
        </form>

        @if ($pengumuman->isEmpty())
            <div class="mt-6">
                <x-empty-state
                    title="Belum ada pengumuman"
                    description="Pengumuman yang ditujukan kepada Anda akan tampil di sini."
                    icon="info"
                />
            </div>
        @else
            <div class="mt-6 grid gap-3">
                @foreach ($pengumuman as $item)
                    <a
                        href="{{ route('mahasiswa.pengumuman.show', $item) }}"
                        class="block rounded-xl border border-border bg-surface p-4 transition hover:border-agenda-pengumuman/60 hover:bg-agenda-pengumuman/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge label="Pengumuman" tone="pengumuman" />
                            @if ($item->kelas_mata_kuliah_id !== null)
                                <x-status-badge :label="$item->kelasMataKuliah?->mataKuliah?->nama ?? 'Kelas'" tone="akademik" />
                            @endif
                            <span class="text-[11px] text-muted">{{ $item->dipublikasikan_pada?->translatedFormat('d M Y H:i') }}</span>
                        </div>
                        <h3 class="mt-2 text-sm font-bold text-text">{{ $item->judul }}</h3>
                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-muted">{{ \Illuminate\Support\Str::limit($item->deskripsi ?? '', 180) }}</p>
                    </a>
                @endforeach
            </div>

            @if ($pengumuman->hasPages())
                <div class="mt-5 border-t border-border pt-4">{{ $pengumuman->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
