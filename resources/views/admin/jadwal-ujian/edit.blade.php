<x-app-layout title="Ubah Jadwal Ujian" :user="$user" :unread-count="$unreadCount" role="admin">
    <div class="space-y-6">
        <x-page-header
            title="Ubah Jadwal Ujian"
            eyebrow="Penjadwalan akademik"
            description="Perbarui pengumuman UTS atau UAS institusional tanpa mengubah data kelas, peserta, atau nilai."
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

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Formulir jadwal ujian">
            <form method="POST" action="{{ route('admin.jadwal-ujian.update', $ujian->getKey()) }}" data-auth-form>
                @csrf
                @method('PUT')
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
                        class="rounded-full bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        <span data-submit-label>Simpan perubahan</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>