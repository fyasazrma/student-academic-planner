<x-app-layout title="Edit Tahun Akademik" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Edit Tahun Akademik"
            eyebrow="Manajemen admin"
            :description="'Perbarui tanggal periode '.$data['tahun_mulai'].'/'.$data['tahun_selesai'].'. Status dan identitas periode dikelola terpisah.'"
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.tahun-akademik.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar
                </a>
            </x-slot:actions>
        </x-page-header>

        @include('admin.tahun-akademik._form')
    </div>
</x-app-layout>
