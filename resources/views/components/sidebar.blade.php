@props([
    'mobile' => false,
    'user' => null,
    'unreadCount' => 0,
    'role' => 'mahasiswa',
])

@php
    $isAdmin = $role === 'admin';
    $isDosen = $role === 'dosen';
    $isMahasiswa = ! $isAdmin && ! $isDosen;

    $items = $isAdmin
        ? [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'routeName' => 'admin.dashboard'],
            ['label' => 'Mata Kuliah', 'icon' => 'book', 'routeName' => 'admin.mata-kuliah.index'],
            ['label' => 'Tahun Akademik', 'icon' => 'calendar', 'routeName' => 'admin.tahun-akademik.index'],
            ['label' => 'Jadwal Kuliah', 'icon' => 'calendar', 'routeName' => 'admin.jadwal-kuliah.index'],
            ['label' => 'Jadwal Ujian', 'icon' => 'exam', 'routeName' => 'admin.jadwal-ujian.index'],
            ['label' => 'Pengumuman', 'icon' => 'info', 'routeName' => 'admin.pengumuman.index'],
            ['label' => 'Manajemen Pengguna', 'icon' => 'user', 'routeName' => 'admin.pengguna.index'],
            ['label' => 'Data Mahasiswa', 'icon' => 'user', 'routeName' => 'admin.mahasiswa.index'],
            ['label' => 'Data Dosen', 'icon' => 'user', 'routeName' => 'admin.dosen.index'],
            ['label' => 'Notifikasi', 'icon' => 'bell', 'routeName' => 'admin.notifikasi'],
        ]
        : ($isDosen
            ? [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'routeName' => 'dosen.dashboard'],
                ['label' => 'Profil Saya', 'icon' => 'user', 'routeName' => 'dosen.profil'],
                ['label' => 'Mata Kuliah Saya', 'icon' => 'book', 'routeName' => 'dosen.mata-kuliah.index'],
                ['label' => 'Nilai', 'icon' => 'chart', 'routeName' => 'dosen.nilai.index'],
                ['label' => 'Absensi', 'icon' => 'check', 'routeName' => 'dosen.absensi.index'],
                ['label' => 'Jadwal Ujian', 'icon' => 'exam', 'routeName' => 'dosen.jadwal-ujian'],
                ['label' => 'Pengumuman Kelas', 'icon' => 'info', 'routeName' => 'dosen.pengumuman.index'],
                ['label' => 'Notifikasi', 'icon' => 'bell', 'routeName' => 'dosen.notifikasi'],
            ]
            : [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'routeName' => 'mahasiswa.dashboard'],
                ['label' => 'Profil Saya', 'icon' => 'user', 'routeName' => 'mahasiswa.profil'],
                ['label' => 'Kalender Akademik', 'icon' => 'calendar', 'routeName' => 'mahasiswa.kalender'],
                ['label' => 'Pengumuman', 'icon' => 'info', 'routeName' => 'mahasiswa.pengumuman.index'],
                ['label' => 'Mata Kuliah', 'icon' => 'book', 'routeName' => 'mahasiswa.mata-kuliah'],
                ['label' => 'Materi Pembelajaran', 'icon' => 'file', 'routeName' => 'mahasiswa.materi.index'],
                ['label' => 'Progres Materi', 'icon' => 'chart', 'routeName' => 'mahasiswa.progres-materi.index'],
                ['label' => 'Tugas & Pengumpulan', 'icon' => 'clipboard', 'routeName' => 'mahasiswa.tugas'],
                ['label' => 'Jadwal Ujian', 'icon' => 'exam', 'routeName' => 'mahasiswa.jadwal-ujian'],
                ['label' => 'Target Belajar', 'icon' => 'target', 'routeName' => 'mahasiswa.target.index'],
                ['label' => 'Nilai', 'icon' => 'chart', 'routeName' => 'mahasiswa.nilai'],
                ['label' => 'Absensi', 'icon' => 'check', 'routeName' => 'mahasiswa.absensi.index'],
                ['label' => 'Notifikasi', 'icon' => 'bell', 'routeName' => 'mahasiswa.notifikasi'],
            ]);

    $navigasiLabel = $isAdmin ? 'Navigasi utama admin' : ($isDosen ? 'Navigasi utama dosen' : 'Navigasi utama mahasiswa');
    $menuLabel = $isAdmin ? 'Menu admin' : ($isDosen ? 'Menu dosen' : 'Menu mahasiswa');
    $peranLabel = $isAdmin ? 'Administrator' : ($isDosen ? 'Dosen' : ($user?->nim ? 'NIM '.$user->nim : 'Mahasiswa'));

    // Pintasan memakai route yang sudah ada; tidak ada tombol palsu.
    $shortcut = $isAdmin
        ? ['label' => 'Kelola jadwal ujian', 'icon' => 'calendar', 'routeName' => 'admin.jadwal-ujian.index']
        : ($isDosen
            ? ['label' => 'Kelola absensi kelas', 'icon' => 'check', 'routeName' => 'dosen.absensi.index']
            : ['label' => 'Target belajar aktif', 'icon' => 'target', 'routeName' => 'mahasiswa.target.index']);
