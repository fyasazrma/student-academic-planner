@props([
    'calendar' => [],
    'currentView' => 'month',
    'activeFilters' => [],
    'filterOptions' => [],
    'today' => null,
    'routeName' => 'admin.dashboard',
])

@php
    $weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $today = $today ?? today();
@endphp

<section id="kalender-akademik" class="min-w-0">
    <div class="mb-4">
        <h2 class="text-lg font-bold text-text">Kalender akademik institusi</h2>
        <p class="mt-1 text-xs text-muted">Agenda institusi yang dipublikasikan dan jadwal kelas pada tahun akademik aktif.</p>
    </div>

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
                        $outside = $calendar['view'] === 'month' && $day->format('Y-m') !== $calendar['focusDate'];
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
                <x-empty-state
                    title="Tidak ada agenda"
                    description="Tidak ada agenda institusi atau jadwal kelas pada periode ini."
                    icon="calendar"
                />
            @else
                <div class="space-y-2">
                    @foreach ($calendar['agendaList'] as $event)
                        <button
                            type="button"
                            data-agenda-trigger="{{ $event['id'] }}"
                            class="flex w-full items-start gap-3 rounded-xl border border-border bg-soft p-3 text-left transition hover:bg-card focus:outline-none focus-visible:ring-2 focus-visible:ring-agenda-tugas"
                        >
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full
                                {{ $event['jenis'] === 'akademik' ? 'bg-agenda-akademik' : '' }}
                                {{ $event['jenis'] === 'ujian' ? 'bg-agenda-uts' : '' }}
                                {{ $event['jenis'] === 'pengumuman' ? 'bg-agenda-pengumuman' : '' }}
                                {{ $event['jenis'] === 'kuliah' ? 'bg-agenda-kuliah' : '' }}"></span>
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
</section>

{{-- Dialog detail agenda memakai handler yang sama dengan panel mahasiswa, tanpa mengubah file mahasiswa. --}}
@push('modals')
    <script type="application/json" id="agenda-events-data">@json($calendar['events'])</script>

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
