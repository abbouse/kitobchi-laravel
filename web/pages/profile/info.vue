<template>
  <div class="py-3 md:py-6 min-h-dvh bg-white md:bg-transparent grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
      <div class="px-4 sm:px-6 lg:px-8 w-full mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm h-11 w-11 flex items-center justify-center p-0 cursor-pointer border-none bg-secondary-100 text-primary"
            >
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Ma'lumotlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-[--ui-container] mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-neutral-500">
          <NuxtLink to="/" class="hover:text-neutral-900 transition-colors no-underline">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-900 transition-colors no-underline">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-medium">Ma'lumotlarim</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20 bg-secondary-50 rounded-3xl p-8">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Shaxsiy ma'lumotlaringizni ko'rish uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <!-- Desktop Sidebar -->
        <ProfileSidebar active="info" />

        <div class="flex-1 min-w-0 space-y-4">
          <!-- Shaxsiy ma'lumotlar kartasi -->
          <div class="bg-secondary-50 rounded-3xl p-4 sm:p-6">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-xl font-bold text-neutral-900 m-0">Ma'lumotlarim</h2>
              
              <button
                @click="openEditModal"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white text-primary text-sm font-semibold transition-colors hover:bg-neutral-50 border-none cursor-pointer shadow-sm"
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                Tahrirlash
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Ism</span>
                <span class="text-base font-semibold text-neutral-900">{{ authStore.user?.name || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Familiya</span>
                <span class="text-base font-semibold text-neutral-900">{{ userAny?.lastname || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Telefon raqam</span>
                <span class="text-base font-semibold text-neutral-900">+{{ authStore.user?.phone_number || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Elektron pochta</span>
                <span class="text-base font-semibold text-neutral-900 truncate">{{ userAny?.email || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Jins</span>
                <span class="text-base font-semibold text-neutral-900">{{ sexLabel }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-neutral-400">Tug'ilgan sana</span>
                <span class="text-base font-semibold text-neutral-900">{{ birthdateLabel }}</span>
              </div>
            </div>
          </div>

          <!-- Mening manzillarim kartasi -->
          <div class="bg-secondary-50 rounded-3xl p-4 sm:p-6">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-xl font-bold text-neutral-900 m-0">Mening manzillarim</h2>
              <button
                type="button"
                @click="openAddressModal"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white text-primary text-sm font-semibold transition-colors hover:bg-neutral-50 border-none cursor-pointer shadow-sm"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Manzil qo'shish
              </button>
            </div>

            <div v-if="addressesLoading" class="flex justify-center py-8">
              <svg class="animate-spin h-6 w-6 text-primary" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
            </div>
            
            <div v-else-if="addresses.length === 0" class="flex flex-col items-center justify-center py-10 bg-white rounded-2xl border border-dashed border-neutral-200">
              <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-3">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
              </div>
              <p class="text-sm font-medium text-neutral-500 m-0">Hech qanday manzil qo'shilmagan</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="loc in addresses"
                :key="loc.id"
                class="relative rounded-2xl p-4 bg-white border-2 transition-all group"
                :class="mainAddressId === loc.id ? 'border-primary' : 'border-transparent'"
              >
                <div v-if="mainAddressId === loc.id" class="absolute top-3 right-3 text-primary bg-primary/10 px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                  Asosiy
                </div>
                
                <div class="flex items-start gap-3">
                  <div class="mt-1 text-primary shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                  </div>
                  <div class="min-w-0 pr-8">
                    <p class="text-sm font-semibold text-neutral-900 leading-snug m-0 line-clamp-2">
                      {{ loc.fullAddress }}
                    </p>
                    <div class="flex items-center gap-3 mt-3">
                      <button
                        v-if="mainAddressId !== loc.id"
                        @click="makeMain(loc)"
                        class="text-xs font-semibold text-primary hover:text-primary-600 bg-transparent border-none cursor-pointer p-0"
                      >
                        Asosiy qilish
                      </button>
                      <button
                        @click="removeAddress(loc)"
                        class="text-xs font-semibold text-red-500 hover:text-red-700 bg-transparent border-none cursor-pointer p-0"
                      >
                        O'chirish
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- Tahrirlash Modal (Piyola 100% matched) -->
    <UModal v-model="isEditModalOpen" :ui="{ width: 'sm:max-w-[600px]', rounded: 'rounded-3xl', background: 'bg-white' }">
      <div class="relative bg-white rounded-3xl p-6 sm:p-8 flex flex-col">
        <!-- Close button -->
        <button type="button" @click="isEditModalOpen = false" class="absolute top-4 right-4 bg-[#F4F4F4] hover:bg-neutral-200 transition-colors rounded-full p-1.5 flex items-center justify-center border-none cursor-pointer">
          <svg class="w-5 h-5 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <h2 class="text-2xl font-bold text-neutral-900 mb-6 m-0">Profilni tahrirlash</h2>
        
        <form @submit.prevent="handleSaveEditModal" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1.5">Ism</label>
              <input v-model="editForm.name" type="text" class="w-full appearance-none text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
            </div>
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1.5">Familiya</label>
              <input v-model="editForm.lastname" type="text" class="w-full appearance-none text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
            </div>
          </div>
          
          <div>
            <label class="block font-medium text-neutral-800 text-sm mb-1.5">Elektron pochta</label>
            <input v-model="editForm.email" type="email" class="w-full appearance-none text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1.5">Tug'ilgan sana</label>
              <input v-model="editForm.birthdate" type="date" :max="todayIso" class="w-full appearance-none text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
            </div>
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1.5">Jins</label>
              <div class="relative flex p-1 w-full rounded-2xl bg-[#F1F2F7] max-md:h-12 md:h-[54px]">
                <button
                  v-for="opt in SEX_TOGGLE_OPTIONS"
                  :key="opt.value"
                  type="button"
                  @click="editForm.sex = opt.value"
                  :class="[
                    'flex-1 rounded-xl text-sm font-semibold transition-all border-none cursor-pointer flex items-center justify-center',
                    editForm.sex === opt.value ? 'bg-white text-neutral-900 shadow-sm' : 'bg-transparent text-neutral-500 hover:text-neutral-700'
                  ]"
                >
                  {{ opt.label }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="editModalError" class="p-3 rounded-2xl bg-red-50 text-red-600 text-sm font-medium">
            {{ editModalError }}
          </div>

          <button
            type="submit"
            :disabled="savingEdit"
            class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75"
          >
            {{ savingEdit ? 'Saqlanmoqda...' : 'Saqlash' }}
          </button>
        </form>
      </div>
    </UModal>

    <!-- Manzil Qo'shish Modal -->
    <UModal v-model="isAddressModalOpen" :ui="{ width: 'sm:max-w-[575px]', rounded: 'rounded-3xl', background: 'bg-white' }">
      <div class="relative bg-white rounded-3xl p-6 sm:p-8 flex flex-col">
        <!-- Close button -->
        <button type="button" @click="isAddressModalOpen = false" class="absolute top-4 right-4 bg-[#F4F4F4] hover:bg-neutral-200 transition-colors rounded-full p-1.5 flex items-center justify-center border-none cursor-pointer">
          <svg class="w-5 h-5 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <h2 class="text-2xl font-bold text-neutral-900 mb-6 m-0">Yangi manzil qo'shish</h2>

        <form @submit.prevent="handleSaveAddressModal" class="space-y-4">
          
          <div>
            <label class="block font-medium text-neutral-800 text-sm mb-1.5">Manzil nomi (Ko'cha, uy)</label>
            <input v-model="modalAddressText" type="text" placeholder="Navoiy ko'chasi 1-uy" class="w-full appearance-none text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
          </div>

          <!-- Joylashuvni aniqlash tugmasi -->
          <div class="p-4 rounded-2xl bg-amber-50 flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            <div class="flex-1 min-w-0 text-sm text-amber-800">
              Manzilingiz xaritadagi aniq koordinatalarini olish uchun ruxsat bering:
              <br>
              <button
                type="button"
                @click="captureModalLocation"
                :disabled="modalLocating"
                class="mt-2 px-4 py-2 rounded-xl bg-white border border-amber-200 text-amber-700 font-semibold hover:border-amber-400 transition-colors border-none cursor-pointer shadow-sm text-xs"
              >
                {{ modalLocating ? 'Aniqlanmoqda...' : (modalCoords ? 'Joylashuv aniqlandi' : 'Joylashuvimni aniqlash') }}
              </button>
            </div>
          </div>

          <div v-if="modalGeoError" class="p-3 rounded-2xl bg-amber-100 text-amber-800 text-sm font-medium">
            {{ modalGeoError }}
          </div>

          <div v-if="modalAddressError" class="p-3 rounded-2xl bg-red-50 text-red-600 text-sm font-medium">
            {{ modalAddressError }}
          </div>

          <button
            type="submit"
            :disabled="!modalAddressText.trim() || savingModalAddress"
            class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75"
          >
            {{ savingModalAddress ? 'Qo\'shilmoqda...' : 'Qo\'shish' }}
          </button>
        </form>
      </div>
    </UModal>
  </div>
</template>


<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()

const userAny = computed(() => authStore.user as any)

const SEX_OPTIONS = [
  { value: '', label: "Ko'rsatilmagan" },
  { value: 'erkak', label: 'Erkak' },
  { value: 'ayol', label: 'Ayol' },
]

// Desktop modaldagi tab-tugma uchun — piyoladagi kabi bo'sh variantsiz,
// faqat Erkak/Ayol.
const SEX_TOGGLE_OPTIONS = SEX_OPTIONS.filter(o => o.value)

const sexLabel = computed(() => {
  const found = SEX_OPTIONS.find(o => o.value === (userAny.value?.sex || ''))
  return found && found.value ? found.label : 'Kiritilmagan'
})

// MUHIM: piyola'dagi kabi "DD.MM.YYYY" formatida ko'rsatiladi. Backend
// `birthdate`ni "YYYY-MM-DD" (Laravel `date` ustuni) shaklida qaytaradi.
const birthdateLabel = computed(() => {
  const raw = userAny.value?.birthdate
  if (!raw) return 'Kiritilmagan'
  const datePart = String(raw).slice(0, 10)
  const [y, m, d] = datePart.split('-')
  if (!y || !m || !d) return 'Kiritilmagan'
  return `${d}.${m}.${y}`
})

const todayIso = computed(() => new Date().toISOString().slice(0, 10))

// ── Desktop Edit Modal Holatlari ────────────────────────────────────
const isEditModalOpen = ref(false)
const savingEdit = ref(false)
const editModalError = ref('')

const editForm = reactive({
  name: '',
  lastname: '',
  sex: '',
  birthdate: '',
  email: '',
})

function openEditModal() {
  editForm.name = authStore.user?.name || ''
  editForm.lastname = userAny.value?.lastname || ''
  editForm.sex = userAny.value?.sex || ''
  editForm.birthdate = userAny.value?.birthdate ? String(userAny.value.birthdate).slice(0, 10) : ''
  editForm.email = userAny.value?.email || ''
  editModalError.value = ''
  isEditModalOpen.value = true
}

async function handleSaveEditModal() {
  savingEdit.value = true
  editModalError.value = ''
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/settings`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        name: editForm.name,
        lastname: editForm.lastname,
        sex: editForm.sex,
        birthdate: editForm.birthdate || '',
        email: editForm.email || '',
      }
    })
    authStore.updateUser({
      name: editForm.name,
      lastname: editForm.lastname,
      sex: editForm.sex,
      birthdate: editForm.birthdate || null,
      email: editForm.email || null,
    })
    isEditModalOpen.value = false
  } catch (e: any) {
    editModalError.value = e?.data?.message || "Saqlashda xatolik yuz berdi"
  } finally {
    savingEdit.value = false
  }
}

// ── Saqlangan manzillar ro'yxati ────────────────────────────────────
const addresses = ref<any[]>([])
const addressesLoading = ref(true)
const mainAddressId = computed(() => userAny.value?.mainAddressID ?? null)

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

async function makeMain(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/select/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    authStore.updateUser({ mainAddressID: loc.id })
  } catch (e) {
    //
  }
}

async function removeAddress(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/delete/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    addresses.value = addresses.value.filter((a) => a.id !== loc.id)
  } catch (e) {
    //
  }
}

// ── Desktop Address Modal Holatlari ─────────────────────────────────
const isAddressModalOpen = ref(false)
const modalAddressText = ref('')
const modalCoords = ref<{ lat: number; lon: number } | null>(null)
const modalLocating = ref(false)
const modalGeoError = ref('')
const savingModalAddress = ref(false)
const modalAddressError = ref('')

function openAddressModal() {
  modalAddressText.value = ''
  modalCoords.value = null
  modalGeoError.value = ''
  modalAddressError.value = ''
  isAddressModalOpen.value = true
}

function captureModalLocation() {
  if (!('geolocation' in navigator)) {
    modalGeoError.value = 'Brauzeringiz joylashuvni aniqlay olmaydi'
    return
  }
  modalLocating.value = true
  modalGeoError.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      modalCoords.value = { lat: pos.coords.latitude, lon: pos.coords.longitude }
      modalLocating.value = false
    },
    () => {
      modalCoords.value = { lat: 41.2995, lon: 69.2401 }
      modalGeoError.value = "Joylashuvga ruxsat berilmadi. Standart shahar koordinatasi olindi."
      modalLocating.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function handleSaveAddressModal() {
  if (!modalAddressText.value.trim()) return
  if (!modalCoords.value) {
    modalCoords.value = { lat: 41.2995, lon: 69.2401 }
  }
  savingModalAddress.value = true
  modalAddressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        lat: modalCoords.value.lat,
        lon: modalCoords.value.lon,
        fullAddress: modalAddressText.value.trim(),
      }
    })
    if (res?.location_id) {
      authStore.updateUser({ mainAddressID: res.location_id })
    }
    await fetchAddresses()
    isAddressModalOpen.value = false
  } catch (e: any) {
    modalAddressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingModalAddress.value = false
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
