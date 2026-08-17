<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="authStore.isAuthModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <!-- Modal Card -->
      <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl relative auth-modal-pop-in">
        <!-- Orqaga qaytish (faqat kod bosqichida, piyoladagi kabi yuqori chapda) -->
        <button
          v-if="step === 'code'"
          type="button"
          @click="goBackToPhone"
          class="absolute top-4 left-4 w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <button
          type="button"
          @click="closeModal"
          class="absolute top-4 right-4 w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="text-center mb-6 pt-8">
          <h2 class="text-xl font-bold text-neutral-900 m-0">
            {{ step === 'phone' ? 'Kirish yoki profil yaratish' : 'SMS xabardagi kodni kiriting' }}
          </h2>
          <p v-if="step === 'code'" class="text-sm text-neutral-500 mt-1 mb-0!">
            Kodni +998 {{ phoneDisplay }} raqamiga jo‘natdik
          </p>
        </div>

        <!-- Step 1: Phone -->
        <form v-if="step === 'phone'" @submit.prevent="handleSendOtp" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Telefon raqamingiz</label>
            <div class="flex items-center rounded-2xl bg-secondary-100 px-4 py-3 border border-gray-200 focus-within:border-gray">
              <span class="text-sm font-semibold text-neutral-600 me-2">+998</span>
              <input
                ref="phoneInputEl"
                :value="phoneDisplay"
                @input="onPhoneInput"
                type="tel"
                inputmode="numeric"
                placeholder="90 123-45-67"
                required
                class="flex-1 bg-transparent border-none outline-none text-base text-neutral-900 font-medium"
              />
            </div>
          </div>

          <div v-if="errorMessage" class="text-xs text-red-500 font-medium">
            {{ errorMessage }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer disabled:opacity-75"
          >
            {{ loading ? 'Yuborilmoqda...' : 'Tasdiqlash kodini olish' }}
          </button>
        </form>

        <!-- Step 2: OTP Code -->
        <form v-else @submit.prevent="handleVerifyOtp" class="space-y-4">
          <div class="flex justify-center gap-2" dir="ltr">
            <input
              v-for="(_, i) in otpDigits"
              :key="i"
              :ref="el => setOtpRef(el, i)"
              :value="otpDigits[i]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              autocomplete="one-time-code"
              @input="onOtpInput(i, $event)"
              @keydown="onOtpKeydown(i, $event)"
              @paste="i === 0 ? onOtpPaste($event) : undefined"
              class="w-12 h-14 rounded-2xl bg-secondary-100 text-center text-2xl font-bold text-neutral-900 border border-gray-200 focus:border-primary/20 outline-none"
            />
          </div>

          <div v-if="errorMessage" class="text-xs text-red-500 font-medium text-center">
            {{ errorMessage }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer disabled:opacity-75"
          >
            {{ loading ? 'Tekshirilmoqda...' : 'Kirish' }}
          </button>

          <div class="text-center text-xs font-medium text-neutral-500 pt-1">
            <span v-if="resendCooldown > 0">{{ resendCooldown }} dan keyin yangi kodni olish mumkin</span>
            <button
              v-else
              type="button"
              @click="handleResend"
              :disabled="loading"
              class="text-primary font-semibold bg-transparent border-none cursor-pointer disabled:opacity-75"
            >
              Kodni qayta yuborish
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()

const step = ref<'phone' | 'code'>('phone')
const phoneRaw = ref('')
const phoneInputEl = ref<HTMLInputElement | null>(null)
const otpDigits = ref<string[]>(['', '', '', '', '', ''])
const otpRefs = ref<(HTMLInputElement | null)[]>([])
const loading = ref(false)
const errorMessage = ref('')
const resendCooldown = ref(0)
let cooldownTimer: ReturnType<typeof setInterval> | null = null

// Piyoladagi kabi "90 123-45-67" ko'rinishida formatlash (faqat ko'rsatish
// uchun — haqiqiy qiymat phoneRaw'da faqat raqamlar sifatida saqlanadi).
const phoneDisplay = computed(() => {
  const d = phoneRaw.value
  let out = ''
  if (d.length > 0) out += d.slice(0, 2)
  if (d.length > 2) out += ' ' + d.slice(2, 5)
  if (d.length > 5) out += '-' + d.slice(5, 7)
  if (d.length > 7) out += '-' + d.slice(7, 9)
  return out
})

const phone = computed(() => '998' + phoneRaw.value)
const codeInput = computed(() => otpDigits.value.join(''))

function onPhoneInput(e: Event) {
  const target = e.target as HTMLInputElement
  phoneRaw.value = target.value.replace(/\D/g, '').slice(0, 9)
}

function setOtpRef(el: any, i: number) {
  otpRefs.value[i] = el as HTMLInputElement | null
}

function onOtpInput(i: number, e: Event) {
  const target = e.target as HTMLInputElement
  const digit = target.value.replace(/\D/g, '').slice(-1)
  otpDigits.value[i] = digit
  errorMessage.value = ''
  if (digit && i < otpDigits.value.length - 1) {
    otpRefs.value[i + 1]?.focus()
  }
  if (otpDigits.value.every((d) => d !== '')) {
    handleVerifyOtp()
  }
}

function onOtpKeydown(i: number, e: KeyboardEvent) {
  if (e.key === 'Backspace' && !otpDigits.value[i] && i > 0) {
    otpDigits.value[i - 1] = ''
    otpRefs.value[i - 1]?.focus()
  }
}

function onOtpPaste(e: ClipboardEvent) {
  const pasted = e.clipboardData?.getData('text')?.replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  e.preventDefault()
  pasted.split('').forEach((d, i) => {
    otpDigits.value[i] = d
  })
  const nextEmpty = otpDigits.value.findIndex((d) => d === '')
  otpRefs.value[nextEmpty === -1 ? otpDigits.value.length - 1 : nextEmpty]?.focus()
  if (otpDigits.value.every((d) => d !== '')) {
    handleVerifyOtp()
  }
}

function resetOtp() {
  otpDigits.value = ['', '', '', '', '', '']
}

function startCooldown(seconds = 60) {
  clearCooldown()
  resendCooldown.value = seconds
  cooldownTimer = setInterval(() => {
    resendCooldown.value -= 1
    if (resendCooldown.value <= 0) clearCooldown()
  }, 1000)
}

function clearCooldown() {
  if (cooldownTimer) {
    clearInterval(cooldownTimer)
    cooldownTimer = null
  }
  resendCooldown.value = 0
}

function goBackToPhone() {
  step.value = 'phone'
  resetOtp()
  errorMessage.value = ''
  clearCooldown()
}

function closeModal() {
  authStore.closeAuthModal()
  step.value = 'phone'
  phoneRaw.value = ''
  resetOtp()
  errorMessage.value = ''
  clearCooldown()
}

async function handleSendOtp() {
  if (phoneRaw.value.length !== 9) {
    errorMessage.value = 'Iltimos, 9 xonali raqam kiriting (masalan: 90 123-45-67)'
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.sendOtp(phone.value)
    step.value = 'code'
    resetOtp()
    startCooldown(60)
    nextTick(() => otpRefs.value[0]?.focus())
  } catch (err: any) {
    const retryAfter = err?.data?.retry_after
    errorMessage.value = err?.data?.message || err?.message || 'SMS yuborishda xatolik yuz berdi'
    if (retryAfter) {
      step.value = 'code'
      resetOtp()
      startCooldown(retryAfter)
    }
  } finally {
    loading.value = false
  }
}

async function handleResend() {
  if (resendCooldown.value > 0 || loading.value) return
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.sendOtp(phone.value)
    resetOtp()
    startCooldown(60)
    nextTick(() => otpRefs.value[0]?.focus())
  } catch (err: any) {
    const retryAfter = err?.data?.retry_after
    errorMessage.value = err?.data?.message || err?.message || 'SMS yuborishda xatolik yuz berdi'
    startCooldown(retryAfter || 60)
  } finally {
    loading.value = false
  }
}

async function handleVerifyOtp() {
  if (codeInput.value.length < 6) {
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.verifyOtp(phone.value, codeInput.value)
    clearCooldown()
    step.value = 'phone'
    phoneRaw.value = ''
    resetOtp()
  } catch (err: any) {
    const remaining = err?.data?.remaining_attempts
    const baseMessage = err?.data?.message || err?.message || 'Kiritilgan kod noto‘g‘ri'
    errorMessage.value = typeof remaining === 'number'
      ? `${baseMessage} (${remaining} ta urinish qoldi)`
      : baseMessage
    resetOtp()
    nextTick(() => otpRefs.value[0]?.focus())
  } finally {
    loading.value = false
  }
}

watch(
  () => authStore.isAuthModalOpen,
  (open) => {
    if (open) nextTick(() => phoneInputEl.value?.focus())
  }
)

onUnmounted(() => clearCooldown())
</script>
