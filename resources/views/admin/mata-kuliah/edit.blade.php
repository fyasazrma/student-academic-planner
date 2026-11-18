<x-app-layout title="Edit Mata Kuliah" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Edit Mata Kuliah"
            eyebrow="Manajemen admin"
            :description="'Perbarui data induk '.$data['kode'].'. Status aktif diubah terpisah dari halaman daftar.'"
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.mata-kuliah.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar
                </a>
            </x-slot:actions>
        </x-page-header>

        @include('admin.mata-kuliah._form')
    </div>
</x-app-layout>
