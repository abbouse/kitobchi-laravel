{{--
    Til almashtirish dropdown paneli. $menuId — trigger tugma bilan bog'lash
    uchun unikal id.

    MUHIM: position/top/right endi inline style'da YO'Q — buni endi JS
    (kcPositionDropdown, layouts/marketplace.blade.php) trigger tugmaning
    haqiqiy ekrandagi joylashuviga qarab position:fixed bilan hisoblaydi
    (ota elementning overflow:hidden/auto bo'lishidan qat'iy nazar
    kesilib qolmasligi uchun). "kc-lang-menu--right" klassi shu JS'ga
    "tugmaning o'ng chetiga tekislab och" deb ko'rsatadi.
--}}
<div id="{{ $menuId }}" class="kc-lang-menu kc-lang-menu--right" style="display:none;min-width:190px;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;overflow:hidden;padding:0.375rem;">
    @foreach(config('landing_locales.codes', ['uz']) as $langCode)
        <a href="{{ route('locale.switch', ['locale' => $langCode, 'redirect' => request()->getRequestUri()]) }}"
           style="display:flex;align-items:center;gap:0.625rem;padding:0.625rem 0.75rem;border-radius:0.625rem;text-decoration:none;font-size:0.875rem;transition:background 0.15s;{{ app()->getLocale() === $langCode ? 'background:var(--color-tima-50);font-weight:700;color:var(--color-tima-600);' : 'font-weight:500;color:#111827;' }}"
           onmouseover="if(!this.style.background.includes('tima'))this.style.background='#f9fafb'" onmouseout="if(!this.style.background.includes('tima'))this.style.background='transparent'">
            <img src="https://cdn.jsdelivr.net/gh/hatscripts/circle-flags@gh-pages/flags/{{ ['uz' => 'uz', 'ru' => 'ru', 'en' => 'us', 'ja' => 'jp'][$langCode] ?? 'uz' }}.svg"
                 alt="" width="20" height="20" style="width:20px;height:20px;border-radius:9999px;flex-shrink:0;object-fit:cover;" loading="lazy">
            <span style="flex:1;">{{ config('landing_locales.labels')[$langCode] ?? strtoupper($langCode) }}</span>
            @if(app()->getLocale() === $langCode)
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
            @endif
        </a>
    @endforeach
</div>
