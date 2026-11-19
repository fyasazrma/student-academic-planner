<x-app-layout title="Riwayat Mata Kuliah" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        <x-page-header
            title="Riwayat Pengambilan"
            eyebrow="{{ $mata_kuliah?->kode ?? 'Mata kuliah' }} — {{ $mata_kuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            description="Bandingkan nilai dari setiap pengambilan mata kuliah ini. Satu nilai terbaik dipakai dalam IPK."
        >
            <x-slot:actions>
                <a
                    href="{{ route('mahasiswa.nilai') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar nilai
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($baris->isEmpty())
            <x-empty-state
                title="Belum ada riwayat pengambilan"
                description="Riwayat ini berisi mata kuliah yang benar-benar Anda ikuti."
                icon="book"
            />
        @else
            <section aria-label="Riwayat pengambilan (tabel)" class="hidden rounded-2xl border border-border bg-card p-5 shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Semester</th>
                                <th scope="col" class="px-4 py-3">UTS</th>
                                <th scope="col" class="px-4 py-3">UAS</th>
                                <th scope="col" class="px-4 py-3">Tugas</th>
                                <th scope="col" class="px-4 py-3">Akhir</th>
                                <th scope="col" class="px-4 py-3">Huruf / Bobot</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($baris as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['periode'] }}</p>
                                        @if ($item['peserta_id'] === $terbaik_peserta_id)
                                            <x-status-badge class="mt-1" label="Dihitung untuk IPK" tone="selesai" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['uts_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['uas_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['tugas_label'] }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-text">{{ $item['akhir_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">
                                        {{ $item['huruf'] ?? '-' }}
                                        @if ($item['bobot_mutu_label'] !== '-')
                                            <span class="text-muted">({{ $item['bobot_mutu_label'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :label="$item['status_label']" :tone="$item['status'] === 'lengkap' ? 'selesai' : 'peringatan'" />
                                        @if ($item['alasan_label'] !== '')
                                            <p class="mt-1 max-w-[14rem] text-[11px] leading-4 text-muted">{{ $item['alasan_label'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Riwayat pengambilan (kartu)" class="space-y-3 md:hidden">
                @foreach ($baris as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-text">{{ $item['periode'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['sks'] }} SKS</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$item['status'] === 'lengkap' ? 'selesai' : 'peringatan'" />
                        </div>
                        @if ($item['peserta_id'] === $terbaik_peserta_id)
                            <x-status-badge class="mt-3" label="Dihitung untuk IPK" tone="selesai" />
                        @endif
                        <div class="mt-4 grid grid-cols-2 gap-3 text-[11px] text-muted">
                            <p>UTS: <span class="font-semibold text-text">{{ $item['uts_label'] }}</span></p>
                            <p>UAS: <span class="font-semibold text-text">{{ $item['uas_label'] }}</span></p>
                            <p>Tugas: <span class="font-semibold text-text">{{ $item['tugas_label'] }}</span></p>
                            <p>Akhir: <span class="font-semibold text-text">{{ $item['akhir_label'] }}</span></p>
                            <p>Huruf: <span class="font-semibold text-text">{{ $item['huruf'] ?? '-' }}</span></p>
                            <p>Bobot: <span class="font-semibold text-text">{{ $item['bobot_mutu_label'] }}</span></p>
                        </div>
                        @if ($item['alasan_label'] !== '')
                            <p class="mt-3 text-[11px] leading-4 text-muted">{{ $item['alasan_label'] }}</p>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
