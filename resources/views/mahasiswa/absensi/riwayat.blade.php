<x-app-layout title="Riwayat Absensi" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    @php
        $tone = fn (string $status): string => match ($status) {
            'hadir' => 'hadir',
            'izin' => 'izin',
            'sakit' => 'sakit',
            'alfa' => 'alfa',
            default => 'netral',
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            title="Riwayat Absensi"
            eyebrow="Kehadiran mahasiswa"
            description="Seluruh catatan absensi Anda, termasuk yang dibuat otomatis saat sesi ditutup atau dikoreksi dosen."
        >
            <x-slot:actions>
                <a href="{{ route('mahasiswa.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke absensi
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($riwayat->isEmpty())
            <x-empty-state
                title="Belum ada riwayat absensi"
                description="Catatan kehadiran akan tampil setelah Anda mengisi absensi atau dosen menutup sesi."
                icon="clipboard"
            />
        @else
            <section aria-label="Riwayat absensi (tabel)" class="hidden overflow-hidden rounded-card bg-card shadow-card md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah</th>
                                <th scope="col" class="px-4 py-3">Pertemuan</th>
                                <th scope="col" class="px-4 py-3">Tanggal</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Metode</th>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($riwayat as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['mata_kuliah'] }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item['kode_kelas'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">Ke-{{ $item['pertemuan_ke'] }} · {{ $item['topik'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['tanggal_label'] }}</td>
                                    <td class="px-4 py-3"><x-status-badge :label="$item['status_label']" :tone="$tone($item['status'])" /></td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['metode_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item['dicatat_pada_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Riwayat absensi (kartu)" class="space-y-3 md:hidden">
                @foreach ($riwayat as $item)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['mata_kuliah'] }}</p>
                                <p class="mt-1 text-xs text-muted">Pertemuan ke-{{ $item['pertemuan_ke'] }} · {{ $item['tanggal_label'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$tone($item['status'])" />
                        </div>
                        <p class="mt-3 text-xs text-text">{{ $item['topik'] }}</p>
                        <p class="mt-2 text-[11px] text-muted">{{ $item['metode_label'] }} · {{ $item['dicatat_pada_label'] }}</p>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
