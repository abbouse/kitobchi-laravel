<template>
  <main class="max-md:grow h-full md:min-h-dvh bg-[#f1f1f1] lg:bg-white">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
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
            <h1 class="text-xl sm:text-xl text-primary font-bold text-center m-0">Ma'lumotlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>
    
    <div class="py-6 min-h-dvh">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        
        <div class="pb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button @click="$router.back()" type="button" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25 border-none bg-transparent cursor-pointer">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="relative min-w-0">
              <ol class="flex items-center gap-2 p-0 m-0 list-none">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <NuxtLink to="/" class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-medium transition-colors text-[#8F8FA1] text-sm no-underline hover:text-neutral-900">
                    <span class="truncate">Asosiy</span>
                  </NuxtLink>
                </li>
                <li class="flex"><span class="text-neutral-400 text-xs"> / </span></li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <span class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-semibold text-[#8F8FA1] text-sm text-neutral-900">
                    <span class="truncate">Profil</span>
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>

        <div v-if="!authStore.isAuthenticated" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl mt-4 max-w-2xl mx-auto shadow-sm">
          <div class="w-24 h-24 bg-[#F6F6F9] rounded-full flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <h2 class="text-2xl font-bold text-neutral-900 mb-2 m-0">Avtorizatsiya</h2>
          <p class="text-neutral-500 mb-8 max-w-xs text-center m-0">Shaxsiy kabinetga kirish uchun tizimga kiring</p>
          <button @click="authStore.openAuthModal()" class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer">
            Kirish
          </button>
        </div>

        <div v-else class="flex flex-col lg:flex-row gap-5">
          <ProfileSidebar active="info" />
          
          <div class="w-full">
            <div class="max-md:min-h-dvh flex flex-col max-md:pb-2">
              <div class="p-4 md:p-6 rounded-3xl bg-secondary-50">
<div class="mb-4 flex items-center justify-between">
          <h2 class="text-primary text-xl font-bold m-0">Ma'lumotlarim</h2>
          <button @click="openEditModal" type="button" class="font-medium items-center transition-colors py-1.5 text-sm gap-1.5 text-primary bg-primary/10 hover:bg-primary/15 outline-primary/25 border-none cursor-pointer h-10 rounded-xl px-4 flex justify-center sm:min-w-40">
            <svg class="shrink-0 size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.158 3.712 3.712 1.158-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z"/><path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z"/></svg>
            <span class="truncate">Tahrirlash</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col">
            <span class="text-neutral-500 flex-1 font-normal shrink-0 text-sm">To'liq ism</span>
            <span class="flex-1 font-medium shrink-0 text-neutral-900">{{ userAny?.name || "Kiritilmagan" }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-neutral-500 flex-1 font-normal shrink-0 text-sm">Tug'ilgan sana</span>
            <span class="flex-1 font-medium shrink-0 text-neutral-900">{{ userAny?.birthdate ? formatDate(userAny.birthdate) : "Kiritilmagan" }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-neutral-500 flex-1 font-normal shrink-0 text-sm">Jins</span>
            <span class="flex-1 font-medium shrink-0 text-neutral-900">{{ userAny?.sex === 'ayol' ? 'Ayol' : (userAny?.sex === 'erkak' ? 'Erkak' : 'Kiritilmagan') }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-neutral-500 flex-1 font-normal shrink-0 text-sm">Telefon raqam</span>
            <span class="flex-1 font-medium shrink-0 text-neutral-900">{{ formatPhone(userAny?.phone) || "Kiritilmagan" }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-neutral-500 flex-1 font-normal shrink-0 text-sm">Elektron pochta</span>
            <span class="flex-1 font-medium shrink-0 text-neutral-900">{{ userAny?.email || "Kiritilmagan" }}</span>
          </div>
        </div>

        <div class="mt-8">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold m-0 text-neutral-900">Saqlangan manzillar</h2>
          </div>

          <!-- Addreslar ro'yxati -->
          <div v-if="addresses.length > 0" class="space-y-4">
            <div v-for="loc in addresses" :key="loc.id" class="rounded-[20px] bg-white p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-transparent shadow-sm">
              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-[#F6F6F9] flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <div class="flex flex-col gap-1">
                  <p class="text-neutral-900 font-medium m-0">{{ loc.fullAddress }}</p>
                  <span v-if="mainAddressId === loc.id" class="text-primary text-sm font-semibold">Asosiy manzil</span>
                </div>
              </div>
              <div class="flex items-center gap-2 sm:shrink-0">
                <button v-if="mainAddressId !== loc.id" @click="makeMain(loc)" class="px-4 py-2 rounded-xl bg-[#F6F6F9] hover:bg-neutral-200 text-neutral-700 text-sm font-medium transition-colors border-none cursor-pointer">Asosiy qilish</button>
                <button @click="removeAddress(loc)" class="w-10 h-10 rounded-xl bg-[#F6F6F9] hover:bg-red-50 text-neutral-400 hover:text-red-500 transition-colors flex items-center justify-center border-none cursor-pointer">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </button>
              </div>
            </div>
            
            <button @click="openAddressModal" type="button" class="mt-4 font-medium inline-flex items-center justify-center text-base gap-2 text-white bg-primary hover:bg-primary/90 transition-colors rounded-2xl h-12 px-6 border-none cursor-pointer w-full sm:w-auto shadow-sm">
              <span class="truncate">Yangi manzil qo‘shish</span>
            </button>
          </div>

          <!-- Bo'sh state -->
          <div v-else class="mt-4 rounded-[20px] bg-[#F6F6F9] p-6 flex flex-col gap-3 items-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm">
              <svg class="w-10 h-10 text-neutral-400" viewBox="0 0 24 24" fill="currentColor"><path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"/><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"/></svg>
            </div>
            <h2 class="text-lg md:text-xl font-semibold text-center m-0 text-neutral-900">Saqlangan manzillar mavjud emas</h2>
            <p class="text-sm font-normal max-w-md text-center m-0 text-neutral-500">Yetkazib berish manzilini qo‘shing</p>
            <button @click="openAddressModal" type="button" class="mt-2 font-medium inline-flex items-center text-base gap-2 text-white bg-primary hover:bg-primary/90 transition-colors rounded-2xl h-12 px-6 border-none cursor-pointer shadow-sm">
              <span class="truncate">Yangi manzil qo‘shish</span>
            </button>
              
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
  </main>


  <!-- Tahrirlash Modali -->
    <!-- MUHIM: bu yerda ilgari `<UModal>` (Nuxt UI) ishlatilgan edi, lekin
         `@nuxt/ui` moduli loyihaga umuman o'rnatilmagan (package.json va
         nuxt.config.ts'da yo'q) — shu sababli `<UModal>` "backdrop + fixed
         overlay" berish o'rniga oddiy tanilmagan HTML elementi sifatida
         DOM'ning ODDIY OQIMIGA (sahifa pastiga, boshqa kontent bilan
         qatorlashib) chizilardi: overlay yo'q, markazlashtirish yo'q,
         yopish/ochish animatsiyasi yo'q — desktopda "Tahrirlash" va
         "Manzil qo'shish" tugmalari bosilganda forma sahifa oxirida paydo
         bo'lib, sindirilgan ko'rinardi. Endi loyihada allaqachon ishlab
         turgan `AuthModal.vue`dagi bilan bir xil naqsh — `fixed inset-0`
         qora fon (backdrop) + markazlashtirilgan oq kartochka — ishlatildi. -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isEditModalOpen = false"
    >
      <div class="modal-sm-600 relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full overflow-y-auto">
        <button @click="isEditModalOpen = false" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
          <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-2xl font-bold text-center m-0 mb-8 text-neutral-900">Tahrirlash</h3>
        <form @submit.prevent="handleSaveInfoModal" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-neutral-700">Ismingiz</label>
            <input v-model="editForm.name" type="text" class="w-full appearance-none text-base text-neutral-900 focus:outline-none rounded-2xl p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all" placeholder="Ismingizni kiriting" required>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-neutral-700">Telefon raqam</label>
            <input v-model="editForm.phone" type="text" class="w-full appearance-none text-base text-neutral-900 focus:outline-none rounded-2xl p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all" placeholder="+998" disabled>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-neutral-700">Tug'ilgan sana</label>
            <input v-model="editForm.birthDate" type="date" class="w-full appearance-none text-base text-neutral-900 focus:outline-none rounded-2xl p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-neutral-700">Jinsingiz</label>
            <div class="flex items-center gap-2 p-1 bg-[#F1F2F7] rounded-2xl">
              <button type="button" @click="editForm.sex = 'erkak'" :class="editForm.sex === 'erkak' ? 'bg-white shadow-sm text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'" class="flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all border-none cursor-pointer">
                Erkak
              </button>
              <button type="button" @click="editForm.sex = 'ayol'" :class="editForm.sex === 'ayol' ? 'bg-white shadow-sm text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'" class="flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all border-none cursor-pointer">
                Ayol
              </button>
            </div>
          </div>

          <button type="submit" :disabled="savingInfoModal" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-sm">
            {{ savingInfoModal ? "Saqlanmoqda..." : "Saqlash" }}
          </button>
        </form>
      </div>
    </div>

    <!-- Manzil Qo'shish Modali -->
    <div
      v-if="isAddressModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isAddressModalOpen = false"
    >
      <div class="modal-sm-600 relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full overflow-y-auto">
        <button @click="isAddressModalOpen = false" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
          <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-2xl font-bold text-center m-0 mb-8 text-neutral-900">Manzil qo'shish</h3>
        <form @submit.prevent="handleSaveAddressModal" class="space-y-4">

          <div v-if="modalAddressError" class="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
            {{ modalAddressError }}
          </div>

          <!-- MUHIM: GPS/geolocation ("Joylashuvni aniqlash") olib
               tashlandi — endi manzil Viloyat → Tuman → Mahalla/qishloq
               tanlovi orqali kiritiladi (MIMAXUZ/uzbekistan-regions-data),
               web buyurtmasi uchun lat/lon shart emas. -->
          <UzAddressPicker ref="addressPickerRef" @update="onAddrUpdate" />

          <div v-if="addressSummary.fullAddress" class="p-3 rounded-xl bg-[#F6F6F9] text-sm text-neutral-600">
            {{ addressSummary.fullAddress }}
          </div>

          <button type="submit" :disabled="!addressSummary.isValid || savingModalAddress" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-sm">
            {{ savingModalAddress ? "Qo'shilmoqda..." : "Qo'shish" }}
          </button>
        </form>
      </div>
    </div>
</template>
<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const authStore = useAuthStore()

const userAny = computed(() => authStore.user as any)

function formatDate(dStr: string) {
  if (!dStr) return ''
  const d = new Date(dStr)
  return d.toLocaleDateString('ru-RU')
}

function formatPhone(phone: string) {
  if (!phone) return ''
  return phone
}

// ── Tahrirlash Modali ───────────────────────────────────────────────
const isEditModalOpen = ref(false)
const savingInfoModal = ref(false)
// MUHIM: jonli backend (`/v1/kitobchi/user/*`) javobini brauzer
// cookie'sidagi `kc_user` orqali bevosita tekshirib TASDIQLADIK:
// tug'ilgan sana maydoni `birthdate` (kichik harf, camelCase emas) va
// jins maydoni `sex` (qiymatlari inglizcha "male"/"female" emas,
// o'zbekcha "erkak"/"ayol") nomi bilan keladi. Oldin bu yerda `birthDate`
// va `gender`("male"/"female") ishlatilgan edi — natijada "Tug'ilgan
// sana" va "Jins" haqiqiy qiymat mavjud bo'lsa ham doim "Kiritilmagan"
// bo'lib ko'rinardi, tahrirlab saqlash esa backend o'qimaydigan noto'g'ri
// kalitlarga yozardi.
const editForm = ref({ name: '', phone: '', birthDate: '', sex: '' })

function openEditModal() {
  editForm.value = {
    name: userAny.value?.name || '',
    phone: userAny.value?.phone || '',
    birthDate: userAny.value?.birthdate || '',
    sex: userAny.value?.sex || 'erkak'
  }
  isEditModalOpen.value = true
}

async function handleSaveInfoModal() {
  savingInfoModal.value = true
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/user/profile`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        name: editForm.value.name,
        birthdate: editForm.value.birthDate,
        sex: editForm.value.sex
      }
    })
    authStore.updateUser({
      name: editForm.value.name,
      birthdate: editForm.value.birthDate,
      sex: editForm.value.sex
    })
    isEditModalOpen.value = false
  } catch (e) {
    // 
  } finally {
    savingInfoModal.value = false
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
// MUHIM: manzil endi GPS/geolocation orqali emas, balki
// components/UzAddressPicker.vue orqali (Viloyat → Tuman → Mahalla/
// qishloq, MIMAXUZ/uzbekistan-regions-data) kiritiladi. `addressSummary`
// — pickerdan har o'zgarishda keladigan xulosa (`regionName`,
// `districtName`, `village`, `fullAddress`, `isValid`).
const isAddressModalOpen = ref(false)
const addressPickerRef = ref<{ reset: () => void } | null>(null)
const addressSummary = ref({
  regionId: null as number | null,
  regionName: '',
  districtId: null as number | null,
  districtName: '',
  village: '',
  street: '',
  fullAddress: '',
  isValid: false,
})
const savingModalAddress = ref(false)
const modalAddressError = ref('')

function openAddressModal() {
  modalAddressError.value = ''
  addressSummary.value = {
    regionId: null, regionName: '', districtId: null, districtName: '',
    village: '', street: '', fullAddress: '', isValid: false,
  }
  isAddressModalOpen.value = true
  nextTick(() => addressPickerRef.value?.reset())
}

function onAddrUpdate(summary: typeof addressSummary.value) {
  addressSummary.value = summary
}

async function handleSaveAddressModal() {
  if (!addressSummary.value.isValid) return
  savingModalAddress.value = true
  modalAddressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        fullAddress: addressSummary.value.fullAddress,
        countryCode: 'UZ',
        regionName: addressSummary.value.regionName,
        districtName: addressSummary.value.districtName,
        cityName: addressSummary.value.village,
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
