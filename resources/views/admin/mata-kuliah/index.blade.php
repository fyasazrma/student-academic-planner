<x-app-layout title="Mata Kuliah" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Mata Kuliah"
            eyebrow="Manajemen admin"
            description="Kelola data induk mata kuliah yang dipakai dosen untuk membuat kelas dan ditemukan mahasiswa."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.mata-kuliah.create') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <x-icon name="book" class="h-4 w-4" />
                    Tambah mata kuliah
                </a>
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

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Daftar mata kuliah">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-text">Daftar mata kuliah</h3>
                    <p class="mt-1 text-xs leading-5 text-muted">
                        Cari berdasarkan kode atau nama mata kuliah.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.mata-kuliah.index') }}" class="w-full sm:max-w-sm" role="search">
                    <label for="q" class="sr-only">Cari kode atau nama mata kuliah</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ $kataKunci }}"
                            maxlength="100"
                            placeholder="Cari kode atau nama"
                            autocomplete="off"
                            class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                        >
                    </div>
                    @if ($kataKunci !== '')
                        <a
                            href="{{ route('admin.mata-kuliah.index') }}"
                            class="mt-2 inline-block text-xs font-semibold text-muted transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Hapus pencarian
                        </a>
                    @endif
                </form>
            </div>

            @if ($mataKuliah->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        :title="$kataKunci === '' ? 'Belum ada mata kuliah' : 'Tidak ada mata kuliah ditemukan'"
                        :description="$kataKunci === '' ? 'Tambahkan data induk pertama agar dosen dapat membuat kelas.' : 'Coba kata kunci lain atau lihat seluruh data.'"
                        icon="book"
                    >
                        @if ($kataKunci === '')
                            <a
                                href="{{ route('admin.mata-kuliah.create') }}"
                                class="inline-flex items-center justify-center rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                Tambah mata kuliah
                            </a>
                        @else
                            <a
                                href="{{ route('admin.mata-kuliah.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Lihat semua mata kuliah
                            </a>
                        @endif
                    </x-empty-state>
                </div>
            @else
                <p class="mt-5 text-xs font-semibold text-muted" role="status">
                    Menampilkan {{ $mataKuliah->firstItem() }}–{{ $mataKuliah->lastItem() }} dari {{ $mataKuliah->total() }} mata kuliah
                </p>

                <div class="mt-3 overflow-x-auto rounded-xl border border-border">
                    <table class="w-full min-w-[880px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Kode</th>
                                <th scope="col" class="px-4 py-3">Nama mata kuliah</th>
                                <th scope="col" class="px-4 py-3">SKS</th>
                                <th scope="col" class="px-4 py-3">Program studi</th>
                                <th scope="col" class="px-4 py-3">Semester</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Kelas</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($mataKuliah as $item)
                                <tr class="align-middle">
                                    <td class="px-4 py-3 font-bold text-text">{{ $item['kode'] }}</td>
                                    <td class="px-4 py-3 text-text">{{ $item['nama'] }}</td>
                                    <td class="px-4 py-3 text-text">{{ $item['sks'] }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item['program_studi'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ $item['semester'] !== null ? 'Semester '.$item['semester'] : '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['status_label']"
                                            :tone="$item['aktif'] ? 'selesai' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $item['jumlah_kelas'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route('admin.mata-kuliah.edit', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Edit
                                            </a>
                                            <button
                                                type="button"
                                                data-mk-konfirmasi
                                                data-mk-action="{{ route('admin.mata-kuliah.status', $item['id']) }}"
                                                data-mk-method="POST"
                                                data-mk-status="{{ $item['aktif'] ? '0' : '1' }}"
                                                data-mk-judul="{{ $item['aktif'] ? 'Nonaktifkan mata kuliah' : 'Aktifkan mata kuliah' }}"
                                                data-mk-pesan="{{ $item['aktif'] ? 'Mata kuliah tidak akan muncul sebagai pilihan kelas baru bagi mahasiswa. Kelas dan peserta yang sudah ada tetap dipertahankan.' : 'Mata kuliah akan kembali tersedia sebagai pilihan kelas baru.' }}"
                                                data-mk-label="{{ $item['kode'] }} — {{ $item['nama'] }}"
                                                data-mk-submit="{{ $item['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                {{ $item['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            @if ($item['jumlah_kelas'] > 0)
                                                <button
                                                    type="button"
                                                    disabled
                                                    title="Mata kuliah yang memiliki kelas tidak dapat dihapus"
                                                    class="cursor-not-allowed rounded-lg border border-border bg-soft px-3 py-1.5 text-xs font-semibold text-muted"
                                                >
                                                    Hapus
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    data-mk-konfirmasi
                                                    data-mk-action="{{ route('admin.mata-kuliah.destroy', $item['id']) }}"
                                                    data-mk-method="DELETE"
                                                    data-mk-judul="Hapus mata kuliah"
                                                    data-mk-pesan="Data induk ini akan dihapus permanen. Pastikan mata kuliah ini tidak memiliki kelas."
                                                    data-mk-label="{{ $item['kode'] }} — {{ $item['nama'] }}"
                                                    data-mk-submit="Hapus"
                                                    class="rounded-lg border border-danger/40 bg-card px-3 py-1.5 text-xs font-semibold text-danger transition hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                                                >
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($mataKuliah->hasPages())
                    <nav class="mt-6" aria-label="Paginasi daftar mata kuliah">
                        {{ $mataKuliah->links() }}
                    </nav>
                @endif
            @endif
        </section>
    </div>

    @push('modals')
        <dialog
            id="dialog-konfirmasi-mk"
            class="ui-dialog max-w-md"
            aria-labelledby="dialog-konfirmasi-mk-judul"
        >
            <form id="form-konfirmasi-mk" method="POST" action="" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="_method" value="POST" data-mk-method-input>
                <input type="hidden" name="aktif" value="1" data-mk-status-input>

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-agenda-tugas">Konfirmasi admin</p>
                        <h3 id="dialog-konfirmasi-mk-judul" data-mk-dialog-judul class="mt-1 text-lg font-bold text-text">Konfirmasi aksi</h3>
                    </div>
                    <button
                        type="button"
                        data-mk-dialog-close
                        class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        aria-label="Tutup dialog konfirmasi"
                    >
                        <x-icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <p data-mk-dialog-pesan class="mt-3 text-sm leading-6 text-muted">
                    Konfirmasi aksi pada mata kuliah terpilih.
                </p>
                <p data-mk-dialog-label class="mt-3 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-text">
                    Mata kuliah terpilih
                </p>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        data-mk-dialog-close
                        class="rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <span data-mk-dialog-submit>Konfirmasi</span>
                    </button>
                </div>
            </form>
        </dialog>
    @endpush
</x-app-layout>