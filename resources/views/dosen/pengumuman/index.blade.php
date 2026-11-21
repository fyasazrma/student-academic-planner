<x-app-layout title="Pengumuman Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $statusInfo = function ($item): array {
            if ($item->dipublikasikan_pada === null) {
                return ['label' => 'Draft', 'tone' => 'netral'];
            }
            if ($item->dipublikasikan_pada->isFuture()) {
                return ['label' => 'Terjadwal', 'tone' => 'peringatan'];
            }

            return ['label' => 'Terbit', 'tone' => 'sukses'];
        };
    @endphp

    <x-page-header
        title="Pengumuman Kelas"
        eyebrow="Komunikasi kelas"
        description="Kelola pengumuman hanya untuk kelas yang Anda ampu. Mahasiswa menerima notifikasi saat pengumuman diterbitkan."
    >
        <x-slot:actions>
            <a
                href="{{ route('dosen.pengumuman.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-agenda-pengumuman px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-pengumuman/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman focus-visible:ring-offset-2"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Tambah pengumuman
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div role="status" class="mt-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
            {{ session('status') }}
        </div>
    @endif

    <section class="mt-6 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar pengumuman kelas">
        <form method="GET" action="{{ route('dosen.pengumuman.index') }}" role="search" class="grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-semibold text-text">Cari pengumuman</label>
                <div class="relative mt-2">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                    <input id="q" type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Judul atau isi pengumuman" autocomplete="off" class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/30">
                </div>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold text-text">Status</label>
                <select id="status" name="status" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/30">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3 flex justify-end">
                <button type="submit" class="rounded-xl border border-border bg-surface px-4 py-2 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">Terapkan filter</button>
            </div>
        </form>

        @if ($pengumuman->isEmpty())
            <div class="mt-6">
                <x-empty-state
                    title="Belum ada pengumuman kelas"
                    description="Buat pengumuman untuk menyampaikan informasi kepada mahasiswa di kelas Anda."
                    icon="info"
                />
            </div>
        @else
            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="border-b border-border text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="pb-3 font-semibold">Judul</th>
                            <th class="pb-3 font-semibold">Kelas</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($pengumuman as $item)
                            @php $status = $statusInfo($item); @endphp
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('dosen.pengumuman.show', $item) }}" class="font-semibold text-text hover:text-agenda-pengumuman focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">{{ $item->judul }}</a>
                                    <p class="mt-1 line-clamp-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit($item->deskripsi ?? '', 90) }}</p>
                                </td>
                                <td class="py-3 pr-4 text-xs text-muted">{{ $item->kelasMataKuliah?->mataKuliah?->nama ?? '-' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :label="$status['label']" :tone="$status['tone']" /></td>
                                <td class="py-3 text-right text-xs">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('dosen.pengumuman.edit', $item) }}" class="rounded-lg border border-border px-2.5 py-1.5 font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">Ubah</a>
                                        <form method="POST" action="{{ route('dosen.pengumuman.destroy', $item) }}" onsubmit="return confirm('Hapus pengumuman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-danger/30 px-2.5 py-1.5 font-semibold text-agenda-terlambat transition hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pengumuman->hasPages())
                <div class="mt-5 border-t border-border pt-4">{{ $pengumuman->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
