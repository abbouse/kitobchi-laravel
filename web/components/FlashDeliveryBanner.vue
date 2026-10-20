<template>
  <!-- Banner faqat aksiya enabled bo'lganda va sahifa ruxsat berganda chiqadi -->
  <Transition name="flash-slide">
    <div
      v-if="show"
      class="flash-delivery-banner"
      :class="{ 'flash-active': isActive, 'flash-upcoming': !isActive }"
      role="banner"
      aria-live="polite"
    >
      <!-- Close button -->
      <button class="flash-close" aria-label="Yopish" @click="dismiss">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
          <path d="M4.293 4.293a1 1 0 011.414 0L8 6.586l2.293-2.293a1 1 0 111.414 1.414L9.414 8l2.293 2.293a1 1 0 01-1.414 1.414L8 9.414l-2.293 2.293a1 1 0 01-1.414-1.414L6.586 8 4.293 5.707a1 1 0 010-1.414z"/>
        </svg>
      </button>

      <div class="flash-inner">
        <!-- Chap: ikon + matn -->
        <div class="flash-left">
          <div class="flash-icon-wrap">
            <AppEmoji name="rocket" :size="28" class="flash-icon" />
            <span v-if="isActive" class="flash-pulse-ring" />
          </div>
          <div class="flash-text">
            <template v-if="isActive">
              <p class="flash-title">Yetkazish <strong>BEPUL!</strong></p>
              <p class="flash-sub">Hoziroq buyurtma bering — faqat {{ timerLabel }} qoldi</p>
            </template>
            <template v-else>
              <p class="flash-title">Bepul yetkazish boshlangunicha</p>
              <p class="flash-sub">Tayyor turing, aksiya tez boshlanadi!</p>
            </template>
          </div>
        </div>

        <!-- O'ng: taymer + tugma -->
        <div class="flash-right">
          <div class="flash-timer" :class="{ urgent: isActive && remaining <= 60 }">
            <div class="flash-digit-group">
              <span class="flash-digit">{{ mm }}</span>
              <span class="flash-digit-label">min</span>
            </div>
            <span class="flash-colon">:</span>
            <div class="flash-digit-group">
              <span class="flash-digit">{{ ss }}</span>
              <span class="flash-digit-label">son</span>
            </div>
          </div>

          <NuxtLink v-if="isActive" to="/cart" class="flash-btn" @click="copyPromo">
            Buyurtma qil
          </NuxtLink>
        </div>
      </div>

      <!-- Progress bar (aktiv paytda) -->
      <div v-if="isActive" class="flash-progress">
        <div class="flash-progress-fill" :style="{ width: progressPct + '%' }" />
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
interface FlashStatus {
  enabled: boolean
  active: boolean
  promo_code: string | null
  seconds_remaining: number
  seconds_until_next: number | null
  start_time?: string
  duration_minutes?: number
}

const config = useRuntimeConfig()
const route = useRoute()

const hiddenPaths = ['/checkout', '/profile/edit', '/profile/address']
const isHiddenPage = computed(() =>
  hiddenPaths.some(p => route.path.startsWith(p))
)

const status = ref<FlashStatus | null>(null)
const dismissed = ref(false)
const remaining = ref(0)
let intervalId: ReturnType<typeof setInterval> | null = null
let pollId: ReturnType<typeof setInterval> | null = null

