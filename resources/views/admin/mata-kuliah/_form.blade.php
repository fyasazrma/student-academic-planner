@php
    $aksi = $mode === 'edit'
        ? route('admin.mata-kuliah.update', $mataKuliahId)
        : route('admin.mata-kuliah.store');
    $labelSimpan = $mode === 'edit' ? 'Simpan perubahan' : 'Simpan mata kuliah';
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

    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Data induk</p>
    <p class="mt-1 text-xs leading-5 text-muted">
        Status aktif tidak diatur di formulir ini. Gunakan aksi status pada daftar mata kuliah.
    </p>

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="kode" class="block text-sm font-semibold text-text">Kode mata kuliah <span class="text-danger">*</span></label>
            <input
                id="kode"
                type="text"
                name="kode"
                value="{{ old('kode', $data['kode']) }}"
                required
                maxlength="20"
                autocomplete="off"
                spellcheck="false"
                placeholder="Contoh: IF101"
                @error('kode') aria-invalid="true" aria-describedby="kode_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('kode') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('kode')
                <p id="kode_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nama" class="block text-sm font-semibold text-text">Nama mata kuliah <span class="text-danger">*</span></label>
            <input
                id="nama"
                type="text"
                name="nama"
                value="{{ old('nama', $data['nama']) }}"
                required
                maxlength="150"
                autocomplete="off"
                placeholder="Contoh: Pemrograman Web"
                @error('nama') aria-invalid="true" aria-describedby="nama_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('nama') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('nama')
                <p id="nama_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="sks" class="block text-sm font-semibold text-text">SKS <span class="text-danger">*</span></label>
            <select
                id="sks"
                name="sks"
                required
                @error('sks') aria-invalid="true" aria-describedby="sks_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('sks') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
                <option value="">Pilih SKS</option>
                @for ($sksOption = 1; $sksOption <= 6; $sksOption++)
                    <option value="{{ $sksOption }}" @selected((string) old('sks', $data['sks']) === (string) $sksOption)>{{ $sksOption }} SKS</option>
                @endfor
            </select>
            @error('sks')
                <p id="sks_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="semester" class="block text-sm font-semibold text-text">Semester kurikuler</label>
            <select
                id="semester"
                name="semester"
                @error('semester') aria-invalid="true" aria-describedby="semester_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('semester') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
                <option value="">Tidak ditentukan</option>
                @for ($semesterOption = 1; $semesterOption <= 8; $semesterOption++)
                    <option value="{{ $semesterOption }}" @selected(old('semester', $data['semester']) !== null && (int) old('semester', $data['semester']) === $semesterOption)>Semester {{ $semesterOption }}</option>
                @endfor
            </select>
            @error('semester')
                <p id="semester_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="program_studi" class="block text-sm font-semibold text-text">Program studi</label>
            <input
                id="program_studi"
                type="text"
                name="program_studi"
                value="{{ old('program_studi', $data['program_studi']) }}"
                maxlength="100"
                autocomplete="off"
                placeholder="Contoh: Informatika"
                @error('program_studi') aria-invalid="true" aria-describedby="program_studi_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('program_studi') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('program_studi')
                <p id="program_studi_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="deskripsi" class="block text-sm font-semibold text-text">Deskripsi</label>
            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="4"
                maxlength="1000"
                placeholder="Ringkasan materi dan tujuan mata kuliah"
                @error('deskripsi') aria-invalid="true" aria-describedby="deskripsi_error" @enderror
                class="mt-2 w-full resize-y rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('deskripsi') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >{{ old('deskripsi', $data['deskripsi']) }}</textarea>
            @error('deskripsi')
                <p id="deskripsi_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-border pt-5">
        <a
            href="{{ route('admin.mata-kuliah.index') }}"
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
