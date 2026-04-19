@php
    $k = $icon ?? 'briefcase';
@endphp
<span class="kc-role-icon" aria-hidden="true">
    @switch($k)
        @case('code')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 18l6-6-6-6M8 6l-6 6 6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @break
        @case('palette')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 3s-4 7-4 11a4 4 0 0 0 8 0c0-4-4-11-4-11Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M12 14v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="6" cy="18" r="1.5" fill="currentColor"/>
                <circle cx="10" cy="20" r="1.5" fill="currentColor"/>
                <circle cx="14" cy="20" r="1.5" fill="currentColor"/>
                <circle cx="18" cy="18" r="1.5" fill="currentColor"/>
            </svg>
            @break
        @case('shop')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9h18v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M3 9 5 3h14l2 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 13v2M15 13v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            @break
        @case('megaphone')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 11v2a4 4 0 0 0 4 4h1l1 4h4v-8H8a4 4 0 0 0-4 4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M18 9a3 3 0 0 1 0 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            @break
        @case('people')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.6"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            @break
        @case('chart')
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 3v18h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M7 16v-5M12 16V8M17 16v-9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            @break
        @default
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2ZM9 5h6v2H9V5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
    @endswitch
</span>
