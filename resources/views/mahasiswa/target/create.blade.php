<x-app-layout title="Tambah Target Belajar" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        <x-page-header
            title="Tambah Target Belajar"
            eyebrow="Perencanaan mahasiswa"
            description="Tentukan target yang terukur, satuan capaian, dan batas waktunya."
        />

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            @include('mahasiswa.target._form', [
                'action' => route('mahasiswa.target.store'),
                'method' => 'POST',
                'submitLabel' => 'Simpan Target',
            ])
        </section>
    </div>
</x-app-layout>
