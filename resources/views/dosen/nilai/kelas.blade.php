<x-app-layout title="Rekap Nilai Kelas" :user="$user" :unread-count="$unreadCount" role="dosen">
    @php
        $statusTugas = $kelas->gunakanTugas();
        $labelTugas = match ($statusTugas) {
            true => 'Tugas digunakan',
            false => 'Tugas tidak digunakan',
            null => 'Belum ditentukan',
        };
    @endphp

    <div class="space-y-6">
        <x-page-header
            title="{{ $kelas->kode_kelas }} — {{ $kelas->mataKuliah?->nama ?? 'Mata kuliah tidak tersedia' }}"
            eyebrow="Rekap nilai"
            description="Input nilai UTS dan UAS, tentukan komponen tugas, lalu pantau nilai akhir tiap mahasiswa."
        >
            <x-slot:actions>
                <a
                    href="{{ route('dosen.nilai.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                >
                    <x-icon name="chevron-left" class="h-4 w-4" />
                    Kembali ke daftar kelas
                </a>
            </x-slot:actions>
        </x-page-header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-text">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-text">
                <p class="font-semibold">Periksa kembali input Anda.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Konfigurasi komponen tugas">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Komponen nilai</p>
                    <h3 class="mt-2 text-lg font-bold text-text">Penggunaan tugas</h3>
                    <p class="mt-2 max-w-2xl text-xs leading-5 text-muted">
                        UTS bobot 35%, UAS bobot 55%. Jika tugas digunakan, tugas bobot 10% dan diambil sebagai rata-rata
                        pengumpulan yang sudah dinilai. Jika tidak, UTS/UAS dinormalisasi dengan rasio 35/90 dan 55/90.
                    </p>
                </div>
                <x-status-badge :label="$labelTugas" :tone="$statusTugas === null ? 'peringatan' : 'selesai'" />
            </div>

            <form method="POST" action="{{ route('dosen.nilai.konfigurasi', $kelas->getKey()) }}" class="mt-5 space-y-3">
                @csrf
                <div>
                    <label for="alasan_konfigurasi" class="block text-[11px] font-semibold text-muted">
                        Alasan perubahan (opsional, tercatat di riwayat)
                    </label>
                    <input
                        id="alasan_konfigurasi"
                        type="text"
                        name="alasan"
                        maxlength="255"
                        placeholder="Misalnya: komponen tugas tidak dipakai semester ini"
                        class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-text placeholder:text-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                    >
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="submit"
                        name="gunakan_tugas"
                        value="1"
                        @class([
                            'rounded-xl px-4 py-2 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                            'bg-primary text-primary-text' => $statusTugas === true,
                            'border border-border bg-soft text-text hover:bg-card' => $statusTugas !== true,
                        ])
                    >
                        Gunakan tugas (10%)
                    </button>
                    <button
                        type="submit"
                        name="gunakan_tugas"
                        value="0"
                        @class([
                            'rounded-xl px-4 py-2 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                            'bg-primary text-primary-text' => $statusTugas === false,
                            'border border-border bg-soft text-text hover:bg-card' => $statusTugas !== false,
                        ])
                    >
                        Tanpa tugas (35/90 + 55/90)
                    </button>
                </div>
            </form>
        </section>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Mahasiswa ditampilkan" :value="$total" hint="Peserta aktif dan arsip yang masih punya nilai" icon="user" tone="kuliah" />
            <x-stat-card label="Nilai lengkap" :value="$lengkap" hint="UTS, UAS, dan tugas (jika dipakai) tersedia" icon="check" tone="tugas" />
            <x-stat-card label="Belum lengkap" :value="$belum_lengkap" hint="Nilai akhir belum dihitung" icon="info" tone="pengumuman" />
        </div>

        @if ($baris->isEmpty())
            <x-empty-state
                title="Belum ada mahasiswa yang ditampilkan"
                description="Daftar ini memuat peserta aktif dan arsip yang memiliki catatan nilai atau pengumpulan."
                icon="user"
            />
        @else
            <section aria-label="Rekap nilai (tabel)" class="hidden rounded-2xl border border-border bg-card p-5 shadow-sm lg:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mahasiswa</th>
                                <th scope="col" class="px-4 py-3">UTS</th>
                                <th scope="col" class="px-4 py-3">UAS</th>
                                <th scope="col" class="px-4 py-3">Tugas</th>
                                <th scope="col" class="px-4 py-3">Akhir</th>
                                <th scope="col" class="px-4 py-3">Huruf / Bobot</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">Riwayat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($baris as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['nama'] }}</p>
                                        <p class="mt-1 text-xs text-muted">NIM {{ $item['nim'] }}</p>
                                        @if ($item['arsip'])
                                            <x-status-badge :label="$item['status_peserta']" tone="peringatan" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('dosen.nilai.simpan', [$kelas->getKey(), $item['peserta_id'], 'uts']) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            <input
                                                type="number"
                                                name="nilai"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value="{{ $item['uts_label'] === '-' ? '' : $item['uts_label'] }}"
                                                placeholder="-"
                                                aria-label="Nilai UTS {{ $item['nama'] }}"
                                                class="w-16 rounded-lg border border-border bg-card px-2 py-1.5 text-xs text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                            <input
                                                type="text"
                                                name="alasan"
                                                maxlength="255"
                                                placeholder="Alasan"
                                                aria-label="Alasan perubahan UTS {{ $item['nama'] }}"
                                                class="w-24 rounded-lg border border-border bg-card px-2 py-1.5 text-[11px] text-text placeholder:text-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                            <button type="submit" class="rounded-lg border border-border bg-soft px-2 py-1.5 text-[11px] font-semibold text-text transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Simpan</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('dosen.nilai.simpan', [$kelas->getKey(), $item['peserta_id'], 'uas']) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            <input
                                                type="number"
                                                name="nilai"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value="{{ $item['uas_label'] === '-' ? '' : $item['uas_label'] }}"
                                                placeholder="-"
                                                aria-label="Nilai UAS {{ $item['nama'] }}"
                                                class="w-16 rounded-lg border border-border bg-card px-2 py-1.5 text-xs text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                            <input
                                                type="text"
                                                name="alasan"
                                                maxlength="255"
                                                placeholder="Alasan"
                                                aria-label="Alasan perubahan UAS {{ $item['nama'] }}"
                                                class="w-24 rounded-lg border border-border bg-card px-2 py-1.5 text-[11px] text-text placeholder:text-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                            <button type="submit" class="rounded-lg border border-border bg-soft px-2 py-1.5 text-[11px] font-semibold text-text transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Simpan</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['tugas_label'] }}</td>
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
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <a
                                                href="{{ $item['riwayat_url'] }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Lihat
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Rekap nilai (kartu)" class="space-y-3 lg:hidden">
                @foreach ($baris as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['nama'] }}</p>
                                <p class="mt-1 text-xs text-muted">NIM {{ $item['nim'] }}</p>
                            </div>
                            <x-status-badge :label="$item['status_label']" :tone="$item['status'] === 'lengkap' ? 'selesai' : 'peringatan'" />
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-[11px] text-muted">
                            <p>Tugas: <span class="font-semibold text-text">{{ $item['tugas_label'] }}</span></p>
                            <p>Akhir: <span class="font-semibold text-text">{{ $item['akhir_label'] }}</span></p>
                            <p>Huruf: <span class="font-semibold text-text">{{ $item['huruf'] ?? '-' }}</span></p>
                            <p>Bobot: <span class="font-semibold text-text">{{ $item['bobot_mutu_label'] }}</span></p>
                        </div>

                        @if ($item['alasan_label'] !== '')
                            <p class="mt-3 text-[11px] leading-4 text-muted">{{ $item['alasan_label'] }}</p>
                        @endif

                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <form method="POST" action="{{ route('dosen.nilai.simpan', [$kelas->getKey(), $item['peserta_id'], 'uts']) }}" class="flex flex-wrap items-center gap-1.5">
                                @csrf
                                <label class="text-[11px] font-semibold text-muted" for="uts_{{ $item['peserta_id'] }}">UTS</label>
                                <input
                                    id="uts_{{ $item['peserta_id'] }}"
                                    type="number"
                                    name="nilai"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="{{ $item['uts_label'] === '-' ? '' : $item['uts_label'] }}"
                                    placeholder="-"
                                    class="w-20 rounded-lg border border-border bg-card px-2 py-1.5 text-xs text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                <input
                                    type="text"
                                    name="alasan"
                                    maxlength="255"
                                    placeholder="Alasan"
                                    aria-label="Alasan perubahan UTS {{ $item['nama'] }}"
                                    class="w-full rounded-lg border border-border bg-card px-2 py-1.5 text-[11px] text-text placeholder:text-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas sm:w-24"
                                >
                                <button type="submit" class="rounded-lg border border-border bg-soft px-2 py-1.5 text-[11px] font-semibold text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Simpan</button>
                            </form>
                            <form method="POST" action="{{ route('dosen.nilai.simpan', [$kelas->getKey(), $item['peserta_id'], 'uas']) }}" class="flex flex-wrap items-center gap-1.5">
                                @csrf
                                <label class="text-[11px] font-semibold text-muted" for="uas_{{ $item['peserta_id'] }}">UAS</label>
                                <input
                                    id="uas_{{ $item['peserta_id'] }}"
                                    type="number"
                                    name="nilai"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="{{ $item['uas_label'] === '-' ? '' : $item['uas_label'] }}"
                                    placeholder="-"
                                    class="w-20 rounded-lg border border-border bg-card px-2 py-1.5 text-xs text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                >
                                <input
                                    type="text"
                                    name="alasan"
                                    maxlength="255"
                                    placeholder="Alasan"
                                    aria-label="Alasan perubahan UAS {{ $item['nama'] }}"
                                    class="w-full rounded-lg border border-border bg-card px-2 py-1.5 text-[11px] text-text placeholder:text-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas sm:w-24"
                                >
                                <button type="submit" class="rounded-lg border border-border bg-soft px-2 py-1.5 text-[11px] font-semibold text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas">Simpan</button>
                            </form>
                        </div>

                        <a
                            href="{{ $item['riwayat_url'] }}"
                            class="mt-4 block rounded-xl border border-border bg-soft px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Lihat riwayat perubahan
                        </a>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
