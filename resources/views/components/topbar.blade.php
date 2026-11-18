@props([
    'title' => 'Dashboard',
    'user' => null,
    'unreadCount' => 0,
])

@php
    $breadcrumbLabels = [
        'mahasiswa.dashboard' => 'Dashboard Mahasiswa',
        'mahasiswa.kalender' => 'Kalender Akademik',
        'mahasiswa.mata-kuliah' => 'Mata Kuliah',
        'mahasiswa.mata-kuliah.cari' => 'Mata Kuliah',
        'mahasiswa.tugas' => 'Tugas & Pengumpulan',
        'mahasiswa.notifikasi' => 'Notifikasi',
    ];

    $currentRoute = request()->route()?->getName();
    $currentLabel = $breadcrumbLabels[$currentRoute] ?? $title;
    $isDashboard = $currentRoute === 'mahasiswa.dashboard';
@endphp

<header class="sticky top-0 z-30 border-b border-border bg-card/95 backdrop-blur-sm">
    <div class="mx-auto flex h-16 w-full max-w-[1600px] items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button
            type="button"
            class="rounded-xl border border-border p-2 text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas lg:hidden"
            data-sidebar-open
            aria-label="Buka menu navigasi"
            aria-controls="sidebar-drawer"
            aria-expanded="false"
        >
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <div class="min-w-0">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-muted">
                <span>Akademik</span>
                <span aria-hidden="true">/</span>
                @unless ($isDashboard)
                    <a href="{{ route('mahasiswa.dashboard') }}" class="transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Dashboard Mahasiswa</a>
                    <span aria-hidden="true">/</span>
                @endunless
                <span class="font-semibold text-text">{{ $currentLabel }}</span>
            </nav>
            <h1 class="truncate text-base font-bold text-text sm:text-lg">{{ $title }}</h1>
        </div>

        <div class="ml-auto flex items-center gap-2 sm:gap-4">
            <a
                href="{{ route('mahasiswa.notifikasi') }}"
                class="relative rounded-xl border border-border p-2 text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                aria-label="Lihat notifikasi{{ $unreadCount > 0 ? ', '.$unreadCount.' belum dibaca' : '' }}"
            >
                <x-icon name="bell" class="h-5 w-5" />
                @if ($unreadCount > 0)
                    <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-danger px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </a>

            <div class="flex items-center gap-2.5 border-l border-border pl-3 sm:pl-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-agenda-tugas/15 text-sm font-bold text-agenda-tugas">
                    {{ strtoupper(mb_substr($user?->name ?? 'M', 0, 1)) }}
                </div>
                <div class="hidden min-w-0 sm:block">
                    <p class="truncate text-sm font-semibold text-text">{{ $user?->name }}</p>
                    <p class="truncate text-[11px] text-muted">Mahasiswa</p>
                </div>
            </div>
        </div>
    </div>
</header>
