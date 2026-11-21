<x-app-layout title="Jadwal Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Jadwal Perkuliahan"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Kelola jadwal mingguan kelas ini. Jadwal dosen lain tidak dapat diakses dari halaman ini."
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
                        href="{{ route('dosen.mata-kuliah.jadwal.create', $kelas) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <x-icon name="calendar" class="h-4 w-4" />
                        Tambah jadwal
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('status') }}
            </div>
        @endif

        @if ($jadwal->isEmpty())
            <x-empty-state
                title="Belum ada jadwal"
                description="Tambahkan jadwal pertama agar mahasiswa dapat melihat jadwal kuliah kelas ini."
                icon="calendar"
            >
                <a
                    href="{{ route('dosen.mata-kuliah.jadwal.create', $kelas) }}"
                    class="inline-flex items-center justify-center rounded-full bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Tambah jadwal
                </a>
            </x-empty-state>
        @else
            <p class="text-xs font-semibold text-muted" role="status">
                Menampilkan {{ $jadwal->count() }} jadwal pada kelas ini.
            </p>

            <div class="hidden overflow-x-auto rounded-card bg-card shadow-card md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3">Hari</th>
                            <th scope="col" class="px-4 py-3">Waktu</th>
                            <th scope="col" class="px-4 py-3">Ruang</th>
                            <th scope="col" class="px-4 py-3">Jenis</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($jadwal as $item)
                            <tr class="align-middle transition hover:bg-soft/60">
                                <td class="px-4 py-3 font-bold text-text">{{ $item['hari_label'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-text">
                                        <x-icon name="clock" class="h-4 w-4 text-muted" />
                                        {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}
                                    </span>
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
                                            href="{{ route('dosen.mata-kuliah.jadwal.edit', [$kelas, $item['id']]) }}"
                                            class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                        >
                                            Ubah
                                        </a>
                                        <button
                                            type="button"
                                            data-dosen-konfirmasi
                                            data-dosen-action="{{ route('dosen.mata-kuliah.jadwal.destroy', [$kelas, $item['id']]) }}"
                                            data-dosen-method="DELETE"
                                            data-dosen-judul="Hapus jadwal"
                                            data-dosen-pesan="Jadwal yang dihapus tidak dapat dikembalikan. Pastikan jadwal ini memang tidak digunakan lagi."
                                            data-dosen-label="{{ $item['hari_label'] }}, {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}"
                                            data-dosen-submit="Hapus jadwal"
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
                @foreach ($jadwal as $item)
                    <div class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-text">{{ $item['hari_label'] }}</p>
                                <p class="mt-1 inline-flex items-center gap-1.5 text-xs text-muted">
                                    <x-icon name="clock" class="h-4 w-4" />
                                    {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}
                                </p>
                            </div>
                            <x-status-badge
                                :label="$item['jenis_label']"
                                :tone="$item['jenis'] === 'kuliah' ? 'kuliah' : ($item['jenis'] === 'praktikum' ? 'akademik' : 'netral')"
                            />
                        </div>
                        <p class="mt-2 text-xs text-muted">Ruang: <span class="font-semibold text-text">{{ $item['ruang'] ?: '-' }}</span></p>
                        <div class="mt-4 flex gap-2">
                            <a
                                href="{{ route('dosen.mata-kuliah.jadwal.edit', [$kelas, $item['id']]) }}"
                                class="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Ubah
                            </a>
                            <button
                                type="button"
                                data-dosen-konfirmasi
                                data-dosen-action="{{ route('dosen.mata-kuliah.jadwal.destroy', [$kelas, $item['id']]) }}"
                                data-dosen-method="DELETE"
                                data-dosen-judul="Hapus jadwal"
                                data-dosen-pesan="Jadwal yang dihapus tidak dapat dikembalikan. Pastikan jadwal ini memang tidak digunakan lagi."
                                data-dosen-label="{{ $item['hari_label'] }}, {{ $item['jam_mulai'] }}–{{ $item['jam_selesai'] }}"
                                data-dosen-submit="Hapus jadwal"
                                class="flex-1 rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @include('dosen.mata-kuliah._dialog')
</x-app-layout>