@php
    $nilai = static fn (string $field, mixed $default = null): mixed => old($field, $target?->{$field} ?? $default);
    $tanggal = static function (string $field) use ($target): string {
        $old = old($field);

        if (is_string($old) && $old !== '') {
            return \Illuminate\Support\Carbon::parse($old)->format('Y-m-d');
        }

        return $target?->{$field}?->format('Y-m-d') ?? '';
    };
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="judul" class="mb-1.5 block text-xs font-semibold text-text">Judul target <span class="text-danger">*</span></label>
        <input
            id="judul"
            name="judul"
            type="text"
            maxlength="200"
            required
            value="{{ $nilai('judul') }}"
            class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition placeholder:text-muted focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            placeholder="Contoh: Menyelesaikan 10 latihan algoritma"
        >
        @error('judul') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="deskripsi" class="mb-1.5 block text-xs font-semibold text-text">Deskripsi</label>
        <textarea
            id="deskripsi"
            name="deskripsi"
            rows="3"
            maxlength="5000"
            class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition placeholder:text-muted focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            placeholder="Rincian target dan cara mengukur capaian"
        >{{ $nilai('deskripsi') }}</textarea>
        @error('deskripsi') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="mata_kuliah_id" class="mb-1.5 block text-xs font-semibold text-text">Mata kuliah (opsional)</label>
            <select
                id="mata_kuliah_id"
                name="mata_kuliah_id"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            >
                <option value="">Umum / tidak terkait mata kuliah</option>
                @foreach ($mataKuliah as $item)
                    <option value="{{ $item['id'] }}" @selected((string) $nilai('mata_kuliah_id') === (string) $item['id'])>
                        {{ $item['label'] }}
                    </option>
                @endforeach
            </select>
            @error('mata_kuliah_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="satuan" class="mb-1.5 block text-xs font-semibold text-text">Satuan capaian <span class="text-danger">*</span></label>
            <input
                id="satuan"
                name="satuan"
                type="text"
                maxlength="30"
                required
                value="{{ $nilai('satuan') }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
                placeholder="jam, materi, latihan, atau poin"
            >
            @error('satuan') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="target_nilai" class="mb-1.5 block text-xs font-semibold text-text">Nilai target <span class="text-danger">*</span></label>
            <input
                id="target_nilai"
                name="target_nilai"
                type="number"
                min="0.01"
                max="9999999999"
                step="0.01"
                required
                value="{{ $nilai('target_nilai') }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
                placeholder="10"
            >
            @error('target_nilai') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="capaian" class="mb-1.5 block text-xs font-semibold text-text">Capaian awal</label>
            <input
                id="capaian"
                name="capaian"
                type="number"
                min="0"
                max="9999999999"
                step="0.01"
                value="{{ $nilai('capaian', 0) }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            >
            @error('capaian') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="tanggal_mulai" class="mb-1.5 block text-xs font-semibold text-text">Tanggal mulai <span class="text-danger">*</span></label>
            <input
                id="tanggal_mulai"
                name="tanggal_mulai"
                type="date"
                required
                value="{{ $tanggal('tanggal_mulai') }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            >
            @error('tanggal_mulai') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="deadline" class="mb-1.5 block text-xs font-semibold text-text">Deadline <span class="text-danger">*</span></label>
            <input
                id="deadline"
                name="deadline"
                type="date"
                required
                value="{{ $tanggal('deadline') }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            >
            @error('deadline') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tanggal_target" class="mb-1.5 block text-xs font-semibold text-text">Tanggal penanda (opsional)</label>
            <input
                id="tanggal_target"
                name="tanggal_target"
                type="date"
                value="{{ $tanggal('tanggal_target') }}"
                class="w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-text outline-none transition focus:border-agenda-tugas focus:ring-2 focus:ring-agenda-tugas/20"
            >
            @error('tanggal_target') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-border pt-4">
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
        >
            <x-icon name="check" class="h-4 w-4" />
            {{ $submitLabel }}
        </button>
        <a
            href="{{ route('mahasiswa.target.index') }}"
            class="inline-flex items-center rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >
            Batal
        </a>
    </div>
</form>