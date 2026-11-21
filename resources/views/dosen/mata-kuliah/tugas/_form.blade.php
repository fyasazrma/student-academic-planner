@php
    $sedangMengubah = $tugas !== null;
    $judulValue = old('judul', $data['judul']);
    $deskripsiValue = old('deskripsi', $data['deskripsi']);
    $statusValue = old('status', $data['status']);
    $publikasiValue = old('dipublikasikan_pada', $data['dipublikasikan_pada']);
    $deadlineValue = old('deadline', $data['deadline']);
    $nilaiValue = old('nilai_maksimal', $data['nilai_maksimal']);
    $terlambatValue = old('izinkan_terlambat', $data['izinkan_terlambat']);
    $formatDiizinkan = '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip';
@endphp

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-xs leading-5 text-text">
        <p class="font-bold">Periksa kembali formulir tugas.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Detail tugas</p>
<p class="mt-1 text-xs leading-5 text-muted">
    Mahasiswa melihat tugas hanya setelah tanggal publikasi dan dapat mengumpulkan hingga deadline.
</p>

<div class="mt-5 grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-text" for="judul">Judul tugas</label>
        <input
            id="judul"
            name="judul"
            type="text"
            value="{{ $judulValue }}"
            maxlength="200"
            required
            placeholder="Contoh: Tugas 1 — Analisis Sistem"
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
            rows="4"
            maxlength="5000"
            placeholder="Jelaskan instruksi, format jawaban, dan kriteria tugas."
            class="mt-2 w-full resize-y rounded-xl border border-border bg-card px-3 py-2.5 text-sm leading-6 text-text transition placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >{{ $deskripsiValue }}</textarea>
        @error('deskripsi')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">Maksimal 5000 karakter.</p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-text" for="deadline">Deadline</label>
        <input
            id="deadline"
            name="deadline"
            type="datetime-local"
            value="{{ $deadlineValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('deadline')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-semibold text-text" for="nilai_maksimal">Nilai maksimal</label>
        <input
            id="nilai_maksimal"
            name="nilai_maksimal"
            type="number"
            min="0"
            max="100"
            step="0.01"
            value="{{ $nilaiValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('nilai_maksimal')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">Rentang 0–100.</p>
    </div>
</div>

<div class="mt-8 border-t border-border pt-6">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Publikasi dan keterlambatan</p>
    <p class="mt-1 text-xs leading-5 text-muted">
        Draft belum dapat dilihat mahasiswa dan tidak muncul pada kalender mahasiswa.
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
                <span class="mt-0.5 block text-[11px] leading-4 text-muted">Disimpan tetapi belum dapat dilihat mahasiswa.</span>
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
                <span class="mt-0.5 block text-[11px] leading-4 text-muted">Terjadwal sesuai tanggal publikasi yang ditentukan.</span>
            </span>
        </label>
    </div>
    @error('status')
        <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror

    <div class="mt-5">
        <label class="block text-xs font-semibold text-text" for="dipublikasikan_pada">Tanggal publikasi</label>
        <input
            id="dipublikasikan_pada"
            name="dipublikasikan_pada"
            type="datetime-local"
            value="{{ $publikasiValue }}"
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('dipublikasikan_pada')
            <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">Wajib diisi saat dipublikasikan dan tidak boleh setelah deadline.</p>
    </div>

    <label class="mt-5 inline-flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card px-4 py-3 text-sm text-text transition has-checked:border-agenda-tugas has-checked:bg-agenda-tugas/10">
        <input
            type="checkbox"
            name="izinkan_terlambat"
            value="1"
            class="mt-0.5 h-4 w-4 accent-agenda-tugas"
            @checked((bool) $terlambatValue)
        >
        <span>
            <span class="block font-semibold">Izinkan pengumpulan terlambat</span>
            <span class="mt-0.5 block text-[11px] leading-4 text-muted">Pengumpulan setelah deadline tetap diterima dan ditandai terlambat.</span>
        </span>
    </label>
    @error('izinkan_terlambat')
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
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Lampiran (opsional)</p>

    @if ($sedangMengubah && $data['lampiran_nama'])
        <div class="mt-3 rounded-xl border border-border bg-soft p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">Lampiran tersimpan</p>
            <p class="mt-1 break-all text-sm font-semibold text-text">{{ $data['lampiran_nama'] }}</p>
            <p class="mt-1 text-xs text-muted">{{ $data['lampiran_size_label'] ?? '-' }} · Dipertahankan jika berkas tidak diganti.</p>
        </div>
    @endif

    <label class="mt-4 block text-xs font-semibold text-text" for="lampiran">
        {{ $sedangMengubah ? 'Ganti lampiran (opsional)' : 'Unggah lampiran (opsional)' }}
    </label>
    <input
        id="lampiran"
        name="lampiran"
        type="file"
        accept="{{ $formatDiizinkan }}"
        class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition file:mr-3 file:rounded-lg file:border-0 file:bg-sidebar file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
    >
    @error('lampiran')
        <p role="alert" aria-live="assertive" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
    <p class="mt-2 text-[11px] leading-5 text-muted">
        Ukuran maksimal 10 MB (10240 KB). Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP.
    </p>
    @if ($sedangMengubah)
        <p class="mt-1 text-[11px] leading-5 text-muted">Biarkan kosong jika lampiran lama tetap digunakan.</p>
        @if ($data['lampiran_nama'])
            <label class="mt-4 inline-flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card px-4 py-3 text-sm text-text transition has-checked:border-danger has-checked:bg-danger/10">
                <input
                    type="checkbox"
                    name="hapus_lampiran"
                    value="1"
                    class="mt-0.5 h-4 w-4 accent-agenda-terlambat"
                    @checked(old('hapus_lampiran', false))
                >
                <span>
                    <span class="block font-semibold">Hapus lampiran yang tersimpan</span>
                    <span class="mt-0.5 block text-[11px] leading-4 text-muted">Berkas lama dihapus permanen saat perubahan disimpan.</span>
                </span>
            </label>
        @endif
    @endif
</div>
