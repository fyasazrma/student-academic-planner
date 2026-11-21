<x-app-layout title="Detail Materi" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif

        <x-page-header
            :title="$materi['judul']"
            eyebrow="Materi pembelajaran"
            :description="'Materi '.$materi['kode_mata_kuliah'].' — '.$materi['nama_mata_kuliah'].' ('.$materi['kode_kelas'].')'"
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.materi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Daftar Materi
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Informasi materi">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-agenda-tugas">{{ $materi['kode_mata_kuliah'] }}</span>
                        @if ($materi['pertemuan_ke'])
                            <span class="rounded-md bg-agenda-kuliah/15 px-1.5 py-0.5 text-[10px] font-bold text-agenda-kuliah">P{{ $materi['pertemuan_ke'] }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-muted">{{ $materi['nama_kelas'] }} · {{ $materi['nama_dosen'] }}</p>
                </div>
                <x-status-badge :label="$progres['status_label']" :tone="$progres['status_tone']" />
            </div>

            <p class="mt-4 text-sm leading-6 text-muted">
                {{ $materi['deskripsi'] ?: 'Tidak ada deskripsi tambahan untuk materi ini.' }}
            </p>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Berkas</dt>
                    <dd class="mt-1 truncate text-sm font-semibold text-text" title="{{ $materi['file_name'] }}">{{ $materi['file_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Tipe</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $materi['file_mime'] ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Dipublikasikan</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $materi['dipublikasikan_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Selesai dipelajari</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $progres['selesai_label'] }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-4">
                <a
                    href="{{ route('mahasiswa.materi.unduh', [$materi['kelas_id'], $materi['id']]) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <x-icon name="file" class="h-4 w-4" /> Unduh Berkas
                </a>

                @if ($progres['selesai'])
                    <form method="POST" action="{{ route('mahasiswa.materi.progres.batalkan', [$materi['kelas_id'], $materi['id']]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-border bg-soft px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-border/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                            <x-icon name="close" class="h-4 w-4" /> Batalkan Status Selesai
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('mahasiswa.materi.progres.selesai', [$materi['kelas_id'], $materi['id']]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-success px-4 py-2.5 text-xs font-bold text-white transition hover:bg-success/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-success focus-visible:ring-offset-2">
                            <x-icon name="check" class="h-4 w-4" /> Tandai Sudah Dipelajari
                        </button>
                    </form>
                @endif
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm" aria-label="Status progres">
                <h3 class="text-sm font-bold text-text">Status penyelesaian</h3>
                <p class="mt-1 text-xs leading-5 text-muted">Membuka dan mengunduh materi tidak otomatis menandai materi selesai.</p>
                <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-muted">
                    <span>Progres</span>
                    <span>{{ $progres['persentase'] }}%</span>
                </div>
                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-border" role="progressbar" aria-valuenow="{{ $progres['persentase'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-success" style="width: {{ $progres['persentase'] }}%"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm" aria-label="Aktivitas materi">
                <h3 class="text-sm font-bold text-text">Aktivitas Anda</h3>
                <p class="mt-1 text-xs leading-5 text-muted">Aktivitas dicatat hanya jika akses atau unduhan berhasil.</p>
                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-surface p-3">
                        <dt class="text-[11px] text-muted">Dibuka</dt>
                        <dd class="mt-1 text-lg font-bold text-text">{{ $aktivitas['dibuka'] }} kali</dd>
                        <dd class="mt-0.5 text-[10px] text-muted">{{ $aktivitas['terakhir_dibuka'] ?? '-' }}</dd>
                    </div>
                    <div class="rounded-xl bg-surface p-3">
                        <dt class="text-[11px] text-muted">Diunduh</dt>
                        <dd class="mt-1 text-lg font-bold text-text">{{ $aktivitas['diunduh'] }} kali</dd>
                        <dd class="mt-0.5 text-[10px] text-muted">{{ $aktivitas['terakhir_diunduh'] ?? '-' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</x-app-layout>