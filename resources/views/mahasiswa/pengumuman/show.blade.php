<x-app-layout title="Detail Pengumuman" :user="$user" :unread-count="$unreadCount">
    <x-page-header
        :title="$pengumuman->judul"
        eyebrow="Pengumuman akademik"
        description="Informasi pengumuman yang ditujukan kepada Anda."
    >
        <x-slot:actions>
            <a href="{{ route('mahasiswa.pengumuman.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali ke daftar
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
            <x-status-badge label="Pengumuman" tone="pengumuman" />
            @if ($pengumuman->kelas_mata_kuliah_id !== null)
                <x-status-badge :label="$pengumuman->kelasMataKuliah?->mataKuliah?->nama ?? 'Kelas'" tone="akademik" />
            @else
                <x-status-badge label="Akademik institusi" tone="akademik" />
            @endif
        </div>

        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold text-muted">Dipublikasikan</dt>
                <dd class="mt-1 text-sm font-semibold text-text">{{ $pengumuman->dipublikasikan_pada?->translatedFormat('d M Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-muted">Sumber</dt>
                <dd class="mt-1 text-sm font-semibold text-text">{{ $pengumuman->pembuat?->name ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold text-muted">Masa berlaku</dt>
                <dd class="mt-1 text-sm font-semibold text-text">
                    {{ $pengumuman->tanggal_mulai?->translatedFormat('d M Y') }}
                    @if ($pengumuman->tanggal_selesai)
                        — {{ $pengumuman->tanggal_selesai->translatedFormat('d M Y') }}
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-6 border-t border-border pt-5">
            <h3 class="text-xs font-bold uppercase tracking-wide text-muted">Isi pengumuman</h3>
            <p class="mt-2 whitespace-pre-line text-sm leading-7 text-text">{{ $pengumuman->deskripsi }}</p>
        </div>
    </section>
</x-app-layout>
