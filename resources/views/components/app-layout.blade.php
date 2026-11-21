@props([
    'title' => 'Dashboard',
    'user' => null,
    'unreadCount' => 0,
    'role' => null,
])

@php
    // Default mahasiswa menjaga seluruh halaman mahasiswa tetap memakai shell lama.
    $shellRole = 'mahasiswa';

    if ($role === 'admin' || ($role === null && $user instanceof \App\Models\User && $user->isAdmin())) {
        $shellRole = 'admin';
    } elseif ($role === 'dosen' || ($role === null && $user instanceof \App\Models\User && $user->isDosen())) {
        $shellRole = 'dosen';
    }

    // Panel kanan opsional: menerima nama slot baru dan nama lama sebagai fallback.
    $rightPanelSlot = $rightPanel ?? $panel ?? null;
    $hasRightPanel = $rightPanelSlot !== null && trim((string) $rightPanelSlot) !== '';
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }} — Student Academic Planner</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-surface font-sans text-text antialiased">
        <div class="min-h-screen p-4 sm:p-5">
            <div @class([
                'grid min-h-[calc(100vh-2rem)] w-full grid-cols-1 gap-5 sm:min-h-[calc(100vh-2.5rem)] lg:grid-cols-[232px_minmax(0,1fr)]',
                'xl:grid-cols-[232px_minmax(0,1fr)_300px]' => $hasRightPanel,
            ])>
                <div class="hidden lg:block">
                    <div class="sticky top-5 h-[calc(100vh-2.5rem)]">
                        <x-sidebar :user="$user" :unread-count="$unreadCount" :role="$shellRole" />
                    </div>
                </div>

                <div class="flex min-w-0 flex-col gap-5">
                    <x-topbar :title="$title" :user="$user" :unread-count="$unreadCount" :role="$shellRole" />

                    <main class="min-w-0 flex-1">
                        {{ $slot }}
                    </main>
                </div>

                @if ($hasRightPanel)
                    <aside class="min-w-0 lg:col-start-2 xl:col-start-3 xl:row-start-1" aria-label="Ringkasan dashboard">
                        {{ $rightPanelSlot }}
                    </aside>
                @endif
            </div>
        </div>

        <div
            id="sidebar-drawer"
            class="fixed inset-0 z-50 hidden lg:hidden"
            aria-hidden="true"
        >
            <button
                type="button"
                class="absolute inset-0 bg-sidebar/50"
                aria-label="Tutup menu navigasi"
                data-sidebar-close
            ></button>
            <div class="absolute inset-y-0 left-0 w-[282px] max-w-[86vw] shadow-card">
                <x-sidebar :user="$user" :unread-count="$unreadCount" :role="$shellRole" mobile />
            </div>
        </div>

        @stack('modals')
    </body>
</html>
