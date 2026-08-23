<template>
  <main class="max-md:grow h-full md:min-h-dvh">
    <!-- Mobile Header (hidden on md) -->
    <div class="md:hidden py-3 rounded-b-2xl mb-2 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto space-y-2">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button @click="$router.back()" type="button" class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm h-11 w-11 flex items-center justify-center p-0 cursor-pointer border-none bg-secondary-100 text-primary">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Buyurtma</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="min-h-dvh md:py-6 bg-white md:bg-transparent">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        
        <!-- Desktop Breadcrumb (hidden on max-md) -->
        <div class="mb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button type="button" @click="$router.back()" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer">
              <svg class="shrink-0 size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="flex items-center gap-2 text-sm text-neutral-500">
              <NuxtLink to="/" class="hover:text-neutral-900 transition-colors no-underline">Asosiy</NuxtLink>
              <span>/</span>
              <NuxtLink to="/cart" class="hover:text-neutral-900 transition-colors no-underline">Savat</NuxtLink>
              <span>/</span>
              <span class="text-neutral-900 font-medium">Buyurtma</span>
            </nav>
          </div>
        </div>

        <!-- TUZATILDI: cart/index.vue'dagi bilan bir xil muammo — pastdagi
             "To'lov sahifasiga o'tish" tugmasi max-md:sticky/max-md:z-50
             orqali "yopishqoq" bo'lishi kerak edi, lekin bu klasslar
             piyola.css'da kompilyatsiya qilinmagan (jonli tekshirildi),
             shu sabab u aslida oddiy static holatda edi. Endi savatchadagi
             kabi flex-grow + min-height texnikasi qo'llanildi. -->
        <div
          class="flex max-lg:flex-col gap-2 md:gap-3 lg:gap-5"
          :style="isMobile ? { minHeight: 'calc(100dvh - 71px)' } : {}"
        >
          <!-- Main Form Content -->
          <form class="w-full space-y-2 md:space-y-4" @submit.prevent="submitOrder">
            
            <!-- Section 1: Buyurtmani oluvchi (Recipient) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <h2 class="text-xl font-bold m-0">Buyurtmani oluvchi</h2>
              <div class="mt-4">
                <label for="fullName" class="block font-medium text-neutral-800 text-base mb-1">To'liq ism</label>
                <div class="relative">
                  <input v-model="form.fullName" type="text" id="fullName" placeholder="Ismingizni kiriting" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-white border border-transparent focus:border-primary/20 transition-all">
                </div>
              </div>
              <div class="mt-4">
                <label for="phone" class="block font-medium text-neutral-800 text-base mb-1">Telefon raqam</label>
                <div class="relative">
                  <input v-model="form.phone" type="tel" id="phone" placeholder="+998" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-white border border-transparent focus:border-primary/20 transition-all">
                </div>
              </div>
            </div>

            <!-- Section 2: Yetkazib berish manzili (Delivery Address) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold m-0">Yetkazib berish manzili</h2>
              </div>

              <!-- MUHIM: profil (profile/info.vue) bilan bir xil holat mantig'i.
                   Userning saqlangan manzillari (`v1/kitobchi/locations`) yuklanmoqda
                   bo'lsa — skeleton; agar saqlangan manzillar mavjud bo'lsa —
                   gorizontal scroll orqali tanlanadigan kartochkalar + "Manzil
                   qo'shish" kartasi (bosilganda profildagi bilan bir xil modal
                   ochiladi); agar umuman manzil bo'lmasa — to'g'ridan-to'g'ri
                   Viloyat → Tuman → Mahalla/qishloq formasi (UzAddressPicker)
                   ko'rsatiladi, chunki tanlaydigan hech narsa yo'q. -->

              <!-- Yuklanmoqda -->
              <div v-if="addressesLoading" class="flex gap-3 overflow-x-auto pb-1">
                <div v-for="n in 2" :key="n" class="shrink-0 w-64 h-[76px] rounded-2xl bg-white/60 animate-pulse"></div>
              </div>

              <!-- Saqlangan manzillar bor: gorizontal scroll orqali tanlash -->
              <div v-else-if="savedAddresses.length > 0" class="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1 snap-x snap-mandatory">
                <button
                  v-for="loc in savedAddresses"
                  :key="loc.id"
                  type="button"
                  @click="selectedAddressId = loc.id"
                  class="shrink-0 snap-start w-64 text-left rounded-2xl p-4 border-2 transition-all cursor-pointer bg-white"
                  :class="selectedAddressId === loc.id ? 'border-primary' : 'border-transparent hover:border-neutral-200'"
                >
                  <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-[#F6F6F9] flex items-center justify-center shrink-0">
                      <svg class="w-[18px] h-[18px] text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm text-neutral-900 font-medium m-0 line-clamp-2">{{ loc.fullAddress }}</p>
                      <span v-if="mainAddressId === loc.id" class="text-primary text-xs font-semibold">Asosiy manzil</span>
                    </div>
                  </div>
                </button>

                <button
                  type="button"
                  @click="openAddAddressModal"
                  class="shrink-0 snap-start w-36 rounded-2xl p-4 border-2 border-dashed border-neutral-300 hover:border-primary/50 transition-all cursor-pointer bg-white/60 flex flex-col items-center justify-center gap-1.5 text-primary"
                >
                  <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                  <span class="text-sm font-medium text-center">Manzil qo'shish</span>
                </button>
              </div>

              <!-- Saqlangan manzil umuman yo'q: to'g'ridan-to'g'ri forma -->
              <template v-else>
                <UzAddressPicker ref="addressPickerRef" @update="onAddrUpdate" />

                <div v-if="addressSummary.fullAddress" class="mt-3 p-3.5 rounded-2xl bg-white text-sm text-neutral-600">
                  {{ addressSummary.fullAddress }}
                </div>
              </template>
            </div>

            <!-- Section 3: To'lov turi (Payment Method) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <h2 class="text-xl font-bold m-0 mb-4">To'lov turi</h2>
              <div class="text-sm">
                <fieldset class="grid sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-5 border-none p-0 m-0">
                  
                  <label v-for="method in paymentMethods" :key="method.id" class="flex items-start flex-row text-sm p-3.5 w-full sm:pr-6 rounded-2xl bg-white border-2 transition-all cursor-pointer" :class="form.paymentMethod === method.id ? 'border-primary' : 'border-transparent'">
                    <input type="radio" :value="method.id" v-model="form.paymentMethod" class="sr-only">
                    <div class="flex items-center h-5 sm:hidden my-auto mr-3">
                      <div class="rounded-full w-4 h-4 border-2 flex items-center justify-center transition-colors" :class="form.paymentMethod === method.id ? 'border-primary' : 'border-neutral-300'">
                        <div v-if="form.paymentMethod === method.id" class="w-2 h-2 bg-primary rounded-full"></div>
                      </div>
                    </div>
                    <div class="w-full">
                      <div class="block font-medium text-neutral-900">
                        <div class="w-full sm:mx-auto max-sm:flex-row-reverse flex flex-col justify-between items-center sm:gap-2">
                          <div class="h-6 sm:h-8 max-w-[110px] flex items-center justify-center text-primary font-bold text-lg italic my-auto">{{ method.name }}</div>
                          <p class="sm:mt-1 leading-6 font-base m-0">{{ method.label }}</p>
                        </div>
                      </div>
                    </div>
                  </label>

                </fieldset>
              </div>
            </div>
          </form>

          <!-- Right Side: Order Summary -->
          <div
            class="lg:w-[400px] w-full shrink-0 h-fit lg:sticky top-24"
            :style="isMobile ? { display: 'flex', flexDirection: 'column', flexGrow: 1 } : {}"
          >
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50">
              
              <div class="space-y-4 mb-4 border-b border-neutral-200/50 pb-4">
                <div class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>{{ cartStore.selectedCount }} ta mahsulot</span><span class="text-neutral-900 font-medium">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
                <div v-if="cartStore.totalDiscount > 0" class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>Chegirma</span><span class="text-red-500 font-medium"> -{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
                </div>
                <div class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>Yetkazib berish narxi</span><span class="text-primary font-medium">Bepul</span>
                </div>
              </div>

              <!-- Total -->
              <div class="flex justify-between items-center font-bold text-xl text-neutral-900">
                <p class="m-0">Jami</p><p class="m-0 text-primary">{{ formatPrice(cartStore.totalAmount - cartStore.totalDiscount) }} so'm</p>
              </div>
            </div>

            <!-- Final Submit Button -->
            <div
              class="p-4 sm:p-6 md:px-0 max-md:bg-white max-md:rounded-t-2xl"
              :style="isMobile ? { marginTop: 'auto' } : {}"
            >
              <button @click="submitOrder" type="button" class="font-bold items-center justify-center transition-colors py-1.5 gap-2 text-white bg-primary hover:bg-primary/90 h-14 flex rounded-2xl text-base px-6 w-full cursor-pointer border-none shadow-sm">
                To'lov sahifasiga o'tish
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
    
    <!-- Success Modal -->
    <!-- MUHIM: bu yerda ham `<UModal>` (o'rnatilmagan `@nuxt/ui`) ishlatilgan
         edi — buyurtma muvaffaqiyatli qabul qilingandan keyingi eng muhim
         lahzada (checkout yakuni) tasdiqlash oynasi overlay sifatida emas,
         sahifa oxirida oddiy blok sifatida chizilib, foydalanuvchini
         chalg'itardi. AuthModal.vue'dagi bilan bir xil fixed-overlay
         naqshiga o'tkazildi. -->
    <div
      v-if="isSuccessOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl">
        <div class="p-8 text-center">
          <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4 text-green-500">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="text-2xl font-bold text-neutral-900 mb-2">Buyurtma qabul qilindi!</h3>
          <p class="text-sm text-neutral-600 mb-6">Tez orada operatorlarimiz siz bilan bog'lanadi.</p>
          <button @click="finishOrder" type="button" class="w-full px-4 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 transition-colors font-bold border-none cursor-pointer">Tushunarli, Asosiyga qaytish</button>
        </div>
      </div>
    </div>

    <!-- Manzil Qo'shish Modali -->
    <!-- MUHIM: profile/info.vue'dagi "Manzil qo'shish" modali bilan bir xil
         naqsh (fixed inset-0 backdrop + markazlashtirilgan oq kartochka) —
         foydalanuvchining saqlangan manzillari mavjud bo'lganda, gorizontal
         scroll ro'yxatidagi "Manzil qo'shish" kartasi bosilganda ochiladi. -->
    <div
      v-if="isAddAddressModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isAddAddressModalOpen = false"
    >
      <div class="relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full max-w-lg sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
        <button @click="isAddAddressModalOpen = false" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
          <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-2xl font-bold text-center m-0 mb-8 text-neutral-900">Manzil qo'shish</h3>
        <form @submit.prevent="handleSaveAddressModal" class="space-y-4">

          <div v-if="modalAddressError" class="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
            {{ modalAddressError }}
          </div>

          <UzAddressPicker ref="modalAddressPickerRef" @update="onModalAddrUpdate" />

          <div v-if="modalAddressSummary.fullAddress" class="p-3 rounded-xl bg-[#F6F6F9] text-sm text-neutral-600">
            {{ modalAddressSummary.fullAddress }}
          </div>

          <button type="submit" :disabled="!modalAddressSummary.isValid || savingModalAddress" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-sm">
            {{ savingModalAddress ? "Qo'shilmoqda..." : "Qo'shish" }}
          </button>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const cartStore = useCartStore()
const authStore = useAuthStore()
const router = useRouter()

const isSuccessOpen = ref(false)

// cart/index.vue'dagi bilan bir xil — piyoladagi flex-grow + min-height
// "pastga itarish" texnikasi faqat mobil kenglikda kerak.
const isMobile = ref(false)
function updateIsMobile() {
  isMobile.value = window.matchMedia('(max-width: 767.98px)').matches
}
onMounted(() => {
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', updateIsMobile)
})

const form = reactive({
  fullName: authStore.user?.name || '',
  phone: authStore.user?.phone_number || '',
  paymentMethod: 'payme'
})

// MUHIM: manzil endi GPS/geolocation yoki qo'lda kiritilgan Viloyat/Tuman
// emas, balki components/UzAddressPicker.vue orqali (Viloyat → Tuman →
// Mahalla/qishloq, MIMAXUZ/uzbekistan-regions-data) tanlanadi.
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

function onAddrUpdate(summary: typeof addressSummary.value) {
  addressSummary.value = summary
}

// ── Saqlangan manzillar (profile/info.vue bilan bir xil endpoint/mantiq) ──
// MUHIM: avval bu yerda faqat bo'sh (yangi) forma ko'rsatilardi — profildagi
// "Manzil qo'shish" bilan farqi yo'q edi, garchi userning allaqachon
// saqlangan manzillari bo'lsa ham. Endi: agar saqlangan manzillar bo'lsa —
// ular orasidan gorizontal scroll orqali tanlanadi (+ "Manzil qo'shish"
// kartasi profildagi bilan bir xil modalni ochadi); agar umuman manzil
// bo'lmasa — to'g'ridan-to'g'ri UzAddressPicker formasi ko'rsatiladi.
const savedAddresses = ref<any[]>([])
const addressesLoading = ref(true)
const selectedAddressId = ref<number | null>(null)
const mainAddressId = computed(() => (authStore.user as any)?.mainAddressID ?? null)

async function fetchAddresses() {
  addressesLoading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    savedAddresses.value = res?.data || []
    if (savedAddresses.value.length > 0) {
      const match = savedAddresses.value.find((a) => a.id === mainAddressId.value)
      selectedAddressId.value = (match || savedAddresses.value[0]).id
    }
  } catch (e) {
    savedAddresses.value = []
  } finally {
    addressesLoading.value = false
  }
}

// Manzil qo'shish modali — profile/info.vue'dagi bilan bir xil (o'z alohida
// `modalAddressSummary`/`modalAddressPickerRef` holati bilan, checkout
// formasidagi asosiy `addressSummary`ga aralashib ketmasligi uchun).
const isAddAddressModalOpen = ref(false)
const modalAddressPickerRef = ref<{ reset: () => void } | null>(null)
const modalAddressSummary = ref({
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

function openAddAddressModal() {
  modalAddressError.value = ''
  modalAddressSummary.value = {
    regionId: null, regionName: '', districtId: null, districtName: '',
    village: '', street: '', fullAddress: '', isValid: false,
  }
  isAddAddressModalOpen.value = true
  nextTick(() => modalAddressPickerRef.value?.reset())
}

function onModalAddrUpdate(summary: typeof modalAddressSummary.value) {
  modalAddressSummary.value = summary
}

async function handleSaveAddressModal() {
  if (!modalAddressSummary.value.isValid) return
  savingModalAddress.value = true
  modalAddressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        fullAddress: modalAddressSummary.value.fullAddress,
        countryCode: 'UZ',
        regionName: modalAddressSummary.value.regionName,
        districtName: modalAddressSummary.value.districtName,
        cityName: modalAddressSummary.value.village,
      }
    })
    if (res?.location_id) {
      savedAddresses.value.push({ id: res.location_id, fullAddress: modalAddressSummary.value.fullAddress })
      selectedAddressId.value = res.location_id
      authStore.updateUser({ mainAddressID: res.location_id })
    }
    isAddAddressModalOpen.value = false
  } catch (e: any) {
    modalAddressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingModalAddress.value = false
  }
}

