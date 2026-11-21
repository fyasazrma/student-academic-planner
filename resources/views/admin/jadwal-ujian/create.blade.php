<x-app-layout title="Tambah Jadwal Ujian" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Tambah Jadwal Ujian"
            eyebrow="Penjadwalan akademik"
            description="Tetapkan pengumuman UTS atau UAS sebagai agenda institusional untuk seluruh mahasiswa dan dosen."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.jadwal-ujian.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Formulir jadwal ujian">
            <form method="POST" action="{{ route('admin.jadwal-ujian.store') }}" data-auth-form>
                @csrf
                @include('admin.jadwal-ujian._form')

                <div class="mt-8 flex flex-wrap justify-end gap-2 border-t border-border pt-5">
                    <a
                        href="{{ route('admin.jadwal-ujian.index') }}"
                        class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        data-submit-button
                        class="rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <span data-submit-label>Simpan jadwal ujian</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>