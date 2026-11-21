@props([
    'variant' => 'hero',
    'label' => 'Ilustrasi aktivitas akademik',
    'class' => 'w-full h-auto',
])

@php
    $titleId = 'landing-ilustrasi-'.uniqid('', true);
@endphp

<svg {{ $attributes->merge(['class' => $class, 'role' => 'img', 'aria-labelledby' => $titleId]) }} viewBox="0 0 480 340" fill="none" xmlns="http://www.w3.org/2000/svg">
    <title id="{{ $titleId }}">{{ $label }}</title>
    <g stroke="#201f31" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        @switch($variant)
            @case('hero')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#dfddf3" stroke="none" />
                <circle cx="426" cy="54" r="14" fill="#daf17c" stroke="none" />
                <circle cx="54" cy="292" r="10" fill="#ffe5de" stroke="none" />
                <path d="M84 58h30M84 68h18" stroke="#201f31" stroke-width="2" stroke="none" opacity="0.35" />

                <x-landing.person x="142" y="112" :scale="1.05" outfit="#daf17c" pose="desk" />

                <rect x="66" y="234" width="348" height="14" rx="7" fill="#ffffff" />
                <path d="M104 248v50M376 248v50" />

                <rect x="206" y="172" width="134" height="76" rx="8" fill="#ffffff" />
                <rect x="216" y="182" width="114" height="56" rx="4" fill="#f1f8cc" stroke="none" />
                <path d="M216 198h114M234 182v56M252 182v56" stroke="#201f31" stroke-width="1.4" stroke="none" opacity="0.5" />
                <rect x="256" y="204" width="10" height="10" rx="3" fill="#daf17c" stroke="none" />
                <path d="M192 248h162l10 8H182l10-8Z" fill="#201f31" stroke="none" />

                <rect x="326" y="66" width="128" height="68" rx="14" fill="#ffffff" />
                <text x="342" y="90" fill="#201f31" font-size="11" font-weight="700" stroke="none">Deadline</text>
                <path d="M342 102h64M342 114h42" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                <rect x="398" y="80" width="40" height="18" rx="9" fill="#daf17c" stroke="none" />

                <rect x="56" y="86" width="122" height="50" rx="14" fill="#ffffff" />
                <circle cx="80" cy="111" r="12" fill="#dcebfb" stroke="none" />
                <path d="M100 105h54M100 117h38" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                @break

            @case('task-scatter')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#fcf1d5" stroke="none" />
                <x-landing.person x="52" y="104" :scale="0.92" outfit="#dcebfb" pose="stand" />
                <path d="M170 100c38-24 74 8 108-14M172 168c46 26 86-12 132 8M170 236c34-18 76 12 112-8" stroke="#201f31" stroke-width="1.6" stroke-dasharray="5 6" fill="none" stroke="none" opacity="0.35" />
                <g transform="rotate(-8 300 96)">
                    <rect x="270" y="66" width="100" height="62" rx="12" fill="#ffffff" />
                    <path d="M286 84h56M286 98h68M286 112h40" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                </g>
                <g transform="rotate(7 332 178)">
                    <rect x="292" y="150" width="96" height="58" rx="12" fill="#ffffff" />
                    <rect x="306" y="164" width="22" height="10" rx="5" fill="#f7d9dc" stroke="none" />
                    <path d="M306 184h58M306 196h38" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                </g>
                <g transform="rotate(-5 286 264)">
                    <rect x="248" y="238" width="94" height="54" rx="12" fill="#ffffff" />
                    <path d="M264 256h52M264 270h34" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                </g>
                <circle cx="424" cy="292" r="10" fill="#daf17c" stroke="none" />
                @break

            @case('deadline')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#dfddf3" stroke="none" />
                <x-landing.person x="52" y="104" :scale="0.92" outfit="#f7d9dc" pose="stand" />
                <circle cx="332" cy="168" r="76" fill="#ffffff" />
                <circle cx="332" cy="168" r="56" fill="#fcf1d5" stroke="none" />
                <path d="M332 128v40l26 16" fill="none" />
                <circle cx="332" cy="168" r="5" fill="#201f31" stroke="none" />
                <path d="M332 84v14M332 238v14M248 168h14M402 168h14" />
                <rect x="96" y="246" width="120" height="50" rx="14" fill="#ffffff" />
                <path d="M116 262h16v18h-16zM140 262h16v18h-16zM164 262h16v18h-16z" fill="#f7d9dc" stroke="none" />
                <path d="M116 270h64" stroke="#201f31" stroke-width="1.6" stroke="none" />
                <circle cx="438" cy="70" r="12" fill="#e9b94f" stroke="none" />
                @break

            @case('schedule')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#ffe5de" stroke="none" />
                <x-landing.person x="52" y="104" :scale="0.92" outfit="#dfddf3" pose="stand" />
                <rect x="196" y="66" width="230" height="62" rx="14" fill="#ffffff" />
                <path d="M216 90h92M216 106h146" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                <rect x="222" y="144" width="230" height="62" rx="14" fill="#ffffff" transform="rotate(-4 337 175)" />
                <path d="M242 168h92M242 184h146" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                <rect x="196" y="228" width="230" height="62" rx="14" fill="#ffffff" transform="rotate(3 311 259)" />
                <path d="M216 252h92M216 268h146" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                <circle cx="438" cy="292" r="10" fill="#daf17c" stroke="none" />
                @break

            @case('role-student')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#f1f8cc" stroke="none" />
                <x-landing.person x="60" y="100" outfit="#daf17c" pose="desk" />
                <rect x="232" y="76" width="176" height="186" rx="18" fill="#ffffff" />
                <text x="252" y="106" fill="#201f31" font-size="12" font-weight="700" stroke="none">Aktivitas saya</text>
                <rect x="252" y="124" width="136" height="26" rx="13" fill="#f1f8cc" stroke="none" />
                <path d="M264 137h6l4 4 8-10" fill="none" stroke="#201f31" stroke-width="2" />
                <path d="M288 137h84" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                <rect x="252" y="160" width="136" height="26" rx="13" fill="#dfddf3" stroke="none" />
                <path d="M264 173h6l4 4 8-10" fill="none" stroke="#201f31" stroke-width="2" />
                <path d="M288 173h84" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                <rect x="252" y="196" width="136" height="26" rx="13" fill="#f4f4f4" stroke="none" />
                <path d="M288 209h84" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                @break

            @case('role-dosen')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#dcebfb" stroke="none" />
                <x-landing.person x="56" y="100" outfit="#dcebfb" pose="stand" />
                <rect x="224" y="72" width="190" height="194" rx="18" fill="#ffffff" />
                <text x="244" y="102" fill="#201f31" font-size="12" font-weight="700" stroke="none">Kelas saya</text>
                <rect x="244" y="118" width="150" height="34" rx="10" fill="#f1f8cc" stroke="none" />
                <path d="M256 135h126" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                <rect x="244" y="160" width="150" height="34" rx="10" fill="#ffe5de" stroke="none" />
                <path d="M256 177h126" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                <rect x="244" y="202" width="150" height="34" rx="10" fill="#dfddf3" stroke="none" />
                <path d="M256 219h126" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.45" />
                @break

            @case('role-admin')
                <rect x="34" y="24" width="412" height="288" rx="52" fill="#fcf1d5" stroke="none" />
                <x-landing.person x="56" y="100" outfit="#fcf1d5" pose="stand" />
                <rect x="222" y="82" width="194" height="176" rx="18" fill="#ffffff" />
                <text x="242" y="110" fill="#201f31" font-size="12" font-weight="700" stroke="none">Data akademik</text>
                <rect x="242" y="126" width="66" height="46" rx="10" fill="#f1f8cc" stroke="none" />
                <rect x="318" y="126" width="66" height="46" rx="10" fill="#dcebfb" stroke="none" />
                <rect x="242" y="182" width="66" height="46" rx="10" fill="#ffe5de" stroke="none" />
                <rect x="318" y="182" width="66" height="46" rx="10" fill="#dfddf3" stroke="none" />
                @break

            @case('step-account')
                <rect x="20" y="20" width="440" height="300" rx="40" fill="#f1f8cc" stroke="none" />
                <x-landing.person x="140" y="58" :scale="0.75" outfit="#daf17c" pose="stand" />
                <rect x="256" y="92" width="150" height="96" rx="14" fill="#ffffff" />
                <circle cx="290" cy="124" r="16" fill="#ffe5de" stroke="none" />
                <path d="M318 118h66M318 132h44M274 158h112" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                @break

            @case('step-setup')
                <rect x="20" y="20" width="440" height="300" rx="40" fill="#dcebfb" stroke="none" />
                <x-landing.person x="140" y="58" :scale="0.75" outfit="#dcebfb" pose="stand" />
                <rect x="256" y="88" width="150" height="104" rx="14" fill="#ffffff" />
                <path d="M256 112h150" stroke="#201f31" stroke-width="1.8" stroke="none" />
                <path d="M276 100v24M306 100v24M336 100v24M366 100v24" stroke="#201f31" stroke-width="1.6" stroke="none" />
                <rect x="276" y="126" width="20" height="18" rx="5" fill="#daf17c" stroke="none" />
                <rect x="306" y="126" width="20" height="18" rx="5" fill="#f4f4f4" stroke="none" />
                <rect x="336" y="126" width="20" height="18" rx="5" fill="#f4f4f4" stroke="none" />
                @break

            @case('step-class')
                <rect x="20" y="20" width="440" height="300" rx="40" fill="#ffe5de" stroke="none" />
                <x-landing.person x="140" y="58" :scale="0.75" outfit="#ffe5de" pose="stand" />
                <rect x="256" y="86" width="150" height="108" rx="14" fill="#ffffff" />
                <path d="M276 110h110M276 128h88M276 150h110M276 168h64" stroke="#201f31" stroke-width="1.8" stroke="none" opacity="0.5" />
                <circle cx="278" cy="110" r="4" fill="#daf17c" stroke="none" />
                @break

            @case('step-activity')
                <rect x="20" y="20" width="440" height="300" rx="40" fill="#dfddf3" stroke="none" />
                <x-landing.person x="140" y="58" :scale="0.75" outfit="#dfddf3" pose="desk" />
                <rect x="256" y="88" width="150" height="108" rx="14" fill="#ffffff" />
                <path d="M276 114h6l4 4 8-10M276 148h6l4 4 8-10" fill="none" stroke="#201f31" stroke-width="2" />
                <path d="M300 114h82M300 148h82M276 178h106" stroke="#201f31" stroke-width="1.6" stroke="none" opacity="0.5" />
                @break

            @case('closing')
                <circle cx="240" cy="162" r="116" fill="#dfddf3" stroke="none" />
                <circle cx="240" cy="162" r="88" fill="#daf17c" stroke="none" />
                <x-landing.person x="178" y="52" :scale="1.05" outfit="#ffffff" pose="wave" />
                <path d="M196 206h88" stroke="#201f31" stroke-width="3" />
                <circle cx="92" cy="86" r="9" fill="#ffe5de" stroke="none" />
                <circle cx="396" cy="102" r="7" fill="#dcebfb" stroke="none" />
                <circle cx="384" cy="256" r="10" fill="#fcf1d5" stroke="none" />
                @break
        @endswitch
    </g>
</svg>
