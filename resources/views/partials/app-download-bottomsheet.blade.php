{{--
    Piyola Market uslubidagi Mobil Ilova Yuklab Olish BottomSheet komponenti.
    - Faqat mobil qurilmalarda chiqadi (ekran <= 768px).
    - Foydalanuvchi qurilmasini (iPhone/iOS vs Android) aniqlab, mos ravishda
      App Store yoki Google Play havolasini chiqaradi.
    - Bir kunda 1 marta chiqadi (localStorage orqali 24 soatlik cooldown).
--}}
<div id="kcAppDownloadModal" class="kc-bottomsheet-wrapper" style="display:none;" aria-hidden="true" role="dialog">
    <!-- Dark Backdrop -->
    <div id="kcAppDownloadBackdrop" class="kc-bottomsheet-backdrop"></div>

    <!-- Bottom Sheet Container -->
    <div class="kc-bottomsheet-sheet catalog-filter-panel">
        <!-- Drag Handle Pill -->
        <div class="flex justify-center pt-1 pb-4">
            <div class="w-10 h-1 rounded-full bg-neutral-300" style="width:40px;height:4px;background:#d1d5db;border-radius:999px;margin:0 auto 1rem auto;"></div>
        </div>

        <!-- App Icon Badge (Exact 1:1 Favicon Asset) -->
        <div class="kc-app-icon-wrap" style="display:flex;justify-content:center;margin-bottom:0.75rem;">
            <img src="{{ asset('favicon.svg') }}" alt="Kitobchi App" class="kc-app-icon" width="64" height="64" style="width:64px;height:64px;border-radius:18px;box-shadow:0 8px 24px rgba(33,120,215,0.25);object-fit:cover;">
        </div>

        <!-- Title & Subtitle -->
        <h3 class="kc-app-title" style="font-size:1.25rem;font-weight:700;text-align:center;color:#111827;margin:0 0 0.5rem 0;">Kitobchi ilovasini yuklab oling</h3>
        <p class="kc-app-desc" style="font-size:0.875rem;color:#6b7280;text-align:center;line-height:1.45;max-width:300px;margin:0 auto 1.5rem auto;">Mahsulotlarni tezroq ko‘ring, oson buyurtma bering va aksiyalardan birinchi bo‘lib xabardor bo‘ling.</p>

        <!-- OS-Specific Store Button (Main Action Card) -->
        <a id="kcStoreBtn" href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener noreferrer" class="kc-store-card" style="background:#f5f6f8;border:1px solid #e5e7eb;border-radius:18px;padding:0.875rem 1rem;display:flex;align-items:center;justify-content:space-between;text-decoration:none;margin-bottom:0.75rem;">
            <div class="kc-store-left" style="display:flex;align-items:center;gap:0.875rem;">
                <!-- Apple Box (Official Imported SVG) -->
                <div id="kcAppleBox" class="kc-store-icon-box" style="width:48px;height:48px;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="{{ asset('images/icons/app-store.svg') }}" alt="Apple App Store" width="48" height="48" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <!-- Google Play Box (Official Imported SVG) -->
                <div id="kcPlayBox" class="kc-store-icon-box" style="width:48px;height:48px;border-radius:14px;overflow:hidden;background:#fff;border:1px solid #e5e7eb;display:none;align-items:center;justify-content:center;padding:6px;">
                    <img src="{{ asset('images/icons/google-play.svg') }}" alt="Google Play Store" width="36" height="36" style="width:100%;height:100%;object-fit:contain;">
                </div>

                <div class="kc-store-meta" style="text-align:left;">
                    <span class="kc-store-sub" style="font-size:0.75rem;color:#6b7280;display:block;">Yuklab olish</span>
                    <span id="kcStoreName" class="kc-store-name" style="font-size:1rem;font-weight:700;color:#111827;display:block;">App Store da ochish</span>
                </div>
            </div>
            <!-- External ↗ icon -->
            <div class="kc-store-arrow" style="color:#9ca3af;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"/>
                </svg>
            </div>
        </a>

        <!-- Secondary "Keyinroq" Button -->
        <button id="kcAppDownloadDismissBtn" type="button" class="kc-dismiss-btn" style="width:100%;padding:0.875rem;border-radius:18px;background:#e8eaef;color:#0b0342;font-weight:600;font-size:0.9375rem;border:none;cursor:pointer;">
            Keyinroq
        </button>
    </div>
</div>

<style>
/* BottomSheet Wrapper Styles */
.kc-bottomsheet-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

@media (min-width: 769px) {
    .kc-bottomsheet-wrapper {
        display: none !important;
    }
}

