<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Ma'lumotlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-600 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Ma'lumotlarim</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Shaxsiy ma'lumotlaringizni ko'rish uchun tizimga kiring.</p>
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

        <div class="flex-1 min-w-0">
          <div class="bg-secondary-50 rounded-3xl p-5 sm:p-6 border border-neutral-100">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-xl font-bold text-neutral-900">Ma'lumotlarim</h2>
              <button
                v-if="!editing"
                type="button"
                @click="startEdit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/10 text-primary text-sm font-semibold border-none cursor-pointer hover:bg-primary/15 transition-colors"
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v4.875c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 01-1.125-1.125V6.375c0-.621.504-1.125 1.125-1.125h9.75"/></svg>
                Tahrirlash
              </button>
            </div>

            <!-- View mode -->
            <div v-if="!editing" class="grid grid-cols-2 gap-x-4 gap-y-5">
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-500">Ism</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ authStore.user?.name || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-500">Familiya</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ userAny?.lastname || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-500">Jins</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ sexLabel }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-500">Telefon raqam</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">+{{ authStore.user?.phone_number || 'Kiritilmagan' }}</span>
              </div>
            </div>

            <!-- Edit mode -->
            <form v-else @submit.prevent="handleSave" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Ism</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium"
                  placeholder="Ismingiz"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Familiya</label>
                <input
                  v-model="form.lastname"
                  type="text"
                  class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium"
                  placeholder="Familiyangiz"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Jins</label>
                <select
                  v-model="form.sex"
                  class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium"
                >
                  <option v-for="opt in SEX_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Telefon raqam</label>
                <input
                  :value="'+' + authStore.user?.phone_number"
                  disabled
                  type="text"
                  class="w-full rounded-2xl bg-secondary-100 px-4 py-3 border border-neutral-100 text-neutral-500 font-medium cursor-not-allowed"
                />
              </div>

              <div class="flex items-center gap-3 pt-1">
                <button
                  type="submit"
                  :disabled="saving"
                  class="px-6 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors disabled:opacity-75 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
                </button>
                <button
                  type="button"
                  @click="cancelEdit"
                  :disabled="saving"
                  class="px-6 py-3 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-sm border-none cursor-pointer hover:bg-secondary-400 transition-colors"
                >
                  Bekor qilish
                </button>
                <span v-if="saveError" class="text-sm text-red-700">{{ saveError }}</span>
                <span v-else-if="saveSuccess" class="text-sm text-emerald-600 font-medium">Saqlandi ✓</span>
              </div>
            </form>
          </div>

          <!-- Saved addresses -->
          <div class="mt-6">
            <h2 class="text-lg font-bold text-neutral-900 mb-3">Saqlangan manzillar</h2>

            <div v-if="addressesLoading" class="space-y-3">
              <div class="animate-pulse bg-secondary-100 h-16 w-full rounded-2xl"></div>
            </div>

            <template v-else>
              <div v-if="!addresses.length && !addingAddress" class="rounded-[20px] bg-secondary-100 p-6 flex flex-col gap-3 items-center text-center">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <h3 class="text-base font-semibold text-neutral-800">Saqlangan manzillar mavjud emas</h3>
                <p class="text-sm text-neutral-500">Yetkazib berish manzilini qo'shing</p>
                <button
                  type="button"
                  @click="startAddAddress"
                  class="inline-flex items-center px-6 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors"
                >
                  Yangi manzil qo'shish
                </button>
              </div>

              <div v-else class="space-y-3">
                <div v-for="loc in addresses" :key="loc.id" class="bg-white border border-neutral-100 rounded-2xl p-4 flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <svg class="w-5 h-5 text-neutral-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-neutral-900 truncate">{{ loc.fullAddress }}</p>
                      <p v-if="loc.id === mainAddressId" class="text-xs text-primary font-semibold mt-0.5">Asosiy manzil</p>
                      <button v-else type="button" @click="makeMain(loc)" class="text-xs text-neutral-500 hover:text-primary/75 transition-colors mt-0.5 border-none bg-transparent cursor-pointer p-0">Asosiy qilish</button>
                    </div>
                  </div>
                  <button type="button" @click="removeAddress(loc)" class="text-red-500 hover:bg-neutral-100 rounded-lg p-1.5 shrink-0 border-none bg-transparent cursor-pointer transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>

                <button
                  v-if="!addingAddress"
                  type="button"
                  @click="startAddAddress"
                  class="w-full py-3 rounded-2xl border border-dashed border-gray-200 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 hover:border-primary/30 transition-colors bg-transparent cursor-pointer"
                >
                  + Yangi manzil qo'shish
                </button>
              </div>

              <!-- Add address form -->
              <div v-if="addingAddress" class="mt-3 bg-secondary-50 rounded-2xl p-4 space-y-3">
                <div>
                  <label class="block text-xs font-semibold text-neutral-600 mb-1">Manzil tavsifi</label>
                  <textarea
                    v-model="newAddressText"
                    rows="2"
                    placeholder="Ko'cha, uy, xonadon raqami..."
                    class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium resize-none"
                  ></textarea>
                </div>
                <div v-if="!coords" class="flex items-center gap-3">
                  <button
                    type="button"
                    @click="captureLocation"
                    :disabled="locating"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-neutral-100 text-sm font-semibold text-neutral-700 cursor-pointer hover:border-primary/30 transition-colors disabled:opacity-75"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    {{ locating ? 'Aniqlanmoqda...' : 'Joriy joylashuvni aniqlash' }}
                  </button>
                  <span v-if="geoError" class="text-xs text-red-700">{{ geoError }}</span>
                </div>
                <div v-else class="text-xs text-emerald-600 font-medium">Joylashuv aniqlandi ✓</div>

                <div class="flex items-center gap-3 pt-1">
                  <button
                    type="button"
                    @click="submitAddress"
                    :disabled="!coords || !newAddressText.trim() || savingAddress"
                    class="px-6 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors disabled:opacity-75 disabled:cursor-not-allowed"
                  >
                    {{ savingAddress ? 'Saqlanmoqda...' : 'Saqlash' }}
                  </button>
                  <button
                    type="button"
                    @click="cancelAddAddress"
                    class="px-6 py-3 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-sm border-none cursor-pointer hover:bg-secondary-400 transition-colors"
                  >
                    Bekor qilish
                  </button>
                  <span v-if="addressError" class="text-sm text-red-700">{{ addressError }}</span>
                </div>
              </div>
            </template>
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

