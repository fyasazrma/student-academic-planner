<x-app-layout title="Detail Sesi Absensi" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $tone = fn (?string $status): string => match ($status) {
            'hadir' => 'hadir',
            'izin' => 'izin',
            'sakit' => 'sakit',
            'alfa' => 'alfa',
            default => 'netral',
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            title="Pertemuan ke-{{ $sesi->pertemuan_ke }} — {{ $sesi->topik ?: 'Topik belum diisi' }}"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah' }}"
            :description="$sesi->tanggal?->translatedFormat('l, d F Y').' · '.$waktu_label.' · '.($sesi->status?->value === 'dibuka' ? 'Sesi masih dibuka.' : 'Sesi sudah ditutup; koreksi tersedia.')"
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dosen.absensi.kelas', $kelas->getKey()) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                        <x-icon name="chevron-left" class="h-4 w-4" />
                        Daftar sesi
                    </a>
                    <a href="{{ $riwayat_url }}" class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Riwayat koreksi</a>
                    <a href="{{ $edit_url }}" class="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Ubah sesi</a>
                    @if ($sesi->status?->value === 'dibuka')
                        <form method="POST" action="{{ route('dosen.absensi.sesi.tutup', [$kelas->getKey(), $sesi->getKey()]) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-bold text-text transition hover:bg-danger/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Tutup sesi</button>
                        </form>
                    @endif
                </div>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">{{ session('error') }}</div>
        @endif

        <section class="flex flex-wrap items-center gap-3 rounded-card bg-card p-5 shadow-card">
            <x-status-badge :label="$sesi->status?->value === 'dibuka' ? 'Dibuka' : 'Ditutup'" :tone="$sesi->status?->value === 'dibuka' ? 'dibuka' : 'ditutup'" />
            <p class="text-xs text-muted">
                Waktu absensi mahasiswa mengikuti rentang sesi ini dalam zona waktu Asia/Jakarta.
            </p>
        </section>

        @if ($peserta->isEmpty())
            <x-empty-state
                title="Belum ada peserta pada kelas ini"
                description="Daftar peserta dan status kehadiran akan tampil setelah mahasiswa terdaftar."
                icon="user"
            />
        @else
            <section aria-label="Daftar peserta (tabel)" class="hidden overflow-hidden rounded-card bg-card shadow-card md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mahasiswa</th>
                                <th scope="col" class="px-4 py-3">Status peserta</th>
                                <th scope="col" class="px-4 py-3">Absensi</th>
                                <th scope="col" class="px-4 py-3">Metode</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($peserta as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['nama'] }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item['nim'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['status_peserta_label'] }}</td>
                                    <td class="px-4 py-3"><x-status-badge :label="$item['status_label']" :tone="$tone($item['status'])" /></td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['metode_label'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($item['boleh_dikoreksi'])
                                            <a href="{{ $item['koreksi_url'] }}" class="rounded-lg border border-border bg-soft px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Koreksi</a>
                                        @else
                                            <span class="text-xs text-muted">Sesi belum ditutup</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Daftar peserta (kartu)" class="space-y-3 md:hidden">
                @foreach ($peserta as $item)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['nama'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['nim'] }} · {{ $item['status_peserta_label'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$tone($item['status'])" />
                        </div>
                        @if ($item['boleh_dikoreksi'])
                            <a href="{{ $item['koreksi_url'] }}" class="mt-3 block rounded-xl border border-border bg-soft px-3 py-2 text-center text-xs font-semibold text-text">Koreksi absensi</a>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
