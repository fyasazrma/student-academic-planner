<x-app-layout title="Sesi Absensi Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            eyebrow="Absensi"
            :description="'Kelola sesi absensi kelas ini. '.$ringkasan['total_sesi_ditutup'].' sesi sudah ditutup dan dihitung dalam rekap.'"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dosen.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                        <x-icon name="chevron-left" class="h-4 w-4" />
                        Daftar kelas
                    </a>
                    <a href="{{ route('dosen.absensi.rekap', $kelas->getKey()) }}" class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Lihat rekap</a>
                    <a href="{{ route('dosen.absensi.sesi.create', $kelas->getKey()) }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        Buat sesi
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">{{ session('error') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Ringkasan sesi">
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Total sesi</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $sesi->count() }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Dibuka</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $sesi->where('status', 'dibuka')->count() }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Ditutup</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $ringkasan['total_sesi_ditutup'] }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Peserta rekap</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $ringkasan['jumlah_peserta'] }}</p>
            </div>
        </section>

        @if ($sesi->isEmpty())
            <x-empty-state
                title="Belum ada sesi absensi"
                description="Buat sesi pertama untuk mulai mencatat kehadiran pada kelas ini."
                icon="calendar"
            />
        @else
            <section aria-label="Daftar sesi (tabel)" class="hidden overflow-hidden rounded-card bg-card shadow-card md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Pertemuan</th>
                                <th scope="col" class="px-4 py-3">Tanggal & waktu</th>
                                <th scope="col" class="px-4 py-3">Topik</th>
                                <th scope="col" class="px-4 py-3">Catatan</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($sesi as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3 font-semibold text-text">Ke-{{ $item['pertemuan_ke'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">
                                        <span class="block">{{ $item['tanggal_label'] }}</span>
                                        <span class="block text-muted">{{ $item['waktu_label'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['topik'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['jumlah_absensi'] }} catatan</td>
                                    <td class="px-4 py-3"><x-status-badge :label="$item['status_label']" :tone="$item['status']" /></td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <a href="{{ $item['detail_url'] }}" class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Detail</a>
                                            <a href="{{ $item['edit_url'] }}" class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Ubah</a>
                                            @if ($item['status'] === 'dibuka')
                                                <form method="POST" action="{{ $item['tutup_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-danger/30 bg-danger/10 px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Tutup</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ $item['buka_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-border bg-soft px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Buka</button>
                                                </form>
                                            @endif
                                            @if ($item['jumlah_absensi'] === 0)
                                                <form method="POST" action="{{ route('dosen.absensi.sesi.destroy', [$kelas->getKey(), $item['id']]) }}" onsubmit="return confirm('Hapus sesi ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Daftar sesi (kartu)" class="space-y-3 md:hidden">
                @foreach ($sesi as $item)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">Pertemuan ke-{{ $item['pertemuan_ke'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['tanggal_label'] }} · {{ $item['waktu_label'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$item['status']" />
                        </div>
                        <p class="mt-3 text-xs text-text">{{ $item['topik'] }}</p>
                        <p class="mt-1 text-[11px] text-muted">{{ $item['jumlah_absensi'] }} catatan absensi</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ $item['detail_url'] }}" class="rounded-xl border border-border bg-soft px-3 py-2 text-xs font-semibold text-text">Detail</a>
                            <a href="{{ $item['edit_url'] }}" class="rounded-xl border border-border bg-soft px-3 py-2 text-xs font-semibold text-text">Ubah</a>
                            @if ($item['status'] === 'dibuka')
                                <form method="POST" action="{{ $item['tutup_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-semibold text-text">Tutup</button>
                                </form>
                            @else
                                <form method="POST" action="{{ $item['buka_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-border bg-soft px-3 py-2 text-xs font-semibold text-text">Buka</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
