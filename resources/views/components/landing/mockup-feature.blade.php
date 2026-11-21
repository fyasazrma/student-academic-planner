@props([
    'variant' => 'tasks',
    'title' => 'Pratinjau',
    'class' => 'w-full',
])

<div {{ $attributes->merge(['class' => 'rounded-panel bg-card p-4 shadow-card sm:p-5 '.$class]) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-bold text-text">{{ $title }}</p>
        <span class="rounded-full bg-soft px-2.5 py-1 text-[10px] font-semibold text-muted">Contoh tampilan</span>
    </div>

    <div class="mt-4">
        @switch($variant)
            @case('tasks')
                @foreach ([
                    ['Laporan Praktikum', 'Sedang dikerjakan', 'bg-tile-lavender text-text'],
                    ['Ringkasan Materi', 'Belum dikumpulkan', 'bg-soft text-muted'],
                    ['Latihan Soal UTS', 'Sudah dikumpulkan', 'bg-tile-lime text-primary-text'],
                ] as $row)
                    <div class="mb-2 flex items-center gap-3 rounded-control bg-soft/60 p-2.5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-tile bg-tile-peach text-text"><x-icon name="file" class="size-4" /></span>
                        <span class="min-w-0 flex-1 truncate text-xs font-semibold text-text">{{ $row[0] }}</span>
                        <span @class(['shrink-0 rounded-full px-2 py-1 text-[10px] font-bold', $row[2]])>{{ $row[1] }}</span>
                    </div>
                @endforeach
                @break

            @case('progress')
                @foreach ([['Praktikum Database', '72%', 'w-[72%]'], ['Sistem Informasi', '48%', 'w-[48%]'], ['Algoritma', '90%', 'w-[90%]']] as $row)
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-xs font-semibold text-text"><span>{{ $row[0] }}</span><span>{{ $row[1] }}</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><div @class(['h-full rounded-full bg-primary', $row[2]])></div></div>
                    </div>
                @endforeach
                @break

            @case('exam')
                @foreach ([['UTS', 'Algoritma', '14 Okt · 09.00–11.00'], ['UAS', 'Praktikum Database', '28 Okt · 13.00–15.00']] as $row)
                    <div class="mb-2 flex items-center gap-3 rounded-control bg-tile-sky/70 p-3">
                        <span class="rounded-full bg-card px-2.5 py-1 text-[11px] font-bold text-text">{{ $row[0] }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-xs font-semibold text-text">{{ $row[1] }}</span><span class="block text-[11px] text-muted">{{ $row[2] }}</span></span>
                    </div>
                @endforeach
                @break

            @case('attendance')
                @foreach ([['Hadir', '18', 'bg-primary'], ['Izin / Sakit', '3', 'bg-warning'], ['Tanpa keterangan', '2', 'bg-danger']] as $row)
                    <div class="mb-2.5 flex items-center gap-3">
                        <span class="w-28 shrink-0 text-xs font-medium text-muted">{{ $row[0] }}</span>
                        <span class="h-3 flex-1 overflow-hidden rounded-full bg-soft"><span @class(['block h-full rounded-full', $row[2]]) style="width: {{ (int) $row[1] * 4 }}%"></span></span>
                        <span class="w-5 shrink-0 text-right text-xs font-bold text-text">{{ $row[1] }}</span>
                    </div>
                @endforeach
                @break

            @case('grades')
                <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold text-muted">
                    <span class="rounded-control bg-soft py-2">Mata Kuliah</span><span class="rounded-control bg-soft py-2">Nilai Akhir</span><span class="rounded-control bg-soft py-2">Bobot</span>
                </div>
                @foreach ([['Algoritma', 'A', '4'], ['Sistem Informasi', 'B+', '3'], ['Praktikum Database', 'A-', '3']] as $row)
                    <div class="grid grid-cols-3 gap-2 border-b border-border py-2.5 text-center text-xs text-text"><span class="truncate">{{ $row[0] }}</span><span class="font-bold">{{ $row[1] }}</span><span>{{ $row[2] }}</span></div>
                @endforeach
                <p class="mt-3 text-right text-xs font-bold text-text">IP semester ini · 3,67</p>
                @break

            @case('target')
                @foreach ([['Selesaikan 3 modul Algoritma', 'bg-tile-lime'], ['Ulangi materi relasi database', 'bg-tile-lavender'], ['Kerjakan latihan UTS', 'bg-soft']] as $row)
                    <div class="mb-2 flex items-center gap-3 rounded-control {{ $row[1] }} p-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full border-2 border-text/40"><x-icon name="check" class="size-3" /></span>
                        <span class="text-xs font-semibold text-text">{{ $row[0] }}</span>
                    </div>
                @endforeach
                @break

            @case('notification')
                @foreach ([['Deadline tugas mendekat', 'Laporan Praktikum · 10 Okt', 'bg-tile-peach'], ['Jadwal UTS ditambahkan', 'Algoritma · 14 Okt', 'bg-tile-sky'], ['Pengumuman baru', 'Kampus · 20 Okt', 'bg-tile-sand']] as $row)
                    <div class="mb-2 flex items-start gap-3 rounded-control {{ $row[2] }} p-2.5">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-card text-text"><x-icon name="bell" class="size-3.5" /></span>
                        <span class="min-w-0"><span class="block text-xs font-bold text-text">{{ $row[0] }}</span><span class="block text-[11px] text-muted">{{ $row[1] }}</span></span>
                    </div>
                @endforeach
                @break
        @endswitch
    </div>
</div>
