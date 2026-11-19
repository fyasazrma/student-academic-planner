<x-app-layout title="Koreksi Absensi" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Koreksi Absensi"
            eyebrow="{{ $kelas->kode_kelas }} — Pertemuan ke-{{ $sesi->pertemuan_ke }}"
            :description="'Mahasiswa: '.$peserta->mahasiswa?->name.' ('.$peserta->mahasiswa?->nim.'). Status saat ini: '.$statusLabel.'.'"
        >
            <x-slot:actions>
                <a href="{{ route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke sesi
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

        <form
            method="POST"
            action="{{ route('dosen.absensi.koreksi', [$kelas->getKey(), $sesi->getKey(), $peserta->getKey()]) }}"
            class="space-y-5 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            @csrf

            <fieldset>
                <legend class="text-xs font-bold text-text">Status baru <span class="text-danger">*</span></legend>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($statusOptions as $opsi)
                        <label class="cursor-pointer rounded-xl border border-border bg-soft px-3 py-3 text-center text-xs font-semibold text-text transition hover:bg-white">
                            <input
                                type="radio"
                                name="status"
                                value="{{ $opsi['value'] }}"
                                class="sr-only"
                                @checked(old('status', $statusSekarang) === $opsi['value'])
                                required
                            >
                            {{ $opsi['label'] }}
                        </label>
                    @endforeach
                </div>
                @error('status')
                    <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                @enderror
            </fieldset>

            <div>
                <label for="alasan" class="block text-xs font-bold text-text">Alasan koreksi <span class="text-danger">*</span></label>
                <textarea
                    id="alasan"
                    name="alasan"
                    rows="3"
                    maxlength="255"
                    class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/30"
                    placeholder="Contoh: Mahasiswa mengikuti kuliah tetapi lupa mengisi absensi mandiri."
                    required
                >{{ old('alasan') }}</textarea>
                @error('alasan')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <p class="rounded-xl bg-soft px-3 py-3 text-xs leading-5 text-muted">
                Status lama, status baru, alasan, dosen pengoreksi, dan waktu perubahan akan disimpan sebagai riwayat audit.
            </p>

            <div class="flex flex-wrap justify-end gap-2 border-t border-border pt-4">
                <a href="{{ route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]) }}" class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Batal</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="save" class="h-4 w-4" />
                    Simpan koreksi
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
