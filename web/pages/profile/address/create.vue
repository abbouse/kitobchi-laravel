<template>
  <div class="py-3 md:py-6 min-h-dvh bg-secondary-300 md:bg-gray-50 grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-xs">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
              aria-label="Orqaga"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Manzil qo'shish</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile/info" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-700 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-700 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile/info" class="hover:text-neutral-700 transition-colors">Ma'lumotlarim</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Manzil qo'shish</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20 bg-white rounded-3xl p-8 shadow-xs">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Yetkazib berish manzilini qo'shish uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <ProfileSidebar active="info" />

        <div class="flex-1 min-w-0 max-w-2xl">
          <div class="bg-white rounded-[20px] p-5 sm:p-7 shadow-xs">
            <h2 class="text-xl font-bold text-neutral-900 mb-5 max-md:hidden">Yangi manzil qo'shish</h2>

            <form @submit.prevent="submitAddress" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">To'liq manzil</label>
                <textarea
                  v-model="newAddressText"
                  rows="3"
                  placeholder="Shahar/Tuman, ko'cha, uy va xonadon raqami..."
                  class="w-full rounded-2xl bg-secondary-50 px-4 py-3.5 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm resize-none"
                  required
                ></textarea>
              </div>

              <!-- Geolocation Button -->
              <div class="p-4 rounded-2xl bg-secondary-50 border border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-white shadow-xs flex items-center justify-center text-primary shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-neutral-800 m-0">Geo-joylashuv</p>
                    <p v-if="coords" class="text-xs text-emerald-600 font-medium m-0 mt-0.5">Joylashuv aniqlandi ✓ ({{ coords.lat.toFixed(4) }}, {{ coords.lon.toFixed(4) }})</p>
                    <p v-else class="text-xs text-neutral-400 m-0 mt-0.5">Xaritada tez topish uchun</p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="captureLocation"
                  :disabled="locating"
                  class="px-4 py-2 rounded-xl bg-white border border-neutral-200 text-xs font-semibold text-neutral-700 hover:border-primary/40 hover:text-primary transition-all cursor-pointer disabled:opacity-75 shrink-0"
                >
                  {{ locating ? 'Aniqlanmoqda...' : (coords ? 'Qayta aniqlash' : 'Joriy joylashuv') }}
                </button>
              </div>

              <div v-if="geoError" class="p-3 rounded-xl bg-amber-50 text-amber-700 text-xs font-medium">
                {{ geoError }}
              </div>

              <div v-if="addressError" class="p-3 rounded-xl bg-red-50 text-red-600 text-xs font-medium">
                {{ addressError }}
              </div>

              <div class="pt-3 flex flex-col sm:flex-row gap-3">
                <button
                  type="submit"
                  :disabled="!newAddressText.trim() || savingAddress"
                  class="w-full sm:flex-1 py-3.5 rounded-2xl bg-primary text-white font-semibold text-base border-none cursor-pointer hover:bg-primary/90 transition-colors shadow-md disabled:opacity-75"
                >
                  {{ savingAddress ? 'Saqlanmoqda...' : 'Saqlash' }}
                </button>
                <button
                  type="button"
                  @click="$router.push('/profile/info')"
                  :disabled="savingAddress"
                  class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-base border-none cursor-pointer hover:bg-secondary-200 transition-colors"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()
const router = useRouter()

const newAddressText = ref('')
const coords = ref<{ lat: number; lon: number } | null>(null)
const locating = ref(false)
const geoError = ref('')
const savingAddress = ref(false)
const addressError = ref('')

function captureLocation() {
  if (!('geolocation' in navigator)) {
    geoError.value = 'Brauzeringiz joylashuvni aniqlay olmaydi'
    return
  }
  locating.value = true
  geoError.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      coords.value = { lat: pos.coords.latitude, lon: pos.coords.longitude }
      locating.value = false
    },
    () => {
      // Default to Tashkent coordinates if permission denied
      coords.value = { lat: 41.2995, lon: 69.2401 }
      geoError.value = "Joylashuvga ruxsat berilmadi. Standart shahar koordinatasi olindi."
      locating.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function submitAddress() {
  if (!newAddressText.value.trim()) return
  if (!coords.value) {
    coords.value = { lat: 41.2995, lon: 69.2401 }
  }
  savingAddress.value = true
  addressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        lat: coords.value.lat,
        lon: coords.value.lon,
        fullAddress: newAddressText.value.trim(),
      }
    })
    if (res?.location_id) {
      authStore.updateUser({ mainAddressID: res.location_id })
    }
    router.push('/profile/info')
  } catch (e: any) {
    addressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingAddress.value = false
  }
}

useSeoMeta({
  title: "Manzil qo'shish — Kitobchi",
  description: "Yetkazib berish manzilini qo'shish."
})
</script>
