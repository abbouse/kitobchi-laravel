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
              <!-- Official Apple App Store Icon Image -->
              <div
                v-if="isIOS"
                class="w-12 h-12 rounded-2xl overflow-hidden shrink-0 shadow-xs flex items-center justify-center"
              >
                <img
                  src="/images/icons/app-store.svg"
                  alt="Apple App Store"
                  class="w-full h-full object-cover"
                  width="48"
                  height="48"
                />
              </div>

              <!-- Official Google Play Store Icon Image -->
              <div
                v-else
                class="w-12 h-12 rounded-2xl overflow-hidden shrink-0 shadow-xs flex items-center justify-center bg-white border border-gray-200/80 p-2"
              >
                <img
                  src="/images/icons/google-play.svg"
                  alt="Google Play Store"
                  class="w-full h-full object-contain"
                  width="48"
                  height="48"
                />
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
