<x-app-layout title="Tugas & Pengumpulan" :user="$user" :unread-count="$unreadCount">
    @php
        $badgeTone = fn (string $status): string => match ($status) {
            'terlambat' => 'deadline',
            'dinilai', 'terkumpul' => 'selesai',
            'mendekati_deadline' => 'peringatan',
            default => 'tugas',
        };
    @endphp

    <x-page-header
        title="Tugas & Pengumpulan"
        eyebrow="Akademik mahasiswa"
        description="Daftar tugas dari kelas yang Anda ikuti. Tanggal publikasi dan deadline sengaja ditampilkan terpisah."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text">
                <x-icon name="clipboard" class="h-4 w-4 text-agenda-tugas" />
                {{ $statusCounts['semua'] }} tugas terdaftar
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Filter status tugas">
        @foreach ($statusOptions as $key => $label)
            <a
                href="{{ route('mahasiswa.tugas', ['status' => $key]) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                    'border-sidebar bg-sidebar text-white' => $activeStatus === $key,
                    'border-border bg-card text-text hover:bg-soft' => $activeStatus !== $key,
                ])
                @if ($activeStatus === $key) aria-current="true" @endif
            >
                {{ $label }}
                <span @class([
                    'rounded-md px-1.5 py-0.5 text-[10px]',
                    'bg-primary text-primary-text' => $activeStatus === $key,
                    'bg-soft text-muted' => $activeStatus !== $key,
                ])>{{ $statusCounts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-muted">
        Menampilkan {{ $tasks->count() }} dari {{ $statusCounts['semua'] }} tugas untuk status yang dipilih.
    </p>

    @if ($tasks->isEmpty())
        <div class="mt-5">
            <x-empty-state
                title="Belum ada tugas pada filter ini"
                description="Tugas dari kelas aktif Anda akan muncul di sini setelah dipublikasikan dosen."
                icon="clipboard"
            />
        </div>
    @else
        <div class="mt-5 space-y-4">
            @foreach ($tasks as $task)
                <article class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-text">{{ $task['judul'] }}</h3>
                                <x-status-badge :label="$task['status_label']" :tone="$badgeTone($task['status'])" />
                            </div>
                            <p class="mt-1 text-xs font-medium text-muted">
                                {{ $task['mata_kuliah'] }}
                                @if ($task['kode_kelas'])
                                    · Kelas {{ $task['kode_kelas'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-border bg-soft p-3">
                            <p class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-muted">
                                <x-icon name="file" class="h-3.5 w-3.5" /> Dipublikasikan
                            </p>
                            <p class="mt-1 text-sm font-semibold text-text">{{ $task['dipublikasikan_label'] }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-soft p-3">
                            <p class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-muted">
                                <x-icon name="clock" class="h-3.5 w-3.5" /> Deadline
                            </p>
                            <p class="mt-1 text-sm font-semibold text-text">{{ $task['deadline_label'] }}</p>
                        </div>
                    </div>

                    @if ($task['deskripsi'])
                        <p class="mt-4 text-sm leading-6 text-muted">{{ $task['deskripsi'] }}</p>
                    @endif

                    @if ($task['pengumpulan'])
                        <div class="mt-4 rounded-xl border border-success/30 bg-success/5 p-4">
                            <p class="inline-flex items-center gap-2 text-xs font-bold text-text">
                                <x-icon name="check" class="h-4 w-4 text-success" />
                                Pengumpulan Anda
                            </p>
                            <dl class="mt-3 grid gap-3 text-xs sm:grid-cols-2">
                                <div>
                                    <dt class="font-semibold uppercase tracking-wide text-muted">Dikumpulkan</dt>
                                    <dd class="mt-0.5 font-medium text-text">{{ $task['pengumpulan']['dikumpulkan_label'] }}</dd>
                                </div>
                                @if ($task['pengumpulan']['file_nama'])
                                    <div>
                                        <dt class="font-semibold uppercase tracking-wide text-muted">File</dt>
                                        <dd class="mt-0.5 break-all font-medium text-text">{{ $task['pengumpulan']['file_nama'] }}</dd>
                                    </div>
                                @endif
                                @if ($task['pengumpulan']['nilai'] !== null)
                                    <div>
                                        <dt class="font-semibold uppercase tracking-wide text-muted">Nilai</dt>
                                        <dd class="mt-0.5 font-semibold text-text">{{ $task['pengumpulan']['nilai'] }}</dd>
                                    </div>
                                @endif
                                @if ($task['pengumpulan']['catatan'])
                                    <div class="sm:col-span-2">
                                        <dt class="font-semibold uppercase tracking-wide text-muted">Catatan Anda</dt>
                                        <dd class="mt-0.5 leading-5 text-text">{{ $task['pengumpulan']['catatan'] }}</dd>
                                    </div>
                                @endif
                                @if ($task['pengumpulan']['umpan_balik'])
                                    <div class="sm:col-span-2">
                                        <dt class="font-semibold uppercase tracking-wide text-muted">Umpan balik dosen</dt>
                                        <dd class="mt-0.5 leading-5 text-text">{{ $task['pengumpulan']['umpan_balik'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @else
                        <p class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-muted">
                            <x-icon name="info" class="h-4 w-4" />
                            Belum ada pengumpulan untuk tugas ini.
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-border pt-4">
                        @if ($task['pengumpulan'])
                            <a
                                href="{{ $task['pengumpulan']['unduh_url'] }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                <x-icon name="file" class="h-4 w-4 text-agenda-tugas" />
                                Unduh lampiran saya
                            </a>

                            @if ($task['pengumpulan']['bisa_resubmit'])
                                <a
                                    href="{{ route('mahasiswa.tugas.pengumpulan', $task['id']) }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                >
                                    <x-icon name="repeat" class="h-4 w-4" />
                                    Kirim ulang
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-muted">
                                    <x-icon name="check" class="h-4 w-4 text-success" />
                                    Terkunci setelah dinilai
                                </span>
                            @endif
                        @elseif ($task['bisa_dikumpulkan'])
                            <a
                                href="{{ route('mahasiswa.tugas.pengumpulan', $task['id']) }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                <x-icon name="clipboard" class="h-4 w-4" />
                                Kumpulkan tugas
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-muted">
                                <x-icon name="clock" class="h-4 w-4" />
                                Pengumpulan ditutup
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-app-layout>
