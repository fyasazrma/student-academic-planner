<x-app-layout title="Materi Perkuliahan" :user="$user" :unread-count="$unreadCount" role="dosen">
    <x-page-header
        title="Materi Perkuliahan"
        eyebrow="Mata kuliah saya"
        description="Daftar materi yang tersedia pada kelas ini."
    />

    <div class="rounded-2xl border border-border bg-card p-5">
        @if ($materi->isEmpty())
            <x-empty-state
                title="Belum ada materi"
                description="Materi belum ditambahkan pada kelas ini."
                icon="file"
            />
        @else
            <ul class="space-y-2">
                @foreach ($materi as $item)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border px-3 py-2 text-sm">
                        <span class="font-semibold text-text">{{ $item['judul'] }}</span>
                        <span class="text-xs text-muted">{{ $item['status_label'] }} · {{ $item['file_name'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
