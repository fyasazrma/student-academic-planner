<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Student Academic Planner — Perencanaan Akademik</title>
        <meta name="description" content="Satu tempat untuk mengelola tugas, jadwal akademik, materi pembelajaran, ujian, dan progres kuliah.">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Interaksi landing memakai radio native: bekerja tanpa JavaScript. */
            .landing-panel { display: none; }
            #landing-view-calendar:checked ~ .landing-panels #landing-panel-calendar,
            #landing-view-timeline:checked ~ .landing-panels #landing-panel-timeline,
            #landing-role-mahasiswa:checked ~ .landing-role-panels #landing-panel-mahasiswa,
            #landing-role-dosen:checked ~ .landing-role-panels #landing-panel-dosen,
            #landing-role-admin:checked ~ .landing-role-panels #landing-panel-admin { display: block; }

            #landing-view-calendar:checked ~ .landing-cal-tabs label[for="landing-view-calendar"],
            #landing-view-timeline:checked ~ .landing-cal-tabs label[for="landing-view-timeline"],
            #landing-role-mahasiswa:checked ~ .landing-role-tabs label[for="landing-role-mahasiswa"],
            #landing-role-dosen:checked ~ .landing-role-tabs label[for="landing-role-dosen"],
            #landing-role-admin:checked ~ .landing-role-tabs label[for="landing-role-admin"] {
                background-color: #201f31;
                color: #daf17c;
            }

            #landing-view-calendar:focus-visible ~ .landing-cal-tabs label[for="landing-view-calendar"],
            #landing-view-timeline:focus-visible ~ .landing-cal-tabs label[for="landing-view-timeline"],
            #landing-role-mahasiswa:focus-visible ~ .landing-role-tabs label[for="landing-role-mahasiswa"],
            #landing-role-dosen:focus-visible ~ .landing-role-tabs label[for="landing-role-dosen"],
            #landing-role-admin:focus-visible ~ .landing-role-tabs label[for="landing-role-admin"] {
                outline: 2px solid #201f31;
                outline-offset: 2px;
            }

            .landing-disclosure summary { list-style: none; cursor: pointer; }
            .landing-disclosure summary::-webkit-details-marker { display: none; }
            .landing-disclosure summary:focus-visible { outline: 2px solid #201f31; outline-offset: 2px; }
            .landing-disclosure .landing-plus { display: block; }
            .landing-disclosure .landing-minus { display: none; }
            .landing-disclosure[open] .landing-plus { display: none; }
            .landing-disclosure[open] .landing-minus { display: block; }
        </style>
    </head>
    <body data-landing class="min-h-full bg-surface font-sans text-text antialiased">
        <div data-progress class="pointer-events-none fixed inset-x-0 top-0 z-30 h-0.5 origin-left scale-x-0 bg-primary" aria-hidden="true"></div>

        @php
            $navLink = 'rounded-full px-3 py-2 text-sm font-semibold text-muted transition hover:bg-soft hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary';
            $primaryButton = 'inline-flex h-11 items-center justify-center gap-2 rounded-full bg-primary px-6 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';
            $secondaryButton = 'inline-flex h-11 items-center justify-center gap-2 rounded-full border border-border bg-card px-6 text-sm font-bold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';
            $footerLink = 'rounded-full py-1 text-sm text-muted transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary';
        @endphp

        {{-- SECTION 1 — NAVBAR --}}
        <header class="relative z-20 border-b border-border bg-card">
            <div class="flex w-full items-center justify-between gap-4 px-5 py-4 sm:px-8 lg:px-12 xl:px-16">
                <a href="#beranda" data-landing-hover="link" class="flex min-w-0 items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <span class="grid size-10 shrink-0 place-items-center rounded-tile bg-primary text-primary-text shadow-card">
                        <x-icon name="book" class="size-5" />
                    </span>
                    <span class="truncate text-sm font-bold tracking-tight text-text sm:text-base">Student Academic Planner</span>
                </a>

                <nav aria-label="Navigasi utama" class="hidden items-center gap-1 lg:flex">
                    <a href="#fitur" data-landing-hover="link" class="{{ $navLink }}">Fitur</a>
                    <a href="#kalender" data-landing-hover="link" class="{{ $navLink }}">Kalender</a>
                    <a href="#untuk-siapa" data-landing-hover="link" class="{{ $navLink }}">Untuk Siapa</a>
                    <a href="#faq" data-landing-hover="link" class="{{ $navLink }}">FAQ</a>
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('login') }}" data-landing-hover="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-full bg-primary px-5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-card">
                        Masuk <x-icon name="chevron-right" class="size-4" />
                    </a>
                    <details class="landing-disclosure lg:hidden">
                        <summary data-landing-hover="button" class="grid size-10 place-items-center rounded-full bg-soft text-text" aria-label="Buka menu navigasi">
                            <x-icon name="menu" class="size-5" />
                        </summary>
                        <nav aria-label="Navigasi mobile" class="absolute right-5 top-16 w-56 rounded-card bg-card p-3 shadow-card sm:right-8">
                            <a href="#fitur" data-landing-hover="link" class="block rounded-full px-4 py-2.5 text-sm font-semibold text-text hover:bg-soft">Fitur</a>
                            <a href="#kalender" data-landing-hover="link" class="block rounded-full px-4 py-2.5 text-sm font-semibold text-text hover:bg-soft">Kalender</a>
                            <a href="#untuk-siapa" data-landing-hover="link" class="block rounded-full px-4 py-2.5 text-sm font-semibold text-text hover:bg-soft">Untuk Siapa</a>
                            <a href="#faq" data-landing-hover="link" class="block rounded-full px-4 py-2.5 text-sm font-semibold text-text hover:bg-soft">FAQ</a>
                        </nav>
                    </details>
                </div>
            </div>
        </header>

        <main>
            {{-- SECTION 2 — HERO --}}
            <section id="beranda" class="scroll-mt-24 w-full px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-16">
                    <div data-reveal-group>
                        <span data-reveal class="inline-flex items-center gap-2 rounded-full bg-tile-lime px-3 py-1.5 text-xs font-bold text-primary-text">
                            <x-icon name="calendar" class="size-4" /> Perencanaan akademik
                        </span>
                        <h1 data-reveal class="mt-6 text-4xl font-bold leading-[1.12] tracking-tight text-text sm:text-5xl xl:text-6xl">Atur Aktivitas Kuliah. Kendalikan Progres Akademik.</h1>
                        <p data-reveal class="mt-6 max-w-xl text-base leading-7 text-muted sm:text-lg">Satu tempat untuk mengelola tugas, jadwal akademik, materi pembelajaran, ujian, dan progres kuliah agar aktivitas perkuliahan lebih terorganisir.</p>
                        <div data-reveal class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('login') }}" data-landing-hover="button" class="{{ $primaryButton }}">Masuk ke Aplikasi <x-icon name="chevron-right" class="size-4" /></a>
                            <a href="#fitur" data-landing-hover="button" class="{{ $secondaryButton }}">Jelajahi Fitur</a>
                        </div>
                        <p data-reveal class="mt-6 text-sm text-muted">Akun dibuat oleh administrator. Tidak tersedia pendaftaran publik.</p>
                    </div>

                    <div data-reveal class="min-w-0">
                        <img
                            data-parallax="12"
                            src="{{ asset('images/landing/hero-student.png') }}"
                            width="2160"
                            height="2160"
                            alt="Ilustrasi mahasiswa mengatur tugas dan kalender akademik di laptop"
                            class="mx-auto w-full max-w-[640px] object-contain"
                            loading="eager"
                            decoding="async"
                        >
                    </div>
                </div>
            </section>

            {{-- SECTION 3 — PROBLEM & SOLUTION --}}
            <section id="masalah" class="scroll-mt-24 w-full bg-soft px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                @php
                    $problems = [
                        ['title' => 'Tugas tersebar di berbagai tempat.', 'body' => 'File, catatan, dan pengingat mudah terpisah sehingga sulit diketahui mana yang masih harus dikerjakan.', 'solution' => 'Kelola tugas dan pengumpulan dalam satu tempat.', 'image' => 'problem-scattered-tasks.png', 'imageAlt' => 'Ilustrasi tugas dan catatan yang tersebar di berbagai tempat'],
                        ['title' => 'Deadline mudah terlewat.', 'body' => 'Tanpa ringkasan yang jelas, batas pengumpulan bisa terlewati sebelum sempat disiapkan.', 'solution' => 'Pantau deadline melalui kalender akademik.', 'image' => 'problem-deadline.png', 'imageAlt' => 'Ilustrasi mahasiswa menghadapi tugas yang menumpuk dan deadline'],
                        ['title' => 'Jadwal akademik sulit dipantau.', 'body' => 'Kuliah, ujian, dan pengumuman datang dari banyak sumber yang berbeda-beda.', 'solution' => 'Lihat agenda akademik dalam satu tampilan.', 'image' => 'problem-academic-schedule.png', 'imageAlt' => 'Ilustrasi jadwal akademik yang bertumpuk dan sulit dipantau'],
                    ];
                @endphp

                <x-landing.section-heading eyebrow="Masalah & Solusi" title="Kuliah Lebih Terarah, Tanpa Kehilangan Jejak." description="Aplikasi membantu menyatukan informasi akademik. Kamu tetap menentukan prioritas dan mengambil tindakannya." align="center" />

                <div data-reveal-group class="mx-auto mt-14 max-w-6xl space-y-12 sm:space-y-16">
                    @foreach ($problems as $index => $problem)
                        <div data-reveal-item class="grid items-center gap-8 md:grid-cols-2 md:gap-12">
                            <div class="{{ $index % 2 === 1 ? 'md:order-2' : '' }}">
                                <span class="text-4xl font-bold text-primary-hover">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="mt-4 text-2xl font-bold leading-snug text-text">{{ $problem['title'] }}</h3>
                                <p class="mt-3 text-base leading-7 text-muted">{{ $problem['body'] }}</p>
                                <p data-landing-hover="card" class="mt-5 inline-flex items-start gap-2 rounded-card bg-card px-4 py-3 text-sm font-semibold text-text shadow-card">
                                    <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-success-text" /> {{ $problem['solution'] }}
                                </p>
                            </div>
                            <div class="{{ $index % 2 === 1 ? 'md:order-1' : '' }} min-w-0">
                                <img
                                    data-parallax="6"
                                    src="{{ asset('images/landing/'.$problem['image']) }}"
                                    width="2160"
                                    height="2160"
                                    alt="{{ $problem['imageAlt'] }}"
                                    class="mx-auto w-full max-w-sm object-contain"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- SECTION 4 — ACADEMIC CALENDAR --}}
            <section id="kalender" class="scroll-mt-24 w-full bg-sidebar py-16 text-white sm:py-24">
                <div class="w-full px-5 sm:px-8 lg:px-12 xl:px-16">
                    <x-landing.section-heading inverted align="center" eyebrow="Kalender Akademik" title="Semua Agenda Akademik dalam Satu Kalender." description="Pantau jadwal perkuliahan, tugas, ujian, dan pengumuman agar kamu dapat merencanakan aktivitas akademik dengan lebih teratur." />

                    <div class="mx-auto mt-12 max-w-5xl">
                        <div role="group" aria-label="Pilih tampilan kalender">
                            <input type="radio" name="landing-calendar-view" id="landing-view-calendar" class="sr-only" checked>
                            <input type="radio" name="landing-calendar-view" id="landing-view-timeline" class="sr-only">

                            <div class="landing-cal-tabs mt-2 flex justify-center">
                                <div class="inline-flex rounded-full bg-white/10 p-1">
                                    <label for="landing-view-calendar" class="cursor-pointer rounded-full px-5 py-2.5 text-sm font-bold text-white/70 transition">Calendar</label>
                                    <label for="landing-view-timeline" class="cursor-pointer rounded-full px-5 py-2.5 text-sm font-bold text-white/70 transition">Timeline</label>
                                </div>
                            </div>

                            <div data-motion="panel-switch" class="landing-panels mt-8">
                                <div id="landing-panel-calendar" class="landing-panel">
                                    <x-landing.mockup-calendar />
                                </div>
                                <div id="landing-panel-timeline" class="landing-panel">
                                    <x-landing.mockup-timeline />
                                </div>
                            </div>
                        </div>
                        <p class="mt-6 text-center text-xs text-white/60">Ilustrasi antarmuka — contoh tampilan</p>
                    </div>
                </div>
            </section>

            {{-- SECTION 5 — FITUR UTAMA --}}
            <section id="fitur" class="scroll-mt-24 w-full px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                <x-landing.section-heading eyebrow="Fitur Utama" title="Fitur yang Membantu Aktivitas Akademik Lebih Terorganisir." description="Setiap fitur dirancang sesuai alur kerja mahasiswa, dosen, dan administrator di aplikasi." align="center" />

                <div data-reveal-group class="mx-auto mt-14 max-w-6xl space-y-12">
                    {{-- Showcase horizontal --}}
                    <div data-reveal-item class="grid items-center gap-8 rounded-panel bg-tile-lavender p-6 sm:p-10 lg:grid-cols-2 lg:gap-14">
                        <div>
                            <span data-landing-float class="grid size-12 place-items-center rounded-tile bg-card text-text shadow-card"><x-icon name="clipboard" class="size-6" /></span>
                            <h3 class="mt-5 text-2xl font-bold text-text">Manajemen Tugas</h3>
                            <p class="mt-3 leading-7 text-muted">Dosen membuat tugas pada kelas yang diajar dan melihat daftar pengumpulan. Mahasiswa melihat tugas, mengumpulkan berkas, dan dapat mengulang pengumpulan sebelum batas waktu.</p>
                            <p class="mt-4 text-sm font-semibold text-text">Tugas dan pengumpulan tersatu dalam alur yang jelas.</p>
                        </div>
                        <div class="min-w-0">
                            <x-landing.mockup-feature variant="tasks" title="Daftar tugas" />
                        </div>
                    </div>

                    {{-- Grid tiga fitur dengan mini UI --}}
                    <div data-reveal-group class="grid gap-6 md:grid-cols-3">
                        <div data-reveal-item class="rounded-panel bg-card p-5 shadow-card">
                            <span class="grid size-11 place-items-center rounded-tile bg-tile-lime text-text"><x-icon name="chart" class="size-5" /></span>
                            <h3 class="mt-4 text-lg font-bold text-text">Materi dan Progres Pembelajaran</h3>
                            <p class="mt-2 text-sm leading-6 text-muted">Dosen mengunggah materi. Mahasiswa membaca, mengunduh, dan memantau progres yang dihitung dari aktivitas pembelajaran.</p>
                            <div class="mt-5"><x-landing.mockup-feature variant="progress" title="Progres materi" /></div>
                        </div>
                        <div data-reveal-item class="rounded-panel bg-card p-5 shadow-card">
                            <span class="grid size-11 place-items-center rounded-tile bg-tile-sky text-text"><x-icon name="exam" class="size-5" /></span>
                            <h3 class="mt-4 text-lg font-bold text-text">Jadwal UTS dan UAS</h3>
                            <p class="mt-2 text-sm leading-6 text-muted">Jadwal ujian institusi dikelola administrator dan dapat dilihat oleh dosen serta mahasiswa, termasuk di kalender akademik.</p>
                            <div class="mt-5"><x-landing.mockup-feature variant="exam" title="Jadwal ujian" /></div>
                        </div>
                        <div data-reveal-item class="rounded-panel bg-card p-5 shadow-card">
                            <span class="grid size-11 place-items-center rounded-tile bg-tile-peach text-text"><x-icon name="check" class="size-5" /></span>
                            <h3 class="mt-4 text-lg font-bold text-text">Absensi Perkuliahan</h3>
                            <p class="mt-2 text-sm leading-6 text-muted">Dosen membuka sesi absensi dan memperbaiki data sesuai ketentuan. Mahasiswa mengikuti sesi yang sedang dibuka.</p>
                            <div class="mt-5"><x-landing.mockup-feature variant="attendance" title="Rekap absensi" /></div>
                        </div>
                    </div>

                    {{-- Dua baris editorial --}}
                    <div data-reveal-item class="grid items-center gap-8 rounded-panel bg-soft p-6 sm:p-10 lg:grid-cols-2 lg:gap-12">
                        <div class="min-w-0">
                            <x-landing.mockup-feature variant="grades" title="Nilai & indeks prestasi" />
                        </div>
                        <div>
                            <span class="grid size-12 place-items-center rounded-tile bg-card text-text shadow-card"><x-icon name="chart" class="size-6" /></span>
                            <h3 class="mt-5 text-2xl font-bold text-text">Nilai dan Indeks Prestasi</h3>
                            <p class="mt-3 leading-7 text-muted">Dosen mengelola komponen nilai UTS dan UAS. Mahasiswa melihat nilai akhir, IP, dan IPK yang dihitung aplikasi dari data yang tersedia.</p>
                        </div>
                    </div>

                    <div data-reveal-item class="grid items-center gap-8 rounded-panel bg-soft p-6 sm:p-10 lg:grid-cols-2 lg:gap-12">
                        <div>
                            <span class="grid size-12 place-items-center rounded-tile bg-card text-text shadow-card"><x-icon name="target" class="size-6" /></span>
                            <h3 class="mt-5 text-2xl font-bold text-text">Target Belajar</h3>
                            <p class="mt-3 leading-7 text-muted">Mahasiswa membuat target belajar, menandai capaian, dan mencatat target yang masih berjalan sesuai kebutuhan sendiri.</p>
                        </div>
                        <div class="min-w-0"><x-landing.mockup-feature variant="target" title="Target belajar" /></div>
                    </div>

                    {{-- Penutup fitur --}}
                    <div data-reveal-item class="grid items-center gap-8 rounded-panel bg-tile-sand p-6 sm:p-10 lg:grid-cols-[1fr_1.2fr] lg:gap-12">
                        <div>
                            <span data-landing-float class="grid size-12 place-items-center rounded-tile bg-card text-text shadow-card"><x-icon name="bell" class="size-6" /></span>
                            <h3 class="mt-5 text-2xl font-bold text-text">Notifikasi dan Pengumuman</h3>
                            <p class="mt-3 leading-7 text-muted">Notifikasi tampil di dalam aplikasi. Pengumuman akademik diterbitkan oleh administrator dan dosen sesuai cakupan perannya, lalu dapat dibaca oleh mahasiswa.</p>
                        </div>
                        <div class="min-w-0"><x-landing.mockup-feature variant="notification" title="Notifikasi terbaru" /></div>
                    </div>
                </div>
            </section>

            {{-- SECTION 6 — UNTUK SIAPA --}}
            <section id="untuk-siapa" class="scroll-mt-24 w-full bg-soft px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                <x-landing.section-heading eyebrow="Untuk Siapa" title="Dirancang untuk Setiap Peran Akademik." description="Hak akses dibedakan agar setiap peran hanya mengelola data yang menjadi tanggung jawabnya." align="center" />

                @php
                    $roles = [
                        ['id' => 'mahasiswa', 'name' => 'Mahasiswa', 'image' => 'role-student.png', 'imageAlt' => 'Ilustrasi mahasiswa dengan daftar aktivitas akademik', 'items' => [
                            'Melihat kalender akademik', 'Melihat dan mengumpulkan tugas', 'Mengakses materi pembelajaran', 'Memantau progres pembelajaran', 'Mengikuti sesi absensi', 'Melihat jadwal UTS dan UAS', 'Melihat nilai serta IP/IPK', 'Mengatur target belajar', 'Membaca notifikasi dan pengumuman', 'Bergabung ke kelas menggunakan kunci kelas',
                        ]],
                        ['id' => 'dosen', 'name' => 'Dosen', 'image' => 'role-lecturer.png', 'imageAlt' => 'Ilustrasi dosen yang mengelola kelas dan tugas', 'items' => [
                            'Mengelola kelas mata kuliah', 'Mengelola tugas kelas', 'Mengunggah materi pembelajaran', 'Mengelola sesi absensi', 'Memperbaiki data absensi sesuai ketentuan', 'Mengelola komponen penilaian dan nilai', 'Melihat jadwal ujian', 'Mengelola pengumuman kelas', 'Membuat dan mengelola kunci kelas',
                        ]],
                        ['id' => 'admin', 'name' => 'Admin', 'image' => 'role-admin.png', 'imageAlt' => 'Ilustrasi administrator yang mengelola data akademik', 'items' => [
                            'Mengelola pengguna', 'Mengelola data mahasiswa dan dosen', 'Mengelola mata kuliah', 'Mengelola tahun akademik', 'Mengelola jadwal kuliah dan ujian', 'Mengelola pengumuman institusi',
                        ]],
                    ];
                @endphp

                <div class="mx-auto mt-12 max-w-5xl">
                    <div role="group" aria-label="Pilih peran pengguna">
                        <input type="radio" name="landing-role" id="landing-role-mahasiswa" class="sr-only" checked>
                        <input type="radio" name="landing-role" id="landing-role-dosen" class="sr-only">
                        <input type="radio" name="landing-role" id="landing-role-admin" class="sr-only">

                        <div data-reveal-group class="landing-role-tabs mt-2 flex flex-wrap justify-center gap-2">
                            @foreach ($roles as $role)
                                <label data-reveal for="landing-role-{{ $role['id'] }}" class="cursor-pointer rounded-full bg-card px-5 py-2.5 text-sm font-bold text-muted shadow-card transition">{{ $role['name'] }}</label>
                            @endforeach
                        </div>

                        <div data-motion="panel-switch" class="landing-role-panels mt-10">
                            @foreach ($roles as $role)
                                <div id="landing-panel-{{ $role['id'] }}" class="landing-panel">
                                    <div data-landing-hover="card" class="grid items-center gap-8 rounded-panel bg-card p-6 shadow-card sm:p-10 lg:grid-cols-2 lg:gap-12">
                                        <div class="min-w-0">
                                            <img
                                                src="{{ asset('images/landing/'.$role['image']) }}"
                                                width="2160"
                                                height="2160"
                                                alt="{{ $role['imageAlt'] }}"
                                                class="mx-auto w-full max-w-md object-contain"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-bold text-text">{{ $role['name'] }}</h3>
                                            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                                                @foreach ($role['items'] as $item)
                                                    <li class="flex items-start gap-2 text-sm text-text">
                                                        <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-success-text" /> {{ $item }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- SECTION 7 — HOW IT WORKS --}}
            <section id="cara-kerja" class="scroll-mt-24 w-full px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                <x-landing.section-heading eyebrow="Cara Kerja" title="Mulai Gunakan Student Academic Planner." description="Empat langkah untuk memakai aplikasi. Seluruh akun disiapkan oleh administrator." align="center" />

                <div data-reveal class="mx-auto mt-12 max-w-2xl">
                    <img
                        data-parallax="6"
                        src="{{ asset('images/landing/how-it-works.png') }}"
                        width="2160"
                        height="2160"
                        alt="Ilustrasi alur penggunaan aplikasi dari penyiapan akun hingga aktivitas mahasiswa"
                        class="mx-auto w-full object-contain"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                @php
                    $steps = [
                        ['title' => 'Akun disiapkan admin', 'body' => 'Administrator membuat akun mahasiswa dan dosen. Tidak tersedia pendaftaran publik.'],
                        ['title' => 'Persiapan akademik', 'body' => 'Administrator menyiapkan tahun akademik, mata kuliah, dan jadwal akademik.'],
                        ['title' => 'Pengelolaan kelas', 'body' => 'Dosen menyiapkan kelas, materi, tugas, sesi absensi, dan kunci gabung kelas.'],
                        ['title' => 'Aktivitas mahasiswa', 'body' => 'Mahasiswa bergabung ke kelas dengan kunci, lalu mengelola tugas, materi, dan target belajar.'],
                    ];
                @endphp

                <div data-reveal-group class="mx-auto mt-10 grid max-w-6xl gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $index => $step)
                        <div data-reveal-item class="rounded-panel bg-card p-5 shadow-card">
                            <div class="flex items-center justify-between">
                                <span class="grid size-9 place-items-center rounded-full bg-primary text-sm font-bold text-primary-text">{{ $index + 1 }}</span>
                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Langkah {{ $index + 1 }}</span>
                            </div>
                            <h3 class="mt-5 text-base font-bold text-text">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-muted">{{ $step['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- SECTION 8 — FAQ --}}
            <section id="faq" class="scroll-mt-24 w-full bg-soft px-5 py-16 sm:px-8 sm:py-24 lg:px-12 xl:px-16">
                <x-landing.section-heading eyebrow="FAQ" title="Pertanyaan yang Sering Diajukan." align="center" />

                @php
                    $faqs = [
                        ['Bagaimana cara mendapatkan akun?', 'Akun mahasiswa dan dosen dibuat oleh administrator. Hubungi administrator untuk mendapatkan akses.'],
                        ['Apakah saya bisa mendaftar sendiri?', 'Tidak. Tidak tersedia pendaftaran publik pada aplikasi ini. Pengguna dengan akun dapat langsung masuk melalui halaman login.'],
                        ['Bagaimana jika saya lupa password?', 'Hubungi administrator untuk mendapatkan bantuan terkait akses akun. Saat ini belum tersedia fitur pemulihan password mandiri melalui email.'],
                        ['Apakah mahasiswa dapat melihat jadwal UTS dan UAS?', 'Ya. Mahasiswa dapat melihat jadwal UTS dan UAS melalui menu Jadwal Ujian serta kalender akademik.'],
                        ['Apakah dosen dapat mengelola materi dan tugas?', 'Ya. Dosen dapat mengunggah materi dan mengelola tugas pada kelas mata kuliah yang diajar.'],
                        ['Apakah aplikasi dapat digunakan melalui ponsel?', 'Ya. Tampilan aplikasi responsif dan dapat dibuka pada layar ponsel, tablet, dan desktop.'],
                        ['Apakah semua pengguna memiliki akses yang sama?', 'Tidak. Hak akses dibedakan antara mahasiswa, dosen, dan administrator agar setiap peran hanya mengelola data yang menjadi tanggung jawabnya.'],
                    ];
                @endphp

                <div data-reveal-group class="mx-auto mt-12 max-w-3xl space-y-3">
                    @foreach ($faqs as $faq)
                        <details data-reveal-item class="landing-disclosure rounded-card bg-card px-5 py-4 shadow-card">
                            <summary data-landing-hover="link" class="flex items-center justify-between gap-4 text-left text-sm font-bold text-text sm:text-base">
                                {{ $faq[0] }}
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-soft text-text" aria-hidden="true">
                                    <x-icon name="plus" class="landing-plus size-4" />
                                    <x-icon name="close" class="landing-minus size-4" />
                                </span>
                            </summary>
                            <p class="mt-3 text-sm leading-7 text-muted">{{ $faq[1] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            {{-- SECTION 9 — CLOSING CTA --}}
            <section id="mulai" class="scroll-mt-24 w-full bg-sidebar py-16 text-white sm:py-24">
                <div class="w-full px-5 sm:px-8 lg:px-12 xl:px-16">
                    <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16">
                        <div data-reveal>
                            <h2 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl">Mulai Kelola Aktivitas Akademik dengan Lebih Terarah.</h2>
                            <p class="mt-5 max-w-xl text-base leading-7 text-white/75">Akses Student Academic Planner dan kelola kebutuhan akademikmu melalui satu platform.</p>
                            <a href="{{ route('login') }}" data-landing-hover="button" class="mt-8 inline-flex h-12 items-center justify-center gap-2 rounded-full bg-primary px-7 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-sidebar">
                                Masuk ke Akun Anda <x-icon name="chevron-right" class="size-4" />
                            </a>
                            <p class="mt-5 text-sm text-white/60">Akun pengguna disiapkan oleh administrator.</p>
                        </div>
                        <div data-reveal class="min-w-0">
                            <img
                                data-parallax="10"
                                src="{{ asset('images/landing/closing-academic.png') }}"
                                width="2160"
                                height="2160"
                                alt="Ilustrasi mahasiswa menyelesaikan rencana akademik"
                                class="mx-auto w-full max-w-sm object-contain"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- SECTION 10 — FOOTER --}}
        <footer class="w-full bg-card px-5 py-12 sm:px-8 lg:px-12 xl:px-16">
            <div class="mx-auto max-w-6xl">
                <div class="grid gap-10 md:grid-cols-[1.4fr_1fr]">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-tile bg-primary text-primary-text shadow-card"><x-icon name="book" class="size-5" /></span>
                            <span class="text-sm font-bold text-text">Student Academic Planner</span>
                        </div>
                        <p class="mt-4 max-w-md text-sm leading-7 text-muted">Sistem perencanaan akademik dan pembelajaran untuk mahasiswa, dosen, dan administrator dalam satu aplikasi.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-text">Navigasi</p>
                            <div class="mt-3 flex flex-col items-start gap-1.5">
                                <a href="#fitur" data-landing-hover="link" class="{{ $footerLink }}">Fitur</a>
                                <a href="#kalender" data-landing-hover="link" class="{{ $footerLink }}">Kalender</a>
                                <a href="#untuk-siapa" data-landing-hover="link" class="{{ $footerLink }}">Untuk Siapa</a>
                                <a href="#faq" data-landing-hover="link" class="{{ $footerLink }}">FAQ</a>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-text">Akses</p>
                            <div class="mt-3 flex flex-col items-start gap-1.5">
                                <a href="{{ route('login') }}" data-landing-hover="link" class="{{ $footerLink }}">Masuk</a>
                                <a href="#cara-kerja" data-landing-hover="link" class="{{ $footerLink }}">Cara Kerja</a>
                                <a href="#beranda" data-landing-hover="link" class="{{ $footerLink }}">Beranda</a>
                            </div>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-text">Catatan</p>
                            <p class="mt-3 text-sm leading-6 text-muted">Akun dibuat oleh administrator. Tidak tersedia pendaftaran publik.</p>
                        </div>
                    </div>
                </div>
                <p class="mt-10 border-t border-border pt-6 text-xs text-muted">Student Academic Planner</p>
            </div>
        </footer>
    </body>
</html>
