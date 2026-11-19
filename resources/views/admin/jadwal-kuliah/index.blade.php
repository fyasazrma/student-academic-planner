<x-app-layout title="Jadwal Kuliah" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Jadwal Kuliah"
            eyebrow="Penjadwalan akademik"
            description="Atur jadwal perkuliahan seluruh kelas. Bentrok kelas, dosen pengampu, dan ruangan diperiksa otomatis."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.jadwal-kuliah.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="calendar" class="h-4 w-4" />
                    Tambah jadwal
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

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar jadwal kuliah">
            <form method="GET" action="{{ route('admin.jadwal-kuliah.index') }}" role="search" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                            placeholder="Mata kuliah, kelas, atau dosen"
                            autocomplete="off"
                            class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                        >
                    </div>
                </div>

                <div>
                    <label for="tahun_akademik" class="block text-xs font-semibold text-text">Tahun akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30">
                        <option value="">Semua tahun akademik</option>
                        @foreach ($tahunOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['tahun_akademik'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="semester" class="block text-xs font-semibold text-text">Semester</label>
                    <select id="semester" name="semester" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30">
                        <option value="">Semua semester</option>
                        @foreach ($semesterOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['semester'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="hari" class="block text-xs font-semibold text-text">Hari</label>
                    <select id="hari" name="hari" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30">
                        <option value="">Semua hari</option>
                        @foreach ($hariOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['hari'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="dosen" class="block text-xs font-semibold text-text">Dosen pengampu</label>
                    <select id="dosen" name="dosen" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30">
                        <option value="">Semua dosen</option>
                        @foreach ($dosenOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $filters['dosen'] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button
                        type="submit"
                        class="rounded-xl bg-agenda-tugas px-4 py-2.5 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Terapkan filter
                    </button>
                    @if (array_filter($filters) !== [])
                        <a
                            href="{{ route('admin.jadwal-kuliah.index') }}"
                            class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if ($jadwal->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        title="{{ array_filter($filters) === [] ? 'Belum ada jadwal kuliah' : 'Tidak ada jadwal ditemukan' }}"
                        description="{{ array_filter($filters) === [] ? 'Tambahkan jadwal pertama agar kalender seluruh role menampilkan perkuliahan.' : 'Ubah kata kunci atau filter untuk melihat jadwal lain.' }}"
                        icon="calendar"
                    >
                        @if (array_filter($filters) === [])
                            <a
                                href="{{ route('admin.jadwal-kuliah.create') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-agenda-tugas px-3 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Tambah jadwal
                            </a>
                        @else
                            <a
                                href="{{ route('admin.jadwal-kuliah.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Lihat semua jadwal
                            </a>
                        @endif
                    </x-empty-state>
                </div>
            @else
                <p class="mt-5 text-xs font-semibold text-muted" role="status">
                    Menampilkan {{ $jadwal->firstItem() }}–{{ $jadwal->lastItem() }} dari {{ $jadwal->total() }} jadwal
                </p>

                <div class="mt-3 hidden overflow-x-auto rounded-xl border border-border md:block">
                    <table class="w-full min-w-[1000px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah / kelas</th>
                                <th scope="col" class="px-4 py-3">Dosen pengampu</th>
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Hari & jam</th>
                                <th scope="col" class="px-4 py-3">Ruang</th>
                                <th scope="col" class="px-4 py-3">Jenis</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($jadwal as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                                        <p class="mt-0.5 text-xs text-muted">Kelas {{ $item['kode_kelas'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-text">{{ $item['dosen'] }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['tahun'] }} · {{ $item['semester'] }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['hari_label'] }}</p>
                                        <p class="mt-0.5 inline-flex items-center gap-1.5 text-xs text-muted">
                                            <x-icon name="clock" class="h-3.5 w-3.5" />
                                            {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['ruang'] ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['jenis_label']"
                                            :tone="$item['jenis'] === 'kuliah' ? 'kuliah' : ($item['jenis'] === 'praktikum' ? 'akademik' : 'netral')"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ route('admin.jadwal-kuliah.edit', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Ubah
                                            </a>
                                            <button
                                                type="button"
                                                data-jadwal-konfirmasi
                                                data-jadwal-action="{{ route('admin.jadwal-kuliah.destroy', $item['id']) }}"
                                                data-jadwal-method="DELETE"
                                                data-jadwal-judul="Hapus jadwal kuliah"
                                                data-jadwal-pesan="Jadwal akan dihapus permanen. Kelas, peserta, dan jadwal lain tidak terpengaruh."
                                                data-jadwal-label="{{ $item['mata_kuliah'] }} — {{ $item['hari_label'] }}, {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}"
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
                    @foreach ($jadwal as $item)
                        <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                                    <p class="mt-0.5 text-xs text-muted">Kelas {{ $item['kode_kelas'] }} · {{ $item['dosen'] }}</p>
                                </div>
                                <x-status-badge
                                    :label="$item['jenis_label']"
                                    :tone="$item['jenis'] === 'kuliah' ? 'kuliah' : ($item['jenis'] === 'praktikum' ? 'akademik' : 'netral')"
                                />
                            </div>
                            <p class="mt-3 text-xs text-text">
                                <span class="font-semibold">{{ $item['hari_label'] }}</span> · {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}
                            </p>
                            <p class="mt-1 text-xs text-muted">{{ $item['tahun'] }} · {{ $item['semester'] }} · Ruang {{ $item['ruang'] ?: '-' }}</p>
                            <div class="mt-4 flex gap-2">
                                <a
                                    href="{{ route('admin.jadwal-kuliah.edit', $item['id']) }}"
                                    class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Ubah
                                </a>
                                <button
                                    type="button"
                                    data-jadwal-konfirmasi
                                    data-jadwal-action="{{ route('admin.jadwal-kuliah.destroy', $item['id']) }}"
                                    data-jadwal-method="DELETE"
                                    data-jadwal-judul="Hapus jadwal kuliah"
                                    data-jadwal-pesan="Jadwal akan dihapus permanen. Kelas, peserta, dan jadwal lain tidak terpengaruh."
                                    data-jadwal-label="{{ $item['mata_kuliah'] }} — {{ $item['hari_label'] }}, {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}"
                                    data-jadwal-submit="Hapus jadwal"
                                    class="flex-1 rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($jadwal->hasPages())
                    <nav class="mt-6" aria-label="Paginasi daftar jadwal kuliah">
                        {{ $jadwal->links() }}
                    </nav>
                @endif
            @endif
        </section>
    </div>

    <x-admin.jadwal-dialog />
</x-app-layout>
