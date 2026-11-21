<x-app-layout title="Riwayat Perubahan Nilai" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $labelKomponen = fn (string $komponen): string => match ($komponen) {
            'uts' => 'UTS',
            'uas' => 'UAS',
            'konfigurasi_tugas' => 'Konfigurasi tugas',
            default => $komponen,
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            title="Riwayat Perubahan Nilai"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Catatan perubahan nilai UTS/UAS dan konfigurasi komponen tugas {{ $peserta->mahasiswa?->name ?? 'mahasiswa' }} (NIM {{ $peserta->mahasiswa?->nim ?? '-' }})."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.nilai.kelas', $kelas->getKey()) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke rekap nilai
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($riwayat->isEmpty())
            <x-empty-state
                title="Belum ada perubahan yang tercatat"
                description="Setiap perubahan nilai UTS/UAS dan konfigurasi komponen tugas akan muncul di sini."
                icon="clock"
            />
        @else
            <section aria-label="Riwayat perubahan nilai (tabel)" class="hidden rounded-2xl border border-border bg-card p-5 shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                                <th scope="col" class="px-4 py-3">Komponen</th>
                                <th scope="col" class="px-4 py-3">Sebelum</th>
                                <th scope="col" class="px-4 py-3">Sesudah</th>
                                <th scope="col" class="px-4 py-3">Oleh</th>
                                <th scope="col" class="px-4 py-3">Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($riwayat as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3 text-xs text-text">{{ $item->created_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :label="$labelKomponen($item->komponen)" tone="tugas" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item->nilai_sebelum ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item->nilai_sesudah ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item->actor?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item->alasan ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Riwayat perubahan nilai (kartu)" class="space-y-3 md:hidden">
                @foreach ($riwayat as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-text">{{ $labelKomponen($item->komponen) }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item->created_at?->format('d M Y H:i') ?? '-' }}</p>
                            </div>
                            <x-status-badge :label="$labelKomponen($item->komponen)" tone="tugas" />
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-[11px] text-muted">
                            <p>Sebelum: <span class="font-semibold text-text">{{ $item->nilai_sebelum ?? '-' }}</span></p>
                            <p>Sesudah: <span class="font-semibold text-text">{{ $item->nilai_sesudah ?? '-' }}</span></p>
                            <p>Oleh: <span class="font-semibold text-text">{{ $item->actor?->name ?? '-' }}</span></p>
                            <p>Alasan: <span class="font-semibold text-text">{{ $item->alasan ?: '-' }}</span></p>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
