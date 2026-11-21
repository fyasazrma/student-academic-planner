<x-app-layout title="{{ $mode === 'buat' ? 'Buat Sesi Absensi' : 'Ubah Sesi Absensi' }}" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $action = $mode === 'buat'
            ? route('dosen.absensi.sesi.store', $kelas->getKey())
            : route('dosen.absensi.sesi.update', [$kelas->getKey(), $sesi->getKey()]);
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            :title="$mode === 'buat' ? 'Buat Sesi Absensi' : 'Ubah Sesi Absensi'"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah' }}"
            description="Tanggal, waktu mulai, dan waktu selesai wajib diisi. Waktu selesai harus lebih besar dari waktu mulai (WIB)."
        >
            <x-slot:actions>
                <a href="{{ route('dosen.absensi.kelas', $kelas->getKey()) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-text">
                <p class="font-semibold">Periksa kembali input Anda.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="space-y-5 rounded-card bg-card p-5 shadow-card sm:p-6">
            @csrf
            @if ($mode === 'ubah')
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="pertemuan_ke" class="block text-xs font-bold text-text">Pertemuan ke <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        id="pertemuan_ke"
                        name="pertemuan_ke"
                        min="1"
                        value="{{ $nilai['pertemuan_ke'] }}"
                        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                        required
                    >
                    @error('pertemuan_ke')
                        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal" class="block text-xs font-bold text-text">Tanggal <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="{{ $nilai['tanggal'] }}"
                        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                        required
                    >
                    @error('tanggal')
                        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="topik" class="block text-xs font-bold text-text">Topik pertemuan</label>
                <input
                    type="text"
                    id="topik"
                    name="topik"
                    maxlength="200"
                    value="{{ $nilai['topik'] }}"
                    placeholder="Contoh: Pengenalan sistem basis data"
                    class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                >
                @error('topik')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="waktu_mulai" class="block text-xs font-bold text-text">Waktu mulai (WIB) <span class="text-danger">*</span></label>
                    <input
                        type="time"
                        id="waktu_mulai"
                        name="waktu_mulai"
                        value="{{ $nilai['waktu_mulai'] }}"
                        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                        required
                    >
                    @error('waktu_mulai')
                        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="waktu_selesai" class="block text-xs font-bold text-text">Waktu selesai (WIB) <span class="text-danger">*</span></label>
                    <input
                        type="time"
                        id="waktu_selesai"
                        name="waktu_selesai"
                        value="{{ $nilai['waktu_selesai'] }}"
                        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                        required
                    >
                    @error('waktu_selesai')
                        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
                <a href="{{ route('dosen.absensi.kelas', $kelas->getKey()) }}" class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Batal</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <x-icon name="save" class="h-4 w-4" />
                    {{ $mode === 'buat' ? 'Buat sesi' : 'Simpan perubahan' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