// Buyurtma yuborish uchun "manzil to'g'ri tanlangan/kiritilgan"ligini
// bitta joydan tekshirish — holatiga qarab ikki xil manbadan keladi.
const hasValidAddress = computed(() => {
  if (savedAddresses.value.length > 0) return !!selectedAddressId.value
  return addressSummary.value.isValid
})

const paymentMethods = [
  { id: 'payme', name: 'Payme', label: "Onlayn to'lov" },
  { id: 'click', name: 'Click', label: "Onlayn to'lov" },
  { id: 'uzumnasiya', name: 'Uzum Nasiya', label: "Muddatli to'lov" },
  { id: 'cash', name: 'Naqd pul', label: "Qabul qilganda" }
]

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function submitOrder() {
  if(!form.fullName || !form.phone || !hasValidAddress.value) {
    alert("Iltimos barcha maydonlarni to'ldiring")
    return
  }
  isSuccessOpen.value = true
}

function finishOrder() {
  isSuccessOpen.value = false
  cartStore.removeSelected()
  router.push('/')
}

onMounted(() => {
  if (cartStore.selectedCount === 0) {
    router.push('/cart')
  }
  if (authStore.isAuthenticated) {
    fetchAddresses()
  } else {
    addressesLoading.value = false
  }
})

useSeoMeta({
  title: 'Buyurtma rasmiylashtirish — Kitobchi'
})
</script>
