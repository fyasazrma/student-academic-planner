<x-app-layout title="Detail Target Belajar" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-danger">{{ session('error') }}</div>
        @endif

        <x-page-header
            :title="$target['judul']"
            eyebrow="Target belajar"
            :description="$target['deskripsi'] ?: 'Target belajar pribadi mahasiswa.'"
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.target.edit', $target['id']) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="clipboard" class="h-4 w-4" /> Edit
                </a>
                <a href="{{ route('mahasiswa.target.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Daftar
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Detail target">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Status</p>
                    <div class="mt-2"><x-status-badge :label="$target['status_label']" :tone="$target['status_tone']" /></div>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-semibold text-muted">Capaian</p>
                    <p class="mt-1 text-2xl font-bold text-text">{{ $target['persentase'] }}%</p>
                </div>
            </div>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-border" role="progressbar" aria-valuenow="{{ $target['persentase'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-agenda-tugas" style="width: {{ $target['persentase'] }}%"></div>
            </div>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Mata kuliah</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['mata_kuliah'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Nilai target</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['target_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Capaian saat ini</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['capaian_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Tanggal mulai</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['tanggal_mulai_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Deadline</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['deadline_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Tanggal penanda</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['tanggal_target_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold text-muted">Dibuat</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $target['dibuat_label'] }}</dd>
                </div>
            </dl>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-card bg-card p-5 shadow-card" aria-label="Perbarui capaian">
                <h3 class="text-sm font-bold text-text">Perbarui capaian</h3>
                <p class="mt-1 text-xs leading-5 text-muted">Capaian tidak boleh melebihi nilai target. Status selesai dihitung ulang otomatis.</p>

                <form method="POST" action="{{ route('mahasiswa.target.capaian', $target['id']) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="min-w-[140px] flex-1">
                        <label for="capaian" class="mb-1.5 block text-[11px] font-semibold text-muted">Capaian ({{ $target['satuan'] ?: 'satuan' }})</label>
                        <input
                            id="capaian"
                            name="capaian"
                            type="number"
                            min="0"
                            max="{{ $target['target_nilai'] ?? 9999999999 }}"
                            step="0.01"
                            required
                            value="{{ old('capaian', $target['capaian']) }}"
                            class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-text outline-none focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
                        >
                        @error('capaian') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                        Simpan Capaian
                    </button>
                </form>
            </section>

            <section class="rounded-card bg-card p-5 shadow-card" aria-label="Aksi target">
                <h3 class="text-sm font-bold text-text">Aksi target</h3>
                <p class="mt-1 text-xs leading-5 text-muted">Status selesai dapat dibatalkan. Target yang dibatalkan tidak dapat diaktifkan kembali.</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($target['bisa_dibatalkan_selesai'])
                        <form method="POST" action="{{ route('mahasiswa.target.batalkan-selesai', $target['id']) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-border bg-soft px-3.5 py-2 text-xs font-semibold text-text transition hover:bg-border/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                <x-icon name="close" class="h-4 w-4" /> Batalkan Status Selesai
                            </button>
                        </form>
                    @elseif ($target['bisa_tandai_selesai'])
                        <form method="POST" action="{{ route('mahasiswa.target.selesai', $target['id']) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-success px-3.5 py-2 text-xs font-bold text-white transition hover:bg-success/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-success focus-visible:ring-offset-2">
                                <x-icon name="check" class="h-4 w-4" /> Tandai Selesai
                            </button>
                        </form>
                    @else
                        <p class="text-xs leading-5 text-muted">Status selesai tercapai dari capaian. Ubah capaian di bawah target jika ingin membatalkan status ini.</p>
                    @endif

                    @if ($target['bisa_dibatalkan'])
                        <form method="POST" action="{{ route('mahasiswa.target.batalkan', $target['id']) }}" onsubmit="return confirm('Batalkan target ini?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-danger/30 bg-danger/10 px-3.5 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">
                                <x-icon name="close" class="h-4 w-4" /> Batalkan Target
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('mahasiswa.target.destroy', $target['id']) }}" onsubmit="return confirm('Hapus target ini secara permanen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-danger/30 bg-danger/10 px-3.5 py-2 text-xs font-semibold text-danger transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">
                            <x-icon name="close" class="h-4 w-4" /> Hapus
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>