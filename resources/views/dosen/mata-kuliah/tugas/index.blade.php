@php
    $totalTugas = $tugas->count();
    $totalDraft = $tugas->where('status', 'draft')->count();
    $totalPublik = $tugas->where('status', 'dipublikasikan')->count();
@endphp

<x-app-layout title="Manajemen Tugas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Manajemen Tugas"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Buat, publikasikan, dan kelola tugas kelas ini. Draft hanya terlihat oleh dosen."
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('dosen.mata-kuliah.show', $kelas->getKey()) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="chevron-left" class="h-4 w-4" />
                        Detail kelas
                    </a>
                    <a
                        href="{{ route('dosen.mata-kuliah.tugas.create', $kelas->getKey()) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <x-icon name="clipboard" class="h-4 w-4" />
                        Tambah tugas
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card
                label="Total tugas"
                :value="$totalTugas"
                hint="Tugas pada kelas ini"
                icon="clipboard"
                tone="tugas"
            />
            <x-stat-card
                label="Draft"
                :value="$totalDraft"
                hint="Belum dipublikasikan"
                icon="info"
                tone="pengumuman"
            />
            <x-stat-card
                label="Dipublikasikan"
                :value="$totalPublik"
                hint="Terlihat oleh mahasiswa"
                icon="check"
                tone="kuliah"
            />
        </div>

        @if ($tugas->isEmpty())
            <x-empty-state
                title="Belum ada tugas"
                description="Tambahkan tugas pertama untuk kelas ini. Tugas dapat disimpan sebagai draft atau dijadwalkan sesuai tanggal publikasi."
                icon="clipboard"
            >
                <a
                    href="{{ route('dosen.mata-kuliah.tugas.create', $kelas->getKey()) }}"
                    class="inline-flex items-center justify-center rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Tambah tugas
                </a>
            </x-empty-state>
        @else
            <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Daftar tugas">
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Tugas</th>
                                <th scope="col" class="px-4 py-3">Dipublikasikan</th>
                                <th scope="col" class="px-4 py-3">Deadline</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Nilai maks.</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($tugas as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['judul'] }}</p>
                                        <p class="mt-1 max-w-md text-xs leading-5 text-muted">
                                            {{ $item['deskripsi_ringkas'] ?: 'Tidak ada deskripsi.' }}
                                        </p>
                                        @if ($item['lampiran_nama'])
                                            <p class="mt-1 max-w-[16rem] break-all text-[11px] text-muted">
                                                Lampiran: {{ $item['lampiran_nama'] }} ({{ $item['lampiran_size_label'] }})
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['dipublikasikan_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['deadline_label'] }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['status_label']"
                                            :tone="$item['status'] === 'draft' ? 'peringatan' : ($item['status'] === 'ditutup' ? 'deadline' : ($item['status'] === 'terjadwal' ? 'pengumuman' : 'selesai'))"
                                        />
                                        @if ($item['izinkan_terlambat'])
                                            <p class="mt-1 text-[11px] text-muted">Terlambat diizinkan</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['nilai_maksimal'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <a
                                                href="{{ route('dosen.mata-kuliah.tugas.edit', [$kelas->getKey(), $item['id']]) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Ubah
                                            </a>
                                            <a
                                                href="{{ route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas->getKey(), $item['id']]) }}"
                                                class="rounded-lg border border-agenda-tugas/30 bg-agenda-tugas/10 px-2.5 py-1.5 text-xs font-semibold text-agenda-tugas transition hover:bg-agenda-tugas/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Lihat pengumpulan @if ($item['jumlah_pengumpulan'] > 0) ({{ $item['jumlah_pengumpulan'] }}) @endif
                                            </a>
                                            @if ($item['lampiran_nama'])
                                                <a
                                                    href="{{ route('dosen.mata-kuliah.tugas.download', [$kelas->getKey(), $item['id']]) }}"
                                                    class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                                >
                                                    Unduh
                                                </a>
                                            @endif
                                            <button
                                                type="button"
                                                data-dosen-konfirmasi
                                                data-dosen-action="{{ route('dosen.mata-kuliah.tugas.destroy', [$kelas->getKey(), $item['id']]) }}"
                                                data-dosen-method="DELETE"
                                                data-dosen-judul="Hapus tugas"
                                                data-dosen-pesan="Tugas dan lampirannya akan dihapus permanen. Tugas yang sudah memiliki pengumpulan tidak dapat dihapus."
                                                data-dosen-label="{{ $item['judul'] }}"
                                                data-dosen-submit="Hapus tugas"
                                                class="rounded-lg border border-danger/30 bg-danger/10 px-2.5 py-1.5 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @foreach ($tugas as $item)
                        <article class="rounded-2xl border border-border bg-soft/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-text">{{ $item['judul'] }}</p>
                                    @if ($item['lampiran_nama'])
                                        <p class="mt-1 break-all text-[11px] text-muted">Lampiran: {{ $item['lampiran_nama'] }}</p>
                                    @endif
                                </div>
                                <x-status-badge
                                    :label="$item['status_label']"
                                    :tone="$item['status'] === 'draft' ? 'peringatan' : ($item['status'] === 'ditutup' ? 'deadline' : ($item['status'] === 'terjadwal' ? 'pengumuman' : 'selesai'))"
                                />
                            </div>
                            <p class="mt-2 text-xs leading-5 text-muted">
                                {{ $item['deskripsi_ringkas'] ?: 'Tidak ada deskripsi.' }}
                            </p>
                            <div class="mt-3 grid gap-1 text-[11px] text-muted">
                                <p>Publikasi: <span class="font-semibold text-text">{{ $item['dipublikasikan_label'] }}</span></p>
                                <p>Deadline: <span class="font-semibold text-text">{{ $item['deadline_label'] }}</span></p>
                                <p>Nilai maksimal: <span class="font-semibold text-text">{{ $item['nilai_maksimal'] }}</span></p>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a
                                    href="{{ route('dosen.mata-kuliah.tugas.edit', [$kelas->getKey(), $item['id']]) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Ubah
                                </a>
                                <a
                                    href="{{ route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas->getKey(), $item['id']]) }}"
                                    class="flex-1 rounded-xl border border-agenda-tugas/30 bg-agenda-tugas/10 px-3 py-2 text-center text-xs font-semibold text-agenda-tugas transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Lihat pengumpulan @if ($item['jumlah_pengumpulan'] > 0) ({{ $item['jumlah_pengumpulan'] }}) @endif
                                </a>
                                @if ($item['lampiran_nama'])
                                    <a
                                        href="{{ route('dosen.mata-kuliah.tugas.download', [$kelas->getKey(), $item['id']]) }}"
                                        class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                    >
                                        Unduh
                                    </a>
                                @endif
                                <button
                                    type="button"
                                    data-dosen-konfirmasi
                                    data-dosen-action="{{ route('dosen.mata-kuliah.tugas.destroy', [$kelas->getKey(), $item['id']]) }}"
                                    data-dosen-method="DELETE"
                                    data-dosen-judul="Hapus tugas"
                                    data-dosen-pesan="Tugas dan lampirannya akan dihapus permanen. Tugas yang sudah memiliki pengumpulan tidak dapat dihapus."
                                    data-dosen-label="{{ $item['judul'] }}"
                                    data-dosen-submit="Hapus tugas"
                                    class="flex-1 rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                                >
                                    Hapus
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @include('dosen.mata-kuliah._dialog')
</x-app-layout>