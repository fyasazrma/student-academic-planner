@props([
    'notifications' => collect(),
    'deadlines' => [],
    'nextSchedules' => [],
    'unreadCount' => 0,
    'absensiRingkasan' => [
        'sesi_dibuka' => 0,
        'sesi_hari_ini' => 0,
        'belum_mengisi' => 0,
        'url' => null,
    ],
    'tugasRingkasan' => [
        'dibuat' => 0,
        'masuk' => 0,
        'belum_dinilai' => 0,
        'belum_dikumpulkan' => 0,
        'terlambat' => 0,
        'url' => null,
    ],
])

<aside id="panel-ringkasan" class="space-y-5 ui-rise">
    <x-mini-calendar :deadlines="$deadlines" :next-schedules="$nextSchedules" />

    <section class="rounded-card bg-card p-5 shadow-card">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-icon name="bell" class="h-5 w-5 text-agenda-tugas" />
                <h3 class="text-sm font-bold text-text">Notifikasi akademik</h3>
            </div>
            @if ($unreadCount > 0)
                <x-status-badge :label="$unreadCount.' belum dibaca'" tone="deadline" />
            @endif
        </div>

        @if ($notifications->isEmpty())
            <p class="mt-4 rounded-control bg-soft px-3 py-4 text-xs leading-5 text-muted">
                Belum ada notifikasi untuk akun dosen ini.
            </p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($notifications as $notifikasi)
                    <li class="rounded-control bg-soft p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-bold text-text">{{ $notifikasi->judul }}</p>
                            @if ($notifikasi->dibaca_pada === null)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-danger" aria-label="Belum dibaca"></span>
                            @endif
                        </div>
                        @if ($notifikasi->pesan)
                            <p class="mt-1 text-[11px] leading-5 text-muted">{{ $notifikasi->pesan }}</p>
                        @endif
                        <p class="mt-2 text-[10px] font-semibold uppercase tracking-wide text-muted">
                            {{ $notifikasi->created_at?->translatedFormat('d M Y H:i') ?? '-' }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="rounded-card bg-card p-5 shadow-card">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <x-icon name="check" class="h-5 w-5 text-agenda-tugas" />
                <h3 class="text-sm font-bold text-text">Ringkasan absensi</h3>
            </div>
            @if ($absensiRingkasan['url'] !== null)
                <a href="{{ $absensiRingkasan['url'] }}" class="text-[11px] font-semibold text-agenda-tugas transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Kelola</a>
            @endif
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $absensiRingkasan['sesi_dibuka'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Sesi dibuka</p>
            </div>
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $absensiRingkasan['sesi_hari_ini'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Sesi hari ini</p>
            </div>
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $absensiRingkasan['belum_mengisi'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Belum mengisi</p>
            </div>
        </div>
    </section>

    <section class="rounded-card bg-card p-5 shadow-card">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <x-icon name="clipboard" class="h-5 w-5 text-agenda-tugas" />
                <h3 class="text-sm font-bold text-text">Ringkasan tugas</h3>
            </div>
            @if ($tugasRingkasan['url'] !== null)
                <a href="{{ $tugasRingkasan['url'] }}" class="text-[11px] font-semibold text-agenda-tugas transition hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Kelola</a>
            @endif
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $tugasRingkasan['dibuat'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Tugas terbit</p>
            </div>
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $tugasRingkasan['masuk'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Pengumpulan masuk</p>
            </div>
            <div class="rounded-control bg-soft px-2 py-3">
                <p class="text-sm font-bold text-text">{{ $tugasRingkasan['belum_dinilai'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Belum dinilai</p>
            </div>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2 text-center">
            <div class="rounded-control bg-agenda-tugas/10 px-2 py-3">
                <p class="text-sm font-bold text-agenda-tugas">{{ $tugasRingkasan['belum_dikumpulkan'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Belum dikumpulkan</p>
            </div>
            <div class="rounded-control bg-agenda-terlambat/10 px-2 py-3">
                <p class="text-sm font-bold text-agenda-terlambat">{{ $tugasRingkasan['terlambat'] }}</p>
                <p class="mt-1 text-[10px] leading-4 text-muted">Terlambat</p>
            </div>
        </div>
    </section>

    <section class="rounded-card bg-card p-5 shadow-card">
        <div class="flex items-center gap-2">
            <x-icon name="clock" class="h-5 w-5 text-agenda-terlambat" />
            <h3 class="text-sm font-bold text-text">Deadline terdekat</h3>
        </div>

        @if ($deadlines === [])
            <p class="mt-4 rounded-control bg-soft px-3 py-4 text-xs leading-5 text-muted">
                Tidak ada deadline tugas mendatang pada kelas yang Anda ampu.
            </p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($deadlines as $deadline)
                    <li class="rounded-control bg-soft p-3">
                        <p class="text-xs font-bold text-text">{{ $deadline['judul'] }}</p>
                        <p class="mt-1 text-[11px] text-muted">{{ $deadline['mata_kuliah'] }} · {{ $deadline['kode_kelas'] }}</p>
                        <p class="mt-2 text-[11px] font-semibold text-agenda-terlambat">{{ $deadline['deadline_label'] }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="rounded-card bg-card p-5 shadow-card">
        <div class="flex items-center gap-2">
            <x-icon name="calendar" class="h-5 w-5 text-agenda-uts" />
            <h3 class="text-sm font-bold text-text">Jadwal & ujian mendatang</h3>
        </div>

        @if ($nextSchedules === [])
            <p class="mt-4 rounded-control bg-soft px-3 py-4 text-xs leading-5 text-muted">
                Belum ada jadwal kuliah atau ujian yang dapat ditampilkan.
            </p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($nextSchedules as $jadwal)
                    <li class="rounded-control bg-soft p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-muted">{{ $jadwal['label'] }}</p>
                            <p class="text-[10px] font-semibold text-muted">{{ $jadwal['tanggal_label'] }}</p>
                        </div>
                        <p class="mt-1 text-xs font-bold text-text">{{ $jadwal['judul'] }}</p>
                        @if ($jadwal['waktu_label'])
                            <p class="mt-1 text-[11px] text-muted">{{ $jadwal['waktu_label'] }}</p>
                        @endif
                        @if ($jadwal['lokasi'])
                            <p class="mt-1 flex items-center gap-1 text-[11px] text-muted">
                                <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                {{ $jadwal['lokasi'] }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</aside>
