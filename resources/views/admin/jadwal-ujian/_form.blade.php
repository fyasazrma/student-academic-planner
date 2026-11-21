@php
    $jenisUjianValue = old('jenis_ujian', $ujian?->jenis_ujian?->value);
    $tahunValue = old('tahun_akademik_id', $tahunValue ?? null);
    $semesterValue = old('semester', $semesterValue ?? null);
    $judulValue = old('judul', $judulValue ?? '');
    $tanggalMulaiValue = old('tanggal_mulai', $ujian?->tanggal_mulai?->toDateString());
    $tanggalSelesaiValue = old('tanggal_selesai', $ujian?->tanggal_selesai?->toDateString() ?? $tanggalMulaiValue);
    $waktuMulaiValue = old('waktu_mulai', $ujian?->waktu_mulai ? \Illuminate\Support\Str::substr((string) $ujian->waktu_mulai, 0, 5) : '');
    $waktuSelesaiValue = old('waktu_selesai', $ujian?->waktu_selesai ? \Illuminate\Support\Str::substr((string) $ujian->waktu_selesai, 0, 5) : '');
    $lokasiValue = old('lokasi', $ujian?->lokasi);
    $deskripsiValue = old('deskripsi', $ujian?->deskripsi);
    $dipublikasikanValue = old('dipublikasikan', $dipublikasikanValue ?? '1');
@endphp

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-agenda-terlambat/30 bg-agenda-terlambat/10 px-4 py-3 text-xs leading-5 text-text">
        <p class="font-bold">Periksa kembali formulir jadwal ujian.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div role="note" class="mt-5 rounded-xl border border-agenda-uts/30 bg-agenda-uts/10 px-4 py-3 text-xs leading-5 text-text">
    Jadwal ini adalah agenda akademik institusional. Berlaku untuk seluruh mahasiswa dan dosen, tanpa memilih mata kuliah atau kelas tertentu.
</div>

<div class="mt-5 grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <span class="block text-xs font-semibold text-text">Jenis ujian</span>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($jenisUjianOptions as $value => $label)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-sm text-text transition has-checked:border-agenda-uts has-checked:bg-agenda-uts/10">
                    <input
                        type="radio"
                        name="jenis_ujian"
                        value="{{ $value }}"
                        class="h-4 w-4 accent-agenda-uts"
                        @checked((string) $jenisUjianValue === (string) $value)
                        @required(true)
                    >
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('jenis_ujian')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tahun_akademik_id" class="block text-xs font-semibold text-text">Tahun akademik</label>
        <select
            id="tahun_akademik_id"
            name="tahun_akademik_id"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
            <option value="">Pilih tahun akademik</option>
            @foreach ($tahunOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $tahunValue === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('tahun_akademik_id')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="semester" class="block text-xs font-semibold text-text">Semester</label>
        <select
            id="semester"
            name="semester"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
            <option value="">Pilih semester</option>
            @foreach ($semesterOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $semesterValue === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('semester')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="judul" class="block text-xs font-semibold text-text">Judul pengumuman</label>
        <input
            id="judul"
            name="judul"
            type="text"
            maxlength="200"
            value="{{ $judulValue }}"
            placeholder="Contoh: Jadwal Ujian Tengah Semester (UTS) — Ganjil 2026/2027"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
        @error('judul')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">
            Gunakan judul yang sesuai dengan jenis ujian. Periode akan disertakan otomatis pada akhir judul.
        </p>
    </div>

    <div>
        <label for="tanggal_mulai" class="block text-xs font-semibold text-text">Tanggal mulai</label>
        <input
            id="tanggal_mulai"
            name="tanggal_mulai"
            type="date"
            value="{{ $tanggalMulaiValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
        @error('tanggal_mulai')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tanggal_selesai" class="block text-xs font-semibold text-text">Tanggal selesai</label>
        <input
            id="tanggal_selesai"
            name="tanggal_selesai"
            type="date"
            value="{{ $tanggalSelesaiValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
        @error('tanggal_selesai')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <span class="block text-xs font-semibold text-text">Waktu ujian (opsional)</span>
        <div class="mt-2 grid gap-3 sm:grid-cols-2">
            <div>
                <label for="waktu_mulai" class="block text-[11px] text-muted">Jam mulai</label>
                <input
                    id="waktu_mulai"
                    name="waktu_mulai"
                    type="time"
                    value="{{ $waktuMulaiValue }}"
                    class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
                >
                @error('waktu_mulai')
                    <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="waktu_selesai" class="block text-[11px] text-muted">Jam selesai</label>
                <input
                    id="waktu_selesai"
                    name="waktu_selesai"
                    type="time"
                    value="{{ $waktuSelesaiValue }}"
                    class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
                >
                @error('waktu_selesai')
                    <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <p class="mt-1.5 text-[11px] leading-4 text-muted">Isi kedua jam bila waktu ujian diketahui.</p>
    </div>

    <div class="sm:col-span-2">
        <label for="lokasi" class="block text-xs font-semibold text-text">Lokasi (opsional)</label>
        <input
            id="lokasi"
            name="lokasi"
            type="text"
            maxlength="150"
            value="{{ $lokasiValue }}"
            placeholder="Contoh: Aula Utama atau R. 3.12"
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
        >
        @error('lokasi')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">
            Ujian pada lokasi yang sama tidak boleh bertabrakan dengan jadwal ujian lain. Lokasi boleh kosong bila belum ditentukan.
        </p>
    </div>

    <div class="sm:col-span-2">
        <label for="deskripsi" class="block text-xs font-semibold text-text">Deskripsi / informasi tambahan (opsional)</label>
        <textarea
            id="deskripsi"
            name="deskripsi"
            rows="3"
            maxlength="1000"
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/25"
            placeholder="Informasi tambahan untuk mahasiswa dan dosen"
        >{{ $deskripsiValue }}</textarea>
        @error('deskripsi')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <input type="hidden" name="dipublikasikan" value="0">
        <label class="inline-flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card px-4 py-3 transition has-checked:border-agenda-uts has-checked:bg-agenda-uts/10">
            <input
                id="dipublikasikan"
                type="checkbox"
                name="dipublikasikan"
                value="1"
                class="mt-0.5 h-4 w-4 accent-agenda-uts"
                @checked((string) $dipublikasikanValue === '1')
            >
            <span>
                <span class="block text-xs font-semibold text-text">Publikasikan jadwal</span>
                <span class="mt-0.5 block text-[11px] leading-4 text-muted">Jadwal yang dipublikasikan tampil di kalender seluruh mahasiswa dan dosen.</span>
            </span>
        </label>
        @error('dipublikasikan')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>
</div>
