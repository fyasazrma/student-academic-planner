@php
    $accept = collect($ekstensiDiizinkan)->map(fn (string $ekstensi): string => '.'.$ekstensi)->implode(',');
@endphp

<x-app-layout title="Kumpulkan Tugas" :user="$user" :unread-count="$unreadCount">
    <x-page-header
        title="Kumpulkan Tugas"
        eyebrow="Pengumpulan mahasiswa"
        description="Unggah lampiran jawaban Anda. Tanggal publikasi dan deadline tetap ditampilkan terpisah."
    >
        <x-slot:actions>
            <a
                href="{{ route('mahasiswa.tugas') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
            >
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali ke daftar tugas
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="mt-6 rounded-xl border border-success/30 bg-success/5 px-4 py-3 text-sm font-medium text-text">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mt-6 rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm font-medium text-text">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Detail tugas</p>
                        <h3 class="mt-2 text-xl font-bold text-text">{{ $tugas->judul }}</h3>
                        <p class="mt-1 text-sm text-muted">
                            {{ $namaMataKuliah }}
                            @if ($kelas?->kode_kelas)
                                · Kelas {{ $kelas->kode_kelas }}
                            @endif
                        </p>
                    </div>
                    @if ($pengumpulan)
                        <span class="rounded-lg bg-success/10 px-3 py-1.5 text-xs font-bold text-success">
                            {{ $pengumpulan->status === 'dinilai' ? 'Sudah dinilai' : 'Sudah dikumpulkan' }}
                        </span>
                    @endif
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-border bg-soft p-4">
                        <p class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-muted">
                            <x-icon name="file" class="h-3.5 w-3.5 text-agenda-tugas" />
                            Dipublikasikan
                        </p>
                        <p class="mt-1.5 text-sm font-semibold text-text">
                            {{ $tugas->dipublikasikan_pada?->translatedFormat('d M Y H:i') ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-soft p-4">
                        <p class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-muted">
                            <x-icon name="clock" class="h-3.5 w-3.5 text-agenda-terlambat" />
                            Deadline
                        </p>
                        <p class="mt-1.5 text-sm font-semibold text-text">
                            {{ $tugas->deadline?->translatedFormat('d M Y H:i') ?? '-' }}
                        </p>
                    </div>
                </div>

                @if ($tugas->deskripsi)
                    <div class="mt-5">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-muted">Deskripsi tugas</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-text">{{ $tugas->deskripsi }}</p>
                    </div>
                @endif
            </section>

            @if ($pengumpulan)
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Pengumpulan Anda</p>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Dikumpulkan</dt>
                            <dd class="mt-1 font-medium text-text">
                                {{ $pengumpulan->dikumpulkan_pada?->translatedFormat('d M Y H:i') ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">File</dt>
                            <dd class="mt-1 break-all font-medium text-text">{{ $pengumpulan->file_name ?: '-' }}</dd>
                        </div>
                        @if ($pengumpulan->catatan)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Catatan Anda</dt>
                                <dd class="mt-1 leading-6 text-text">{{ $pengumpulan->catatan }}</dd>
                            </div>
                        @endif
                        @if ($pengumpulan->nilai !== null)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nilai</dt>
                                <dd class="mt-1 font-bold text-text">{{ $pengumpulan->nilai }}</dd>
                            </div>
                        @endif
                        @if ($pengumpulan->umpan_balik)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Umpan balik dosen</dt>
                                <dd class="mt-1 leading-6 text-text">{{ $pengumpulan->umpan_balik }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($unduh_url)
                        <div class="mt-5 border-t border-border pt-4">
                            <a
                                href="{{ $unduh_url }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                <x-icon name="file" class="h-4 w-4 text-agenda-tugas" />
                                Unduh lampiran saya
                            </a>
                        </div>
                    @endif
                </section>
            @endif

            @if ($alasan_terkunci)
                <div class="rounded-2xl border border-warning/40 bg-warning/5 p-5">
                    <p class="inline-flex items-center gap-2 text-sm font-bold text-text">
                        <x-icon name="info" class="h-4 w-4 text-warning" />
                        Pengumpulan terkunci
                    </p>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ $alasan_terkunci }}</p>
                </div>
            @endif

            @if ($bisa_dikumpulkan)
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">
                        {{ $pengumpulan ? 'Kirim ulang jawaban' : 'Unggah jawaban' }}
                    </p>
                    <h3 class="mt-2 text-lg font-bold text-text">Lampiran pengumpulan</h3>

                    @if ($pengumpulan)
                        <p class="mt-2 text-sm leading-6 text-muted">
                            Mengirim ulang akan mengganti lampiran dan catatan Anda pada baris pengumpulan yang sama.
                        </p>
                    @endif

                    <form class="mt-5 space-y-5" method="POST" enctype="multipart/form-data" action="{{ route('mahasiswa.tugas.pengumpulan.simpan', $tugas->id) }}">
                        @csrf

                        <div>
                            <label for="lampiran" class="block text-xs font-semibold text-text">
                                Lampiran jawaban <span class="text-agenda-terlambat">*</span>
                            </label>
                            <input
                                id="lampiran"
                                name="lampiran"
                                type="file"
                                accept="{{ $accept }}"
                                required
                                class="mt-2 block w-full cursor-pointer rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
                            >
                            <p class="mt-2 text-xs leading-5 text-muted">
                                Format: {{ strtoupper(implode(', ', $ekstensiDiizinkan)) }}. Maksimal 10 MB.
                            </p>
                            @error('lampiran')
                                <p role="alert" aria-live="assertive" class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="catatan" class="block text-xs font-semibold text-text">Catatan untuk dosen</label>
                            <textarea
                                id="catatan"
                                name="catatan"
                                rows="4"
                                maxlength="5000"
                                placeholder="Tulis catatan singkat jika diperlukan"
                                class="mt-2 w-full resize-y rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
                            >{{ old('catatan', $pengumpulan?->catatan) }}</textarea>
                            @error('catatan')
                                <p role="alert" aria-live="assertive" class="mt-1.5 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-3 border-t border-border pt-4">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                <x-icon name="check" class="h-4 w-4" />
                                {{ $pengumpulan ? 'Kirim ulang pengumpulan' : 'Kumpulkan tugas' }}
                            </button>
                            <a
                                href="{{ route('mahasiswa.tugas') }}"
                                class="text-xs font-semibold text-muted transition hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                Batal
                            </a>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Informasi pengumpulan</p>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-muted">
                <li class="flex gap-2">
                    <x-icon name="clipboard" class="mt-1 h-4 w-4 shrink-0 text-agenda-tugas" />
                    <span>Lampiran wajib diunggah. Catatan bersifat opsional.</span>
                </li>
                <li class="flex gap-2">
                    <x-icon name="clock" class="mt-1 h-4 w-4 shrink-0 text-agenda-terlambat" />
                    <span>Status keterlambatan dan batas waktu ditentukan sistem, bukan oleh formulir.</span>
                </li>
                <li class="flex gap-2">
                    <x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-success" />
                    <span>Pengumpulan yang sudah dinilai tidak dapat dikirim ulang.</span>
                </li>
            </ul>
        </aside>
    </div>
</x-app-layout>
