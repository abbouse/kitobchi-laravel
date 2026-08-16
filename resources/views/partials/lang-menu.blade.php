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
{{--
    MUHIM (piyolamarket'ga moslashtirish): piyolamarket.uz'ning til
    menyusi juda sodda — soyasi yengil, qatorlar orasida rang farqi yo'q
    (faqat hover'da och kulrang), tanlangan til uchun faqat belgicha (✓)
    o'ng chetda. Avval bu yerda tanlangan til uchun butun qator yashil
    fonga (--color-tima-50) bo'yalardi — bu piyoladan farq qilardi, endi
    olib tashlandi. Bayroqchalar (foydalanuvchi alohida so'ragan: "flag
    ikonkalarini top va yumaloq qilib chiroyli joylashtir") saqlab
    qolindi — piyolada yo'q, lekin bu ataylab qo'shilgan farq.
--}}
<div id="{{ $menuId }}" class="kc-lang-menu kc-lang-menu--right" style="display:none;min-width:180px;background:#fff;border-radius:0.875rem;box-shadow:0 8px 24px rgba(15,23,42,0.12);z-index:200;overflow:hidden;padding:0.375rem;">
    @foreach(config('landing_locales.codes', ['uz']) as $langCode)
        <a href="{{ route('locale.switch', ['locale' => $langCode, 'redirect' => request()->getRequestUri()]) }}"
           style="display:flex;align-items:center;gap:0.625rem;padding:0.5625rem 0.625rem;border-radius:0.5rem;text-decoration:none;font-size:0.875rem;transition:background 0.15s;color:#111827;{{ app()->getLocale() === $langCode ? 'font-weight:700;' : 'font-weight:500;' }}"
           onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <img src="https://cdn.jsdelivr.net/gh/hatscripts/circle-flags@gh-pages/flags/{{ ['uz' => 'uz', 'ru' => 'ru', 'en' => 'us', 'ja' => 'jp'][$langCode] ?? 'uz' }}.svg"
                 alt="" width="18" height="18" style="width:18px;height:18px;border-radius:9999px;flex-shrink:0;object-fit:cover;" loading="lazy">
            <span style="flex:1;">{{ config('landing_locales.labels')[$langCode] ?? strtoupper($langCode) }}</span>
            @if(app()->getLocale() === $langCode)
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" style="flex-shrink:0;color:var(--color-tima-500,#10b981);"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
            @endif
        </a>
    @endforeach
</div>
