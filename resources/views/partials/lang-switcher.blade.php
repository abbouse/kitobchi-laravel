@php
    $codes = config('landing_locales.codes', ['uz']);
    $labels = config('landing_locales.labels', []);
    $current = app()->getLocale();
    $here = request()->getRequestUri();
@endphp
<details class="kc-lang">
    <summary class="kc-lang__summary" aria-label="{{ __('nav.lang_label') }}">
        <span class="kc-lang__surface">
            <span class="kc-lang__flag-wrap">
                @include('partials.flag-icon', ['code' => $current])
            </span>
            <span class="kc-lang__label">{{ $labels[$current] ?? strtoupper($current) }}</span>
            <span class="kc-lang__chev" aria-hidden="true">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 4.25L6 7.75l3.5-3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </span>
    </summary>
    <div class="kc-lang__panel" role="listbox" aria-label="{{ __('nav.lang_label') }}">
        @foreach ($codes as $code)
            @php
                $href = route('locale.switch', ['locale' => $code, 'redirect' => $here]);
                $isActive = $current === $code;
            @endphp
            <a href="{{ $href }}"
               class="kc-lang__option {{ $isActive ? 'kc-lang__option--active' : '' }}"
               @if($isActive) aria-current="true" @endif
               role="option">
                <span class="kc-lang__option-flag">@include('partials.flag-icon', ['code' => $code])</span>
                <span class="kc-lang__option-text">{{ $labels[$code] ?? strtoupper($code) }}</span>
                @if ($isActive)
                    <span class="kc-lang__check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 8.25L6.5 11.25L12.5 4.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</details>
