<x-app-layout title="Riwayat Koreksi Absensi" :user="$user" :unread-count="$unreadCount" role="dosen">
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
            title="Riwayat Koreksi Absensi"
            eyebrow="{{ $kelas->kode_kelas }} — Pertemuan ke-{{ $sesi->pertemuan_ke }}"
            description="Setiap perubahan status menyimpan nilai lama, nilai baru, alasan, actor, dan waktu perubahan."
        >
            <x-slot:actions>
                <a href="{{ route('dosen.absensi.sesi.show', [$kelas->getKey(), $sesi->getKey()]) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke sesi
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($riwayat->isEmpty())
            <x-empty-state
                title="Belum ada koreksi"
                description="Riwayat akan tampil setelah dosen mengoreksi absensi pada sesi yang sudah ditutup."
                icon="clipboard"
            />
        @else
            <section aria-label="Riwayat koreksi (tabel)" class="hidden overflow-hidden rounded-2xl border border-border bg-card shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mahasiswa</th>
                                <th scope="col" class="px-4 py-3">Perubahan</th>
                                <th scope="col" class="px-4 py-3">Alasan</th>
                                <th scope="col" class="px-4 py-3">Dosen</th>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($riwayat as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item->pesertaMataKuliah?->mahasiswa?->name ?? '-' }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item->pesertaMataKuliah?->mahasiswa?->nim ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <x-status-badge :label="$item->status_sebelum ?? 'Kosong'" :tone="$tone($item->status_sebelum)" />
                                            <span class="text-xs text-muted">→</span>
                                            <x-status-badge :label="$item->status_sesudah" :tone="$tone($item->status_sesudah)" />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item->alasan }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item->actor?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ $item->created_at?->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Riwayat koreksi (kartu)" class="space-y-3 md:hidden">
                @foreach ($riwayat as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <p class="text-sm font-bold text-text">{{ $item->pesertaMataKuliah?->mahasiswa?->name ?? '-' }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <x-status-badge :label="$item->status_sebelum ?? 'Kosong'" :tone="$tone($item->status_sebelum)" />
                            <span class="text-xs text-muted">→</span>
                            <x-status-badge :label="$item->status_sesudah" :tone="$tone($item->status_sesudah)" />
                        </div>
                        <p class="mt-3 text-xs leading-5 text-text">{{ $item->alasan }}</p>
                        <p class="mt-2 text-[11px] text-muted">{{ $item->actor?->name ?? '-' }} · {{ $item->created_at?->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }}</p>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
