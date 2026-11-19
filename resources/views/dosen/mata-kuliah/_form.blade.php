@php
    $aksi = $mode === 'edit'
        ? route('dosen.mata-kuliah.update', $kelas->id)
        : route('dosen.mata-kuliah.store');
    $labelSimpan = $mode === 'edit' ? 'Simpan perubahan' : 'Buat kelas';
@endphp

<form
    method="POST"
    action="{{ $aksi }}"
    class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
    data-auth-form
>
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-text">
            <p class="font-semibold">Periksa kembali formulir berikut:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Identitas kelas</p>
    <p class="mt-1 text-xs leading-5 text-muted">
        @if ($mode === 'edit')
            Mata kuliah, tahun akademik, dan kode kelas dikunci setelah kelas dibuat. Hanya nama kelas dan kapasitas yang dapat diubah.
        @else
            Pilih data induk yang aktif. Status pendaftaran awal selalu ditutup dan kunci gabung dibuat terpisah dari halaman detail.
        @endif
    </p>

    @if ($mode === 'edit')
        <div
            id="identitas-kelas-terkunci"
            role="status"
            class="mt-4 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-xs leading-5 text-text"
        >
            Identitas kelas tidak diubah agar riwayat peserta, tugas, dan jadwal tetap konsisten.
        </div>
    @endif

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        @if ($mode === 'create')
            <div>
                <label for="mata_kuliah_id" class="block text-sm font-semibold text-text">Mata kuliah <span class="text-danger">*</span></label>
                <select
                    id="mata_kuliah_id"
                    name="mata_kuliah_id"
                    required
                    @error('mata_kuliah_id') aria-invalid="true" aria-describedby="mata_kuliah_id_error" @enderror
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('mata_kuliah_id') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                >
                    <option value="">Pilih mata kuliah aktif</option>
                    @foreach ($mataKuliahOptions as $opsi)
                        <option value="{{ $opsi['id'] }}" @selected(old('mata_kuliah_id', $data['mata_kuliah_id']) == $opsi['id'])>{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
                @error('mata_kuliah_id')
                    <p id="mata_kuliah_id_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tahun_akademik_id" class="block text-sm font-semibold text-text">Tahun akademik <span class="text-danger">*</span></label>
                <select
                    id="tahun_akademik_id"
                    name="tahun_akademik_id"
                    required
                    @error('tahun_akademik_id') aria-invalid="true" aria-describedby="tahun_akademik_id_error" @enderror
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('tahun_akademik_id') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                >
                    <option value="">Pilih tahun akademik aktif</option>
                    @foreach ($tahunAkademikOptions as $opsi)
                        <option value="{{ $opsi['id'] }}" @selected(old('tahun_akademik_id', $data['tahun_akademik_id']) == $opsi['id'])>{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
                @error('tahun_akademik_id')
                    <p id="tahun_akademik_id_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="kode_kelas" class="block text-sm font-semibold text-text">Kode kelas <span class="text-danger">*</span></label>
                <input
                    id="kode_kelas"
                    type="text"
                    name="kode_kelas"
                    value="{{ old('kode_kelas', $data['kode_kelas']) }}"
                    maxlength="10"
                    required
                    autocomplete="off"
                    placeholder="Contoh: A01"
                    @error('kode_kelas') aria-invalid="true" aria-describedby="kode_kelas_error" @enderror
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('kode_kelas') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                >
                @error('kode_kelas')
                    <p id="kode_kelas_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div>
                <p class="block text-sm font-semibold text-text">Mata kuliah</p>
                <input
                    type="text"
                    value="{{ $detail['kode_mata_kuliah'] }} — {{ $detail['nama_mata_kuliah'] }}"
                    disabled
                    aria-describedby="identitas-kelas-terkunci"
                    class="mt-2 w-full cursor-not-allowed rounded-xl border border-border bg-soft px-3 py-2.5 text-sm text-muted"
                >
            </div>

            <div>
                <p class="block text-sm font-semibold text-text">Tahun akademik</p>
                <input
                    type="text"
                    value="{{ $detail['tahun_akademik'] }}"
                    disabled
                    aria-describedby="identitas-kelas-terkunci"
                    class="mt-2 w-full cursor-not-allowed rounded-xl border border-border bg-soft px-3 py-2.5 text-sm text-muted"
                >
            </div>

            <div class="sm:col-span-2">
                <p class="block text-sm font-semibold text-text">Kode kelas</p>
                <input
                    type="text"
                    value="{{ $detail['kode_kelas'] }}"
                    disabled
                    aria-describedby="identitas-kelas-terkunci"
                    class="mt-2 w-full cursor-not-allowed rounded-xl border border-border bg-soft px-3 py-2.5 text-sm text-muted"
                >
            </div>
        @endif

        <div>
            <label for="nama_kelas" class="block text-sm font-semibold text-text">Nama kelas</label>
            <input
                id="nama_kelas"
                type="text"
                name="nama_kelas"
                value="{{ old('nama_kelas', $data['nama_kelas']) }}"
                maxlength="100"
                autocomplete="off"
                placeholder="Contoh: Kelas A"
                @error('nama_kelas') aria-invalid="true" aria-describedby="nama_kelas_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('nama_kelas') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('nama_kelas')
                <p id="nama_kelas_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="kapasitas" class="block text-sm font-semibold text-text">Kapasitas</label>
            <input
                id="kapasitas"
                type="number"
                name="kapasitas"
                value="{{ old('kapasitas', $data['kapasitas']) }}"
                min="1"
                max="65535"
                inputmode="numeric"
                placeholder="Kosongkan untuk tanpa batas"
                @if ($mode === 'edit' && $detail['jumlah_peserta_aktif'] > 0) min="{{ max(1, $detail['jumlah_peserta_aktif']) }}" @endif
                @error('kapasitas') aria-invalid="true" aria-describedby="kapasitas_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('kapasitas') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            <p class="mt-2 text-[11px] leading-5 text-muted">
                @if ($mode === 'edit')
                    Saat ini {{ $detail['jumlah_peserta_aktif'] }} peserta aktif. Kapasitas tidak boleh lebih kecil dari jumlah tersebut.
                @else
                    Kosongkan kapasitas jika kelas tidak memiliki batas peserta.
                @endif
            </p>
            @error('kapasitas')
                <p id="kapasitas_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-border pt-5">
        <a
            href="{{ $mode === 'edit' ? route('dosen.mata-kuliah.show', $kelas->id) : route('dosen.mata-kuliah.index') }}"
            class="rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >
            Batal
        </a>
        <button
            type="submit"
            data-submit-button
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
        >
            <x-icon name="check" class="h-4 w-4" />
            <span data-submit-label>{{ $labelSimpan }}</span>
        </button>
    </div>
</form>
