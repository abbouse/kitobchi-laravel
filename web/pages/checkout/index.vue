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

        <div class="flex max-lg:flex-col gap-2 md:gap-3 lg:gap-5">
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
              <!-- MUHIM: avvalgi Viloyat/Tuman selectlari qattiq
                   kodlangan (hardcoded), haqiqiy ma'lumotga bog'lanmagan
                   5 tagina soxta variant edi. Endi haqiqiy Viloyat →
                   Tuman → Mahalla/qishloq tanlovi (MIMAXUZ/
                   uzbekistan-regions-data) ishlatiladi, GPS/lat-lon
                   shart emas. -->
              <UzAddressPicker ref="addressPickerRef" @update="onAddrUpdate" />

              <div v-if="addressSummary.fullAddress" class="mt-3 p-3.5 rounded-2xl bg-white text-sm text-neutral-600">
                {{ addressSummary.fullAddress }}
              </div>
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
          <div class="lg:w-[400px] w-full shrink-0 h-fit lg:sticky top-24">
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
            <div class="p-4 sm:p-6 md:px-0 max-md:bg-white max-md:mt-2 max-md:rounded-t-2xl max-md:sticky max-md:bottom-0 max-md:z-50 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] md:shadow-none">
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
  </main>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()
const router = useRouter()

const isSuccessOpen = ref(false)

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
  if(!form.fullName || !form.phone || !addressSummary.value.isValid) {
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
})

useSeoMeta({
  title: 'Buyurtma rasmiylashtirish — Kitobchi'
})
</script>
