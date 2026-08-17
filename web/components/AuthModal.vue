<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="authStore.isAuthModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="authStore.closeAuthModal()"
    >
      <!-- Modal Card -->
      <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl relative animate-in fade-in zoom-in duration-200">
        <button
          type="button"
          @click="authStore.closeAuthModal()"
          class="absolute top-4 right-4 w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="text-center mb-6">
          <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary mx-auto flex items-center justify-center mb-3">
            <i class="icon-profile text-3xl"></i>
          </div>
          <h2 class="text-xl font-bold text-neutral-900 m-0">
            {{ step === 'phone' ? 'Tizimga kirish' : 'Kodni tasdiqlash' }}
          </h2>
          <p class="text-sm text-neutral-500 mt-1 mb-0">
            {{ step === 'phone' ? 'Telefon raqamingizni kiriting' : `+${phone} raqamiga yuborilgan kodni kiriting` }}
          </p>
        </div>

        <!-- Step 1: Phone -->
        <form v-if="step === 'phone'" @submit.prevent="handleSendOtp" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Telefon raqam</label>
            <div class="flex items-center rounded-2xl bg-secondary-100 px-4 py-3 border border-secondary-200 focus-within:border-primary">
              <span class="text-sm font-semibold text-neutral-600 mr-2">+998</span>
              <input
                v-model="phoneInput"
                type="tel"
                placeholder="90 123 45 67"
                maxlength="9"
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
            class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer disabled:opacity-50"
          >
            {{ loading ? 'Yuborilmoqda...' : 'Kodni olish' }}
          </button>
        </form>

        <!-- Step 2: OTP Code -->
        <form v-else @submit.prevent="handleVerifyOtp" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-neutral-700 uppercase mb-1">Tasdiqlash kodi</label>
            <input
              v-model="codeInput"
              type="text"
              placeholder="123456"
              maxlength="6"
              required
              class="w-full rounded-2xl bg-secondary-100 px-4 py-3.5 text-center text-xl font-bold tracking-widest text-neutral-900 border border-secondary-200 focus:border-primary outline-none"
            />
          </div>

          <div v-if="errorMessage" class="text-xs text-red-500 font-medium text-center">
            {{ errorMessage }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer disabled:opacity-50"
          >
            {{ loading ? 'Tekshirilmoqda...' : 'Kirish' }}
          </button>

          <button
            type="button"
            @click="step = 'phone'"
            class="w-full py-2 text-xs font-medium text-neutral-500 hover:text-neutral-800 bg-transparent border-none cursor-pointer"
          >
            Raqamni o‘zgartirish
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()

const step = ref<'phone' | 'code'>('phone')
const phoneInput = ref('')
const codeInput = ref('')
const loading = ref(false)
const errorMessage = ref('')

const phone = computed(() => '998' + phoneInput.value.replace(/\D/g, ''))

async function handleSendOtp() {
  if (phoneInput.value.replace(/\D/g, '').length !== 9) {
    errorMessage.value = 'Iltimos, 9 xonali raqam kiriting (masalan: 90 123 45 67)'
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.sendOtp(phone.value)
    step.value = 'code'
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'SMS yuborishda xatolik yuz berdi'
  } finally {
    loading.value = false
  }
}

async function handleVerifyOtp() {
  if (codeInput.value.length < 4) {
    errorMessage.value = 'Kodni to‘liq kiriting'
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.verifyOtp(phone.value, codeInput.value)
    step.value = 'phone'
    phoneInput.value = ''
    codeInput.value = ''
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Kiritilgan kod noto‘g‘ri'
  } finally {
    loading.value = false
  }
}
</script>
