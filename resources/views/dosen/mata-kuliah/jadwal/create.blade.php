<x-app-layout title="Tambah Jadwal" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Tambah Jadwal"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Tambahkan jadwal kuliah, praktikum, atau kegiatan lain untuk kelas ini."
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
            action="{{ route('dosen.mata-kuliah.jadwal.store', $kelas) }}"
            class="rounded-card bg-card p-5 shadow-card sm:p-6"
            data-auth-form
            novalidate
        >
            @csrf
            @include('dosen.mata-kuliah.jadwal._form', ['jadwal' => null])

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-border pt-5">
                <a
                    href="{{ route('dosen.mata-kuliah.jadwal.index', $kelas) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    Simpan jadwal
                </button>
            </div>
        </form>
    </div>
</x-app-layout>