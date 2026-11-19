<x-app-layout title="Absensi Mahasiswa" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    @php
        $tone = fn (?string $status): string => match ($status) {
            'hadir' => 'hadir',
            'izin' => 'izin',
            'sakit' => 'sakit',
            'alfa' => 'alfa',
            default => 'netral',
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            title="Absensi"
            eyebrow="Kehadiran mahasiswa"
            description="Isi absensi mandiri hanya saat sesi dibuka dan masih dalam rentang waktunya. Riwayat dan rekap bersumber dari data kelas yang Anda ikuti."
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('mahasiswa.absensi.riwayat') }}" class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Riwayat absensi</a>
                    <a href="{{ route('mahasiswa.absensi.rekap') }}" class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Rekap kehadiran</a>
                </div>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">{{ session('error') }}</div>
        @endif

        @if ($rekap->isNotEmpty())
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Ringkasan kehadiran per mata kuliah">
                @foreach ($rekap as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <p class="truncate text-xs font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                        <p class="mt-1 text-[11px] text-muted">{{ $item['kode_kelas'] }} · {{ $item['total_sesi_ditutup'] }} sesi ditutup</p>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <x-status-badge :label="$item['persentase_label']" :tone="$item['persentase'] >= 75 ? 'hadir' : ($item['persentase'] >= 50 ? 'peringatan' : 'alfa')" />
                            <p class="text-[11px] text-muted">Hadir {{ $item['hadir'] }} · Alfa {{ $item['alfa'] }}</p>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        @if ($sesi->isEmpty())
            <x-empty-state
                title="Belum ada sesi absensi"
                description="Sesi absensi dari kelas yang Anda ikuti akan tampil di sini setelah dosen membuatnya."
                icon="calendar"
            />
        @else
            <section aria-label="Daftar sesi absensi" class="space-y-3">
                @foreach ($sesi as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['kode_kelas'] }} · {{ $item['periode'] }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-status-badge :label="$item['status_sesi_label']" :tone="$item['status_sesi']" />
                                <x-status-badge :label="$item['status_label']" :tone="$tone($item['status'])" />
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2 text-xs text-text sm:grid-cols-2">
                            <p><span class="font-semibold">Pertemuan:</span> ke-{{ $item['pertemuan_ke'] }}</p>
                            <p><span class="font-semibold">Tanggal:</span> {{ $item['tanggal_label'] }}</p>
                            <p><span class="font-semibold">Waktu:</span> {{ $item['waktu_label'] }}</p>
                            <p><span class="font-semibold">Topik:</span> {{ $item['topik'] }}</p>
                        </div>

                        @if ($item['boleh_isi'])
                            <form method="POST" action="{{ $item['url'] }}" class="mt-4 border-t border-border pt-4">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                                    <x-icon name="check" class="h-4 w-4" />
                                    Isi absensi hadir
                                </button>
                            </form>
                        @elseif ($item['status'] !== null)
                            <p class="mt-4 border-t border-border pt-4 text-[11px] text-muted">
                                Dicatat melalui metode {{ $item['metode_label'] }}. Koreksi hanya dapat dilakukan oleh dosen pengampu.
                            </p>
                        @else
                            <p class="mt-4 border-t border-border pt-4 text-[11px] text-muted">
                                Pengisian tersedia hanya ketika sesi dibuka dan berada dalam rentang waktu yang ditentukan.
                            </p>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
