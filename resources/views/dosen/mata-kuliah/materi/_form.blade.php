@php
    $sedangMengubah = $materi !== null;
    $judulValue = old('judul', $data['judul']);
    $deskripsiValue = old('deskripsi', $data['deskripsi']);
    $statusValue = old('status', $data['status']);
    $formatDiizinkan = '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip';
@endphp

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-xs leading-5 text-text">
        <p class="font-bold">Periksa kembali formulir materi.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Detail materi</p>
<p class="mt-1 text-xs leading-5 text-muted">
    Judul dan deskripsi membantu mahasiswa memahami isi materi sebelum mengunduhnya.
</p>

<div class="mt-5 grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-text" for="judul">Judul materi</label>
        <input
            id="judul"
            name="judul"
            type="text"
            value="{{ $judulValue }}"
            maxlength="200"
            required
            placeholder="Contoh: Modul 1 — Pengantar Sistem Informasi"
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('judul')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-text" for="deskripsi">Deskripsi (opsional)</label>
        <textarea
            id="deskripsi"
            name="deskripsi"
            rows="5"
            maxlength="5000"
            placeholder="Ringkas materi yang akan dipelajari mahasiswa."
            class="mt-2 w-full resize-y rounded-xl border border-border bg-card px-3 py-2.5 text-sm leading-6 text-text transition placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >{{ $deskripsiValue }}</textarea>
        @error('deskripsi')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">Maksimal 5000 karakter.</p>
    </div>
</div>

<div class="mt-8 border-t border-border pt-6">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Status publikasi</p>
    <p class="mt-1 text-xs leading-5 text-muted">
        Draft belum dapat dilihat mahasiswa. Materi yang dipublikasikan langsung tersedia pada kelas ini.
    </p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <label class="inline-flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card px-4 py-3 text-sm text-text transition has-checked:border-agenda-tugas has-checked:bg-agenda-tugas/10">
            <input
                type="radio"
                name="status"
                value="draft"
                class="mt-0.5 h-4 w-4 accent-agenda-tugas"
                @checked($statusValue === 'draft')
                @required(true)
            >
            <span>
                <span class="block font-semibold">Draft</span>
                <span class="mt-0.5 block text-[11px] leading-4 text-muted">Disimpan tetapi belum dipublikasikan.</span>
            </span>
        </label>
        <label class="inline-flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card px-4 py-3 text-sm text-text transition has-checked:border-agenda-tugas has-checked:bg-agenda-tugas/10">
            <input
                type="radio"
                name="status"
                value="dipublikasikan"
                class="mt-0.5 h-4 w-4 accent-agenda-tugas"
                @checked($statusValue === 'dipublikasikan')
                @required(true)
            >
            <span>
                <span class="block font-semibold">Dipublikasikan</span>
                <span class="mt-0.5 block text-[11px] leading-4 text-muted">Langsung tersedia untuk dilihat mahasiswa.</span>
            </span>
        </label>
    </div>
    @error('status')
        <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror

    @if ($sedangMengubah)
        <p class="mt-3 text-xs leading-5 text-muted">
            Status saat ini: <span class="font-semibold text-text">{{ $data['status'] === 'dipublikasikan' ? 'Dipublikasikan' : 'Draft' }}</span>.
            {{ $data['dipublikasikan_label'] }}.
        </p>
    @endif
</div>

<div class="mt-8 border-t border-border pt-6">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Berkas materi</p>

    @if ($sedangMengubah && $data['file_name'])
        <div class="mt-3 rounded-xl border border-border bg-soft p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">File tersimpan</p>
            <p class="mt-1 break-all text-sm font-semibold text-text">{{ $data['file_name'] }}</p>
            <p class="mt-1 text-xs text-muted">{{ $data['file_size_label'] ?? '-' }} · Dipertahankan jika berkas tidak diganti.</p>
        </div>
    @endif

    <label class="mt-4 block text-xs font-semibold text-text" for="file">
        {{ $sedangMengubah ? 'Ganti file (opsional)' : 'Upload file' }}
    </label>
    <input
        id="file"
        name="file"
        type="file"
        accept="{{ $formatDiizinkan }}"
        @required(! $sedangMengubah)
        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition file:mr-3 file:rounded-lg file:border-0 file:bg-sidebar file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
    >
    @error('file')
        <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
    <p class="mt-2 text-[11px] leading-5 text-muted">
        Ukuran maksimal 10 MB (10240 KB). Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP.
    </p>
    @if ($sedangMengubah)
        <p class="mt-1 text-[11px] leading-5 text-muted">Biarkan kosong jika file lama tetap digunakan.</p>
    @endif
</div>
