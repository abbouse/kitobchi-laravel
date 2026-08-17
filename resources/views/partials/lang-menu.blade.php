<div id="{{ $menuId }}" class="kc-lang-menu kc-lang-menu--right" style="display:none;min-width:160px;background:#fff;border-radius:1rem;box-shadow:0 10px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.1);z-index:200;overflow:hidden;padding:0.375rem;border:1px solid #f1f5f9;">
    @foreach(config('landing_locales.codes', ['uz', 'ru']) as $langCode)
        <a href="{{ route('locale.switch', ['locale' => $langCode, 'redirect' => request()->getRequestUri()]) }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm transition-colors text-neutral-800 hover:bg-neutral-50 {{ app()->getLocale() === $langCode ? 'font-bold' : 'font-medium' }}"
           style="text-decoration:none;">
            <img src="https://cdn.jsdelivr.net/gh/hatscripts/circle-flags@gh-pages/flags/{{ ['uz' => 'uz', 'ru' => 'ru'][$langCode] ?? 'uz' }}.svg"
                 alt="" width="18" height="18" style="width:18px;height:18px;border-radius:9999px;flex-shrink:0;object-fit:cover;" loading="lazy">
            <span style="flex:1;">{{ config('landing_locales.labels')[$langCode] ?? strtoupper($langCode) }}</span>
            @if(app()->getLocale() === $langCode)
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-tima-500,#10b981);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
            @endif
        </a>
    @endforeach
</div>
