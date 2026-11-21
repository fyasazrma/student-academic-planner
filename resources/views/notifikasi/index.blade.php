<x-app-layout title="Notifikasi" :user="$user" :unread-count="$unreadCount">
    @php
        $prefiks = $peran === 'admin' ? 'admin.notifikasi' : ($peran === 'dosen' ? 'dosen.notifikasi' : 'mahasiswa.notifikasi');
        $eyebrow = $peran === 'admin' ? 'Administrasi akademik' : ($peran === 'dosen' ? 'Akademik dosen' : 'Akademik mahasiswa');
    @endphp
    <x-page-header
        title="Notifikasi"
        :eyebrow="$eyebrow"
        description="Notifikasi akademik milik Anda. Tandai pesan yang sudah dibaca agar daftar tetap ringkas."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text">
                <x-icon name="bell" class="h-4 w-4 text-agenda-tugas" />
                {{ $unreadCount }} belum dibaca
            </span>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route($prefiks.'.baca-semua') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sidebar px-4 py-2.5 text-xs font-bold text-white transition hover:bg-sidebar-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas focus-visible:ring-offset-2">
                        <x-icon name="check" class="h-4 w-4" />
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div role="status" class="mt-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Filter status notifikasi">
        @foreach ($statusOptions as $key => $label)
            <a
                href="{{ route($prefiks, ['status' => $key]) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                    'border-sidebar bg-sidebar text-white' => $activeStatus === $key,
                    'border-border bg-card text-text hover:bg-soft' => $activeStatus !== $key,
                ])
                @if ($activeStatus === $key) aria-current="true" @endif
            >{{ $label }}</a>
        @endforeach
    </div>

    @if ($notifications->isEmpty())
        <div class="mt-5">
            <x-empty-state
                title="Belum ada notifikasi pada filter ini"
                description="Notifikasi yang relevan dengan peran Anda akan muncul di sini."
                icon="bell"
            />
        </div>
    @else
        <div class="mt-5 space-y-3">
            @foreach ($notifications as $notification)
                @php $link = $notification->tautanInternal(); @endphp
                <article class="rounded-card bg-card p-5 shadow-card">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->sudahDibaca() ? 'bg-border' : 'bg-agenda-tugas' }}"></span>
                                @if ($link !== null)
                                    <a
                                        href="{{ $link }}"
                                        class="text-sm font-bold text-text transition hover:text-agenda-tugas hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                    >{{ $notification->judul }}</a>
                                @else
                                    <h3 class="text-sm font-bold text-text">{{ $notification->judul }}</h3>
                                @endif
                                <x-status-badge
                                    :label="$notification->sudahDibaca() ? 'Sudah dibaca' : 'Belum dibaca'"
                                    :tone="$notification->sudahDibaca() ? 'netral' : 'tugas'"
                                />
                                <x-status-badge :label="\App\Enums\JenisNotifikasi::tryFrom((string) $notification->tipe)?->label() ?? $notification->tipe" tone="akademik" />
                            </div>
                            <p class="mt-2 text-sm leading-6 text-muted">{{ $notification->pesan }}</p>
                            <p class="mt-2 text-[11px] text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        @if (! $notification->sudahDibaca())
                            <form method="POST" action="{{ route($prefiks.'.baca', $notification) }}" class="shrink-0">
                                @csrf
                                <input type="hidden" name="status" value="{{ $activeStatus }}">
                                <button
                                    type="submit"
                                    class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($notifications->hasPages())
            <div class="mt-5 border-t border-border pt-4">{{ $notifications->links() }}</div>
        @endif
    @endif
</x-app-layout>
