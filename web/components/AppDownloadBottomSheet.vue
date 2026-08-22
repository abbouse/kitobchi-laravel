<template>
  <Teleport to="body">
    <!-- Backdrop & BottomSheet Transition -->
    <Transition name="kc-bottomsheet">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center md:hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="app-download-title"
      >
        <!-- Dark Backdrop (matching CatalogFilterDrawer) -->
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
          @click="dismiss"
        ></div>

        <!-- Sheet Container (matching CatalogFilterDrawer rounded-t-3xl) -->
        <div
          class="catalog-filter-panel relative w-full max-w-lg bg-white rounded-t-3xl p-6 pb-8 shadow-2xl z-10 transform transition-transform duration-300 ease-out"
          style="padding-bottom: max(2rem, env(safe-area-inset-bottom, 2rem))"
        >
          <!-- Drag Handle Pill (matching CatalogFilterDrawer) -->
          <div class="flex justify-center pt-1 pb-4">
            <div class="w-10 h-1 rounded-full bg-neutral-300"></div>
          </div>

          <!-- App Icon Badge (Exact 1:1 Favicon Asset) -->
          <div class="flex justify-center mb-3">
            <img
              src="/favicon.svg"
              alt="Kitobchi App"
              class="w-16 h-16 rounded-2xl shadow-lg shadow-[#2178D7]/20 object-cover"
              width="64"
              height="64"
            />
          </div>

          <!-- Title & Subtitle -->
          <h3
            id="app-download-title"
            class="text-[20px] font-bold text-center text-neutral-900 tracking-tight mb-2 font-['Urbanist',sans-serif]"
          >
            Kitobchi ilovasini yuklab oling
          </h3>
          <p class="text-[14px] text-center text-neutral-500 leading-relaxed max-w-xs mx-auto mb-6 font-['Urbanist',sans-serif]">
            Mahsulotlarni tezroq ko‘ring, oson buyurtma bering va aksiyalardan birinchi bo‘lib xabardor bo‘ling.
          </p>

          <!-- OS-Specific Store Button (Main Action Card) -->
          <a
            :href="storeLink"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full bg-[#F6F6F9] hover:bg-[#ECECEF] border border-neutral-200/80 rounded-2xl p-3.5 flex items-center justify-between transition-all duration-200 active:scale-[0.99] mb-3 text-neutral-900 no-underline cursor-pointer shadow-xs"
            @click="onStoreClick"
          >
            <div class="flex items-center gap-3.5">
              <!-- Official Apple App Store Container -->
              <div
                v-if="isIOS"
                class="w-12 h-12 rounded-2xl bg-black text-white flex items-center justify-center shrink-0 shadow-sm p-2.5"
              >
                <svg class="w-7 h-7 fill-white" viewBox="0 0 24 24">
                  <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.87-.93.04-2.01.63-2.65 1.38-.56.65-1.06 1.71-.92 2.74 1.04.08 2.05-.53 2.65-1.25z"/>
                </svg>
              </div>

              <!-- Official Google Play Container (High-Resolution Vector Gradient) -->
              <div
                v-else
                class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center shrink-0 border border-gray-200/80 shadow-sm p-2"
              >
                <svg class="w-7 h-7" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <defs>
                    <linearGradient id="gplay_grad1" x1="262.9" y1="266.3" x2="18.9" y2="22.3" gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-color="#00A0FF"/>
                      <stop offset="0.26" stop-color="#00BEFF"/>
                      <stop offset="0.76" stop-color="#00DFFF"/>
                      <stop offset="1" stop-color="#00E3FF"/>
                    </linearGradient>
                    <linearGradient id="gplay_grad2" x1="324.9" y1="289.4" x2="89.1" y2="525.2" gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-color="#FF3A44"/>
                      <stop offset="1" stop-color="#C31162"/>
                    </linearGradient>
                    <linearGradient id="gplay_grad3" x1="89.1" y1="-13.2" x2="324.9" y2="222.6" gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-color="#32A071"/>
                      <stop offset="0.48" stop-color="#15CF74"/>
                      <stop offset="1" stop-color="#00F076"/>
                    </linearGradient>
                    <linearGradient id="gplay_grad4" x1="492.5" y1="256" x2="252.3" y2="256" gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-color="#FFE000"/>
                      <stop offset="0.41" stop-color="#FFBD00"/>
                      <stop offset="0.78" stop-color="#FFA500"/>
                      <stop offset="1" stop-color="#FF9C00"/>
                    </linearGradient>
                  </defs>
                  <path fill="url(#gplay_grad1)" d="M53.9 35.7C48.2 41.4 45 49.3 45 58v396c0 8.7 3.2 16.6 8.9 22.3l212.2-228.4L53.9 35.7z"/>
                  <path fill="url(#gplay_grad2)" d="M352.4 334.2l-86.3-86.3L53.9 476.3c4.2 4.2 9.9 6.7 16.1 6.7h234.7c18.7 0 35.6-9.8 44.8-25.5l2.9-5.3 0-118z"/>
                  <path fill="url(#gplay_grad3)" d="M352.4 177.8L355.3 172.5c-9.2-15.7-26.1-25.5-44.8-25.5H70c-6.2 0-11.9 2.5-16.1 6.7l212.2 228.4 86.3-86.3z"/>
                  <path fill="url(#gplay_grad4)" d="M460.9 230.5L352.4 177.8l-86.3 70.1 86.3 86.3 108.5-52.7c13.7-6.7 22.1-20.4 22.1-35.5s-8.4-28.8-22.1-35.5z"/>
                </svg>
              </div>

              <!-- Store Text Block -->
              <div class="text-left">
                <div class="text-[12px] font-medium text-neutral-500 leading-tight">
                  Yuklab olish
                </div>
                <div class="text-[16px] font-bold text-neutral-900 leading-tight mt-0.5 font-['Urbanist',sans-serif]">
                  {{ isIOS ? "App Store da ochish" : "Google Play da ochish" }}
                </div>
              </div>
            </div>

            <!-- External Link Icon ↗ -->
            <div class="text-neutral-400 pr-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"/>
              </svg>
            </div>
          </a>

          <!-- Secondary "Keyinroq" (Dismiss) Button -->
          <button
            type="button"
            class="w-full py-3.5 rounded-2xl bg-[#ECECEF] hover:bg-neutral-200 text-neutral-800 font-semibold text-[15px] active:scale-[0.99] transition font-['Urbanist',sans-serif] border-none cursor-pointer"
            @click="dismiss"
          >
            Keyinroq
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue"

const STORAGE_KEY = "kc_app_download_prompt_dismissed_at"
const ONE_DAY_MS = 24 * 60 * 60 * 1000 // 24 hours

const isOpen = ref(false)
const isIOS = ref(false)

const APP_STORE_URL = "https://apps.apple.com/uz/app/kitobchi/id6753818078"
const PLAY_STORE_URL = "https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi"

const storeLink = computed(() => {
  return isIOS.value ? APP_STORE_URL : PLAY_STORE_URL
})

function checkShouldShow() {
  if (typeof window === "undefined") return

  // Check screen width — only show on mobile devices (<= 768px)
  const isMobileViewport = window.innerWidth <= 768

  // Detect OS
  const ua = navigator.userAgent || navigator.vendor || ""
  const isApple = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1)
  isIOS.value = isApple

  const isAndroid = /Android/.test(ua)

  // Only trigger on mobile viewports/devices
  if (!isMobileViewport && !isApple && !isAndroid) {
    return
  }

  // Check 1-time per day frequency in localStorage
  try {
    const lastDismissed = localStorage.getItem(STORAGE_KEY)
    if (lastDismissed) {
      const timestamp = parseInt(lastDismissed, 10)
      if (!isNaN(timestamp) && Date.now() - timestamp < ONE_DAY_MS) {
        return // Already shown within last 24 hours
      }
    }
  } catch (e) {
    // If localStorage is unavailable, fail gracefully
  }

  // Smooth delayed appearance (1.2 seconds after load)
  setTimeout(() => {
    isOpen.value = true
  }, 1200)
}

function dismiss() {
  isOpen.value = false
  saveDismissTimestamp()
}

function onStoreClick() {
  saveDismissTimestamp()
  isOpen.value = false
}

function saveDismissTimestamp() {
  try {
    localStorage.setItem(STORAGE_KEY, String(Date.now()))
  } catch (e) {
    // Ignore storage quota or security errors
  }
}

onMounted(() => {
  checkShouldShow()
})
</script>

<style scoped>
/* BottomSheet Entrance & Exit Animations */
.kc-bottomsheet-enter-active,
.kc-bottomsheet-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.kc-bottomsheet-enter-from,
.kc-bottomsheet-leave-to {
  opacity: 0;
}

.kc-bottomsheet-enter-from > div:last-child,
.kc-bottomsheet-leave-to > div:last-child {
  transform: translateY(100%);
}

.backdrop-blur-xs {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}
</style>
