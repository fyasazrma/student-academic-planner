<x-app-layout title="Tambah Pengguna" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Tambah Pengguna"
            eyebrow="Manajemen admin"
            description="Buat akun baru dengan role dan profil yang sesuai. Password akan disimpan menggunakan hashing Laravel."
        >
            <x-slot:actions>
                <a href="{{ route($routePrefix.'.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        @include('admin.pengguna._form')
    </div>
</x-app-layout>
