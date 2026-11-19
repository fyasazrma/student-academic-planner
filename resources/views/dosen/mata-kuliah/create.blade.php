<x-app-layout title="Buat Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Buat Kelas"
            eyebrow="Mata kuliah saya"
            description="Buat kelas pengampu dari data mata kuliah dan tahun akademik yang masih aktif."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($mataKuliahOptions->isEmpty() || $tahunAkademikOptions->isEmpty())
            <x-empty-state
                title="Data pendukung belum tersedia"
                description="Kelas baru hanya dapat dibuat jika ada mata kuliah aktif dan tahun akademik aktif. Hubungi administrator jika keduanya belum tersedia."
                icon="info"
            />
        @else
            @include('dosen.mata-kuliah._form')
        @endif
    </div>
</x-app-layout>
