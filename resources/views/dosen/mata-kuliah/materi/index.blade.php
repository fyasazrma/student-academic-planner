@php
    $totalMateri = $materi->count();
    $totalDraft = $materi->where('status', 'draft')->count();
    $totalPublik = $materi->where('status', 'dipublikasikan')->count();
@endphp

<x-app-layout title="Materi Perkuliahan" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Materi Perkuliahan"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Kelola materi pembelajaran kelas ini. File tersimpan pada penyimpanan privat dan hanya dapat diunduh melalui halaman ini."
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
                        href="{{ route('dosen.mata-kuliah.materi.create', $kelas->getKey()) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <x-icon name="file" class="h-4 w-4" />
                        Tambah materi
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
                label="Total materi"
                :value="$totalMateri"
                hint="Materi pada kelas ini"
                icon="file"
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
                hint="Sudah tersedia untuk dilihat"
                icon="check"
                tone="kuliah"
            />
        </div>

        @if ($materi->isEmpty())
            <x-empty-state
                title="Belum ada materi"
                description="Tambahkan materi pertama untuk kelas ini. Materi dapat disimpan sebagai draft atau langsung dipublikasikan."
                icon="file"
            >
                <a
                    href="{{ route('dosen.mata-kuliah.materi.create', $kelas->getKey()) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Tambah materi
                </a>
            </x-empty-state>
        @else
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar materi">
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Materi</th>
                                <th scope="col" class="px-4 py-3">File</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Tanggal publikasi</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($materi as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['judul'] }}</p>
                                        <p class="mt-1 max-w-md text-xs leading-5 text-muted">
                                            {{ $item['deskripsi_ringkas'] ?: 'Tidak ada deskripsi.' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="max-w-[16rem] break-all text-xs font-semibold text-text">{{ $item['file_name'] }}</p>
                                        <p class="mt-1 text-[11px] text-muted">{{ $item['file_size_label'] }} · {{ $item['file_mime'] ?: '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['status_label']"
                                            :tone="$item['status'] === 'dipublikasikan' ? 'selesai' : 'peringatan'"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['dipublikasikan_label'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <button
                                                type="button"
                                                data-materi-detail-trigger
                                                data-materi-detail="{{ json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                data-materi-download="{{ route('dosen.mata-kuliah.materi.download', [$kelas->getKey(), $item['id']]) }}"
                                                data-materi-edit="{{ route('dosen.mata-kuliah.materi.edit', [$kelas->getKey(), $item['id']]) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Detail
                                            </button>
                                            <a
                                                href="{{ route('dosen.mata-kuliah.materi.edit', [$kelas->getKey(), $item['id']]) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Ubah
                                            </a>
                                            <a
                                                href="{{ route('dosen.mata-kuliah.materi.download', [$kelas->getKey(), $item['id']]) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Unduh
                                            </a>
                                            <button
                                                type="button"
                                                data-dosen-konfirmasi
                                                data-dosen-action="{{ route('dosen.mata-kuliah.materi.destroy', [$kelas->getKey(), $item['id']]) }}"
                                                data-dosen-method="DELETE"
                                                data-dosen-judul="Hapus materi"
                                                data-dosen-pesan="Materi dan file yang tersimpan akan dihapus permanen. Progres terkait juga ikut terhapus."
                                                data-dosen-label="{{ $item['judul'] }} — {{ $item['file_name'] }}"
                                                data-dosen-submit="Hapus materi"
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
                    @foreach ($materi as $item)
                        <article class="rounded-2xl border border-border bg-soft/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-text">{{ $item['judul'] }}</p>
                                    <p class="mt-1 max-w-full break-all text-xs text-muted">{{ $item['file_name'] }}</p>
                                </div>
                                <x-status-badge
                                    :label="$item['status_label']"
                                    :tone="$item['status'] === 'dipublikasikan' ? 'selesai' : 'peringatan'"
                                />
                            </div>
                            <p class="mt-2 text-xs leading-5 text-muted">
                                {{ $item['deskripsi_ringkas'] ?: 'Tidak ada deskripsi.' }}
                            </p>
                            <p class="mt-2 text-[11px] text-muted">
                                {{ $item['file_size_label'] }} · {{ $item['dipublikasikan_label'] }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    data-materi-detail-trigger
                                    data-materi-detail="{{ json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                    data-materi-download="{{ route('dosen.mata-kuliah.materi.download', [$kelas->getKey(), $item['id']]) }}"
                                    data-materi-edit="{{ route('dosen.mata-kuliah.materi.edit', [$kelas->getKey(), $item['id']]) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Detail
                                </button>
                                <a
                                    href="{{ route('dosen.mata-kuliah.materi.edit', [$kelas->getKey(), $item['id']]) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Ubah
                                </a>
                                <a
                                    href="{{ route('dosen.mata-kuliah.materi.download', [$kelas->getKey(), $item['id']]) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Unduh
                                </a>
                                <button
                                    type="button"
                                    data-dosen-konfirmasi
                                    data-dosen-action="{{ route('dosen.mata-kuliah.materi.destroy', [$kelas->getKey(), $item['id']]) }}"
                                    data-dosen-method="DELETE"
                                    data-dosen-judul="Hapus materi"
                                    data-dosen-pesan="Materi dan file yang tersimpan akan dihapus permanen. Progres terkait juga ikut terhapus."
                                    data-dosen-label="{{ $item['judul'] }} — {{ $item['file_name'] }}"
                                    data-dosen-submit="Hapus materi"
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

    @push('modals')
        <dialog
            id="dialog-detail-materi"
            class="w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-border bg-card p-0 text-text shadow-xl backdrop:bg-sidebar/50"
            aria-labelledby="dialog-detail-materi-judul"
        >
            <div class="p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-agenda-tugas">Detail materi</p>
                        <h3 id="dialog-detail-materi-judul" data-materi-field="judul" class="mt-1 text-lg font-bold text-text">Judul materi</h3>
                    </div>
                    <button
                        type="button"
                        data-materi-detail-close
                        class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        aria-label="Tutup detail materi"
                    >
                        <x-icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span data-materi-status-wrap="draft">
                        <x-status-badge label="Draft" tone="peringatan" />
                    </span>
                    <span data-materi-status-wrap="dipublikasikan" class="hidden">
                        <x-status-badge label="Dipublikasikan" tone="selesai" />
                    </span>
                    <p data-materi-field="dipublikasikan_label" class="text-xs text-muted">Belum dipublikasikan</p>
                </div>

                <p data-materi-field="deskripsi" class="mt-4 text-sm leading-6 text-text">Tidak ada deskripsi.</p>

                <dl class="mt-5 grid gap-4 rounded-xl border border-border bg-soft p-4 text-xs sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-muted">Nama file</dt>
                        <dd data-materi-field="file_name" class="mt-1 break-all font-semibold text-text">-</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-muted">Ukuran file</dt>
                        <dd data-materi-field="file_size_label" class="mt-1 font-semibold text-text">-</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-muted">Jenis file</dt>
                        <dd data-materi-field="file_mime" class="mt-1 font-semibold text-text">-</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-muted">Dibuat oleh</dt>
                        <dd data-materi-field="dibuat_oleh" class="mt-1 font-semibold text-text">-</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        data-materi-detail-close
                        class="rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Tutup
                    </button>
                    <a
                        data-materi-action="download"
                        href="{{ route('dosen.mata-kuliah.materi.index', $kelas->getKey()) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="file" class="h-4 w-4" />
                        Unduh
                    </a>
                    <a
                        data-materi-action="edit"
                        href="{{ route('dosen.mata-kuliah.materi.index', $kelas->getKey()) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <x-icon name="clipboard" class="h-4 w-4" />
                        Ubah materi
                    </a>
                </div>
            </div>
        </dialog>
    @endpush

    @include('dosen.mata-kuliah._dialog')
</x-app-layout>