<x-app-layout title="Detail Pengumuman Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $status = $pengumuman->dipublikasikan_pada === null
            ? ['label' => 'Draft', 'tone' => 'netral']
            : ($pengumuman->dipublikasikan_pada->isFuture()
                ? ['label' => 'Terjadwal', 'tone' => 'peringatan']
                : ['label' => 'Terbit', 'tone' => 'sukses']);
    @endphp

    <x-page-header
        :title="$pengumuman->judul"
        eyebrow="Pengumuman kelas"
        description="Detail pengumuman yang Anda buat untuk kelas Anda."
    >
        <x-slot:actions>
            <a href="{{ route('dosen.pengumuman.edit', $pengumuman) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">
                <x-icon name="edit" class="h-4 w-4" />
                Ubah pengumuman
            </a>
            <a href="{{ route('dosen.pengumuman.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="mt-6 rounded-card bg-card p-5 shadow-card sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
            <x-status-badge :label="$status['label']" :tone="$status['tone']" />
            <x-status-badge label="Pengumuman kelas" tone="pengumuman" />
        </div>

        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold text-muted">Kelas tujuan</dt>
                <dd class="mt-1 text-sm font-semibold text-text">{{ $pengumuman->kelasMataKuliah?->mataKuliah?->nama ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-muted">Waktu publikasi</dt>
                <dd class="mt-1 text-sm font-semibold text-text">{{ $pengumuman->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? 'Belum dipublikasikan' }}</dd>
            </div>
            <div>
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
