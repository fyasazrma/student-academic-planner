<x-app-layout title="Nilai Akademik" :user="$user" :unread-count="$unreadCount" role="dosen">
    <div class="space-y-6">
        <x-page-header
            title="Nilai Akademik"
            eyebrow="Penilaian"
            description="Kelola nilai UTS dan UAS, atur penggunaan komponen tugas, lalu pantau rekap nilai kelas yang Anda ampu."
        />

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

        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Ringkasan penilaian">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-agenda-tugas">Ringkasan</p>
                    <p class="mt-2 text-sm text-muted">
                        <span class="font-bold text-text">{{ $kelas->count() }}</span> kelas terdaftar dengan rekap nilai.
                    </p>
                </div>
                @if ($belum_dikonfigurasi > 0)
                    <x-status-badge
                        :label="$belum_dikonfigurasi.' kelas belum menentukan tugas'"
                        tone="peringatan"
                    />
                @endif
            </div>
        </section>

        @if ($kelas->isEmpty())
            <x-empty-state
                title="Belum ada kelas yang dikelola"
                description="Nilai dapat dikelola setelah Anda memiliki kelas dengan peserta terdaftar."
                icon="book"
            />
        @else
            <section aria-label="Daftar kelas (tabel)" class="hidden rounded-2xl border border-border bg-card p-5 shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Mata kuliah</th>
                                <th scope="col" class="px-4 py-3">Periode</th>
                                <th scope="col" class="px-4 py-3">Komponen tugas</th>
                                <th scope="col" class="px-4 py-3">Peserta</th>
                                <th scope="col" class="px-4 py-3">Lengkap</th>
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($kelas as $item)
                                <tr class="align-middle transition hover:bg-soft/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-text">{{ $item['nama_mata_kuliah'] }}</p>
                                        <p class="mt-1 text-xs text-muted">{{ $item['kode_mata_kuliah'] }} · {{ $item['kode_kelas'] }} · {{ $item['sks'] }} SKS</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-text">{{ $item['periode'] }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :label="$item['gunakan_tugas_label']"
                                            :tone="$item['gunakan_tugas_label'] === 'Belum ditentukan' ? 'peringatan' : 'netral'"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">{{ $item['total'] }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-text">
                                        {{ $item['lengkap'] }} / {{ $item['total'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <a
                                                href="{{ $item['url'] }}"
                                                class="rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                                            >
                                                Kelola nilai
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-label="Daftar kelas (kartu)" class="space-y-3 md:hidden">
                @foreach ($kelas as $item)
                    <article class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-text">{{ $item['nama_mata_kuliah'] }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $item['kode_kelas'] }} · {{ $item['periode'] }}</p>
                            </div>
                            <x-status-badge
                                :label="$item['gunakan_tugas_label']"
                                :tone="$item['gunakan_tugas_label'] === 'Belum ditentukan' ? 'peringatan' : 'netral'"
                            />
                        </div>
                        <p class="mt-3 text-[11px] text-muted">
                            Peserta: <span class="font-semibold text-text">{{ $item['total'] }}</span>
                            · Lengkap: <span class="font-semibold text-text">{{ $item['lengkap'] }}</span>
                        </p>
                        <a
                            href="{{ $item['url'] }}"
                            class="mt-4 block rounded-xl border border-border bg-soft px-3 py-2 text-center text-xs font-semibold text-text transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            Kelola nilai
                        </a>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-app-layout>
