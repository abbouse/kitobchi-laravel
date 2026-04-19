{{-- Tiniq vektor bayroqlar (24×16), favicon va kichik UI uchun --}}
@php
    $code = strtolower($code ?? 'uz');
@endphp
<span class="kc-flag" data-flag="{{ $code }}" aria-hidden="true">
    @switch($code)
        @case('uz')
            {{-- Oʻzbekiston: koʻk, oq, yashil + qizil chiziqlar (soddalashtirilgan) --}}
            <svg viewBox="0 0 24 16" width="24" height="16" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="4" fill="#0099B5"/>
                <rect y="4" width="24" height="1" fill="#CE1126"/>
                <rect y="5" width="24" height="6" fill="#FFFFFF"/>
                <rect y="11" width="24" height="1" fill="#CE1126"/>
                <rect y="12" width="24" height="4" fill="#43B02A"/>
            </svg>
            @break
        @case('ru')
            {{-- Rossiya --}}
            <svg viewBox="0 0 24 16" width="24" height="16" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="5.33" fill="#FFFFFF"/>
                <rect y="5.33" width="24" height="5.33" fill="#0039A6"/>
                <rect y="10.66" width="24" height="5.34" fill="#D52B1E"/>
            </svg>
            @break
        @case('en')
            {{-- Buyuk Britaniya (ingliz tili uchun keng qabul qilingan) --}}
            <svg viewBox="0 0 60 30" width="24" height="12" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
                <path fill="#012169" d="M0,0h60v30H0z"/>
                <path d="M0,0L60,30M60,0L0,30" stroke="#FFF" stroke-width="6"/>
                <path d="M0,0L60,30M60,0L0,30" stroke="#C8102E" stroke-width="4"/>
                <path d="M30,0v30M0,15h60" stroke="#FFF" stroke-width="10"/>
                <path d="M30,0v30M0,15h60" stroke="#C8102E" stroke-width="6"/>
            </svg>
            @break
        @case('ja')
            {{-- Yaponiya --}}
            <svg viewBox="0 0 24 16" width="24" height="16" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="16" fill="#FFFFFF"/>
                <circle cx="12" cy="8" r="4.25" fill="#BC002D"/>
            </svg>
            @break
        @default
            <svg viewBox="0 0 24 16" width="24" height="16" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="16" rx="2" fill="#e4e3e3"/>
            </svg>
    @endswitch
</span>
