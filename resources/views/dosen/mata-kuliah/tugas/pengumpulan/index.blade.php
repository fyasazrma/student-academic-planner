<x-app-layout title="Pengumpulan Tugas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Pengumpulan Tugas"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Pantau pengumpulan mahasiswa pada tugas ini. Data berikut berasal dari database kelas Anda."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.tugas.index', $kelas->getKey()) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar tugas
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

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Informasi tugas">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Tugas</p>
                    <h3 class="mt-2 text-xl font-bold text-text">{{ $infoTugas['judul'] }}</h3>
                    @if ($infoTugas['deskripsi_ringkas'] !== '')
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-muted">{{ $infoTugas['deskripsi_ringkas'] }}</p>
                    @endif
                </div>
                <x-status-badge label="Nilai maks. {{ $infoTugas['nilai_maksimal'] }}" tone="tugas" />
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-border bg-soft p-4">
                    <p class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-muted">
                        <x-icon name="file" class="h-3.5 w-3.5 text-agenda-tugas" />
                        Dipublikasikan
                    </p>
                    <p class="mt-1.5 text-sm font-semibold text-text">{{ $infoTugas['publikasi_label'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-soft p-4">
                    <p class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-muted">
                        <x-icon name="clock" class="h-3.5 w-3.5 text-agenda-terlambat" />
                        Deadline
                    </p>
                    <p class="mt-1.5 text-sm font-semibold text-text">{{ $infoTugas['deadline_label'] }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-stat-card
                label="Jumlah peserta"
                :value="$statistik['jumlah_peserta']"
                hint="Peserta aktif dan arsip pengumpulan"
                icon="user"
                tone="kuliah"
            />
            <x-stat-card
                label="Sudah mengumpulkan"
                :value="$statistik['sudah_mengumpulkan']"
                hint="Pengumpulan tercatat"
                icon="check"
                tone="tugas"
            />
            <x-stat-card
                label="Belum mengumpulkan"
                :value="$statistik['belum_mengumpulkan']"
                hint="Belum ada baris pengumpulan"
                icon="info"
                tone="pengumuman"
            />
            <x-stat-card
                label="Belum dinilai"
                :value="$statistik['belum_dinilai']"
                hint="Menunggu penilaian"
                icon="clipboard"
                tone="pengumuman"
            />
            <x-stat-card
                label="Sudah dinilai"
                :value="$statistik['sudah_dinilai']"
                hint="Nilai tersimpan"
                icon="check"
                tone="kuliah"
            />
            <x-stat-card
                label="Terlambat"
                :value="$statistik['terlambat']"
                hint="Berdasarkan data pengumpulan"
                icon="clock"
                tone="deadline"
            />
        </div>

        @if ($peserta->isEmpty())
            <x-empty-state
                title="Belum ada peserta yang ditampilkan"
                description="Daftar ini memuat peserta aktif dan arsip pengumpulan dari peserta yang sudah tidak aktif."
                icon="user"
            />
        @else
            <section aria-label="Daftar pengumpulan (tabel)" class="hidden rounded-card bg-card p-5 shadow-card md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mahasiswa</th>
                                <th scope="col" class="px-4 py-3">Status peserta</th>
                                <th scope="col" class="px-4 py-3">Status pengumpulan</th>
                                <th scope="col" class="px-4 py-3">Dikumpulkan</th>
                                <th scope="col" class="px-4 py-3">Lampiran</th>
                                <th scope="col" class="px-4 py-3">Nilai</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($peserta as $baris)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $baris['nama'] }}</p>
                                        <p class="mt-1 text-xs text-muted">NIM {{ $baris['nim'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            <x-status-badge :label="$baris['status_peserta_label']" :tone="$baris['arsip'] ? 'peringatan' : 'netral'" />
                                            @if ($baris['arsip'])
                                                <x-status-badge label="Arsip" tone="peringatan" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :label="$baris['status_label']" :tone="$baris['status_tone']" />
                                        @if ($baris['terlambat'] && $baris['status'] === 'dinilai')
                                            <p class="mt-1 text-[11px] text-agenda-terlambat">Dikumpulkan terlambat</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $baris['dikumpulkan_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $baris['lampiran_nama'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $baris['nilai_label'] ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            @if ($baris['detail_url'])
                                                <a
                                                    href="{{ $baris['detail_url'] }}"
                                                    class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                                >
                                                    Lihat detail
                                                </a>
                                            @else
                                                <span class="text-xs text-muted">Belum ada pengumpulan</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Daftar pengumpulan (kartu)" class="space-y-3 md:hidden">
                @foreach ($peserta as $baris)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $baris['nama'] }}</p>
                                <p class="mt-1 text-xs text-muted">NIM {{ $baris['nim'] }}</p>
                            </div>
                            <x-status-badge :label="$baris['status_label']" :tone="$baris['status_tone']" />
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <x-status-badge :label="$baris['status_peserta_label']" :tone="$baris['arsip'] ? 'peringatan' : 'netral'" />
                            @if ($baris['arsip'])
                                <x-status-badge label="Arsip" tone="peringatan" />
                            @endif
                            @if ($baris['terlambat'] && $baris['status'] === 'dinilai')
                                <x-status-badge label="Dikumpulkan terlambat" tone="deadline" />
                            @endif
                        </div>

                        <div class="mt-4 grid gap-1 text-[11px] text-muted">
                            <p>Dikumpulkan: <span class="font-semibold text-text">{{ $baris['dikumpulkan_label'] }}</span></p>
                            <p>Lampiran: <span class="font-semibold text-text">{{ $baris['lampiran_nama'] ?? '-' }}</span></p>
                            <p>Nilai: <span class="font-semibold text-text">{{ $baris['nilai_label'] ?? '-' }}</span></p>
                        </div>

                        <div class="mt-4">
                            @if ($baris['detail_url'])
                                <a
                                    href="{{ $baris['detail_url'] }}"
                                    class="block rounded-xl border border-border bg-soft px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                    Lihat detail
                                </a>
                            @else
                                <p class="text-center text-xs text-muted">Belum ada pengumpulan</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
