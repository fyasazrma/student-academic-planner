@push('modals')
    <dialog
        id="dialog-konfirmasi-jadwal"
        class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-border bg-card p-0 text-text shadow-xl backdrop:bg-sidebar/50"
        aria-labelledby="dialog-konfirmasi-jadwal-judul"
    >
        <form id="form-konfirmasi-jadwal" method="POST" action="" class="p-5 sm:p-6" data-auth-form>
            @csrf
            <input type="hidden" name="_method" value="DELETE" data-jadwal-method-input>

            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-agenda-tugas">Konfirmasi jadwal</p>
                    <h3 id="dialog-konfirmasi-jadwal-judul" data-jadwal-dialog-judul class="mt-1 text-lg font-bold text-text">Konfirmasi aksi</h3>
                </div>
                <button
                    type="button"
                    data-jadwal-dialog-close
                    class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    aria-label="Tutup dialog konfirmasi"
                >
                    <x-icon name="close" class="h-4 w-4" />
                </button>
            </div>

            <p data-jadwal-dialog-pesan class="mt-3 text-sm leading-6 text-muted">
                Konfirmasi aksi pada jadwal terpilih.
            </p>
            <p data-jadwal-dialog-label class="mt-3 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-text">
                Jadwal terpilih
            </p>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    data-jadwal-dialog-close
                    class="rounded-xl border border-border bg-card px-4 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    data-submit-button
                    class="rounded-xl bg-agenda-tugas px-4 py-2 text-xs font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <span data-jadwal-dialog-submit data-submit-label>Hapus</span>
                </button>
            </div>
        </form>
    </dialog>
@endpush
