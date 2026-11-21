<x-app-layout title="Nilai Akademik" :user="$user" :unread-count="$unreadCount" role="mahasiswa">
    <div class="space-y-6">
        <x-page-header
            title="Nilai Akademik"
            eyebrow="Penilaian"
            description="Lihat nilai UTS, UAS, tugas, nilai akhir, huruf dan bobot mutu, serta IP dan IPK Anda."
        />

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Ringkasan indeks prestasi">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Indeks prestasi</p>
                    <p class="mt-2 text-xs leading-5 text-muted">
                        IP dihitung dari nilai pengambilan pada semester dipilih. IPK memakai nilai terbaik per mata kuliah
                        dari seluruh riwayat, sehingga SKS tidak dihitung dua kali.
                    </p>
                </div>
                @if ($dipilih !== null)
                    <x-status-badge :label="$dipilih->label()" tone="akademik" />
                @endif
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-stat-card
                    label="IP semester ini"
                    :value="$ip ?? '-'"
                    :hint="$dipilih !== null ? 'Semester '.$dipilih->label().' · '.$total_sks.' SKS' : 'Belum ada data'"
                    icon="chart"
                    tone="tugas"
                />
                <x-stat-card
                    label="IPK"
                    :value="$ipk ?? '-'"
                    hint="Rata-rata tertimbang nilai terbaik per mata kuliah"
                    icon="repeat"
                    tone="akademik"
                />
            </div>

            @if ($dipilih !== null)
                <p class="mt-4 text-[11px] text-muted">
                    {{ $jumlah_lengkap }} mata kuliah memiliki nilai lengkap pada semester ini.
                </p>
            @endif
        </section>

        @if ($periode->count() > 1)
            <section class="rounded-card bg-card p-4 shadow-card sm:p-5" aria-label="Filter semester">
                <form method="GET" action="{{ route('mahasiswa.nilai') }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1">
                        <label for="tahun_akademik_id" class="block text-[11px] font-semibold text-muted">Semester</label>
                        <select
                            id="tahun_akademik_id"
                            name="tahun_akademik_id"
                            class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            @foreach ($periode as $item)
                                <option
                                    value="{{ $item->getKey() }}"
                                    @selected($dipilih?->getKey() === $item->getKey())
                                >{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button
                        type="submit"
                        class="rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-text transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Tampilkan
                    </button>
                </form>
            </section>
        @endif

        @if ($baris->isEmpty())
            <x-empty-state
                title="Belum ada nilai yang ditampilkan"
                description="Nilai muncul setelah dosen menginput UTS dan UAS pada kelas yang Anda ikuti dan komponen tugas sudah ditentukan."
                icon="chart"
            />
        @else
            <section aria-label="Daftar nilai (tabel)" class="hidden rounded-card bg-card p-5 shadow-card lg:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah</th>
                                <th scope="col" class="px-4 py-3">Semester</th>
                                <th scope="col" class="px-4 py-3">UTS</th>
                                <th scope="col" class="px-4 py-3">UAS</th>
                                <th scope="col" class="px-4 py-3">Tugas</th>
                                <th scope="col" class="px-4 py-3">Akhir</th>
                                <th scope="col" class="px-4 py-3">Huruf / Bobot</th>
                                <th scope="col" class="px-4 py-3 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($baris as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['nama'] }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item['kode'] }} · {{ $item['sks'] }} SKS</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['periode'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['uts_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['uas_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['tugas_label'] }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-text">{{ $item['akhir_label'] }}</td>
                                    <td class="px-4 py-3 text-xs text-text">
                                        {{ $item['huruf'] ?? '-' }}
                                        @if ($item['bobot_mutu_label'] !== '-')
                                            <span class="text-muted">({{ $item['bobot_mutu_label'] }})</span>
                                        @endif
                                        <x-status-badge :label="$item['status_label']" :tone="$item['status'] === 'lengkap' ? 'selesai' : 'peringatan'" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <a
                                                href="{{ $item['detail_url'] }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Riwayat
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Daftar nilai (kartu)" class="space-y-3 lg:hidden">
                @foreach ($baris as $item)
                    <article class="rounded-card bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['nama'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['kode'] }} · {{ $item['sks'] }} SKS</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['periode'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$item['status'] === 'lengkap' ? 'selesai' : 'peringatan'" />
                        </div>
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
                        <a
                            href="{{ $item['detail_url'] }}"
                            class="mt-4 block rounded-xl border border-border bg-soft px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Lihat riwayat pengambilan
                        </a>
                    </article>
                @endforeach
            </section>
        @endif

        <section class="rounded-card bg-card p-5 shadow-card sm:p-6" aria-label="Konversi nilai">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Konversi nilai</p>
            <div class="mt-4 grid gap-2 sm:grid-cols-5">
                @foreach ([['A', '80–100', '4.00'], ['B', '70–<80', '3.00'], ['C', '60–<70', '2.00'], ['D', '50–<60', '1.00'], ['E', '0–<50', '0.00']] as $skala)
                    <div class="rounded-xl border border-border bg-soft px-3 py-2 text-center">
                        <p class="text-sm font-bold text-text">{{ $skala[0] }}</p>
                        <p class="mt-1 text-[10px] text-muted">{{ $skala[1] }}</p>
                        <p class="mt-1 text-xs font-semibold text-text">{{ $skala[2] }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-[11px] leading-5 text-muted">
                Nilai akhir dibulatkan dua angka desimal. Nilai E tetap dihitung dalam denominator IP/IPK.
            </p>
        </section>
    </div>
</x-app-layout>
