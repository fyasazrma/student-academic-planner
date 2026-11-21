<x-app-layout title="Tambah Tugas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Tambah Tugas"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Buat tugas untuk kelas ini. Atur publikasi dan deadline agar mahasiswa mendapat informasi yang jelas."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.tugas.index', $kelas) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('error') }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('dosen.mata-kuliah.tugas.store', $kelas) }}"
            enctype="multipart/form-data"
            class="rounded-card bg-card p-5 shadow-card sm:p-6"
            data-auth-form
            novalidate
        >
            @csrf
            @include('dosen.mata-kuliah.tugas._form', ['tugas' => null])

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-border pt-5">
                <a
                    href="{{ route('dosen.mata-kuliah.tugas.index', $kelas) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    data-submit-button
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    <span data-submit-label>Simpan tugas</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>