<x-app-layout title="Detail Pengumpulan" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Detail Pengumpulan"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Lihat data pengumpulan mahasiswa pada tugas ini."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.tugas.pengumpulan.index', [$kelas->getKey(), $infoTugas['id']]) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar pengumpulan
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
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Tugas</p>
            <h3 class="mt-2 text-xl font-bold text-text">{{ $infoTugas['judul'] }}</h3>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-soft p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Dipublikasikan</p>
                    <p class="mt-1.5 text-sm font-semibold text-text">{{ $infoTugas['publikasi_label'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-soft p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Deadline</p>
                    <p class="mt-1.5 text-sm font-semibold text-text">{{ $infoTugas['deadline_label'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-soft p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Nilai maksimal</p>
                    <p class="mt-1.5 text-sm font-semibold text-text">{{ $infoTugas['nilai_maksimal'] }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Detail pengumpulan mahasiswa">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Pengumpulan mahasiswa</p>
                    <h3 class="mt-2 text-lg font-bold text-text">{{ $pengumpulan['nama'] }}</h3>
                    <p class="mt-1 text-sm text-muted">NIM {{ $pengumpulan['nim'] }} · Status {{ $pengumpulan['status_peserta_label'] }}</p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <x-status-badge :label="$pengumpulan['status_label']" :tone="$pengumpulan['status_tone']" />
                    @if ($pengumpulan['arsip'])
                        <x-status-badge label="Arsip" tone="peringatan" />
                    @endif
                    @if ($pengumpulan['terlambat'] && $pengumpulan['status_label'] === 'Sudah dinilai')
                        <x-status-badge label="Dikumpulkan terlambat" tone="deadline" />
                    @endif
                </div>
            </div>

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Dikumpulkan</dt>
                    <dd class="mt-1 font-medium text-text">{{ $pengumpulan['dikumpulkan_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Lampiran</dt>
                    <dd class="mt-1 break-all font-medium text-text">{{ $pengumpulan['lampiran_label'] }}</dd>
                </div>
                @if ($pengumpulan['catatan'])
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Catatan mahasiswa</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-text">{{ $pengumpulan['catatan'] }}</dd>
                    </div>
                @endif
                @if ($pengumpulan['sudah_dinilai'])
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nilai</dt>
                        <dd class="mt-1 font-bold text-text">{{ $pengumpulan['nilai_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Dinilai oleh</dt>
                        <dd class="mt-1 font-medium text-text">{{ $pengumpulan['penilai'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Waktu penilaian</dt>
                        <dd class="mt-1 font-medium text-text">{{ $pengumpulan['dinilai_pada_label'] ?? '-' }}</dd>
                    </div>
                    @if ($pengumpulan['umpan_balik'])
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Umpan balik</dt>
                            <dd class="mt-1 whitespace-pre-line leading-6 text-text">{{ $pengumpulan['umpan_balik'] }}</dd>
                        </div>
                    @endif
                @endif
            </dl>
        </section>
    </div>
</x-app-layout>
