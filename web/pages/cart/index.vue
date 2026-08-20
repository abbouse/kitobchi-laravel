<template>
  <main class="max-md:grow h-full md:min-h-dvh max-md:pb-[71px] bg-[#F1F2F7]">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-[--ui-container] mx-auto py-4 md:py-6">
      
      <!-- Desktop Breadcrumb -->
      <div class="mb-5 max-md:hidden">
        <div class="flex items-center gap-2">
          <button type="button" @click="$router.back()" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer" aria-label="Orqaga">
            <span class="iconify i-lucide:arrow-left shrink-0 size-5" aria-hidden="true"></span>
          </button>
          <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
            <NuxtLink to="/" class="hover:text-neutral-900 transition-colors no-underline">Asosiy</NuxtLink>
            <span>/</span>
            <span class="text-neutral-600 font-medium">Savat</span>
          </nav>
        </div>
      </div>

      <!-- Main Layout Split -->
      <div v-if="cartStore.items.length > 0" class="flex flex-col lg:flex-row gap-5 lg:items-start min-h-[calc(100dvh-140px)]">
        
        <!-- Left Column (Items List) -->
        <div class="flex-1 space-y-4">
          <!-- Select All Header -->
          <div class="flex flex-col gap-4">
            <h2 class="text-xl font-bold flex items-center gap-2 m-0">Savat <span class="text-neutral-400 text-sm font-medium leading-5">{{ cartStore.totalCount }} ta mahsulot</span></h2>

            <div class="flex gap-4 items-center justify-between">
              <div class="flex gap-4 items-center">
                <div class="relative flex items-start flex-row">
                  <div class="flex items-center h-6">
                    <button @click="cartStore.toggleSelectAll()" class="rounded-sm ring ring-inset ring-neutral-200 overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer transition-colors" type="button">
                      <span class="flex items-center justify-center size-full text-white transition-colors" :class="cartStore.isAllSelected ? 'bg-primary' : 'bg-transparent border border-neutral-300'">
                        <svg v-if="cartStore.isAllSelected" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                      </span>
                    </button>
                  </div>
                  <div class="w-full ms-2 text-base">
                    <label @click="cartStore.toggleSelectAll()" class="block font-medium text-neutral-800 cursor-pointer">Barcha mahsulotlarni tanlash</label>
                  </div>
                </div>
                <span class="text-neutral-400 text-sm font-medium leading-5 hidden sm:inline">{{ cartStore.selectedCount }} ta mahsulot tanlandi</span>
              </div>
              
              <!-- Delete Selected (Kitobchi extra but useful) -->
              <button
                type="button"
                @click="cartStore.removeSelected()"
                :disabled="cartStore.selectedCount === 0"
                class="p-1.5 text-neutral-400 hover:text-red-500 disabled:opacity-40 transition-colors border-none bg-transparent cursor-pointer"
                aria-label="Tanlanganlarni o'chirish"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21q.512.078 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48 48 0 0 0-3.478-.397m-12 .562q.51-.088 1.022-.165m0 0a48 48 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a52 52 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49 49 0 0 0-7.5 0"/></svg>
              </button>
            </div>
          </div>

          <!-- Items -->
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="rounded-[20px] px-[14px] py-[18px] bg-white flex gap-4 border border-transparent hover:border-neutral-200 transition-colors"
          >
            <!-- Checkbox -->
            <div>
              <div class="relative flex items-start flex-row">
                <div class="flex items-center h-6 mt-1 sm:mt-0">
                  <button @click.stop="cartStore.toggleSelect(item.id)" class="rounded-sm ring ring-inset ring-neutral-200 overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer transition-colors" type="button">
                    <span class="flex items-center justify-center size-full text-white transition-colors" :class="item.selected ? 'bg-primary' : 'bg-transparent border border-neutral-300'">
                      <svg v-if="item.selected" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                    </span>
                  </button>
                </div>
              </div>
            </div>

            <div class="flex gap-3 flex-1 overflow-hidden max-sm:flex-col sm:flex-row">
              <!-- Image -->
              <NuxtLink :to="productUrl(item)" class="shrink-0 flex justify-center sm:block">
                <img class="w-[100px] h-[133px] rounded-xl object-contain bg-[#F1F2F7] shrink-0" :src="item.image" :alt="item.name">
              </NuxtLink>
              
              <div class="flex flex-col justify-between flex-1 min-w-0">
                <div class="space-y-2">
                  <div class="flex justify-between items-start gap-4">
                    <!-- Title -->
                    <NuxtLink :to="productUrl(item)" class="text-sm leading-5 font-normal lg:max-w-[70%] line-clamp-2 text-neutral-900 no-underline hover:text-primary">
                      {{ item.name }}
                    </NuxtLink>
                    
                    <!-- Favorite & Delete Actions -->
                    <div class="flex shrink-0">
                      <button @click="moveToFavorites(item)" aria-label="Favorite button" class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 bg-transparent border-none cursor-pointer p-0 text-neutral-400 hover:text-red-500">
                        <svg class="w-5 h-5 relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                      </button>
                      <button @click="cartStore.removeItem(item.id)" type="button" class="rounded-md font-medium inline-flex items-center transition-colors text-sm gap-1.5 text-neutral-400 hover:text-red-500 bg-transparent border-none cursor-pointer p-1.5">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21q.512.078 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48 48 0 0 0-3.478-.397m-12 .562q.51-.088 1.022-.165m0 0a48 48 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a52 52 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49 49 0 0 0-7.5 0"/></svg>
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Price & Quantity Counter -->
                <div class="flex items-center justify-between md:justify-end gap-6 pt-2 mt-auto">
                  <h2 class="text-lg font-bold shrink-0 m-0">{{ formatPrice(item.price) }} so'm</h2>
                  
                  <div class="relative inline-flex items-center" role="group">
                    <button @click="cartStore.updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1" type="button" class="absolute flex items-center inset-y-0 start-0 ps-1 rounded-md font-medium inline-flex items-center disabled:opacity-50 transition-colors text-sm text-neutral-800 hover:text-primary bg-transparent border-none cursor-pointer p-1.5 z-10">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                    </button>
                    <input type="text" readonly :value="item.quantity" class="w-full border-0 transition-colors px-2.5 py-1.5 text-base/5 font-semibold text-neutral-900 focus:outline-none text-center ps-9 pe-9 md:text-sm bg-[#F1F2F7] max-w-[120px] rounded-xl h-10">
                    <button @click="cartStore.updateQuantity(item.id, item.quantity + 1)" type="button" class="absolute flex items-center inset-y-0 end-0 pe-1 rounded-md font-medium inline-flex items-center transition-colors text-sm text-neutral-800 hover:text-primary bg-transparent border-none cursor-pointer p-1.5 z-10">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column (Order Summary) -->
        <div class="lg:w-100 shrink-0 space-y-4 sticky top-24">
          
          <div class="p-4 sm:p-6 rounded-2xl bg-white space-y-2 sm:space-y-3 md:space-y-4 shadow-sm border border-neutral-100/50">
            <!-- Promo code input -->
            <div class="relative inline-flex items-center w-full">
              <input v-model="promoCode" type="text" placeholder="Promokod" class="w-full appearance-none placeholder:text-neutral-400 text-base/5 text-neutral-900 focus:outline-none md:text-sm rounded-2xl p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
            </div>
            
            <!-- Total Amounts -->
            <div class="space-y-4 pt-2">
              <div class="flex justify-between text-neutral-500 text-sm md:text-base">
                <span>{{ cartStore.selectedCount }} ta mahsulot</span><span class="font-medium text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
              </div>
              <div v-if="cartStore.totalDiscount > 0" class="flex justify-between text-neutral-500 text-sm md:text-base">
                <span>Chegirma</span><span class="font-medium text-red-500"> -{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
              </div>
              <div class="flex justify-between text-neutral-500 text-sm md:text-base">
                <span>Yetkazib berish narxi</span><span class="font-medium text-neutral-900">Bepul</span>
              </div>
            </div>
            
            <div class="flex justify-between items-center bg-white pt-2 border-t border-neutral-100 mt-2">
              <span class="text-xl font-bold text-neutral-900">Jami</span>
              <span class="text-xl font-bold text-primary">{{ formatPrice(cartStore.totalAmount - cartStore.totalDiscount) }} so'm</span>
            </div>
          </div>

          <!-- Muddatli to'lov box (Kitobchi version) -->
          <div class="p-4 sm:p-6 rounded-t-2xl md:rounded-2xl bg-white md:space-y-4 shadow-sm border border-neutral-100/50">
            <div class="flex items-center justify-between">
              <h3 class="md:text-lg font-semibold leading-6 md:max-w-[200px] m-0 text-neutral-900">Muddatli to‘lovga rasmiylashtirish</h3>
              <button @click="isInstallmentActive = !isInstallmentActive" class="w-12 h-6 rounded-full transition-colors relative border-none cursor-pointer p-0" :class="isInstallmentActive ? 'bg-primary' : 'bg-neutral-200'">
                <span class="absolute top-1 bg-white w-4 h-4 rounded-full transition-all shadow-sm" :class="isInstallmentActive ? 'left-7' : 'left-1'"></span>
              </button>
            </div>
            <div class="text-neutral-500 text-sm max-md:hidden mt-2">Muddatli to'lovni yoqish orqali xaridingizni qismlarga bo'ling</div>
          </div>

          <!-- Checkout Button -->
          <button @click="handleCheckout" :disabled="cartStore.selectedCount === 0" type="button" class="inline-flex items-center justify-center transition-colors px-2.5 py-1.5 gap-1.5 hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed outline-none w-full bg-primary text-white rounded-2xl h-14 text-base font-bold border-none cursor-pointer">
            Rasmiylashtirishga o'tish <span class="iconify i-heroicons:arrow-right w-5 h-5 ml-1" aria-hidden="true"></span>
          </button>
        </div>

      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-20 px-4 mt-10">
        <div class="w-32 h-32 rounded-full bg-white flex items-center justify-center shadow-sm mb-6 border border-neutral-100">
          <svg class="w-16 h-16 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
        </div>
        <h2 class="text-2xl font-bold text-neutral-900 mb-2">Savatingiz bo'sh</h2>
        <p class="text-neutral-500 mb-8 text-center max-w-md">Katalogdan o'zingizga yoqqan mahsulotlarni tanlang va savatga qo'shing</p>
        <NuxtLink to="/" class="px-8 py-3.5 bg-primary text-white font-semibold rounded-2xl hover:bg-primary/90 transition-colors shadow-sm no-underline inline-block">
          Asosiy sahifaga qaytish
        </NuxtLink>
      </div>

    </div>

    <!-- Modals -->
    <!-- Buyurtmani tasdiqlash modal -->
    <UModal v-model="isOrderConfirmOpen">
      <div class="p-6">
        <h3 class="text-xl font-bold text-neutral-900 mb-4">Buyurtmani tasdiqlash</h3>
        <p class="text-sm text-neutral-600 mb-6">Tanlangan mahsulotlarni xarid qilishni tasdiqlaysizmi?</p>
        <div class="flex justify-end gap-3">
          <button @click="closeOrderConfirm" class="px-4 py-2 rounded-xl border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 transition-colors font-medium cursor-pointer">Bekor qilish</button>
          <button @click="submitCheckout" class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primary/90 transition-colors font-medium border-none cursor-pointer">Tasdiqlash</button>
        </div>
      </div>
    </UModal>

    <!-- Buyurtma muvaffaqiyatli modal -->
    <UModal v-model="isOrderSuccessOpen">
      <div class="p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4 text-green-500">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="text-2xl font-bold text-neutral-900 mb-2">Buyurtma qabul qilindi!</h3>
        <p class="text-sm text-neutral-600 mb-6">Tez orada operatorlarimiz siz bilan bog'lanadi.</p>
        <button @click="isOrderSuccessOpen = false" class="w-full px-4 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 transition-colors font-bold border-none cursor-pointer">Tushunarli</button>
      </div>
    </UModal>

  </main>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import type { CartItem } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { useFavoritesStore } from '~/stores/favorites'

const cartStore = useCartStore()
const authStore = useAuthStore()
const favStore = useFavoritesStore()

const activeMenuId = ref<string | number | null>(null)
// Piyola'dagi "Promokod" maydoniga vizual parallellik — yuqoridagi
// shablon izohiga qarang (haqiqiy tekshiruv hali ulanmagan).
const promoCode = ref('')
const isInstallmentActive = ref(false)
const isDrawerOpen = ref(false)
const selectedInstallmentMonths = ref(12)
const tempMonths = ref(12)
const isOrderConfirmOpen = ref(false)
const isOrderSuccessOpen = ref(false)
const checkoutForm = reactive({
  name: authStore.user?.name || '',
  phone: authStore.user?.phone_number ? String(authStore.user.phone_number).replace(/^998/, '') : '',
  region: '',
  district: '',
  address: ''
})

const monthlyPayment = computed(() => {
  if (!cartStore.totalAmount || selectedInstallmentMonths.value <= 0) return 0
  return Math.round(cartStore.totalAmount / selectedInstallmentMonths.value)
})

function toggleItemMenu(id: string | number) {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

// Click outside to close active item menu
onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
      activeMenuId.value = null
    })
  }
})

function moveToFavorites(item: CartItem) {
  favStore.toggleFavorite({
    id: item.productId,
    name: item.name,
    price: item.originalPrice,
    discountPrice: item.price < item.originalPrice ? item.price : undefined,
    image_urls: [item.image]
  }, item.type)
  cartStore.removeItem(item.id)
}

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function productUrl(item: CartItem) {
  const slug = (item.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return item.type === 'stationery' ? `/stationery/${item.productId}-${slug}` : `/books/${item.productId}-${slug}`
}

function handleCheckout() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }
  isOrderConfirmOpen.value = true
}

function closeOrderConfirm() {
  isOrderConfirmOpen.value = false
}

function submitCheckout() {
  isOrderConfirmOpen.value = false
  isOrderSuccessOpen.value = true
  cartStore.removeSelected()
}

useSeoMeta({
  title: 'Savatcha — Kitobchi',
  description: 'Tanlangan kitoblar va xaridlar savatchasi.'
})
</script>
