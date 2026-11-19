<x-app-layout title="Profil Saya" :user="$user" :unread-count="$unreadCount" :role="$profil['role']->value">
    @php
        $identitas = $profil['nim'] ? 'NIM '.$profil['nim'] : ($profil['nidn'] ? 'NIDN '.$profil['nidn'] : '-');
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header
            title="Profil Saya"
            eyebrow="Akun"
            description="Informasi akun dan data akademik Anda. Perubahan identitas akademik menjadi kewenangan admin."
        >
            <x-slot:actions>
                <a
                    href="{{ route($routePrefix.'.edit') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-agenda-tugas px-4 py-2.5 text-sm font-bold text-white transition hover:bg-agenda-tugas/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2"
                >
                    <x-icon name="user" class="h-4 w-4" />
                    Edit profil
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-agenda-tugas/15 text-2xl font-bold text-agenda-tugas">
                    {{ strtoupper(mb_substr($profil['name'], 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h3 class="truncate text-xl font-bold text-text">{{ $profil['name'] }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $profil['email'] }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-status-badge :label="$peranLabel" tone="tugas" />
                        <x-status-badge label="Akun aktif" tone="selesai" />
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-text">Informasi akun</h3>
            <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nama lengkap</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $profil['name'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Email</dt>
                    <dd class="mt-1 text-sm text-text">{{ $profil['email'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nomor telepon</dt>
                    <dd class="mt-1 text-sm text-text">{{ $profil['no_telepon'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Email terverifikasi</dt>
                    <dd class="mt-1 text-sm text-text">{{ $profil['email_verified_at'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-text">Informasi akademik</h3>
            <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Identitas</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $identitas }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Program studi</dt>
                    <dd class="mt-1 text-sm text-text">{{ $profil['program_studi'] ?? '-' }}</dd>
                </div>
                @if ($profil['nim'])
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Angkatan</dt>
                        <dd class="mt-1 text-sm text-text">{{ $profil['angkatan'] ?? '-' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Akun dibuat</dt>
                    <dd class="mt-1 text-sm text-text">{{ $profil['created_at'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-text">Ringkasan akademik</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @if ($profil['nim'])
                    <x-stat-card label="Kelas diikuti" :value="$profil['jumlah_kelas_diikuti']" icon="book" tone="tugas" />
                @else
                    <x-stat-card label="Kelas diajar" :value="$profil['jumlah_kelas_diajar']" icon="book" tone="kuliah" />
                    <x-stat-card label="Materi dibuat" :value="$profil['jumlah_materi']" icon="file" tone="pengumuman" />
                    <x-stat-card label="Tugas dibuat" :value="$profil['jumlah_tugas']" icon="clipboard" tone="deadline" />
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