.kc-bottomsheet-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.52);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.kc-bottomsheet-sheet {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border-top-left-radius: 32px;
    border-top-right-radius: 32px;
    padding: 1.5rem 1.5rem calc(2rem + env(safe-area-inset-bottom, 1rem)) 1.5rem;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
    z-index: 10;
    transform: translateY(100%);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.kc-bottomsheet-wrapper.kc-open .kc-bottomsheet-backdrop {
    opacity: 1;
}

.kc-bottomsheet-wrapper.kc-open .kc-bottomsheet-sheet {
    transform: translateY(0);
}

.kc-bottomsheet-pill {
    width: 48px;
    height: 5px;
    background-color: #e5e7eb;
    border-radius: 999px;
    margin: 0 auto 1.25rem auto;
}

.kc-app-icon-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.kc-app-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #469EF8 0%, #2178D7 100%);
    padding: 10px;
    box-shadow: 0 8px 20px rgba(33, 120, 215, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
}

.kc-app-icon-svg {
    width: 100%;
    height: 100%;
    color: #ffffff;
}

.kc-app-title {
    font-size: 1.25rem;
    font-weight: 700;
    text-align: center;
    color: #111827;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.01em;
}

.kc-app-desc {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
    line-height: 1.45;
    max-width: 300px;
    margin: 0 auto 1.5rem auto;
}

.kc-store-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    border: 2px solid #111827;
    border-radius: 18px;
    padding: 0.875rem 1rem;
    margin-bottom: 0.75rem;
    text-decoration: none;
    color: #111827;
    background: #ffffff;
    box-sizing: border-box;
    transition: transform 0.15s ease, background-color 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}

.kc-store-card:active {
    transform: scale(0.99);
    background-color: #f9fafb;
}

.kc-store-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.kc-store-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background-color: #111827;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.kc-store-icon-svg {
    width: 24px;
    height: 24px;
}

.kc-store-meta {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.kc-store-sub {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    line-height: 1.1;
}

.kc-store-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
    margin-top: 2px;
}

.kc-store-arrow {
    color: #9ca3af;
    display: flex;
    align-items: center;
    padding-right: 4px;
}

.kc-dismiss-btn {
    width: 100%;
    padding: 0.875rem;
    border-radius: 18px;
    background-color: #f3f4f6;
    color: #374151;
    font-size: 0.9375rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background-color 0.15s ease, transform 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}

.kc-dismiss-btn:active {
    background-color: #e5e7eb;
    transform: scale(0.99);
}
</style>

<script>
(function() {
    const STORAGE_KEY = 'kc_app_download_prompt_dismissed_at';
    const ONE_DAY_MS = 24 * 60 * 60 * 1000;

    const APP_STORE_URL = 'https://apps.apple.com/uz/app/kitobchi/id6753818078';
    const PLAY_STORE_URL = 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi';

    function initAppDownloadModal() {
        if (window.innerWidth > 768) return;

        const ua = navigator.userAgent || navigator.vendor || '';
        const isApple = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isAndroid = /Android/.test(ua);

        try {
            const lastDismissed = localStorage.getItem(STORAGE_KEY);
            if (lastDismissed) {
                const ts = parseInt(lastDismissed, 10);
                if (!isNaN(ts) && Date.now() - ts < ONE_DAY_MS) {
                    return;
                }
            }
        } catch (e) {}

        const modal = document.getElementById('kcAppDownloadModal');
        const backdrop = document.getElementById('kcAppDownloadBackdrop');
        const dismissBtn = document.getElementById('kcAppDownloadDismissBtn');
        const storeBtn = document.getElementById('kcStoreBtn');
        const appleBox = document.getElementById('kcAppleBox');
        const playBox = document.getElementById('kcPlayBox');

        if (!modal) return;

        if (isApple) {
            storeBtn.href = APP_STORE_URL;
            storeName.textContent = 'App Store da ochish';
            if (appleBox) appleBox.style.display = 'flex';
            if (playBox) playBox.style.display = 'none';
        } else {
            storeBtn.href = PLAY_STORE_URL;
            storeName.textContent = 'Google Play da ochish';
            if (appleBox) appleBox.style.display = 'none';
            if (playBox) playBox.style.display = 'flex';
        }

        setTimeout(() => {
            modal.style.display = 'flex';
            modal.offsetHeight;
            modal.classList.add('kc-open');
        }, 1200);

        function closeModal() {
            modal.classList.remove('kc-open');
            try {
                localStorage.setItem(STORAGE_KEY, String(Date.now()));
            } catch (e) {}
            setTimeout(() => {
                modal.style.display = 'none';
            }, 350);
        }

        if (backdrop) backdrop.addEventListener('click', closeModal);
        if (dismissBtn) dismissBtn.addEventListener('click', closeModal);
        if (storeBtn) storeBtn.addEventListener('click', closeModal);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAppDownloadModal);
    } else {
        initAppDownloadModal();
    }
})();
</script>