async function fetchStatus() {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/flash-delivery/status`, { timeout: 5000 })
    const s: FlashStatus = res?.data ?? res
    status.value = s
    if (s.enabled) {
      remaining.value = s.active ? s.seconds_remaining : (s.seconds_until_next ?? 0)
    }
  } catch {
    status.value = null
  }
}

function startTimer() {
  if (intervalId) clearInterval(intervalId)
  intervalId = setInterval(() => {
    if (remaining.value <= 1) {
      remaining.value = 0
      fetchStatus()
    } else {
      remaining.value--
    }
  }, 1000)
}

const isActive   = computed(() => status.value?.active ?? false)
const promoCode  = computed(() => status.value?.promo_code ?? null)
const totalSec   = computed(() => (status.value?.duration_minutes ?? 15) * 60)

const mm = computed(() => String(Math.floor(remaining.value / 60)).padStart(2, '0'))
const ss = computed(() => String(remaining.value % 60).padStart(2, '0'))
const timerLabel = computed(() => `${mm.value}:${ss.value}`)

const progressPct = computed(() => {
  if (!isActive.value || totalSec.value <= 0) return 0
  return Math.max(0, Math.min(100, (remaining.value / totalSec.value) * 100))
})

const show = computed(() =>
  !dismissed.value &&
  !isHiddenPage.value &&
  status.value?.enabled === true &&
  remaining.value > 0
)

function dismiss() {
  dismissed.value = true
  if (intervalId) clearInterval(intervalId)
  if (pollId) clearInterval(pollId)
}

function copyPromo() {
  if (promoCode.value) {
    navigator.clipboard?.writeText(promoCode.value).catch(() => {})
  }
}

onMounted(async () => {
  await fetchStatus()
  if (status.value?.enabled) {
    startTimer()
    pollId = setInterval(fetchStatus, 5 * 60 * 1000)
  }
})

onBeforeUnmount(() => {
  if (intervalId) clearInterval(intervalId)
  if (pollId) clearInterval(pollId)
})
</script>

<style scoped>
.flash-slide-enter-active,
.flash-slide-leave-active {
  transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}
.flash-slide-enter-from,
.flash-slide-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.flash-delivery-banner {
  position: fixed;
  bottom: 84px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 38;
  width: calc(100% - 2rem);
  max-width: 600px;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.22), 0 2px 8px rgba(0,0,0,0.14);
}

.flash-active {
  background: linear-gradient(135deg, #0f4c2a 0%, #166534 40%, #15803d 100%);
  border: 1.5px solid rgba(134, 239, 172, 0.3);
}
.flash-upcoming {
  background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #1d4ed8 100%);
  border: 1.5px solid rgba(147, 197, 253, 0.25);
}

.flash-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px 12px;
}

.flash-left { display: flex; align-items: center; gap: 10px; min-width: 0; }

.flash-icon-wrap { position: relative; flex-shrink: 0; }
.flash-icon { font-size: 28px; line-height: 1; display: block; }
.flash-pulse-ring {
  position: absolute; inset: -4px; border-radius: 50%;
  border: 2px solid rgba(134, 239, 172, 0.6);
  animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}
@keyframes pulse-ring {
  0%   { transform: scale(0.8); opacity: 1; }
  80%, 100% { transform: scale(1.5); opacity: 0; }
}

.flash-text { min-width: 0; }
.flash-title { font-size: 14px; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; white-space: nowrap; }
.flash-title strong { color: #86efac; }
.flash-sub { font-size: 11px; color: rgba(255,255,255,0.75); margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.flash-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.flash-timer {
  display: flex; align-items: center; gap: 3px;
  background: rgba(0,0,0,0.2); border-radius: 12px; padding: 5px 10px;
}
.flash-timer.urgent {
  background: rgba(220,38,38,0.35);
  animation: urgent-pulse 0.8s ease-in-out infinite alternate;
}
@keyframes urgent-pulse {
  from { background: rgba(220,38,38,0.25); }
  to   { background: rgba(220,38,38,0.5); }
}

.flash-digit-group { display: flex; flex-direction: column; align-items: center; }
.flash-digit {
  font-size: 20px; font-weight: 800; color: #fff;
  font-variant-numeric: tabular-nums; line-height: 1; min-width: 24px; text-align: center;
}
.flash-digit-label { font-size: 9px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.05em; line-height: 1; margin-top: 1px; }
.flash-colon {
  font-size: 20px; font-weight: 800; color: rgba(255,255,255,0.7); margin-bottom: 10px;
  animation: blink-colon 1s step-end infinite;
}
@keyframes blink-colon {
  0%, 100% { opacity: 1; } 50% { opacity: 0; }
}

.flash-btn {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 8px 14px; border-radius: 12px; background: #fff; color: #166534;
  font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap;
  transition: background 0.15s, transform 0.1s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.flash-btn:active { transform: scale(0.96); background: #f0fdf4; }

.flash-progress { height: 3px; background: rgba(255,255,255,0.12); }
.flash-progress-fill { height: 100%; background: linear-gradient(90deg, #4ade80, #86efac); transition: width 1s linear; }

.flash-close {
  position: absolute; top: 8px; right: 10px; padding: 4px;
  color: rgba(255,255,255,0.55); background: none; border: none; cursor: pointer;
  line-height: 0; border-radius: 50%; transition: color 0.15s, background 0.15s;
}
.flash-close:hover { color: #fff; background: rgba(255,255,255,0.1); }

@media (min-width: 768px) {
  .flash-delivery-banner {
    bottom: 24px; left: auto; right: 24px; transform: none; width: 380px; max-width: 380px;
  }
}
</style>
