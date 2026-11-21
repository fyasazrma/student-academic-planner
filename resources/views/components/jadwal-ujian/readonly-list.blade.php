@props([
    'ujian' => [],
    'filters' => [],
    'jenisUjianOptions' => [],
    'tahunOptions' => [],
    'routeName' => '',
])

@php
    $adaFilter = ($filters['jenis_ujian'] ?? '') !== '' || ($filters['tahun_akademik'] ?? '') !== '';
@endphp

<section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6" aria-label="Daftar jadwal ujian">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-text">Daftar jadwal ujian</h2>
            <p class="mt-1 text-xs text-muted">Data berikut hanya dapat dilihat. Perubahan jadwal dilakukan oleh administrator.</p>
        </div>
        <x-status-badge label="Hanya baca" tone="netral" />
    </div>

    <form method="GET" action="{{ route($routeName) }}" role="search" class="mt-5 grid gap-3 sm:grid-cols-3">
        <div>
            <label for="jenis_ujian" class="block text-xs font-semibold text-text">Jenis ujian</label>
            <select
                id="jenis_ujian"
                name="jenis_ujian"
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30"
            >
                <option value="">Semua jenis</option>
                @foreach ($jenisUjianOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['jenis_ujian'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="tahun_akademik" class="block text-xs font-semibold text-text">Tahun akademik</label>
            <select
                id="tahun_akademik"
                name="tahun_akademik"
                class="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-text focus:border-agenda-uts focus:outline-none focus:ring-2 focus:ring-agenda-uts/30"
            >
                <option value="">Semua tahun akademik</option>
                @foreach ($tahunOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($filters['tahun_akademik'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button
                type="submit"
                class="rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-text transition hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                Terapkan filter
            </button>
            @if ($adaFilter)
                <a
                    href="{{ route($routeName) }}"
                    class="rounded-xl border border-border bg-card px-4 py-2.5 text-xs font-semibold text-text transition hover:bg-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-uts"
                >
                    Reset
                </a>
            @endif
        </div>
    </form>

    @if ($ujian === [])
        <div class="mt-6">
            <x-empty-state
                :title="$adaFilter ? 'Tidak ada jadwal ditemukan' : 'Belum ada jadwal ujian'"
                :description="$adaFilter ? 'Ubah filter untuk melihat jadwal lain.' : 'Jadwal UTS dan UAS yang dipublikasikan akan tampil di sini.'"
                icon="exam"
            />
        </div>
    @else
        <div class="mt-5 hidden overflow-x-auto rounded-xl border border-border md:block">
            <table class="w-full min-w-[760px] border-collapse text-left text-sm">
                <thead class="bg-soft text-[11px] font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3">Jenis</th>
                        <th scope="col" class="px-4 py-3">Judul</th>
                        <th scope="col" class="px-4 py-3">Periode</th>
                        <th scope="col" class="px-4 py-3">Tanggal & jam</th>
                        <th scope="col" class="px-4 py-3">Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border bg-card">
                    @foreach ($ujian as $item)
                        <tr class="align-middle transition hover:bg-soft/60">
                            <td class="px-4 py-3">
                                <x-status-badge :label="$item['label']" :tone="$item['kategori']" />
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-text">{{ $item['judul'] }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $item['konteks'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted">{{ $item['tahun'] }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-text">{{ $item['tanggal_label'] }}</p>
                                <p class="mt-0.5 inline-flex items-center gap-1.5 text-xs text-muted">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                    {{ $item['waktu_label'] }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-xs text-text">{{ $item['lokasi'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 space-y-3 md:hidden">
            @foreach ($ujian as $item)
                <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-text">{{ $item['judul'] }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $item['konteks'] }} · {{ $item['tahun'] }}</p>
                        </div>
                        <x-status-badge :label="$item['label']" :tone="$item['kategori']" />
                    </div>
                    <p class="mt-3 text-xs text-text">
                        <span class="font-semibold">{{ $item['tanggal_label'] }}</span> · {{ $item['waktu_label'] }}
                    </p>
                    <p class="mt-1 text-xs text-muted">Lokasi {{ $item['lokasi'] ?: '-' }}</p>
                </div>
            @endforeach
        </div>
    @endif
</section>