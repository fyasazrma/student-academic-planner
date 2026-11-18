@props([
    'mobile' => false,
    'user' => null,
    'unreadCount' => 0,
])

@php
    $items = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'routeName' => 'mahasiswa.dashboard'],
        ['label' => 'Profil Saya', 'icon' => 'user'],
        ['label' => 'Kalender Akademik', 'icon' => 'calendar', 'routeName' => 'mahasiswa.kalender'],
        ['label' => 'Mata Kuliah', 'icon' => 'book', 'routeName' => 'mahasiswa.mata-kuliah'],
        ['label' => 'Materi Pembelajaran', 'icon' => 'file'],
        ['label' => 'Tugas & Pengumpulan', 'icon' => 'clipboard', 'routeName' => 'mahasiswa.tugas'],
        ['label' => 'Jadwal Ujian', 'icon' => 'exam'],
        ['label' => 'Target Belajar', 'icon' => 'target'],
        ['label' => 'Nilai', 'icon' => 'chart'],
        ['label' => 'Absensi', 'icon' => 'check'],
        ['label' => 'Notifikasi', 'icon' => 'bell', 'routeName' => 'mahasiswa.notifikasi'],
    ];
@endphp

<aside
    @class([
        'flex h-full flex-col bg-sidebar text-white',
        'rounded-r-2xl' => $mobile,
    ])
    aria-label="Navigasi utama mahasiswa"
>
    <div class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-text">
            <x-icon name="book" class="h-5 w-5" />
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-bold leading-tight">Student Academic Planner</p>
            <p class="mt-0.5 text-[11px] text-white/60">Perencanaan akademik</p>
        </div>
        @if ($mobile)
            <button type="button" class="ml-auto rounded-lg p-1.5 text-white/70 hover:bg-sidebar-hover hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" data-sidebar-close aria-label="Tutup menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        @endif
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5">
        <p class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/45">Menu mahasiswa</p>
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
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                                'bg-primary text-primary-text shadow-sm' => $active,
                                'text-white/80 hover:bg-sidebar-hover hover:text-white' => ! $active,
                            ])
                            @if ($active) aria-current="page" @endif
                        >
                            <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                            <span>{{ $item['label'] }}</span>
                            @if ($item['label'] === 'Notifikasi' && $unreadCount > 0)
                                <span @class([
                                    'ml-auto rounded-full px-2 py-0.5 text-[10px] font-bold text-white',
                                    'bg-sidebar' => $active,
                                    'bg-danger' => ! $active,
                                ])>{{ $unreadCount }}</span>
                            @endif
                        </a>
                    @else
                        <span
                            class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-white/45"
                            aria-disabled="true"
                            title="Halaman belum tersedia"
                        >
                            <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                            <span>{{ $item['label'] }}</span>
                            <span class="ml-auto rounded-md bg-white/10 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide">Segera</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="mb-3 flex items-center gap-3 rounded-xl bg-white/5 p-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-text">
                {{ strtoupper(mb_substr($user?->name ?? 'M', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ $user?->name }}</p>
                <p class="truncate text-[11px] text-white/60">{{ $user?->nim ? 'NIM '.$user->nim : 'Mahasiswa' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-3 py-2.5 text-sm font-semibold text-white/80 transition hover:border-danger/60 hover:bg-danger/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                <x-icon name="logout" class="h-4 w-4" />
                Keluar
            </button>
        </form>
    </div>
</aside>
