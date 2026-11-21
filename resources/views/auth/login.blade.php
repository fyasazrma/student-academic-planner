<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Masuk — Student Academic Planner</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-page-bg font-sans text-text antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <main class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary text-primary-text shadow-sm">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 2.75 7.5 12 12l9.25-4.5L12 3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.25v4.2c0 1.1 2.9 3.05 6.75 3.05s6.75-1.95 6.75-3.05v-4.2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.25 7.5v5.25" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-tight text-text">Student Academic Planner</p>
                        <p class="text-xs text-muted">Sistem Perencanaan Akademik</p>
                    </div>
                </div>

                <section class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    <div class="mb-7">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-agenda-tugas">Masuk</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-text">Selamat datang kembali</h1>
                        <p class="mt-2 text-sm leading-6 text-muted">
                            Gunakan email akademik dan password yang diberikan administrator.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-auth-form>
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-text">Email</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75V6.75Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.5 7.5 5.25L19.5 7.5" />
                                    </svg>
                                </span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama@kampus.ac.id"
                                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                    @if ($errors->has('email')) aria-describedby="email-error" @endif
                                    class="block w-full rounded-xl border bg-soft py-3 pl-11 pr-4 text-sm text-text placeholder:text-muted/60 transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 {{ $errors->has('email') ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border' }}"
                                >
                            </div>
                            @error('email')
                                <p id="email-error" class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-text">Password</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 10.5V8.25a5.25 5.25 0 0 1 10.5 0v2.25" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.5h13.5v9H5.25v-9Z" />
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan password"
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                    @if ($errors->has('password')) aria-describedby="password-error" @endif
                                    class="block w-full rounded-xl border bg-soft py-3 pl-11 pr-12 text-sm text-text placeholder:text-muted/60 transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 {{ $errors->has('password') ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border' }}"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="password"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-muted transition hover:text-text focus:outline-none focus-visible:text-agenda-tugas"
                                >
                                    <svg data-eye-icon class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                        <circle cx="12" cy="12" r="2.75" />
                                    </svg>
                                    <svg data-eye-off-icon class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 6.95A9.8 9.8 0 0 1 12 6.75c6 0 9.75 5.25 9.75 5.25a17.3 17.3 0 0 1-3.35 3.9M6.35 8.35A17.2 17.2 0 0 0 2.25 12s3.75 6.75 9.75 6.75a9.7 9.7 0 0 0 3.4-.6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p id="password-error" class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            data-submit-button
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-card disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <svg data-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                                <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                            </svg>
                            <span data-submit-label>Masuk</span>
                            <svg data-submit-arrow class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                            </svg>
                        </button>
                    </form>

                    <p class="mt-6 border-t border-border pt-5 text-center text-xs leading-5 text-muted">
                        Belum memiliki akun? Akun mahasiswa dan dosen dibuat oleh administrator program studi.
                    </p>
                </section>

                <p class="mt-6 text-center text-xs text-muted">
                    Akses terbatas untuk mahasiswa, dosen, dan administrator.
                </p>
            </main>
        </div>
    </body>
</html>
