<x-app-layout :title="$judul" :user="$user" :unread-count="$unreadCount" role="admin">
    @php
        $opsiRole = \App\Enums\UserRole::cases();
        $aksiTambah = route($routePrefix.'.create');
    @endphp

    <div class="space-y-6">
        <x-page-header
            :title="$judul"
            eyebrow="Manajemen admin"
            :description="$deskripsi"
        >
            <x-slot:actions>
                <a
                    href="{{ $aksiTambah }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="user" class="h-4 w-4" />
                    Tambah {{ strtolower($label) }}
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar {{ strtolower($label) }}">
            <form method="GET" action="{{ route($routePrefix.'.index') }}" role="search" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <label for="pencarian-pengguna" class="sr-only">Cari nama, email, NIM, atau NIDN</label>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                    <input
                        id="pencarian-pengguna"
                        type="search"
                        name="q"
                        value="{{ $filter['q'] }}"
                        placeholder="Cari nama, email, NIM, atau NIDN"
                        class="w-full rounded-xl border border-border bg-surface py-2.5 pl-9 pr-3 text-sm text-text placeholder:text-muted focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30"
                    >
                </div>

                @if ($roleTetap === null)
                    <div>
                        <label for="filter-role" class="sr-only">Filter role</label>
                        <select
                            id="filter-role"
                            name="role"
                            class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 sm:w-44"
                        >
                            <option value="">Semua role</option>
                            @foreach ($opsiRole as $opsi)
                                <option value="{{ $opsi->value }}" @selected($filter['role'] === $opsi)>{{ \App\Services\Admin\UserManagementService::labelRole($opsi) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="filter-status" class="sr-only">Filter status</label>
                    <select
                        id="filter-status"
                        name="status"
                        class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-tugas focus:outline-none focus:ring-2 focus:ring-agenda-tugas/30 sm:w-40"
                    >
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($filter['status'] === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filter['status'] === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-soft px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan
                </button>
            </form>

            <p class="mt-4 text-xs text-muted">
                Menampilkan {{ $pengguna->firstItem() ?? 0 }}–{{ $pengguna->lastItem() ?? 0 }} dari {{ $pengguna->total() }} akun.
            </p>

            @if ($pengguna->isEmpty())
                <div class="mt-4">
                    <x-empty-state
                        :title="$filter['q'] !== '' ? 'Tidak ada hasil pencarian' : 'Belum ada akun'"
                        :description="$filter['q'] !== '' ? 'Coba kata kunci atau filter lain.' : 'Tambahkan akun baru untuk mulai mengelola data pengguna.'"
                        icon="user"
                    />
                </div>
            @else
                <div class="mt-4 hidden overflow-x-auto rounded-xl border border-border md:block">
                    <table class="w-full min-w-[920px] border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-4 py-3">Nama &amp; email</th>
                                @if ($roleTetap === null)
                                    <th class="px-4 py-3">Role</th>
                                @endif
                                <th class="px-4 py-3">Identitas</th>
                                <th class="px-4 py-3">Program studi</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($pengguna as $item)
                                @php
                                    $adminLain = $item['role']->value === 'admin' && $item['id'] !== $user->getKey();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route($routePrefix.'.show', $item['id']) }}" class="font-semibold text-text hover:text-agenda-tugas hover:underline">
                                            {{ $item['name'] }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-muted">{{ $item['email'] }}</p>
                                    </td>
                                    @if ($roleTetap === null)
                                        <td class="px-4 py-3 text-xs text-muted">{{ $item['role_label'] }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-xs text-muted">
                                        {{ $item['nim'] ? 'NIM '.$item['nim'] : ($item['nidn'] ? 'NIDN '.$item['nidn'] : '-') }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">
                                        {{ $item['program_studi'] ?? '-' }}
                                        @if ($item['angkatan'])
                                            <span class="block text-[11px]">Angkatan {{ $item['angkatan'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :label="$item['status_label']" :tone="$item['is_active'] ? 'selesai' : 'netral'" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['created_at'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2 text-xs font-semibold">
                                            <a href="{{ route($routePrefix.'.show', $item['id']) }}" class="rounded-lg border border-border px-2.5 py-1.5 text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Detail</a>
                                            @unless ($adminLain)
                                                <a href="{{ route($routePrefix.'.edit', $item['id']) }}" class="rounded-lg border border-border px-2.5 py-1.5 text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Ubah</a>
                                            @endunless
                                            @if ($item['role'] !== \App\Enums\UserRole::Admin)
                                                <button
                                                    type="button"
                                                    class="rounded-lg border px-2.5 py-1.5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                                    :class="$item['is_active'] ? 'border-danger/40 text-danger hover:bg-danger/10' : 'border-success/40 text-success hover:bg-success/10'"
                                                    data-user-konfirmasi
                                                    data-user-action="{{ route($routePrefix.'.status', $item['id']) }}"
                                                    data-user-status="{{ $item['is_active'] ? '0' : '1' }}"
                                                    data-user-judul="{{ $item['is_active'] ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                                    data-user-pesan="{{ $item['is_active'] ? 'Akun akan dinonaktifkan. Data akademik tidak dihapus.' : 'Akun akan dapat digunakan kembali.' }}"
                                                    data-user-label="{{ $item['name'] }}"
                                                    data-user-submit="{{ $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                >
                                                    {{ $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($pengguna as $item)
                        @php
                            $adminLain = $item['role']->value === 'admin' && $item['id'] !== $user->getKey();
                        @endphp
                        <article class="rounded-xl border border-border bg-soft/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-text">{{ $item['name'] }}</p>
                                    <p class="truncate text-xs text-muted">{{ $item['email'] }}</p>
                                </div>
                                <x-status-badge :label="$item['status_label']" :tone="$item['is_active'] ? 'selesai' : 'netral'" />
                            </div>
                            <p class="mt-2 text-xs text-muted">
                                @if ($roleTetap === null){{ $item['role_label'] }} · @endif
                                {{ $item['nim'] ? 'NIM '.$item['nim'] : ($item['nidn'] ? 'NIDN '.$item['nidn'] : '-') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                                <a href="{{ route($routePrefix.'.show', $item['id']) }}" class="flex-1 rounded-lg border border-border bg-card px-2.5 py-2 text-center text-text">Detail</a>
                                @unless ($adminLain)
                                    <a href="{{ route($routePrefix.'.edit', $item['id']) }}" class="flex-1 rounded-lg border border-border bg-card px-2.5 py-2 text-center text-text">Ubah</a>
                                @endunless
                                @if ($item['role'] !== \App\Enums\UserRole::Admin)
                                    <button
                                        type="button"
                                        class="flex-1 rounded-lg border border-border bg-card px-2.5 py-2 text-text"
                                        data-user-konfirmasi
                                        data-user-action="{{ route($routePrefix.'.status', $item['id']) }}"
                                        data-user-status="{{ $item['is_active'] ? '0' : '1' }}"
                                        data-user-judul="{{ $item['is_active'] ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                        data-user-pesan="{{ $item['is_active'] ? 'Akun akan dinonaktifkan. Data akademik tidak dihapus.' : 'Akun akan dapat digunakan kembali.' }}"
                                        data-user-label="{{ $item['name'] }}"
                                        data-user-submit="{{ $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    >
                                        {{ $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($pengguna->hasPages())
                <nav class="mt-6" aria-label="Paginasi daftar {{ strtolower($label) }}">
                    {{ $pengguna->links() }}
                </nav>
            @endif
        </section>
    </div>

    @include('admin.pengguna._dialog')
</x-app-layout>
