<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $dashboardTitle }} — Student Academic Planner</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-page-bg font-sans text-text antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
            <main class="w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-agenda-tugas">Dashboard</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-text">{{ $dashboardTitle }}</h1>
                    </div>
                    <span class="rounded-full border border-agenda-tugas/30 bg-agenda-tugas/10 px-3 py-1 text-xs font-semibold text-agenda-tugas">
                        {{ ucfirst($user->role->value) }}
                    </span>
                </div>

                <div class="mt-6 rounded-xl border border-border bg-soft p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted">Masuk sebagai</p>
                    <p class="mt-2 text-lg font-semibold text-text">{{ $user->name }}</p>
                    <p class="text-sm text-muted">{{ $user->email }}</p>
                </div>

                <p class="mt-6 text-sm leading-6 text-muted">
                    Halaman ini masih berupa placeholder autentikasi. Dashboard final akan diimplementasikan pada tahap berikutnya.
                </p>

                <form method="POST" action="{{ route('logout') }}" class="mt-6">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-soft px-4 py-2.5 text-sm font-semibold text-text transition hover:border-danger/50 hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger/40"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m9 0-3-3m3 3-3 3" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </main>
        </div>
    </body>
</html>
