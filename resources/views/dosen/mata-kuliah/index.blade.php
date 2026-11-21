<x-app-layout title="Mata Kuliah Saya" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Mata Kuliah Saya"
            eyebrow="Pengampu kelas"
            description="Kelola kelas yang Anda ampu. Kelas historis tetap ditampilkan walaupun data induknya sudah dinonaktifkan."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <x-icon name="book" class="h-4 w-4" />
                    Buat kelas
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

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar kelas dosen">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-text">Daftar kelas</h3>
                    <p class="mt-1 text-xs leading-5 text-muted">Cari berdasarkan kode atau nama mata kuliah, kode kelas, atau nama kelas.</p>
                </div>

                <form method="GET" action="{{ route('dosen.mata-kuliah.index') }}" class="w-full sm:max-w-sm" role="search">
                    <label for="q" class="sr-only">Cari kelas</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ $kataKunci }}"
                            maxlength="100"
                            placeholder="Cari kelas"
                            autocomplete="off"
                            class="w-full rounded-xl border border-border bg-surface py-2.5 pl-10 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                        >
                    </div>
                    @if ($kataKunci !== '')
                        <a
                            href="{{ route('dosen.mata-kuliah.index') }}"
                            class="mt-2 inline-block text-xs font-semibold text-muted transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Hapus pencarian
                        </a>
                    @endif
                </form>
            </div>

            @if ($kelas->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        :title="$kataKunci === '' ? 'Belum ada kelas' : 'Tidak ada kelas ditemukan'"
                        :description="$kataKunci === '' ? 'Buat kelas pertama untuk mulai mengelola pengampu.' : 'Coba kata kunci lain atau lihat seluruh kelas.'"
                        icon="book"
                    >
                        @if ($kataKunci === '')
                            <a
                                href="{{ route('dosen.mata-kuliah.create') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                Buat kelas
                            </a>
                        @else
                            <a
                                href="{{ route('dosen.mata-kuliah.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Lihat semua kelas
                            </a>
                        @endif
                    </x-empty-state>
                </div>
            @else
                <p class="mt-5 text-xs font-semibold text-muted" role="status">
                    Menampilkan {{ $kelas->firstItem() }}–{{ $kelas->lastItem() }} dari {{ $kelas->total() }} kelas
                </p>

                <div class="mt-3 overflow-x-auto rounded-xl border border-border">
                    <table class="w-full min-w-[900px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah</th>
                                <th scope="col" class="px-4 py-3">Kelas</th>
                                <th scope="col" class="px-4 py-3">Tahun akademik</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Peserta</th>
                                <th scope="col" class="px-4 py-3">Kunci gabung</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($kelas as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-text">{{ $item['nama_mata_kuliah'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $item['kode_mata_kuliah'] }}</p>
                                        @if (! $item['mata_kuliah_aktif'])
                                            <x-status-badge class="mt-1" label="MK historis" tone="netral" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a
                                            href="{{ route('dosen.mata-kuliah.show', $item['id']) }}"
                                            class="font-bold text-agenda-tugas underline-offset-2 transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                        >{{ $item['kode_kelas'] }}</a>
                                        @if ($item['nama_kelas'])
                                            <p class="mt-0.5 text-[11px] text-muted">{{ $item['nama_kelas'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-text">{{ $item['tahun_akademik'] }}</p>
                                        @if (! $item['tahun_akademik_aktif'])
                                            <x-status-badge class="mt-1" label="Periode historis" tone="netral" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['status_label']"
                                            :tone="$item['status_pendaftaran'] === 'buka' ? 'selesai' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['kapasitas_label'] }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['memiliki_kode_gabung'] ? 'Sudah dibuat' : 'Belum dibuat'"
                                            :tone="$item['memiliki_kode_gabung'] ? 'selesai' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route('dosen.mata-kuliah.show', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Detail
                                            </a>
                                            <a
                                                href="{{ route('dosen.mata-kuliah.edit', $item['id']) }}"
                                                class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($kelas->hasPages())
                    <nav class="mt-6" aria-label="Paginasi daftar kelas">
                        {{ $kelas->links() }}
                    </nav>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>