@endphp

<aside
    @class([
        'flex h-full min-h-0 flex-col rounded-panel bg-sidebar p-4 text-white',
        'rounded-r-panel' => $mobile,
    ])
    aria-label="{{ $navigasiLabel }}"
>
    <div class="flex items-center gap-3 px-1 pb-4 pt-1">
        <div class="grid size-10 shrink-0 place-items-center rounded-tile bg-primary text-primary-text">
            <x-icon name="book" class="size-5" />
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-bold leading-tight">Student Academic Planner</p>
            <p class="mt-0.5 text-[11px] text-sidebar-text">Perencanaan akademik</p>
        </div>
        @if ($mobile)
            <button type="button" class="ml-auto grid size-9 shrink-0 place-items-center rounded-full text-sidebar-text transition hover:bg-sidebar-hover hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" data-sidebar-close aria-label="Tutup menu">
                <x-icon name="close" class="size-5" />
            </button>
        @endif
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto pr-1">
        <p class="mb-3 px-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-sidebar-text/70">{{ $menuLabel }}</p>
        <ul class="space-y-1">
            @foreach ($items as $item)
                <li>
                    @if (! empty($item['routeName']))
                        @php
                            $active = request()->routeIs($item['routeName'], $item['routeName'].'.*');
                            $href = route($item['routeName']);
                        @endphp
                        <a
                            href="{{ $href }}"
                            @class([
                                'flex h-11 items-center gap-3 rounded-full px-4 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                                'bg-primary font-semibold text-primary-text hover:bg-primary-hover' => $active,
                                'text-sidebar-text hover:bg-sidebar-hover hover:text-white' => ! $active,
                            ])
                            @if ($active) aria-current="page" @endif
                        >
                            <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                            @if ($item['label'] === 'Notifikasi' && $unreadCount > 0)
                                <span class="ml-auto grid min-w-5 shrink-0 place-items-center rounded-full bg-badge px-1 py-0.5 text-[10px] font-bold leading-none text-badge-text">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </a>
                    @else
                        <span
                            class="flex h-11 cursor-not-allowed items-center gap-3 rounded-full px-4 text-sm font-medium text-sidebar-text/60"
                            aria-disabled="true"
                            title="Halaman belum tersedia"
                        >
                            <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                            <span class="ml-auto rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide">Segera</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="mt-4 space-y-3">
        <a
            href="{{ route($shortcut['routeName']) }}"
            class="relative block rounded-card bg-primary p-4 pt-7 text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
            <span class="absolute -top-5 left-4 grid size-11 place-items-center rounded-full bg-white text-primary-text shadow-card">
                <x-icon :name="$shortcut['icon']" class="size-5" />
            </span>
            <span class="block text-sm font-bold">{{ $shortcut['label'] }}</span>
            <span class="mt-1 block text-xs font-medium text-primary-text/75">Pintasan cepat</span>
            <span class="mt-3 inline-flex items-center gap-1 rounded-full bg-white/75 px-3 py-1.5 text-xs font-bold">
                Buka
                <x-icon name="chevron-right" class="size-3.5" />
            </span>
        </a>

        <div class="flex items-center gap-3 rounded-card bg-white/5 p-3">
            <div class="grid size-9 shrink-0 place-items-center rounded-full bg-tile-lavender text-xs font-bold text-text">
                {{ strtoupper(mb_substr($user?->name ?? 'M', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ $user?->name }}</p>
                <p class="truncate text-[11px] text-sidebar-text">{{ $peranLabel }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex h-10 w-full items-center justify-center gap-2 rounded-full border border-white/15 px-3 text-sm font-semibold text-sidebar-text transition hover:border-danger/60 hover:bg-danger/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                <x-icon name="logout" class="size-4" />
                Keluar
            </button>
        </form>
    </div>
</aside>
