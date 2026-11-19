@push('modals')
    <dialog
        id="dialog-konfirmasi-pengguna"
        class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-border bg-card p-0 text-text shadow-xl"
        aria-labelledby="judul-konfirmasi-pengguna"
    >
        <div class="p-5 sm:p-6">
            <h3 id="judul-konfirmasi-pengguna" data-user-dialog-judul class="text-lg font-bold">Konfirmasi aksi</h3>
            <p data-user-dialog-pesan class="mt-2 text-sm leading-6 text-muted">Konfirmasi perubahan status akun.</p>
            <p data-user-dialog-label class="mt-2 text-sm font-semibold text-text"></p>
        </div>
        <form id="form-konfirmasi-pengguna" method="POST" class="flex items-center justify-end gap-2 border-t border-border bg-soft px-5 py-4">
            @csrf
            <input type="hidden" name="aktif" data-user-status-input value="1">
            <button
                type="button"
                data-user-dialog-close
                class="rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
            >
                Batal
            </button>
            <button
                type="submit"
                data-user-dialog-submit
                class="rounded-xl bg-agenda-tugas px-4 py-2 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
            >
                Konfirmasi
            </button>
        </form>
    </dialog>
@endpush
