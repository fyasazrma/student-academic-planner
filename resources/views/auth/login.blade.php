<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Masuk — Student Academic Planner</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-login class="min-h-full bg-surface font-sans text-text antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 sm:py-12 lg:px-10 lg:py-16">
            <div class="w-full max-w-6xl">
                <div class="grid items-center gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 xl:gap-20">
                    {{-- Kolom editorial: identitas dan konteks aplikasi --}}
                    <div>
                        <div class="flex items-center justify-center gap-3 lg:justify-start">
                            <span class="grid size-11 place-items-center rounded-tile bg-primary text-primary-text shadow-card">
                                <x-icon name="calendar" class="size-6" />
                            </span>
                            <div>
                                <p class="text-sm font-bold tracking-tight text-text">Student Academic Planner</p>
                                <p class="text-xs text-muted">Sistem Perencanaan Akademik</p>
                            </div>
                        </div>

                        <div class="mt-12 hidden lg:block">
                            <h2 class="max-w-xl text-4xl font-bold leading-[1.15] tracking-tight text-text xl:text-5xl">Atur Aktivitas Kuliah. Kendalikan Progres Akademik.</h2>
                            <p class="mt-5 max-w-lg text-base leading-7 text-muted">Satu tempat untuk mengelola tugas, jadwal akademik, materi pembelajaran, ujian, dan progres kuliah agar aktivitas perkuliahan lebih terorganisir.</p>

                            <div class="mt-9 max-w-lg rounded-card bg-card p-6 shadow-card">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 place-items-center rounded-tile bg-tile-lime text-text"><x-icon name="clipboard" class="size-5" /></span>
                                    <div>
                                        <p class="text-sm font-bold text-text">Ringkasan kemampuan aplikasi</p>
                                        <p class="text-xs text-muted">Tersedia setelah Anda masuk</p>
                                    </div>
                                </div>

                                <div class="mt-5 divide-y divide-border">
                                    <div class="flex items-center gap-3 py-3">
                                        <span class="grid size-9 place-items-center rounded-tile bg-tile-sky text-text"><x-icon name="list" class="size-5" /></span>
                                        <div>
                                            <p class="text-sm font-semibold text-text">Tugas dan pengumpulan</p>
                                            <p class="text-xs text-muted">Pantau tugas, publikasi, dan deadline.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-3">
                                        <span class="grid size-9 place-items-center rounded-tile bg-tile-peach text-text"><x-icon name="calendar" class="size-5" /></span>
                                        <div>
                                            <p class="text-sm font-semibold text-text">Jadwal kuliah</p>
                                            <p class="text-xs text-muted">Lihat jadwal kelas yang diikuti.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 py-3">
                                        <span class="grid size-9 place-items-center rounded-tile bg-tile-lavender text-text"><x-icon name="exam" class="size-5" /></span>
                                        <div>
                                            <p class="text-sm font-semibold text-text">UTS dan UAS</p>
                                            <p class="text-xs text-muted">Jadwal ujian dengan label yang jelas.</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-4 rounded-control bg-soft px-4 py-3 text-xs font-medium leading-5 text-muted">Akun dibuat oleh administrator. Tidak tersedia pendaftaran publik.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom form: fokus utama halaman --}}
                    <div class="mx-auto w-full max-w-md lg:max-w-none">
                        <section class="rounded-card bg-card p-6 shadow-card sm:p-8 lg:p-9">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Masuk</p>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-text">Selamat datang kembali</h1>
                            <p class="mt-2 text-sm leading-6 text-muted">Gunakan email akademik dan password yang diberikan administrator.</p>

                            @if (session('status'))
                                <div class="mt-6 rounded-control border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success-text" role="status">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5" data-auth-form>
                                @csrf

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-semibold text-text">Email</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted">
                                            <x-icon name="user" class="size-5" />
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
                                            class="block h-12 w-full rounded-control border bg-soft py-3 pl-11 pr-4 text-sm text-text placeholder:text-muted/70 transition duration-200 focus:border-sidebar focus:outline-none focus:ring-2 focus:ring-sidebar/15 {{ $errors->has('email') ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border' }}"
                                        >
                                    </div>
                                    @error('email')
                                        <p id="email-error" role="alert" aria-live="assertive" class="mt-2 text-sm font-medium text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="mb-2 block text-sm font-semibold text-text">Password</label>
                                    <div class="relative">
                                        <input
                                            id="password"
                                            name="password"
                                            type="password"
                                            required
                                            autocomplete="current-password"
                                            placeholder="Masukkan password"
                                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                            @if ($errors->has('password')) aria-describedby="password-error" @endif
                                            class="block h-12 w-full rounded-control border bg-soft py-3 pl-4 pr-12 text-sm text-text placeholder:text-muted/70 transition duration-200 focus:border-sidebar focus:outline-none focus:ring-2 focus:ring-sidebar/15 {{ $errors->has('password') ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border' }}"
                                        >
                                        <button
                                            type="button"
                                            data-password-toggle="password"
                                            aria-controls="password"
                                            aria-label="Tampilkan password"
                                            aria-pressed="false"
                                            class="absolute inset-y-0 right-0 flex items-center rounded-full pr-3.5 text-muted transition duration-200 hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-sidebar focus-visible:ring-offset-2 focus-visible:ring-offset-card"
                                        >
                                            <svg data-eye-icon class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                                <circle cx="12" cy="12" r="2.75" />
                                            </svg>
                                            <svg data-eye-off-icon class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m3 3 18 18" />
                                                <path d="M10.6 6.95A9.8 9.8 0 0 1 12 6.75c6 0 9.75 5.25 9.75 5.25a17.3 17.3 0 0 1-3.35 3.9M6.35 8.35A17.2 17.2 0 0 0 2.25 12s3.75 6.75 9.75 6.75a9.7 9.7 0 0 0 3.4-.6" />
                                                <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p id="password-error" role="alert" aria-live="assertive" class="mt-2 text-sm font-medium text-danger">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button
                                    type="submit"
                                    data-submit-button
                                    class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-primary-text shadow-card transition duration-200 hover:bg-primary-hover hover:shadow-card-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-sidebar focus-visible:ring-offset-2 focus-visible:ring-offset-card active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-primary disabled:hover:shadow-card"
                                >
                                    <svg data-submit-spinner class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                                        <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                                    </svg>
                                    <span data-submit-label>Masuk</span>
                                    <svg data-submit-arrow class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6" />
                                    </svg>
                                </button>
                            </form>

                            <p class="mt-7 border-t border-border pt-5 text-center text-xs leading-5 text-muted">
                                Belum memiliki akun? Akun mahasiswa dan dosen dibuat oleh administrator program studi.
                            </p>
                            <div class="mt-4 text-center">
                                <a href="{{ route('landing.index') }}" class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-sm font-semibold text-muted transition duration-200 hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-sidebar focus-visible:ring-offset-2 focus-visible:ring-offset-card">
                                    <x-icon name="chevron-left" class="size-4" /> Kembali ke beranda
                                </a>
                            </div>
                        </section>

                        <p class="mt-6 text-center text-xs text-muted">Akses terbatas untuk mahasiswa, dosen, dan administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
