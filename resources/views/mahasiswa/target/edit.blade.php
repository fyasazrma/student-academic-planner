<x-app-layout title="Edit Target Belajar" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-danger">{{ session('error') }}</div>
        @endif

        <x-page-header
            title="Edit Target Belajar"
            eyebrow="Perencanaan mahasiswa"
            description="Ubah judul, capaian, atau rentang waktu target. Status tetap dihitung oleh sistem."
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.target.show', $target->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            @include('mahasiswa.target._form', [
                'action' => route('mahasiswa.target.update', $target->id),
                'method' => 'PATCH',
                'submitLabel' => 'Simpan Perubahan',
            ])
        </section>
    </div>
</x-app-layout>
