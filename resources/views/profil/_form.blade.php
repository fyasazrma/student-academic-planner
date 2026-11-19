<x-app-layout title="Edit Profil" :user="$user" :unread-count="$unreadCount" :role="$profil['role']->value">
    @php
        $adaErrorProfil = $errors->has('name') || $errors->has('email') || $errors->has('no_telepon');
        $adaErrorPassword = $errors->has('current_password') || $errors->has('password');
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            title="Edit Profil"
            eyebrow="Akun"
            description="Perbarui data pribadi yang diizinkan dan ubah password Anda. Role, identitas akademik, dan status akun tidak dapat diubah di sini."
        >
            <x-slot:actions>
                <a href="{{ route($routePrefix) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">{{ session('error') }}</div>
        @endif

        <form
            method="POST"
            action="{{ route($routePrefix.'.update') }}"
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
            data-auth-form
        >
            @csrf
            @method('PUT')

            <h3 class="text-base font-bold text-text">Data pribadi</h3>

            @if ($adaErrorProfil)
                <div role="alert" class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-text">
                    <p class="font-semibold">Periksa kembali data pribadi Anda.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                        @foreach ($errors->only(['name', 'email', 'no_telepon']) as $pesan)
                            @foreach ((array) $pesan as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="profil-name" class="block text-sm font-semibold text-text">Nama lengkap <span class="text-danger">*</span></label>
                    <input
                        id="profil-name"
                        type="text"
                        name="name"
                        value="{{ old('name', $profil['name']) }}"
                        required
                        maxlength="150"
                        autocomplete="name"
                        @error('name') aria-invalid="true" aria-describedby="profil-name-error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('name') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('name')
                        <p id="profil-name-error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="profil-email" class="block text-sm font-semibold text-text">Email <span class="text-danger">*</span></label>
                    <input
                        id="profil-email"
                        type="email"
                        name="email"
                        value="{{ old('email', $profil['email']) }}"
                        required
                        maxlength="255"
                        autocomplete="email"
                        @error('email') aria-invalid="true" aria-describedby="profil-email-error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('email') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('email')
                        <p id="profil-email-error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="profil-telepon" class="block text-sm font-semibold text-text">Nomor telepon</label>
                    <input
                        id="profil-telepon"
                        type="tel"
                        name="no_telepon"
                        value="{{ old('no_telepon', $profil['no_telepon']) }}"
                        maxlength="20"
                        @error('no_telepon') aria-invalid="true" aria-describedby="profil-telepon-error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('no_telepon') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('no_telepon')
                        <p id="profil-telepon-error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-sm font-semibold text-text">Identitas akademik</p>
                    <p class="mt-2 rounded-xl border border-border bg-soft px-3 py-2.5 text-sm text-muted">
                        {{ $profil['nim'] ? 'NIM '.$profil['nim'] : 'NIDN '.$profil['nidn'] }}
                    </p>
                    <p class="mt-1 text-xs text-muted">Hubungi admin jika data ini perlu diperbaiki.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-border pt-5">
                <button
                    type="submit"
                    data-submit-button
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    <span data-submit-label>Simpan perubahan</span>
                </button>
            </div>
        </form>

        <form
            method="POST"
            action="{{ route($routePrefix.'.password') }}"
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
            data-auth-form
        >
            @csrf

            <h3 class="text-base font-bold text-text">Ubah password</h3>
            <p class="mt-1 text-xs leading-5 text-muted">Masukkan password saat ini untuk mengonfirmasi perubahan.</p>

            @if ($adaErrorPassword)
                <div role="alert" class="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-text">
                    <p class="font-semibold">Periksa kembali password Anda.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                        @foreach ($errors->only(['current_password', 'password']) as $pesan)
                            @foreach ((array) $pesan as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="current-password" class="block text-sm font-semibold text-text">Password saat ini <span class="text-danger">*</span></label>
                    <input
                        id="current-password"
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('current_password') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('current_password')
                        <p id="current-password-error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password-baru" class="block text-sm font-semibold text-text">Password baru <span class="text-danger">*</span></label>
                    <input
                        id="password-baru"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        @error('password') aria-invalid="true" aria-describedby="password-baru-error" @enderror
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 @error('password') border-danger focus:border-danger focus:ring-danger/30 @enderror"
                    >
                    @error('password')
                        <p id="password-baru-error" role="alert" class="mt-2 text-xs font-semibold text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password-baru-konfirmasi" class="block text-sm font-semibold text-text">Konfirmasi password baru <span class="text-danger">*</span></label>
                    <input
                        id="password-baru-konfirmasi"
                        type="password"
                        name="password_confirmation"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                    >
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-border pt-5">
                <button
                    type="submit"
                    data-submit-button
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    <span data-submit-label>Ubah password</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
