@props([
    'label' => '',
    'tone' => 'netral',
])

@php
    $tones = [
        'netral' => 'bg-soft text-muted ring-border',
        'tugas' => 'bg-agenda-tugas/10 text-agenda-tugas ring-agenda-tugas/30',
        'deadline' => 'bg-agenda-terlambat/10 text-agenda-terlambat ring-agenda-terlambat/30',
        'ujian' => 'bg-agenda-uts/10 text-agenda-uts ring-agenda-uts/30',
        'kuliah' => 'bg-agenda-kuliah/12 text-agenda-kuliah ring-agenda-kuliah/30',
        'akademik' => 'bg-agenda-akademik/12 text-agenda-akademik ring-agenda-akademik/30',
        'pengumuman' => 'bg-agenda-pengumuman/12 text-agenda-pengumuman ring-agenda-pengumuman/30',
        'selesai' => 'bg-success/10 text-success ring-success/30',
        'peringatan' => 'bg-warning/12 text-warning ring-warning/30',
        'hadir' => 'bg-success/10 text-success ring-success/30',
        'izin' => 'bg-agenda-pengumuman/12 text-agenda-pengumuman ring-agenda-pengumuman/30',
        'sakit' => 'bg-warning/12 text-warning ring-warning/30',
        'alfa' => 'bg-danger/10 text-danger ring-danger/30',
        'dibuka' => 'bg-agenda-tugas/10 text-agenda-tugas ring-agenda-tugas/30',
        'ditutup' => 'bg-soft text-muted ring-border',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg px-2 py-1 text-[11px] font-semibold ring-1 ring-inset '.($tones[$tone] ?? $tones['netral'])]) }}>
    {{ $label }}
</span>
