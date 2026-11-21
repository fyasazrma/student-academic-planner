@php
    $aksi = $mode === 'edit'
        ? route($routePrefix.'.update', $target->getKey())
        : route($routePrefix.'.store');
    $labelSimpan = $mode === 'edit' ? 'Simpan perubahan' : 'Simpan '.$label;
    $peranForm = $roleTetap?->value ?? old('role', $data['role']);
    $peranTampil = $roleTetap
        ? \App\Services\Admin\UserManagementService::labelRole($roleTetap)
        : ($target ? \App\Services\Admin\UserManagementService::labelRole($target->role) : '-');
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

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-semibold text-text">Nama lengkap <span class="text-danger">*</span></label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $data['name']) }}"
                required
                maxlength="150"
                autocomplete="name"
                @error('name') aria-invalid="true" aria-describedby="name_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('name') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('name')
                <p id="name_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="email" class="block text-sm font-semibold text-text">Email <span class="text-danger">*</span></label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $data['email']) }}"
                required
                maxlength="255"
                autocomplete="email"
                @error('email') aria-invalid="true" aria-describedby="email_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('email') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('email')
                <p id="email_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        @if ($roleTetap === null && $mode === 'create')
            <div class="sm:col-span-2">
                <label for="role" class="block text-sm font-semibold text-text">Role <span class="text-danger">*</span></label>
                <select
                    id="role"
                    name="role"
                    required
                    data-role-select
                    @error('role') aria-invalid="true" aria-describedby="role_error" @enderror
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('role') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                >
                    <option value="">Pilih role</option>
                    <option value="mahasiswa" @selected(old('role', $data['role']) === 'mahasiswa')>Mahasiswa</option>
                    <option value="dosen" @selected(old('role', $data['role']) === 'dosen')>Dosen</option>
                    <option value="admin" @selected(old('role', $data['role']) === 'admin')>Admin</option>
                </select>
                @error('role')
                    <p id="role_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="sm:col-span-2">
                <p class="text-sm font-semibold text-text">Role</p>
                <p class="mt-2 rounded-xl border border-border bg-soft px-3 py-2.5 text-sm text-muted">
                    {{ $peranTampil }}
                </p>
                <p class="mt-1 text-xs text-muted">Role tidak dapat diubah setelah akun dibuat.</p>
            </div>
        @endif

        <div data-role-field="mahasiswa" class="@if ($peranForm !== 'mahasiswa') hidden @endif">
            <label for="nim" class="block text-sm font-semibold text-text">NIM <span class="text-danger">*</span></label>
            <input
                id="nim"
                type="text"
                name="nim"
                value="{{ old('nim', $data['nim']) }}"
                maxlength="20"
                @error('nim') aria-invalid="true" aria-describedby="nim_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('nim') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('nim')
                <p id="nim_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div data-role-field="mahasiswa" class="@if ($peranForm !== 'mahasiswa') hidden @endif">
            <label for="angkatan" class="block text-sm font-semibold text-text">Angkatan <span class="text-danger">*</span></label>
            <input
                id="angkatan"
                type="number"
                name="angkatan"
                value="{{ old('angkatan', $data['angkatan']) }}"
                min="1900"
                max="2100"
                @error('angkatan') aria-invalid="true" aria-describedby="angkatan_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('angkatan') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('angkatan')
                <p id="angkatan_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div data-role-field="dosen" class="@if ($peranForm !== 'dosen') hidden @endif">
            <label for="nidn" class="block text-sm font-semibold text-text">NIDN <span class="text-danger">*</span></label>
            <input
                id="nidn"
                type="text"
                name="nidn"
                value="{{ old('nidn', $data['nidn']) }}"
                maxlength="20"
                @error('nidn') aria-invalid="true" aria-describedby="nidn_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('nidn') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('nidn')
                <p id="nidn_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="program_studi" class="block text-sm font-semibold text-text">Program studi</label>
            <input
                id="program_studi"
                type="text"
                name="program_studi"
                value="{{ old('program_studi', $data['program_studi']) }}"
                maxlength="100"
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
            >
        </div>

        <div>
            <label for="no_telepon" class="block text-sm font-semibold text-text">Nomor telepon</label>
            <input
                id="no_telepon"
                type="tel"
                name="no_telepon"
                value="{{ old('no_telepon', $data['no_telepon']) }}"
                maxlength="20"
                @error('no_telepon') aria-invalid="true" aria-describedby="no_telepon_error" @enderror
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('no_telepon') border-danger focus:border-danger focus:ring-danger/30 @enderror"
            >
            @error('no_telepon')
                <p id="no_telepon_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
            @enderror
        </div>

        @if ($mode === 'create')
            <div>
                <label for="password" class="block text-sm font-semibold text-text">Password <span class="text-danger">*</span></label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    @error('password') aria-invalid="true" aria-describedby="password_error" @enderror
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('password') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                >
                @error('password')
                    <p id="password_error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-text">Konfirmasi password <span class="text-danger">*</span></label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                >
            </div>
        @endif
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-end">
        <a
            href="{{ route($routePrefix.'.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
        >
            Batal
        </a>
        <button
            type="submit"
            data-submit-button
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
        >
            <x-icon name="check" class="h-4 w-4" />
            <span data-submit-label>{{ $labelSimpan }}</span>
        </button>
    </div>
</form>