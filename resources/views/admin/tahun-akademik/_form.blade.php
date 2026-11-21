@php
    $aksi = $mode === 'edit'
        ? route('admin.tahun-akademik.update', $tahunAkademikId)
        : route('admin.tahun-akademik.store');
    $labelSimpan = $mode === 'edit' ? 'Simpan perubahan' : 'Simpan tahun akademik';
    $identitasTerkunci = $mode === 'edit' && ($memilikiKelas ?? false);
@endphp

<form
    method="POST"
    action="{{ $aksi }}"
    class="rounded-card bg-card p-5 shadow-card sm:p-6"
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

    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-agenda-tugas">Periode akademik</p>
    <p class="mt-1 text-xs leading-5 text-muted">
        Status aktif tidak diatur di formulir ini. Periode baru dibuat nonaktif; gunakan aksi status pada daftar tahun akademik.
    </p>

    @if ($identitasTerkunci)
        <div
            id="identitas_terkunci"
            role="status"
            class="mt-4 rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-xs leading-5 text-text"
        >
            Tahun akademik ini sudah memiliki kelas. Tahun mulai, tahun selesai, dan semester tidak dapat diubah. Tanggal periode tetap dapat diperbarui.
        </div>
    @endif

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="tahun_mulai" class="block text-sm font-semibold text-text">Tahun mulai <span class="text-danger">*</span></label>
            <input
                id="tahun_mulai"
                type="number"
                name="tahun_mulai"
                value="{{ old('tahun_mulai', $data['tahun_mulai']) }}"
                min="2000"
                max="2200"
                required
                inputmode="numeric"
                placeholder="Contoh: 2026"
                @if ($identitasTerkunci) readonly aria-describedby="identitas_terkunci" @endif
                @error('tahun_mulai') aria-invalid="true" aria-describedby="tahun_mulai_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('tahun_mulai') border-danger focus:border-danger focus:ring-danger/30 @enderror @if ($identitasTerkunci) read-only:bg-soft @endif"
            >
            @error('tahun_mulai')
                <p id="tahun_mulai_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tahun_selesai" class="block text-sm font-semibold text-text">Tahun selesai <span class="text-danger">*</span></label>
            <input
                id="tahun_selesai"
                type="number"
                name="tahun_selesai"
                value="{{ old('tahun_selesai', $data['tahun_selesai']) }}"
                min="2000"
                max="2200"
                required
                inputmode="numeric"
                placeholder="Contoh: 2027"
                @if ($identitasTerkunci) readonly aria-describedby="identitas_terkunci" @endif
                @error('tahun_selesai') aria-invalid="true" aria-describedby="tahun_selesai_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('tahun_selesai') border-danger focus:border-danger focus:ring-danger/30 @enderror @if ($identitasTerkunci) read-only:bg-soft @endif"
            >
            @error('tahun_selesai')
                <p id="tahun_selesai_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="semester" class="block text-sm font-semibold text-text">Semester <span class="text-danger">*</span></label>
            <select
                id="semester"
                name="semester"
                required
                @if ($identitasTerkunci) disabled aria-describedby="identitas_terkunci" @endif
                @error('semester') aria-invalid="true" aria-describedby="semester_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('semester') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
                <option value="">Pilih semester</option>
                @foreach (\App\Enums\Semester::cases() as $opsiSemester)
                    <option value="{{ $opsiSemester->value }}" @selected(old('semester', $data['semester']) === $opsiSemester->value)>{{ ucfirst($opsiSemester->value) }}</option>
                @endforeach
            </select>
            @if ($identitasTerkunci)
                <input type="hidden" name="semester" value="{{ old('semester', $data['semester']) }}">
            @endif
            @error('semester')
                <p id="semester_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <p class="text-xs leading-5 text-muted">
                Rentang tanggal boleh diperbarui, termasuk pada periode yang sudah memiliki kelas.
            </p>
        </div>

        <div>
            <label for="tanggal_mulai" class="block text-sm font-semibold text-text">Tanggal mulai <span class="text-danger">*</span></label>
            <input
                id="tanggal_mulai"
                type="date"
                name="tanggal_mulai"
                value="{{ old('tanggal_mulai', $data['tanggal_mulai']) }}"
                required
                @error('tanggal_mulai') aria-invalid="true" aria-describedby="tanggal_mulai_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('tanggal_mulai') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('tanggal_mulai')
                <p id="tanggal_mulai_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tanggal_selesai" class="block text-sm font-semibold text-text">Tanggal selesai <span class="text-danger">*</span></label>
            <input
                id="tanggal_selesai"
                type="date"
                name="tanggal_selesai"
                value="{{ old('tanggal_selesai', $data['tanggal_selesai']) }}"
                required
                @error('tanggal_selesai') aria-invalid="true" aria-describedby="tanggal_selesai_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('tanggal_selesai') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('tanggal_selesai')
                <p id="tanggal_selesai_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-border pt-5">
        <a
            href="{{ route('admin.tahun-akademik.index') }}"
            class="rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >
            Batal
        </a>
        <button
            type="submit"
            data-submit-button
            class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
        >
            <x-icon name="check" class="h-4 w-4" />
            <span data-submit-label>{{ $labelSimpan }}</span>
        </button>
    </div>
</form>