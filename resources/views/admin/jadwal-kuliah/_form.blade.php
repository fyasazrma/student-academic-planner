@php
    $kelasValue = old('kelas_mata_kuliah_id', $jadwal?->kelas_mata_kuliah_id);
    $hariValue = old('hari', $jadwal?->hari);
    $jamMulaiValue = old('jam_mulai', $jadwal?->jam_mulai ? \Illuminate\Support\Str::substr((string) $jadwal->jam_mulai, 0, 5) : '');
    $jamSelesaiValue = old('jam_selesai', $jadwal?->jam_selesai ? \Illuminate\Support\Str::substr((string) $jadwal->jam_selesai, 0, 5) : '');
    $ruangValue = old('ruang', $jadwal?->ruang);
    $jenisValue = old('jenis', $jadwal?->jenis?->value ?? 'kuliah');
@endphp

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-xl border border-agenda-terlambat/30 bg-agenda-terlambat/10 px-4 py-3 text-xs leading-5 text-text">
        <p class="font-bold">Periksa kembali formulir jadwal.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-5 grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="kelas_mata_kuliah_id" class="block text-xs font-semibold text-text">Kelas mata kuliah</label>
        <select
            id="kelas_mata_kuliah_id"
            name="kelas_mata_kuliah_id"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
            <option value="">Pilih kelas</option>
            @foreach ($kelasOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $kelasValue === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('kelas_mata_kuliah_id')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">
            Dosen pengampu mengikuti dosen yang ditugaskan pada kelas ini dan tidak dapat diubah dari formulir.
        </p>
    </div>

    <div>
        <label for="hari" class="block text-xs font-semibold text-text">Hari</label>
        <select
            id="hari"
            name="hari"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
            <option value="">Pilih hari</option>
            @foreach ($hariOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $hariValue === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('hari')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <span class="block text-xs font-semibold text-text">Jenis jadwal</span>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($jenisOptions as $value => $label)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-sm text-text transition has-checked:border-agenda-tugas has-checked:bg-agenda-tugas/10">
                    <input
                        type="radio"
                        name="jenis"
                        value="{{ $value }}"
                        class="h-4 w-4 accent-agenda-tugas"
                        @checked($jenisValue === $value)
                        @required(true)
                    >
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('jenis')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="jam_mulai" class="block text-xs font-semibold text-text">Jam mulai</label>
        <input
            id="jam_mulai"
            name="jam_mulai"
            type="time"
            value="{{ $jamMulaiValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('jam_mulai')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="jam_selesai" class="block text-xs font-semibold text-text">Jam selesai</label>
        <input
            id="jam_selesai"
            name="jam_selesai"
            type="time"
            value="{{ $jamSelesaiValue }}"
            required
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('jam_selesai')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="ruang" class="block text-xs font-semibold text-text">Ruangan (opsional)</label>
        <input
            id="ruang"
            name="ruang"
            type="text"
            maxlength="50"
            value="{{ $ruangValue }}"
            placeholder="Contoh: R. 3.12"
            class="mt-2 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text transition placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/25"
        >
        @error('ruang')
            <p class="mt-1.5 text-xs text-agenda-terlambat">{{ $message }}</p>
        @enderror
        <p class="mt-1.5 text-[11px] leading-4 text-muted">
            Ruangan yang sama tidak boleh dipakai dua jadwal pada hari dan jam bertabrakan.
        </p>
    </div>
</div>
