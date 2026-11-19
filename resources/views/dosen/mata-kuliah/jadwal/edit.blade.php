<x-app-layout title="Ubah Jadwal" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Ubah Jadwal"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Perbarui hari, waktu, ruang, atau jenis jadwal kelas ini."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.jadwal.index', $kelas) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke jadwal
                </a>
            </x-slot:actions>
        </x-page-header>

        <form
            method="POST"
            action="{{ route('dosen.mata-kuliah.jadwal.update', [$kelas, $jadwal]) }}"
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
            data-auth-form
            novalidate
        >
            @csrf
            @method('PUT')
            @include('dosen.mata-kuliah.jadwal._form', ['jadwal' => $jadwal])

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-border pt-5">
                <a
                    href="{{ route('dosen.mata-kuliah.jadwal.index', $kelas) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    Simpan perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
