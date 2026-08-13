{{-- Til almashtirish dropdown paneli. $menuId — trigger tugma bilan bog'lash uchun unikal id. --}}
<div id="{{ $menuId }}" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 10px);right:0;min-width:170px;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;overflow:hidden;padding:0.375rem;">
    @foreach(config('landing_locales.codes', ['uz']) as $langCode)
        <a href="{{ route('locale.switch', ['locale' => $langCode, 'redirect' => request()->getRequestUri()]) }}"
           style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.625rem 0.75rem;border-radius:0.625rem;text-decoration:none;font-size:0.875rem;transition:background 0.15s;{{ app()->getLocale() === $langCode ? 'background:var(--color-tima-50);font-weight:700;color:var(--color-tima-600);' : 'font-weight:500;color:#111827;' }}"
           onmouseover="if(!this.style.background.includes('tima'))this.style.background='#f9fafb'" onmouseout="if(!this.style.background.includes('tima'))this.style.background='transparent'">
            {{ config('landing_locales.labels')[$langCode] ?? strtoupper($langCode) }}
            @if(app()->getLocale() === $langCode)
                <iconify-icon icon="lucide:check" class="w-4 h-4"></iconify-icon>
            @endif
        </a>
    @endforeach
</div>
