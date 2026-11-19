<x-app-layout title="Edit Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Edit Kelas"
            eyebrow="Mata kuliah saya"
            :description="'Perbarui nama dan kapasitas kelas '.$detail['kode_kelas'].'. Status pendaftaran dan kunci gabung dikelola dari halaman detail.'"
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.mata-kuliah.show', $kelas->id) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke detail
                </a>
            </x-slot:actions>
        </x-page-header>

        @include('dosen.mata-kuliah._form')
    </div>
</x-app-layout>
