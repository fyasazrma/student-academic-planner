@props([
    'label' => '',
    'tone' => 'netral',
])

@php
    $tones = [
        'netral' => 'bg-soft text-muted',
        'tugas' => 'bg-tile-lavender text-sidebar',
        'deadline' => 'bg-tile-peach text-badge-text',
        'ujian' => 'bg-tile-sky text-sidebar',
        'uts' => 'bg-tile-sky text-sidebar',
        'uas' => 'bg-tile-sky text-sidebar',
        'kuliah' => 'bg-tile-lime text-primary-text',
        'akademik' => 'bg-tile-rose text-badge-text',
        'pengumuman' => 'bg-tile-sand text-badge-text',
        'selesai' => 'bg-tile-lime text-primary-text',
        'peringatan' => 'bg-tile-sand text-badge-text',
        'hadir' => 'bg-tile-lime text-primary-text',
        'izin' => 'bg-tile-sand text-badge-text',
        'sakit' => 'bg-tile-sand text-badge-text',
        'alfa' => 'bg-tile-rose text-badge-text',
        'dibuka' => 'bg-tile-lavender text-sidebar',
        'ditutup' => 'bg-soft text-muted',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-7 items-center rounded-full px-3 text-xs font-semibold '.($tones[$tone] ?? $tones['netral'])]) }}>
    {{ $label }}
</span>
