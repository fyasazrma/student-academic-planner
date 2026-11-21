@props([
    'title' => 'Dashboard',
    'user' => null,
    'unreadCount' => 0,
    'role' => 'mahasiswa',
])

@php
    $isAdmin = $role === 'admin';
    $isDosen = $role === 'dosen';
    $dashboardRoute = $isAdmin ? 'admin.dashboard' : ($isDosen ? 'dosen.dashboard' : 'mahasiswa.dashboard');
    $dashboardLabel = $isAdmin ? 'Dashboard Admin' : ($isDosen ? 'Dashboard Dosen' : 'Dashboard Mahasiswa');
    $notifikasiRoute = $isAdmin ? 'admin.notifikasi' : ($isDosen ? 'dosen.notifikasi' : 'mahasiswa.notifikasi');

    $currentRoute = request()->route()?->getName();
    $isDashboard = $currentRoute === $dashboardRoute;
@endphp

<header class="flex items-center gap-3 pt-1">
    <button
        type="button"
        class="grid size-10 shrink-0 place-items-center rounded-full bg-card text-text shadow-card transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary lg:hidden"
        data-sidebar-open
        aria-label="Buka menu navigasi"
        aria-controls="sidebar-drawer"
        aria-expanded="false"
    >
        <x-icon name="menu" class="size-5" />
    </button>

    <div class="min-w-0 flex-1">
        @unless ($isDashboard)
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-muted">
                <span class="shrink-0">Akademik</span>
                <span aria-hidden="true">/</span>
                <a href="{{ route($dashboardRoute) }}" class="truncate transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">{{ $dashboardLabel }}</a>
            </nav>
        @endunless
    </div>

    <div class="flex shrink-0 items-center gap-2">
        <a
            href="{{ route($notifikasiRoute) }}"
            class="relative grid size-10 place-items-center rounded-full bg-card text-text shadow-card transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            aria-label="Lihat notifikasi{{ $unreadCount > 0 ? ', '.$unreadCount.' belum dibaca' : '' }}"
        >
            <x-icon name="bell" class="size-5" />
            @if ($unreadCount > 0)
                <span class="absolute -right-0.5 -top-0.5 grid min-w-5 place-items-center rounded-full bg-badge px-1 py-0.5 text-center text-[10px] font-bold leading-none text-badge-text">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </a>

        @php
            $profilRoute = $isDosen ? 'dosen.profil' : ($isAdmin ? null : 'mahasiswa.profil');
        @endphp
        @if ($profilRoute !== null)
            <a
                href="{{ route($profilRoute) }}"
                class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 transition hover:bg-card hover:shadow-card focus:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:pr-3"
                aria-label="Buka profil saya"
            >
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-tile-lavender text-sm font-bold text-text">
                    {{ strtoupper(mb_substr($user?->name ?? 'M', 0, 1)) }}
                </span>
                <span class="hidden min-w-0 sm:block">
                    <span class="block truncate text-sm font-semibold text-text">{{ $user?->name }}</span>
                    <span class="block truncate text-[11px] text-muted">{{ $isDosen ? 'Dosen' : 'Mahasiswa' }}</span>
                </span>
            </a>
        @else
            <div class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 sm:pr-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-full bg-tile-lavender text-sm font-bold text-text">
                    {{ strtoupper(mb_substr($user?->name ?? 'M', 0, 1)) }}
                </div>
                <div class="hidden min-w-0 sm:block">
                    <p class="truncate text-sm font-semibold text-text">{{ $user?->name }}</p>
                    <p class="truncate text-[11px] text-muted">Administrator</p>
                </div>
            </div>
        @endif
    </div>
</header>
