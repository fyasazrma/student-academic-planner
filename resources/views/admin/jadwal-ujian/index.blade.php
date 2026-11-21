<x-app-layout title="Jadwal Ujian" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Jadwal Ujian UTS & UAS"
            eyebrow="Penjadwalan akademik"
            description="Kelola pengumuman UTS dan UAS sebagai agenda akademik institusional untuk seluruh mahasiswa dan dosen."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.jadwal-ujian.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-uts px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-uts/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts focus-visible:ring-offset-2"
                >
                    <x-icon name="exam" class="h-4 w-4" />
                    Tambah jadwal ujian
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

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar jadwal ujian institusional">
            <form method="GET" action="{{ route('admin.jadwal-ujian.index') }}" role="search" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="q" class="block text-xs font-semibold text-text">Cari</label>
                    <div class="relative mt-2">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ $filters['q'] }}"
                            maxlength="100"
                            placeholder="Judul atau deskripsi pengumuman"
                            autocomplete="off"
                            class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30"
                        >
                    </div>
                </div>

                <div>
                    <label for="jenis_ujian" class="block text-xs font-semibold text-text">Jenis ujian</label>
                    <select id="jenis_ujian" name="jenis_ujian" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30">
                        <option value="">Semua jenis</option>
                        @foreach ($jenisUjianOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['jenis_ujian'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tahun_akademik" class="block text-xs font-semibold text-text">Tahun akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30">
                        <option value="">Semua tahun akademik</option>
                        @foreach ($tahunOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['tahun_akademik'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="semester" class="block text-xs font-semibold text-text">Semester</label>
                    <select id="semester" name="semester" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30">
                        <option value="">Semua semester</option>
                        @foreach ($semesterOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['semester'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tanggal" class="block text-xs font-semibold text-text">Tanggal ujian</label>
                    <input
                        id="tanggal"
                        type="date"
                        name="tanggal"
                        value="{{ $filters['tanggal'] }}"
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30"
                    >
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button
                        type="submit"
                        class="rounded-xl bg-agenda-uts px-4 py-2.5 text-xs font-bold text-white transition hover:bg-agenda-uts/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                    >
                        Terapkan filter
                    </button>
                    @if (array_filter($filters) !== [])
                        <a
                            href="{{ route('admin.jadwal-ujian.index') }}"
                            class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if ($ujian->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        title="{{ array_filter($filters) === [] ? 'Belum ada jadwal ujian' : 'Tidak ada jadwal ditemukan' }}"
                        description="{{ array_filter($filters) === [] ? 'Tambahkan pengumuman UTS atau UAS institusional pertama.' : 'Ubah kata kunci atau filter untuk melihat jadwal lain.' }}"
                        icon="exam"
                    >
                        @if (array_filter($filters) === [])
                            <a
                                href="{{ route('admin.jadwal-ujian.create') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-agenda-uts px-3 py-2 text-xs font-bold text-white transition hover:bg-agenda-uts/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                            >
                                Tambah jadwal ujian
                            </a>
                        @else
                            <a
                                href="{{ route('admin.jadwal-ujian.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                            >
                                Lihat semua jadwal
                            </a>
                        @endif
                    </x-empty-state>
                </div>
            @else
                <p class="mt-5 text-xs font-semibold text-muted" role="status">
                    Menampilkan {{ $ujian->firstItem() }}–{{ $ujian->lastItem() }} dari {{ $ujian->total() }} jadwal ujian
                </p>

                <div class="mt-3 hidden overflow-x-auto rounded-xl border border-border md:block">
                    <table class="w-full min-w-[900px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Jenis</th>
                                <th scope="col" class="px-4 py-3">Judul pengumuman</th>
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Tanggal & jam</th>
                                <th scope="col" class="px-4 py-3">Lokasi</th>
                                <th scope="col" class="px-4 py-3">Publikasi</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($ujian as $item)
                                @php
                                    $waktu = $item['waktu_mulai'] === '-' && $item['waktu_selesai'] === '-'
                                        ? '-'
                                        : $item['waktu_mulai'].'–'.$item['waktu_selesai'];
                                @endphp
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['jenis_label']"
                                            :tone="$item['jenis_ujian'] === 'uas' ? 'uas' : 'uts'"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-text">{{ $item['judul'] }}</p>
                                        <p class="mt-0.5 text-xs text-muted">Agenda institusional</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['tahun'] }} · {{ $item['semester'] }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['tanggal_label'] }}</p>
                                        <p class="mt-0.5 inline-flex items-center gap-1.5 text-xs text-muted">
                                            <x-icon name="clock" class="h-3.5 w-3.5" />
                                            {{ $waktu }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['lokasi'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['publikasi_label'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ route('admin.jadwal-ujian.edit', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                                            >
                                                Ubah
                                            </a>
                                            <button
                                                type="button"
                                                data-jadwal-konfirmasi
                                                data-jadwal-action="{{ route('admin.jadwal-ujian.destroy', $item['id']) }}"
                                                data-jadwal-method="DELETE"
                                                data-jadwal-judul="Hapus jadwal ujian"
                                                data-jadwal-pesan="Agenda UTS/UAS institusional akan dihapus permanen. Data kelas, peserta, dan nilai tidak terpengaruh."
                                                data-jadwal-label="{{ $item['jenis_label'] }} — {{ $item['judul'] }}, {{ $item['tanggal_label'] }}"
                                                data-jadwal-submit="Hapus jadwal"
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

                <div class="mt-3 space-y-3 md:hidden">
                    @foreach ($ujian as $item)
                        @php
                            $waktu = $item['waktu_mulai'] === '-' && $item['waktu_selesai'] === '-'
                                ? '-'
                                : $item['waktu_mulai'].'–'.$item['waktu_selesai'];
                        @endphp
                        <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-text">{{ $item['judul'] }}</p>
                                    <p class="mt-0.5 text-xs text-muted">Agenda institusional · {{ $item['tahun'] }} · {{ $item['semester'] }}</p>
                                </div>
                                <x-status-badge
                                    :label="$item['jenis_label']"
                                    :tone="$item['jenis_ujian'] === 'uas' ? 'uas' : 'uts'"
                                />
                            </div>
                            <p class="mt-3 text-xs text-text">
                                <span class="font-semibold">{{ $item['tanggal_label'] }}</span> · {{ $waktu }}
                            </p>
                            <p class="mt-1 text-xs text-muted">Lokasi {{ $item['lokasi'] ?: '-' }} · {{ $item['publikasi_label'] }}</p>
                            <div class="mt-4 flex gap-2">
                                <a
                                    href="{{ route('admin.jadwal-ujian.edit', $item['id']) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                                >
                                    Ubah
                                </a>
                                <button
                                    type="button"
                                    data-jadwal-konfirmasi
                                    data-jadwal-action="{{ route('admin.jadwal-ujian.destroy', $item['id']) }}"
                                    data-jadwal-method="DELETE"
                                    data-jadwal-judul="Hapus jadwal ujian"
                                    data-jadwal-pesan="Agenda UTS/UAS institusional akan dihapus permanen. Data kelas, peserta, dan nilai tidak terpengaruh."
                                    data-jadwal-label="{{ $item['jenis_label'] }} — {{ $item['judul'] }}, {{ $item['tanggal_label'] }}"
                                    data-jadwal-submit="Hapus jadwal"
                                    class="flex-1 rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($ujian->hasPages())
                    <nav class="mt-6" aria-label="Paginasi daftar jadwal ujian">
                        {{ $ujian->links() }}
                    </nav>
                @endif
            @endif
        </section>
    </div>

    <x-admin.jadwal-dialog />
</x-app-layout>
