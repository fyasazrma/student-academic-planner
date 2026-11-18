@props([
    'title' => 'Dashboard',
    'user' => null,
    'unreadCount' => 0,
    'role' => null,
])

@php
    // Default mahasiswa menjaga seluruh halaman mahasiswa tetap memakai shell lama.
    $shellRole = $role === 'admin'
        || ($role === null && $user instanceof \App\Models\User && $user->isAdmin())
            ? 'admin'
            : 'mahasiswa';
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
    <body class="min-h-full bg-page-bg font-sans text-text antialiased">
        <div class="min-h-screen lg:pl-[264px]">
            <div class="fixed inset-y-0 left-0 z-40 hidden w-[264px] lg:block">
                <x-sidebar :user="$user" :unread-count="$unreadCount" :role="$shellRole" />
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
                <div class="absolute inset-y-0 left-0 w-[282px] max-w-[86vw] shadow-2xl">
                    <x-sidebar :user="$user" :unread-count="$unreadCount" :role="$shellRole" mobile />
                </div>
            </div>

            <div class="flex min-h-screen flex-col">
                <x-topbar :title="$title" :user="$user" :unread-count="$unreadCount" :role="$shellRole" />

                <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')
    </body>
</html>
