@props([
    'name' => 'dashboard',
    'class' => 'h-5 w-5',
])

<svg
    {{ $attributes->merge(['class' => $class]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('dashboard')
            <path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.25" />
            <path d="M5.5 20c.6-3.2 3.1-5 6.5-5s5.9 1.8 6.5 5" />
            @break
        @case('calendar')
            <rect x="3.75" y="5.25" width="16.5" height="15" rx="2" />
            <path d="M8 3.75v3M16 3.75v3M3.75 10h16.5" />
            @break
        @case('book')
            <path d="M4.5 4.75h6a2 2 0 0 1 2 2V20a2 2 0 0 0-2-2h-6V4.75Z" />
            <path d="M19.5 4.75h-6a2 2 0 0 0-2 2V20a2 2 0 0 1 2-2h6V4.75Z" />
            @break
        @case('clipboard')
            <path d="M9 4.75h6v2.5H9z" />
            <path d="M7.5 6h-1a1.75 1.75 0 0 0-1.75 1.75V19a1.75 1.75 0 0 0 1.75 1.75h11A1.75 1.75 0 0 0 19.25 19V7.75A1.75 1.75 0 0 0 17.5 6h-1" />
            <path d="M9 12h6M9 16h4" />
            @break
        @case('exam')
            <path d="M6 3.75h12v16.5H6z" />
            <path d="M9 8h6M9 12h2.5M9 16h2.5M15.5 12h.01M15.5 16h.01" />
            @break
        @case('target')
            <circle cx="12" cy="12" r="8.25" />
            <circle cx="12" cy="12" r="4.5" />
            <circle cx="12" cy="12" r="1" />
            @break
        @case('chart')
            <path d="M4.5 19.5h15" />
            <path d="M7 16V9.5M12 16V5.5M17 16v-4" />
            @break
        @case('bell')
            <path d="M6.75 9.5a5.25 5.25 0 0 1 10.5 0c0 4 1.5 5.25 1.5 5.25H5.25S6.75 13.5 6.75 9.5Z" />
            <path d="M10 18a2 2 0 0 0 4 0" />
            @break
        @case('logout')
            <path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
            <path d="M18 12H9m9 0-3-3m3 3-3 3" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('chevron-left')
            <path d="m14.5 6-6 6 6 6" />
            @break
        @case('chevron-right')
            <path d="m9.5 6 6 6-6 6" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.25" />
            <path d="M12 7.5V12l3 2" />
            @break
        @case('map-pin')
            <path d="M12 21s6.25-5.4 6.25-10.5a6.25 6.25 0 1 0-12.5 0C5.75 15.6 12 21 12 21Z" />
            <circle cx="12" cy="10.5" r="2.25" />
            @break
        @case('file')
            <path d="M6.75 3.75h7l4.5 4.5v12H6.75V3.75Z" />
            <path d="M13.5 3.75v4.5H18" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="8.25" />
            <path d="M12 11v5M12 8h.01" />
            @break
        @case('filter')
            <path d="M4 6h16M7 12h10M10 18h4" />
            @break
        @case('search')
            <circle cx="10.75" cy="10.75" r="6.25" />
            <path d="m15.5 15.5 4 4" />
            @break
        @case('list')
            <path d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01" />
            @break
        @case('timeline')
            <path d="M4 7h9M4 12h16M4 17h7" />
            <circle cx="15" cy="7" r="2" />
            <circle cx="9" cy="17" r="2" />
            @break
        @case('repeat')
            <path d="M17 3.75 20.25 7 17 10.25" />
            <path d="M20.25 7H7.5A3.75 3.75 0 0 0 3.75 10.75v1.5" />
            <path d="M7 20.25 3.75 17 7 13.75" />
            <path d="M3.75 17h12.75a3.75 3.75 0 0 0 3.75-3.75v-1.5" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @default
            <circle cx="12" cy="12" r="8.25" />
    @endswitch
</svg>
