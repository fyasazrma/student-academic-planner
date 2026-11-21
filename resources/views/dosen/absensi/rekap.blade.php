<x-app-layout title="Rekap Kehadiran Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Rekap Kehadiran"
            eyebrow="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            :description="'Berdasarkan '.$total_sesi_ditutup.' sesi yang sudah ditutup. Persentase = hadir / sesi ditutup × 100%. Peserta aktif dan mengulang dihitung.'"
        >
            <x-slot:actions>
                <a href="{{ route('dosen.absensi.kelas', $kelas->getKey()) }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Daftar sesi
                </a>
            </x-slot:actions>
        </x-page-header>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="Ringkasan rekap">
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Peserta</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $jumlah_peserta }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Sesi ditutup</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $total_sesi_ditutup }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Hadir</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $hadir }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Izin</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $izin }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Sakit</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $sakit }}</p>
            </div>
            <div class="rounded-card bg-card p-4 shadow-card">
                <p class="text-[11px] font-semibold text-muted">Alfa</p>
                <p class="mt-2 text-xl font-bold text-text">{{ $alfa }}</p>
            </div>
        </section>

        @if ($baris->isEmpty())
            <x-empty-state
                title="Belum ada peserta yang masuk rekap"
                description="Rekap menampilkan peserta berstatus aktif atau mengulang pada kelas ini."
                icon="user"
            />
        @else
            <section aria-label="Rekap per peserta (tabel)" class="hidden overflow-hidden rounded-card bg-card shadow-card md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mahasiswa</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Hadir</th>
                                <th scope="col" class="px-4 py-3">Izin</th>
                                <th scope="col" class="px-4 py-3">Sakit</th>
                                <th scope="col" class="px-4 py-3">Alfa</th>
                                <th scope="col" class="px-4 py-3">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($baris as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['nama'] }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item['nim'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['status_peserta_label'] }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['hadir'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['izin'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['sakit'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['alfa'] }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :label="$item['persentase_label']" :tone="$item['persentase'] >= 75 ? 'hadir' : ($item['persentase'] >= 50 ? 'peringatan' : 'alfa')" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Rekap per peserta (kartu)" class="space-y-3 md:hidden">
                @foreach ($baris as $item)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['nama'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['nim'] }} · {{ $item['status_peserta_label'] }}</p>
                            </div>
                            <x-status-badge :label="$item['persentase_label']" :tone="$item['persentase'] >= 75 ? 'hadir' : ($item['persentase'] >= 50 ? 'peringatan' : 'alfa')" />
                        </div>
                        <p class="mt-3 text-[11px] text-muted">
                            Hadir {{ $item['hadir'] }} · Izin {{ $item['izin'] }} · Sakit {{ $item['sakit'] }} · Alfa {{ $item['alfa'] }}
                        </p>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
