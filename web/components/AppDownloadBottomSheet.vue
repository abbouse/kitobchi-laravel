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
        <!-- Dark Backdrop -->
        <div
          class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
          @click="dismiss"
        ></div>

        <!-- Sheet Container -->
        <div
          class="relative w-full max-w-lg bg-white rounded-t-[32px] p-6 pb-8 shadow-2xl z-10 transform transition-transform duration-300 ease-out"
          style="padding-bottom: max(2rem, env(safe-area-inset-bottom, 2rem))"
        >
          <!-- Drag Handle Pill -->
          <div class="w-12 h-1.5 bg-gray-300/80 rounded-full mx-auto mb-5"></div>

          <!-- App Icon Badge (Exact 1:1 Favicon Asset) -->
          <div class="flex justify-center mb-4">
            <img
              src="/favicon.svg"
              alt="Kitobchi App"
              class="w-16 h-16 rounded-2xl shadow-lg shadow-[#2178D7]/25 object-cover"
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
          <p class="text-[14px] text-center text-gray-500 leading-relaxed max-w-xs mx-auto mb-6 font-['Urbanist',sans-serif]">
            Mahsulotlarni tezroq ko‘ring, buyurtma bering va aksiyalardan birinchi bo‘lib xabardor bo‘ling.
          </p>

          <!-- OS-Specific Store Button (Main Action Card) -->
          <a
            :href="storeLink"
            target="_blank"
            rel="noopener noreferrer"
            class="w-full border-2 border-neutral-900 rounded-2xl p-3.5 flex items-center justify-between hover:bg-neutral-50 active:scale-[0.99] transition mb-3 text-neutral-900 no-underline cursor-pointer"
            @click="onStoreClick"
          >
            <div class="flex items-center gap-3.5">
              <!-- Store Icon Container -->
              <div class="w-11 h-11 rounded-xl bg-neutral-900 text-white flex items-center justify-center shrink-0">
                <!-- Apple Icon for iOS -->
                <svg v-if="isIOS" class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                  <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.87-.93.04-2.01.63-2.65 1.38-.56.65-1.06 1.71-.92 2.74 1.04.08 2.05-.53 2.65-1.25z"/>
                </svg>
                <!-- Google Play Icon for Android / Other -->
                <svg v-else class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                  <path d="M3.609 1.814L13.792 12 3.61 22.186a1.98 1.98 0 0 1-.61-.924V2.738c.15-.36.368-.68.61-.924zm11.242 11.244l2.585 2.586-11.83 6.83 9.245-9.416zm0-2.116L5.606 1.526l11.83 6.83-2.585 2.586zm1.488 1.058l3.433 1.982a1.2 1.2 0 0 1 0 2.036l-3.433 1.982-2.43-2.43 2.43-2.43z"/>
                </svg>
              </div>

              <!-- Store Text Block -->
              <div class="text-left">
                <div class="text-[12px] font-medium text-gray-500 leading-tight">
                  Yuklab olish
                </div>
                <div class="text-[16px] font-bold text-neutral-900 leading-tight mt-0.5 font-['Urbanist',sans-serif]">
                  {{ isIOS ? "App Store" : "Google Play" }}
                </div>
              </div>
            </div>

            <!-- External Link Icon ↗ -->
            <div class="text-gray-400 pr-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
            </div>
          </a>

          <!-- Secondary "Keyinroq" (Dismiss) Button -->
          <button
            type="button"
            class="w-full py-3.5 rounded-2xl bg-gray-100 text-neutral-700 font-semibold text-[15px] hover:bg-gray-200 active:scale-[0.99] transition font-['Urbanist',sans-serif]"
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
