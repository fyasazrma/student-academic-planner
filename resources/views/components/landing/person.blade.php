@props([
    'x' => 0,
    'y' => 0,
    'scale' => 1,
    'outfit' => '#daf17c',
    'skin' => '#ffe5de',
    'pose' => 'desk',
])

{{-- Karakter editorial yang dipakai ulang di seluruh ilustrasi landing. --}}
<g transform="translate({{ $x }}, {{ $y }}) scale({{ $scale }})" stroke="#201f31" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M38 102v22M62 102v22" fill="none" />

    @if ($pose === 'wave')
        <path d="M28 68 10 48M72 68 90 48" fill="none" />
    @elseif ($pose === 'desk')
        <path d="M28 70 14 94M72 70 86 94" fill="none" />
    @else
        <path d="M28 70 20 94M72 70 80 94" fill="none" />
    @endif

    <rect x="26" y="54" width="48" height="52" rx="16" fill="{{ $outfit }}" />
    <circle cx="50" cy="30" r="20" fill="{{ $skin }}" />
    <path d="M30 29c0-12 8-20 20-20s20 8 20 20c-4-6-10-9-20-9s-16 3-20 9Z" fill="#201f31" stroke="none" />
    <circle cx="43" cy="31" r="1.9" fill="#201f31" stroke="none" />
    <circle cx="57" cy="31" r="1.9" fill="#201f31" stroke="none" />
    <path d="M45 38c3 3 7 3 10 0" fill="none" />
</g>
