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
    <div class="kc-bottomsheet-sheet">
        <!-- Drag Handle Pill -->
        <div class="kc-bottomsheet-pill"></div>

        <!-- App Icon Badge -->
        <div class="kc-app-icon-wrap">
            <div class="kc-app-icon">
                <svg viewBox="0 0 512 512" class="kc-app-icon-svg" fill="currentColor">
                    <!-- Book Spine (Left Stem of K) -->
                    <path d="M 132 88 C 114 88, 100 102, 98 120 C 84 196, 84 316, 98 392 C 100 410, 114 424, 132 424 C 148 424, 160 410, 156 392 C 144 316, 144 196, 156 120 C 160 102, 148 88, 132 88 Z" fill="#FFFFFF"/>
                    <!-- Upper Wing -->
                    <path d="M 150 256 C 150 182, 206 112, 312 88 C 358 76, 420 82, 420 126 C 420 166, 338 216, 258 237 C 210 248, 178 256, 150 256 Z" fill="#FFFFFF"/>
                    <!-- Lower Wing -->
                    <path d="M 150 256 C 178 256, 210 264, 258 275 C 338 296, 420 346, 420 386 C 420 430, 358 436, 312 424 C 206 400, 150 330, 150 256 Z" fill="#FFFFFF"/>
                </svg>
            </div>
        </div>

        <!-- Title & Subtitle -->
        <h3 class="kc-app-title">Kitobchi ilovasini yuklab oling</h3>
        <p class="kc-app-desc">Mahsulotlarni tezroq ko‘ring, buyurtma bering va aksiyalardan birinchi bo‘lib xabardor bo‘ling.</p>

        <!-- OS-Specific Store Button (Main Action Card) -->
        <a id="kcStoreBtn" href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener noreferrer" class="kc-store-card">
            <div class="kc-store-left">
                <div class="kc-store-icon-box">
                    <!-- Apple SVG for iOS -->
                    <svg id="kcAppleIcon" class="kc-store-icon-svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.87-.93.04-2.01.63-2.65 1.38-.56.65-1.06 1.71-.92 2.74 1.04.08 2.05-.53 2.65-1.25z"/>
                    </svg>
                    <!-- Google Play SVG for Android -->
                    <svg id="kcPlayIcon" class="kc-store-icon-svg" style="display:none;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.609 1.814L13.792 12 3.61 22.186a1.98 1.98 0 0 1-.61-.924V2.738c.15-.36.368-.68.61-.924zm11.242 11.244l2.585 2.586-11.83 6.83 9.245-9.416zm0-2.116L5.606 1.526l11.83 6.83-2.585 2.586zm1.488 1.058l3.433 1.982a1.2 1.2 0 0 1 0 2.036l-3.433 1.982-2.43-2.43 2.43-2.43z"/>
                    </svg>
                </div>
                <div class="kc-store-meta">
                    <span class="kc-store-sub">Yuklab olish</span>
                    <span id="kcStoreName" class="kc-store-name">App Store</span>
                </div>
            </div>
            <!-- External ↗ icon -->
            <div class="kc-store-arrow">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </div>
        </a>

        <!-- Secondary "Keyinroq" Button -->
        <button id="kcAppDownloadDismissBtn" type="button" class="kc-dismiss-btn">
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
        const storeName = document.getElementById('kcStoreName');
        const appleIcon = document.getElementById('kcAppleIcon');
        const playIcon = document.getElementById('kcPlayIcon');

        if (!modal) return;

        if (isApple) {
            storeBtn.href = APP_STORE_URL;
            storeName.textContent = 'App Store';
            appleIcon.style.display = 'block';
            playIcon.style.display = 'none';
        } else {
            storeBtn.href = PLAY_STORE_URL;
            storeName.textContent = 'Google Play';
            appleIcon.style.display = 'none';
            playIcon.style.display = 'block';
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