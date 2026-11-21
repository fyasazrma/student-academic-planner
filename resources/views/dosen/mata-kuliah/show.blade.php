<x-app-layout title="Detail Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="{{ $detail['kode_kelas'] }} — {{ $detail['nama_mata_kuliah'] }}"
            eyebrow="Mata kuliah saya"
            description="Detail pengampu, status pendaftaran, kunci gabung, dan jumlah peserta kelas."
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('dosen.mata-kuliah.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="chevron-left" class="h-4 w-4" />
                        Kembali ke daftar
                    </a>
                    <a
                        href="{{ route('dosen.mata-kuliah.jadwal.index', $kelas->id) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="calendar" class="h-4 w-4" />
                        Kelola jadwal
                    </a>
                    <a
                        href="{{ route('dosen.mata-kuliah.tugas.index', $kelas->id) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="clipboard" class="h-4 w-4" />
                        Kelola tugas
                    </a>
                    <a
                        href="{{ route('dosen.mata-kuliah.materi.index', $kelas->id) }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        <x-icon name="file" class="h-4 w-4" />
                        Kelola materi
                    </a>
                    <a
                        href="{{ route('dosen.mata-kuliah.edit', $kelas->id) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <x-icon name="check" class="h-4 w-4" />
                        Edit kelas
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

        @if (session('kode_gabung'))
            <div
                role="status"
                class="rounded-2xl border border-warning/40 bg-warning/10 p-5"
                data-kode-gabung-container
            >
                <div class="flex items-start gap-3">
                    <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-warning" />
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-text">
                            {{ session('kode_gabung_jenis') === 'rotasi' ? 'Kunci gabung baru' : 'Kunci gabung dibuat' }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-muted">
                            Salin kunci berikut sekarang. Kunci asli hanya ditampilkan sekali dan tidak dapat dilihat kembali setelah halaman ini ditutup atau dimuat ulang.
                        </p>
                        <p class="mt-3 break-all rounded-xl border border-warning/40 bg-card px-4 py-3 font-mono text-lg font-bold tracking-[0.12em] text-text" data-kode-gabung>
                            {{ session('kode_gabung') }}
                        </p>
                        @if (session('kode_gabung_jenis') === 'rotasi')
                            <p class="mt-2 text-[11px] font-semibold text-danger">Kunci lama sudah tidak berlaku. Peserta yang telah terdaftar tetap tersimpan.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Identitas kelas">
                    <h2 class="text-sm font-bold text-text">Identitas kelas</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Mata kuliah</dt>
                            <dd class="mt-1 text-sm font-semibold text-text">{{ $detail['kode_mata_kuliah'] }} — {{ $detail['nama_mata_kuliah'] }}</dd>
                            <dd class="mt-1 text-[11px] text-muted">SKS: {{ $detail['sks'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Tahun akademik</dt>
                            <dd class="mt-1 text-sm font-semibold text-text">{{ $detail['tahun_akademik'] }}</dd>
                            <x-status-badge
                                class="mt-1"
                                :label="$detail['tahun_akademik_aktif'] ? 'Periode aktif' : 'Periode historis'"
                                :tone="$detail['tahun_akademik_aktif'] ? 'selesai' : 'netral'"
                            />
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Kode kelas</dt>
                            <dd class="mt-1 font-mono text-sm font-bold text-text">{{ $detail['kode_kelas'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Nama kelas</dt>
                            <dd class="mt-1 text-sm text-text">{{ $detail['nama_kelas'] ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Dosen pengampu</dt>
                            <dd class="mt-1 text-sm text-text">{{ $detail['nama_dosen'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Kapasitas</dt>
                            <dd class="mt-1 text-sm text-text">{{ $detail['kapasitas_label'] }}</dd>
                            <dd class="mt-1 text-[11px] text-muted">{{ $detail['jumlah_peserta_aktif'] }} peserta aktif</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Ringkasan materi">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-text">Ringkasan materi</h2>
                            <p class="mt-1 text-xs leading-5 text-muted">
                                Materi pada kelas ini. Draft belum dapat dilihat mahasiswa.
                            </p>
                        </div>
                        <a
                            href="{{ route('dosen.mata-kuliah.materi.index', $kelas->id) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            <x-icon name="file" class="h-4 w-4" />
                            Lihat semua materi
                        </a>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-border bg-soft p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">Draft</p>
                            <p class="mt-1 text-2xl font-bold leading-none text-text">{{ $detail['jumlah_materi_draft'] }}</p>
                            <p class="mt-2 text-[11px] text-muted">Belum dipublikasikan</p>
                        </div>
                        <div class="rounded-xl border border-border bg-soft p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">Dipublikasikan</p>
                            <p class="mt-1 text-2xl font-bold leading-none text-text">{{ $detail['jumlah_materi_dipublikasikan'] }}</p>
                            <p class="mt-2 text-[11px] text-muted">Sudah tersedia untuk dilihat</p>
                        </div>
                    </div>

                    @if (count($detail['materi_terbaru']) > 0)
                        <ul class="mt-4 divide-y divide-border">
                            @foreach ($detail['materi_terbaru'] as $materiItem)
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-text">{{ $materiItem['judul'] }}</p>
                                        <p class="mt-1 break-all text-[11px] text-muted">
                                            {{ $materiItem['file_name'] }} · {{ $materiItem['file_size_label'] }}
                                        </p>
                                    </div>
                                    <x-status-badge
                                        class="shrink-0"
                                        :label="$materiItem['status_label']"
                                        :tone="$materiItem['status_tone']"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-4 rounded-xl border border-dashed border-border bg-soft px-4 py-3 text-xs leading-5 text-muted">
                            Belum ada materi pada kelas ini.
                        </p>
                    @endif

                    <p class="mt-4 text-[11px] leading-5 text-muted">
                        Total {{ $detail['jumlah_materi'] }} materi pada kelas ini.
                    </p>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Kelola pendaftaran dan kunci">
                    <h2 class="text-sm font-bold text-text">Kelola pendaftaran</h2>
                    <p class="mt-1 text-xs leading-5 text-muted">
                        Pendaftaran hanya dapat dibuka jika kunci gabung sudah dibuat. Rotasi kunci langsung membatalkan kunci lama tanpa menghapus peserta.
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-border bg-soft p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold text-text">Status pendaftaran</p>
                                <x-status-badge
                                    :label="$detail['status_label']"
                                    :tone="$detail['status_pendaftaran'] === 'buka' ? 'selesai' : 'netral'"
                                />
                            </div>

                            @if (! $detail['memiliki_kode_gabung'] && $detail['status_pendaftaran'] === 'tutup')
                                <button
                                    type="button"
                                    disabled
                                    title="Buat kunci gabung terlebih dahulu"
                                    class="mt-4 w-full cursor-not-allowed rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-muted"
                                >
                                    Buat kunci gabung terlebih dahulu
                                </button>
                            @else
                                <button
                                    type="button"
                                    data-dosen-konfirmasi
                                    data-dosen-action="{{ route('dosen.mata-kuliah.status', $kelas->id) }}"
                                    data-dosen-judul="{{ $detail['status_pendaftaran'] === 'buka' ? 'Tutup pendaftaran' : 'Buka pendaftaran' }}"
                                    data-dosen-pesan="{{ $detail['status_pendaftaran'] === 'buka' ? 'Mahasiswa tidak akan dapat bergabung menggunakan kunci kelas ini. Peserta yang sudah terdaftar tetap tersimpan.' : 'Mahasiswa dapat menemukan kelas ini menggunakan kunci gabung yang sudah dibuat.' }}"
                                    data-dosen-label="{{ $detail['kode_kelas'] }} — {{ $detail['nama_mata_kuliah'] }}"
                                    data-dosen-submit="{{ $detail['status_pendaftaran'] === 'buka' ? 'Tutup pendaftaran' : 'Buka pendaftaran' }}"
                                    data-dosen-status="{{ $detail['status_pendaftaran'] === 'buka' ? 'tutup' : 'buka' }}"
                                    class="mt-4 w-full rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    {{ $detail['status_pendaftaran'] === 'buka' ? 'Tutup pendaftaran' : 'Buka pendaftaran' }}
                                </button>
                            @endif
                        </div>

                        <div class="rounded-xl border border-border bg-soft p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold text-text">Kunci gabung</p>
                                <x-status-badge
                                    :label="$detail['kode_gabung_status_label']"
                                    :tone="$detail['memiliki_kode_gabung'] ? 'selesai' : 'netral'"
                                />
                            </div>
                            <p class="mt-2 text-[11px] leading-5 text-muted">
                                Kunci asli hanya ditampilkan sekali saat dibuat atau dirotasi. Nilai yang tersimpan hanya hash.
                            </p>
                            <button
                                type="button"
                                data-dosen-konfirmasi
                                data-dosen-action="{{ route('dosen.mata-kuliah.kunci', $kelas->id) }}"
                                data-dosen-judul="{{ $detail['memiliki_kode_gabung'] ? 'Rotasi kunci gabung' : 'Buat kunci gabung' }}"
                                data-dosen-pesan="{{ $detail['memiliki_kode_gabung'] ? 'Kunci lama segera tidak berlaku. Peserta yang sudah terdaftar tidak dihapus.' : 'Kunci baru akan dibuat dan ditampilkan sekali pada halaman ini.' }}"
                                data-dosen-label="{{ $detail['kode_kelas'] }} — {{ $detail['nama_mata_kuliah'] }}"
                                data-dosen-submit="{{ $detail['memiliki_kode_gabung'] ? 'Rotasi kunci' : 'Buat kunci' }}"
                                data-dosen-status=""
                                class="mt-4 w-full rounded-xl bg-sidebar px-3 py-2 text-xs font-bold text-white transition hover:bg-sidebar-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                {{ $detail['memiliki_kode_gabung'] ? 'Rotasi kunci gabung' : 'Buat kunci gabung' }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-text">Ringkasan keamanan kelas</h2>
                    <ul class="mt-4 space-y-3 text-xs leading-5 text-muted">
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                            <span>Status awal kelas baru selalu ditutup.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                            <span>Pendaftaran hanya dibuka setelah kunci tersedia.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                            <span>Rotasi kunci tidak mengubah daftar peserta.</span>
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-text">Identitas terkunci</h2>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        Mata kuliah, tahun akademik, dan kode kelas tidak dapat diubah setelah kelas dibuat.
                    </p>
                </section>
            </aside>
        </div>
    </div>

    @include('dosen.mata-kuliah._dialog')
</x-app-layout>