@props([
    'calendar' => [],
    'timeline' => [],
    'currentView' => 'month',
    'activeFilters' => [],
    'filterOptions' => [],
    'today' => null,
    'routeName' => 'dosen.dashboard',
])

@php
    $tab = request('tab') === 'timeline' ? 'timeline' : 'calendar';
    $queryBase = array_filter([
        'date' => request('date'),
        'jenis' => request('jenis'),
    ], fn ($value): bool => $value !== null && $value !== '');
    $tabLink = fn (string $target): string => route($routeName, $queryBase + ['tab' => $target]);
    $weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $today = $today ?? today();
@endphp

<section id="kalender-akademik" class="min-w-0">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-text">Kalender akademik</h2>
            <p class="mt-1 text-xs text-muted">Agenda institusi, jadwal, tugas, dan ujian pada kelas yang Anda ampu.</p>
        </div>
        <div class="inline-flex w-fit rounded-xl border border-border bg-card p-1" role="tablist" aria-label="Mode tampilan agenda">
            <a
                href="{{ $tabLink('calendar') }}"
                role="tab"
                @class([
                    'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                    'bg-sidebar text-white' => $tab === 'calendar',
                    'text-muted hover:text-text' => $tab !== 'calendar',
                ])
                @if ($tab === 'calendar') aria-selected="true" @endif
            >
                <x-icon name="calendar" class="h-4 w-4" /> Kalender
            </a>
            <a
                href="{{ $tabLink('timeline') }}"
                role="tab"
                @class([
                    'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas',
                    'bg-sidebar text-white' => $tab === 'timeline',
                    'text-muted hover:text-text' => $tab !== 'timeline',
                ])
                @if ($tab === 'timeline') aria-selected="true" @endif
            >
                <x-icon name="timeline" class="h-4 w-4" /> Timeline
            </a>
        </div>
    </div>

    @if ($tab === 'calendar')
        <div class="rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5">
            <x-calendar-toolbar
                :calendar="$calendar"
                :current-view="$currentView"
                :active-filters="$activeFilters"
                :filter-options="$filterOptions"
                :route-name="$routeName"
            />

            <div class="mt-5 hidden overflow-hidden rounded-xl border border-border sm:block">
                <div class="grid grid-cols-7 border-b border-border bg-soft">
                    @foreach ($weekdays as $weekday)
                        <div class="px-2 py-2.5 text-center text-[11px] font-bold uppercase tracking-wide text-muted">{{ $weekday }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($calendar['days'] as $day)
                        @php
                            $dayEvents = $calendar['eventsByDate'][$day->toDateString()] ?? collect();
                            $isToday = $day->toDateString() === $today->toDateString();
                            $outside = $calendar['view'] === 'month'
                                && $day->format('Y-m') !== $calendar['focusDate'];
                        @endphp
                        <div @class([
                            'min-h-[112px] border-b border-r border-border p-1.5 last:border-r-0',
                            'bg-soft/50' => $outside,
                            'bg-card' => ! $outside,
                        ])>
                            <div class="mb-1.5 flex items-center justify-between px-0.5">
                                <span @class([
                                    'flex h-6 w-6 items-center justify-center rounded-lg text-[11px] font-semibold',
                                    'bg-sidebar text-white' => $isToday,
                                    'text-muted' => $outside && ! $isToday,
                                    'text-text' => ! $outside && ! $isToday,
                                ])>{{ $day->format('d') }}</span>
                                @if ($dayEvents->count() > 3)
                                    <span class="text-[9px] font-semibold text-muted">+{{ $dayEvents->count() - 3 }}</span>
                                @endif
                            </div>
                            <div class="space-y-1">
                                @foreach ($dayEvents->take(3) as $event)
                                    <x-calendar-event :event="$event" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 sm:hidden">
                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Agenda periode ini</p>
                @if ($calendar['agendaList']->isEmpty())
                    <x-empty-state title="Tidak ada agenda" description="Tidak ada agenda pada periode ini untuk kelas yang Anda ampu." icon="calendar" />
                @else
                    <div class="space-y-2">
                        @foreach ($calendar['agendaList'] as $event)
                            <button
                                type="button"
                                data-agenda-trigger="{{ $event['id'] }}"
                                class="flex w-full items-start gap-3 rounded-xl border border-border bg-soft p-3 text-left transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                            >
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full
                                    {{ $event['jenis'] === 'tugas' ? 'bg-agenda-tugas' : '' }}
                                    {{ $event['jenis'] === 'deadline' ? 'bg-agenda-terlambat' : '' }}
                                    {{ $event['jenis'] === 'ujian' ? 'bg-agenda-uts' : '' }}
                                    {{ $event['jenis'] === 'kuliah' ? 'bg-agenda-kuliah' : '' }}
                                    {{ $event['jenis'] === 'akademik' ? 'bg-agenda-akademik' : '' }}
                                    {{ $event['jenis'] === 'pengumuman' ? 'bg-agenda-pengumuman' : '' }}"></span>
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-muted">{{ $event['label'] }} · {{ $event['tanggal_label'] }}</span>
                                    <span class="mt-0.5 block text-sm font-semibold text-text">{{ $event['judul'] }}</span>
                                    <span class="mt-0.5 block text-[11px] text-muted">{{ $event['mata_kuliah'] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-text">Rentang pengerjaan tugas</h3>
                    <p class="mt-1 text-xs text-muted">{{ $timeline['start_label'] }} – {{ $timeline['end_label'] }}</p>
                </div>
                <p class="text-[11px] text-muted"><span class="font-bold text-agenda-tugas">●</span> Publikasi <span class="ml-2 font-bold text-agenda-terlambat">◆</span> Deadline</p>
            </div>

            @if ($timeline['bars'] === [] && $timeline['markers'] === [])
                <x-empty-state
                    class="mt-5"
                    title="Belum ada tugas atau ujian"
                    description="Timeline akan menampilkan rentang pengerjaan tugas dan titik ujian setelah data tersedia pada kelas Anda."
                    icon="timeline"
                />
            @else
                <div class="mt-6 hidden sm:block">
                    <div class="relative mb-6 h-5 border-b border-border">
                        @foreach ($timeline['ticks'] as $tick)
                            <span class="absolute -translate-x-1/2 text-[10px] font-medium text-muted" style="left: {{ $tick['posisi'] }}%;">{{ $tick['label'] }}</span>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        @foreach ($timeline['bars'] as $bar)
                            <x-timeline-item :bar="$bar" />
                        @endforeach
                    </div>

                    @if ($timeline['markers'] !== [])
                        <div class="mt-6 rounded-xl border border-border bg-soft p-4">
                            <p class="text-xs font-bold text-text">Penanda ujian</p>
                            <div class="relative mt-5 h-8 border-b border-border">
                                @foreach ($timeline['ticks'] as $tick)
                                    <span class="absolute -translate-x-1/2 text-[10px] text-muted" style="left: {{ $tick['posisi'] }}%;">{{ $tick['label'] }}</span>
                                @endforeach
                                @foreach ($timeline['markers'] as $marker)
                                    <span
                                        class="absolute -top-1 h-4 w-4 -translate-x-1/2 rotate-45 rounded-sm bg-agenda-uts shadow"
                                        style="left: {{ $marker['posisi'] }}%;"
                                        title="{{ $marker['judul'] }} — {{ $marker['tanggal_label'] }}"
                                        role="button"
                                        tabindex="0"
                                        data-agenda-trigger="{{ $marker['id'] }}"
                                        aria-label="Lihat detail ujian {{ $marker['judul'] }}"
                                    ></span>
                                    <span class="absolute top-5 -translate-x-1/2 whitespace-nowrap text-[9px] font-semibold text-muted" style="left: {{ $marker['posisi'] }}%;">{{ $marker['tanggal_label'] }}</span>
                                @endforeach
                            </div>
                            <div class="mt-3 space-y-1.5">
                                @foreach ($timeline['markers'] as $marker)
                                    <p class="text-[11px] text-text"><span class="font-bold text-agenda-uts">◆ Ujian</span> — {{ $marker['judul'] }} · {{ $marker['mata_kuliah'] }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 space-y-2 sm:hidden">
                    @foreach ($timeline['bars'] as $bar)
                        <article class="rounded-xl border border-border bg-soft p-3">
                            <p class="text-sm font-bold text-text">{{ $bar['judul'] }}</p>
                            <p class="mt-0.5 text-[11px] text-muted">{{ $bar['mata_kuliah'] }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
                                <div class="rounded-lg bg-card p-2">
                                    <p class="text-[10px] font-semibold uppercase text-muted">Dipublikasikan</p>
                                    <p class="mt-0.5 font-semibold text-text">{{ $bar['dipublikasikan_label'] }}</p>
                                </div>
                                <div class="rounded-lg bg-card p-2">
                                    <p class="text-[10px] font-semibold uppercase text-muted">Deadline</p>
                                    <p class="mt-0.5 font-semibold text-text">{{ $bar['deadline_label'] }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] font-semibold text-agenda-tugas">Status: {{ $bar['status_label'] }}</p>
                        </article>
                    @endforeach
                    @foreach ($timeline['markers'] as $marker)
                        <article
                            class="rounded-xl border border-border bg-soft p-3"
                            role="button"
                            tabindex="0"
                            data-agenda-trigger="{{ $marker['id'] }}"
                            aria-label="Lihat detail ujian {{ $marker['judul'] }}"
                        >
                            <p class="text-[10px] font-bold uppercase tracking-wide text-agenda-uts">Ujian · {{ $marker['tanggal_label'] }}</p>
                            <p class="mt-1 text-sm font-semibold text-text">{{ $marker['judul'] }}</p>
                            <p class="mt-0.5 text-[11px] text-muted">{{ $marker['mata_kuliah'] }}</p>
                        </article>
                    @endforeach
                </div>
            @endif

            <p class="mt-5 flex items-start gap-2 rounded-xl bg-soft p-3 text-[11px] leading-5 text-muted">
                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-agenda-uts" />
                <span>Schema saat ini menyimpan ujian sebagai <strong class="font-semibold text-text">jenis = ujian</strong> tanpa field UTS/UAS terpisah. Label ujian tidak ditebak dari judul agenda.</span>
            </p>
        </div>
    @endif
</section>

@push('modals')
    <script type="application/json" id="agenda-events-data">@json($calendar['events'])</script>
    <script type="application/json" id="timeline-events-data">@json(['bars' => $timeline['bars'], 'markers' => $timeline['markers']])</script>

    <dialog
        id="agenda-detail"
        class="w-[calc(100%-2rem)] max-w-lg rounded-2xl border border-border bg-card p-0 text-text shadow-xl backdrop:bg-sidebar/50"
        aria-labelledby="agenda-detail-title"
    >
        <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
            <div class="min-w-0">
                <p id="agenda-detail-label" class="text-[10px] font-bold uppercase tracking-[0.18em] text-agenda-tugas">Agenda</p>
                <h3 id="agenda-detail-title" class="mt-1 text-lg font-bold">Detail agenda</h3>
            </div>
            <button
                type="button"
                data-agenda-close
                class="rounded-lg border border-border p-1.5 text-muted transition hover:bg-soft hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                aria-label="Tutup detail agenda"
            >
                <x-icon name="close" class="h-4 w-4" />
            </button>
        </div>

        <dl class="grid gap-3 px-5 py-5 text-sm sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Mata kuliah / konteks</dt>
                <dd id="agenda-detail-course" class="mt-1 font-semibold text-text"></dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Tanggal agenda</dt>
                <dd id="agenda-detail-date" class="mt-1 font-medium text-text"></dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Jam</dt>
                <dd id="agenda-detail-time" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div>
                <dt id="agenda-detail-start-label" class="text-[11px] font-semibold uppercase tracking-wide text-muted">Tanggal mulai</dt>
                <dd id="agenda-detail-start" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div>
                <dt id="agenda-detail-end-label" class="text-[11px] font-semibold uppercase tracking-wide text-muted">Tanggal selesai</dt>
                <dd id="agenda-detail-end" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Dipublikasikan pada</dt>
                <dd id="agenda-detail-published" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Lokasi</dt>
                <dd id="agenda-detail-location" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div>
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Status</dt>
                <dd id="agenda-detail-status" class="mt-1 font-medium text-text">Tidak tersedia</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted">Deskripsi</dt>
                <dd id="agenda-detail-description" class="mt-1 leading-6 text-text">Tidak tersedia</dd>
            </div>
        </dl>

        <div class="flex justify-end border-t border-border bg-soft px-5 py-3">
            <button
                type="button"
                data-agenda-close
                class="rounded-xl bg-sidebar px-4 py-2 text-xs font-semibold text-white transition hover:bg-sidebar-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
            >Tutup</button>
        </div>
    </dialog>
@endpush
