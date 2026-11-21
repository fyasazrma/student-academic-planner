<x-app-layout :title="'Detail '.$detail['name']" :user="$user" :unread-count="$unreadCount" role="admin">
    @php
        $identitas = $detail['nim'] ? 'NIM '.$detail['nim'] : ($detail['nidn'] ? 'NIDN '.$detail['nidn'] : '-');
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header
            :title="$detail['name']"
            eyebrow="Detail akun"
            :description="$detail['email'].' · '.$detail['role_label']"
        >
            <x-slot:actions>
                <a
                    href="{{ route($routePrefix.'.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali
                </a>
                @if (! ($detail['role']->value === 'admin' && $detail['id'] !== $user->getKey()))
                    <a
                        href="{{ route($routePrefix.'.edit', $detail['id']) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        Ubah data
                    </a>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">{{ session('error') }}</div>
        @endif

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center gap-3">
                <h3 class="text-base font-bold text-text">Informasi akun</h3>
                <x-status-badge :label="$detail['status_label']" :tone="$detail['is_active'] ? 'selesai' : 'netral'" />
            </div>
            <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nama</dt>
                    <dd class="mt-1 text-sm font-semibold text-text">{{ $detail['name'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Email</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['email'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Role</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['role_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Dibuat</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['created_at'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-text">Informasi profil</h3>
            <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Identitas</dt>
                    <dd class="mt-1 text-sm text-text">{{ $identitas }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Program studi</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['program_studi'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Angkatan</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['angkatan'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nomor telepon</dt>
                    <dd class="mt-1 text-sm text-text">{{ $detail['no_telepon'] ?? '-' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-bold text-text">Ringkasan keterkaitan akademik</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card label="Kelas diajar" :value="$detail['jumlah_kelas_diajar']" icon="book" tone="kuliah" />
                <x-stat-card label="Kelas diikuti" :value="$detail['jumlah_pendaftaran']" icon="book" tone="tugas" />
                <x-stat-card label="Materi dibuat" :value="$detail['jumlah_materi']" icon="file" tone="pengumuman" />
                <x-stat-card label="Tugas dibuat" :value="$detail['jumlah_tugas']" icon="clipboard" tone="deadline" />
            </div>
            <p class="mt-4 text-xs leading-5 text-muted">
                Penonaktifan akun tidak menghapus data akademik yang tercatat pada akun ini.
            </p>
        </section>
    </div>
</x-app-layout>