<x-app-layout :title="$pengumuman === null ? 'Tambah Pengumuman' : 'Ubah Pengumuman'" :user="$user" :unread-count="$unreadCount" role="admin">
    @php
        $statusValue = old('status', $pengumuman === null
            ? 'draft'
            : ($pengumuman->dipublikasikan_pada === null
                ? 'draft'
                : ($pengumuman->dipublikasikan_pada->isFuture() ? 'terjadwal' : 'terbit')));
        $publikasiValue = old('tanggal_publikasi', $pengumuman?->dipublikasikan_pada?->format('Y-m-d\TH:i'));
    @endphp

    <x-page-header
        :title="$pengumuman === null ? 'Tambah Pengumuman Akademik' : 'Ubah Pengumuman Akademik'"
        eyebrow="Pengumuman"
        :description="$pengumuman === null
            ? 'Pengumuman dapat disimpan sebagai draft, diterbitkan langsung, atau dijadwalkan.'
            : 'Perbarui informasi pengumuman dan status publikasinya.'"
    >
        <x-slot:actions>
            <a href="{{ route('admin.pengumuman.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman">
                <x-icon name="chevron-left" class="h-4 w-4" />
                Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-xs leading-5 text-text">
            <p class="font-bold">Periksa kembali formulir pengumuman.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $pengumuman === null ? route('admin.pengumuman.store') : route('admin.pengumuman.update', $pengumuman) }}"
        class="mt-6 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        data-auth-form
    >
        @csrf
        @if ($pengumuman !== null)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="judul" class="block text-xs font-semibold text-text">Judul pengumuman</label>
                <input id="judul" name="judul" type="text" required maxlength="200" value="{{ old('judul', $pengumuman?->judul) }}" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">
                @error('judul')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="isi" class="block text-xs font-semibold text-text">Isi pengumuman</label>
                <textarea id="isi" name="isi" rows="6" required maxlength="5000" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">{{ old('isi', $pengumuman?->deskripsi) }}</textarea>
                @error('isi')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="kelas_mata_kuliah_id" class="block text-xs font-semibold text-text">Target penerima</label>
                <select id="kelas_mata_kuliah_id" name="kelas_mata_kuliah_id" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">
                    <option value="">Seluruh mahasiswa</option>
                    @foreach ($kelasOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('kelas_mata_kuliah_id', $pengumuman?->kelas_mata_kuliah_id ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('kelas_mata_kuliah_id')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-[11px] leading-4 text-muted">Kosongkan untuk pengumuman akademik global.</p>
            </div>

            <div class="sm:col-span-2">
                <span class="block text-xs font-semibold text-text">Status publikasi</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($statusOptions as $value => $label)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-border bg-surface px-3 py-2 text-sm text-text transition has-checked:border-agenda-pengumuman has-checked:bg-agenda-pengumuman/10">
                            <input type="radio" name="status" value="{{ $value }}" class="h-4 w-4 accent-agenda-pengumuman" @checked($statusValue === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('status')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="tanggal_publikasi" class="block text-xs font-semibold text-text">Waktu publikasi (untuk status terjadwal)</label>
                <input id="tanggal_publikasi" name="tanggal_publikasi" type="datetime-local" value="{{ $publikasiValue }}" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">
                @error('tanggal_publikasi')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tanggal_mulai" class="block text-xs font-semibold text-text">Tanggal mulai berlaku</label>
                <input id="tanggal_mulai" name="tanggal_mulai" type="date" required value="{{ old('tanggal_mulai', $pengumuman?->tanggal_mulai?->toDateString() ?? now()->toDateString()) }}" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">
                @error('tanggal_mulai')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tanggal_selesai" class="block text-xs font-semibold text-text">Tanggal berakhir (opsional)</label>
                <input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ old('tanggal_selesai', $pengumuman?->tanggal_selesai?->toDateString()) }}" class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-pengumuman focus:outline-none focus:ring-2 focus:ring-agenda-pengumuman/25">
                @error('tanggal_selesai')<p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="submit" data-submit-button class="rounded-xl bg-agenda-pengumuman px-5 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-pengumuman/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-pengumuman focus-visible:ring-offset-2">
                {{ $pengumuman === null ? 'Simpan pengumuman' : 'Simpan perubahan' }}
            </button>
        </div>
    </form>
</x-app-layout>
