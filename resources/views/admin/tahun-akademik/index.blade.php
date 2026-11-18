<x-app-layout title="Tahun Akademik" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Tahun Akademik"
            eyebrow="Manajemen admin"
            description="Kelola periode akademik dan semester. Hanya satu periode yang boleh aktif; periode baru dibuat nonaktif."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.tahun-akademik.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="calendar" class="h-4 w-4" />
                    Tambah tahun akademik
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

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar tahun akademik">
            <div>
                <h3 class="text-base font-bold text-text">Daftar periode akademik</h3>
                <p class="mt-1 text-xs leading-5 text-muted">
                    Mengaktifkan periode baru akan menonaktifkan periode aktif sebelumnya. Peserta dan riwayat akademik tidak dihapus.
                </p>
            </div>

            @if ($tahunAkademik->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        title="Belum ada tahun akademik"
                        description="Tambahkan periode pertama untuk mengatur tahun akademik dan semester aktif."
                        icon="calendar"
                    >
                        <a
                            href="{{ route('admin.tahun-akademik.create') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-agenda-tugas px-3 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Tambah tahun akademik
                        </a>
                    </x-empty-state>
                </div>
            @else
                <p class="mt-5 text-xs font-semibold text-muted" role="status">
                    Menampilkan {{ $tahunAkademik->firstItem() }}–{{ $tahunAkademik->lastItem() }} dari {{ $tahunAkademik->total() }} tahun akademik
                </p>

                <div class="mt-3 overflow-x-auto rounded-xl border border-border">
                    <table class="w-full min-w-[820px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Semester</th>
                                <th scope="col" class="px-4 py-3">Tanggal mulai</th>
                                <th scope="col" class="px-4 py-3">Tanggal selesai</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Kelas</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($tahunAkademik as $item)
                                <tr class="align-middle">
                                    <td class="px-4 py-3 font-bold text-text">{{ $item['label'] }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item['semester_label'] }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item['tanggal_mulai_label'] }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item['tanggal_selesai_label'] }}</td>
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
                                                href="{{ route('admin.tahun-akademik.edit', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Edit
                                            </a>
                                            <button
                                                type="button"
                                                data-ta-konfirmasi
                                                data-ta-action="{{ route('admin.tahun-akademik.status', $item['id']) }}"
                                                data-ta-method="POST"
                                                data-ta-status="{{ $item['aktif'] ? '0' : '1' }}"
                                                data-ta-judul="{{ $item['aktif'] ? 'Nonaktifkan tahun akademik' : 'Aktifkan tahun akademik' }}"
                                                data-ta-pesan="{{ $item['aktif'] ? 'Periode ini tidak akan dipakai untuk pendaftaran kelas baru. Peserta dan riwayat akademik yang sudah ada tetap dipertahankan.' : 'Periode ini akan menjadi satu-satunya tahun akademik aktif. Periode aktif sebelumnya akan dinonaktifkan.' }}"
                                                data-ta-label="{{ $item['label'] }} — {{ $item['semester_label'] }}"
                                                data-ta-submit="{{ $item['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                {{ $item['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            @if ($item['jumlah_kelas'] > 0)
                                                <button
                                                    type="button"
                                                    disabled
                                                    title="Tahun akademik yang memiliki kelas tidak dapat dihapus"
                                                    class="cursor-not-allowed rounded-lg border border-border bg-soft px-3 py-1.5 text-xs font-semibold text-muted"
                                                >
                                                    Hapus
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    data-ta-konfirmasi
                                                    data-ta-action="{{ route('admin.tahun-akademik.destroy', $item['id']) }}"
                                                    data-ta-method="DELETE"
                                                    data-ta-judul="Hapus tahun akademik"
                                                    data-ta-pesan="Periode ini akan dihapus permanen. Pastikan tidak ada kelas yang memakai tahun akademik ini."
                                                    data-ta-label="{{ $item['label'] }} — {{ $item['semester_label'] }}"
                                                    data-ta-submit="Hapus"
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

                @if ($tahunAkademik->hasPages())
                    <nav class="mt-6" aria-label="Paginasi daftar tahun akademik">
                        {{ $tahunAkademik->links() }}
                    </nav>
                @endif
            @endif
        </section>
    </div>

    @push('modals')
        <dialog
            id="dialog-konfirmasi-ta"
            class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-border bg-card p-0 text-text shadow-xl backdrop:bg-sidebar/50"
            aria-labelledby="dialog-konfirmasi-ta-judul"
        >
            <form id="form-konfirmasi-ta" method="POST" action="" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="_method" value="POST" data-ta-method-input>
                <input type="hidden" name="aktif" value="1" data-ta-status-input>

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-agenda-tugas">Konfirmasi admin</p>
                        <h3 id="dialog-konfirmasi-ta-judul" data-ta-dialog-judul class="mt-1 text-lg font-bold text-text">Konfirmasi aksi</h3>
                    </div>
                    <button
                        type="button"
                        data-ta-dialog-close
                        class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        aria-label="Tutup dialog konfirmasi"
                    >
                        <x-icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <p data-ta-dialog-pesan class="mt-3 text-sm leading-6 text-muted">
                    Konfirmasi aksi pada tahun akademik terpilih.
                </p>
                <p data-ta-dialog-label class="mt-3 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-text">
                    Tahun akademik terpilih
                </p>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        data-ta-dialog-close
                        class="rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-agenda-tugas px-4 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                    >
                        <span data-ta-dialog-submit>Konfirmasi</span>
                    </button>
                </div>
            </form>
        </dialog>
    @endpush
</x-app-layout>