const userAny = computed(() => authStore.user as any)

const SEX_OPTIONS = [
  { value: '', label: "Ko'rsatilmagan" },
  { value: 'male', label: 'Erkak' },
  { value: 'female', label: 'Ayol' },
]

const sexLabel = computed(() => {
  const found = SEX_OPTIONS.find(o => o.value === (userAny.value?.sex || ''))
  return found && found.value ? found.label : 'Kiritilmagan'
})

const editing = ref(false)
const saving = ref(false)
const saveError = ref('')
const saveSuccess = ref(false)

const form = reactive({
  name: '',
  lastname: '',
  sex: '',
})

function startEdit() {
  form.name = authStore.user?.name || ''
  form.lastname = userAny.value?.lastname || ''
  form.sex = userAny.value?.sex || ''
  saveError.value = ''
  saveSuccess.value = false
  editing.value = true
}

function cancelEdit() {
  editing.value = false
  saveError.value = ''
}

async function handleSave() {
  saving.value = true
  saveError.value = ''
  saveSuccess.value = false
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/settings`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        name: form.name,
        lastname: form.lastname,
        sex: form.sex,
      }
    })
    await authStore.fetchUser()
    saveSuccess.value = true
    editing.value = false
  } catch (e: any) {
    saveError.value = e?.data?.message || "Saqlashda xatolik yuz berdi"
  } finally {
    saving.value = false
  }
}

// ── Saqlangan manzillar (real: v1/kitobchi/locations/*) ──────────────
const addresses = ref<any[]>([])
const addressesLoading = ref(true)
const mainAddressId = computed(() => userAny.value?.mainAddressID ?? null)

const addingAddress = ref(false)
const newAddressText = ref('')
const coords = ref<{ lat: number; lon: number } | null>(null)
const locating = ref(false)
const geoError = ref('')
const savingAddress = ref(false)
const addressError = ref('')

async function fetchAddresses() {
  addressesLoading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    addresses.value = res?.data || []
  } catch (e) {
    addresses.value = []
  } finally {
    addressesLoading.value = false
  }
}

function startAddAddress() {
  addingAddress.value = true
  newAddressText.value = ''
  coords.value = null
  geoError.value = ''
  addressError.value = ''
}

function cancelAddAddress() {
  addingAddress.value = false
}

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
      geoError.value = "Joylashuvga ruxsat berilmadi. Brauzer sozlamalarida ruxsat bering."
      locating.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function submitAddress() {
  if (!coords.value || !newAddressText.value.trim()) return
  savingAddress.value = true
  addressError.value = ''
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        lat: coords.value.lat,
        lon: coords.value.lon,
        fullAddress: newAddressText.value.trim(),
      }
    })
    await Promise.all([fetchAddresses(), authStore.fetchUser()])
    addingAddress.value = false
  } catch (e: any) {
    addressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingAddress.value = false
  }
}

async function makeMain(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/select/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    await authStore.fetchUser()
  } catch (e) {
    // jim — asosiy manzilni belgilashda xatolik, ro'yxat holati o'zgarmaydi
  }
}

async function removeAddress(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/delete/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    addresses.value = addresses.value.filter((a) => a.id !== loc.id)
  } catch (e) {
    // jim — o'chirishda xatolik, ro'yxat o'zgarishsiz qoladi
  }
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchAddresses()
  } else {
    addressesLoading.value = false
  }
})

useSeoMeta({ title: "Ma'lumotlarim — Kitobchi" })
</script>
