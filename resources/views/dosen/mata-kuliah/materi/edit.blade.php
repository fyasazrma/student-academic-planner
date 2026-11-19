<x-app-layout title="Edit Materi" :user="$user" :unread-count="$unreadCount" role="dosen">
    <x-page-header
        title="Edit Materi"
        eyebrow="Mata kuliah saya"
        description="Pembaruan materi pada kelas ini."
    />

    <div class="rounded-2xl border border-border bg-card p-5 text-sm leading-6 text-text">
        <p class="font-semibold">{{ $materi->judul }}</p>
        <p class="mt-1 text-xs text-muted">{{ $data['file_name'] }} · {{ $data['dipublikasikan_label'] }}</p>
        <p class="mt-3 text-xs text-muted">
            Endpoints backend untuk memperbarui materi sudah tersedia. Formulir pengelolaan akan disiapkan pada tahap antarmuka.
        </p>
    </div>
</x-app-layout>
