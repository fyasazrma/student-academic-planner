<x-app-layout title="Tambah Jadwal Kuliah" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Tambah Jadwal Kuliah"
            eyebrow="Penjadwalan akademik"
            description="Tetapkan hari, jam, jenis, dan ruangan untuk sebuah kelas mata kuliah."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.jadwal-kuliah.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Formulir jadwal kuliah">
            <form method="POST" action="{{ route('admin.jadwal-kuliah.store') }}" data-auth-form>
                @csrf
                @include('admin.jadwal-kuliah._form')

                <div class="mt-8 flex flex-wrap justify-end gap-2 border-t border-border pt-5">
                    <a
                        href="{{ route('admin.jadwal-kuliah.index') }}"
                        class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        data-submit-button
                        class="rounded-xl bg-agenda-tugas px-4 py-2.5 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                    >
                        <span data-submit-label>Simpan jadwal</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
