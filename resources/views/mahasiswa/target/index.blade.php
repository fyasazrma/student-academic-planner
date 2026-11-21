<x-app-layout title="Target Belajar" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-danger">{{ session('error') }}</div>
        @endif

        <x-page-header
            title="Target Belajar"
            eyebrow="Perencanaan mahasiswa"
            description="Atur target pribadi dan pantau capaiannya. Status dihitung otomatis dari capaian dan deadline."
        >
            <x-slot:actions>
                <a
                    href="{{ route('mahasiswa.target.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Target
                </a>
            </x-slot:actions>
        </x-page-header>

        <section aria-label="Ringkasan target" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Target aktif" :value="$ringkasan['aktif']" hint="Belum selesai dan tidak dibatalkan" icon="target" tone="tugas" />
            <x-stat-card label="Selesai" :value="$ringkasan['selesai']" hint="Capaian tercapai atau ditandai selesai" icon="check" tone="kuliah" />
            <x-stat-card label="Terlambat" :value="$ringkasan['terlambat']" hint="Melewati deadline dan belum selesai" icon="clock" tone="deadline" />
            <x-stat-card label="Dibatalkan" :value="$ringkasan['dibatalkan']" hint="Tidak dihitung sebagai target aktif" icon="info" tone="akademik" />
        </section>

        <section class="rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5" aria-label="Daftar target belajar">
            <form method="GET" action="{{ route('mahasiswa.target.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[180px] flex-1">
                    <label for="status" class="mb-1.5 block text-[11px] font-semibold text-muted">Status</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-xs text-text outline-none focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status['value'] }}" @selected($selectedStatus === $status['value'])>{{ $status['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[200px] flex-1">
                    <label for="mata_kuliah" class="mb-1.5 block text-[11px] font-semibold text-muted">Mata kuliah</label>
                    <select id="mata_kuliah" name="mata_kuliah" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-xs text-text outline-none focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20">
                        <option value="">Semua mata kuliah</option>
                        @foreach ($mataKuliah as $item)
                            <option value="{{ $item['id'] }}" @selected($selectedMataKuliahId === $item['id'])>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-border bg-soft px-3 py-2 text-xs font-semibold text-text transition hover:bg-border/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan
                </button>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($targets as $item)
                    <article class="rounded-xl border border-border bg-surface p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('mahasiswa.target.show', $item['id']) }}" class="text-sm font-bold text-text transition hover:text-agenda-tugas focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                    {{ $item['judul'] }}
                                </a>
                                <p class="mt-1 text-[11px] text-muted">{{ $item['mata_kuliah'] }} · Deadline {{ $item['deadline_label'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$item['status_tone']" />
                        </div>

                        <div class="mt-3 flex items-center justify-between text-[11px] font-semibold text-muted">
                            <span>{{ $item['capaian_label'] }} / {{ $item['target_label'] }}</span>
                            <span>{{ $item['persentase'] }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-border" role="progressbar" aria-valuenow="{{ $item['persentase'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-agenda-tugas" style="width: {{ $item['persentase'] }}%"></div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('mahasiswa.target.show', $item['id']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 py-1.5 text-[11px] font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                <x-icon name="info" class="h-3.5 w-3.5" /> Detail
                            </a>
                            <a href="{{ route('mahasiswa.target.edit', $item['id']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 py-1.5 text-[11px] font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                <x-icon name="clipboard" class="h-3.5 w-3.5" /> Edit
                            </a>
                        </div>
                    </article>
                @empty
                    <x-empty-state
                        title="Belum ada target belajar"
                        description="Buat target pertama Anda untuk memantau capaian belajar."
                        icon="target"
                    >
                        <a href="{{ route('mahasiswa.target.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2">
                            <x-icon name="plus" class="h-4 w-4" /> Tambah Target
                        </a>
                    </x-empty-state>
                @endforelse
            </div>

            @if ($targets->hasPages())
                <div class="mt-5">{{ $targets->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
