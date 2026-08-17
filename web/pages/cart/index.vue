<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow">
    <!-- ====== MOBILE STICKY TOP BAR (Piyola 1:1) ====== -->
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
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Savatcha</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/catalog" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Savatcha</span>
        </nav>
      </div>

      <!-- Content (If has items) -->
      <div v-if="cartStore.items.length > 0" class="flex flex-col lg:flex-row gap-5 items-start">
        <!-- Left Column: Items List (Piyola 1:1) -->
        <div class="md:p-6 rounded-3xl bg-secondary-50 flex-1 space-y-4 w-full p-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-200">
            <button
              type="button"
              @click="cartStore.toggleSelectAll()"
              class="flex items-center gap-2 text-sm font-semibold text-neutral-800 border-none bg-transparent cursor-pointer p-0"
            >
              <CartCheckbox :checked="cartStore.isAllSelected" />
              <span>Barcha mahsulotlarni tanlash</span>
            </button>
            <span class="text-xs text-neutral-400 font-medium">{{ cartStore.selectedCount }} ta mahsulot tanlandi</span>
          </div>

          <!-- Item Card -->
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="rounded-[20px] p-4 bg-secondary-50 transition-colors flex flex-col sm:flex-row items-start sm:items-end gap-4"
          >
            <button
              type="button"
              @click="cartStore.toggleSelect(item.id)"
              class="border-none bg-transparent cursor-pointer p-0 shrink-0"
              aria-label="Tanlash"
            >
              <CartCheckbox :checked="item.selected" />
            </button>

            <!-- Image — mahsulot sahifasiga o'tish uchun link (ilgari oddiy
                 <img> edi, bosilganda hech qayerga ochilmasdi) -->
            <NuxtLink :to="productUrl(item)" class="w-16 h-20 rounded-xl overflow-hidden bg-secondary-100 shrink-0 flex items-center justify-center">
              <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
            </NuxtLink>

            <!-- Title & Info -->
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-neutral-900 m-0">
                <NuxtLink :to="productUrl(item)" class="line-clamp-2 hover:underline">{{ item.name }}</NuxtLink>
              </h3>
              <div class="text-base font-bold text-primary mt-1">
                {{ formatPrice(item.price) }} so‘m
              </div>
            </div>

            <!-- Quantity & Actions -->
            <div class="flex items-center gap-4 shrink-0">
              <div class="flex items-center rounded-full bg-secondary-100 p-1">
                <button
                  type="button"
                  @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                  class="w-7 h-7 rounded-full bg-white flex items-center justify-center font-bold text-neutral-700 hover:bg-neutral-50 border-none cursor-pointer"
                >
                  -
                </button>
                <span class="w-8 text-center text-sm font-bold">{{ item.quantity }}</span>
                <button
                  type="button"
                  @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                  class="w-7 h-7 rounded-full bg-white flex items-center justify-center font-bold text-neutral-700 hover:bg-neutral-50 border-none cursor-pointer"
                >
                  +
                </button>
              </div>

              <!-- Sevimlilarga ko'chirish — piyoladagi kabi yurakcha
                   ikonkasi. Haqiqiy `favorites` do'koniga yozadi (mahsulot
                   nomi/narxi/rasmi savatdagi haqiqiy ma'lumotdan olinadi —
                   hech narsa o'ylab topilmagan). -->
              <button
                type="button"
                @click="moveToFavorites(item)"
                class="w-8 h-8 rounded-full bg-secondary-100 text-neutral-400 hover:text-primary-500 flex items-center justify-center border-none cursor-pointer transition-colors"
                title="Sevimlilarga qo‘shish"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
              </button>

              <button
                type="button"
                @click="cartStore.removeItem(item.id)"
                class="w-8 h-8 rounded-full bg-secondary-100 text-neutral-400 hover:text-red-500 flex items-center justify-center border-none cursor-pointer transition-colors"
                title="O‘chirish"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Column: Summary Card (Piyola 1:1) -->
        <div class="p-4 md:p-6 rounded-3xl bg-secondary-50 sticky top-24 space-y-4 border border-gray-100 lg:w-100 shrink-0 w-full">
          <h2 class="text-xl font-bold text-neutral-900 m-0">Buyurtmangiz</h2>

          <div class="space-y-2 text-sm">
            <div class="flex justify-between text-neutral-600">
              <span>Mahsulotlar ({{ cartStore.selectedCount }}):</span>
              <span class="font-bold text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so‘m</span>
            </div>
            <div class="flex justify-between text-neutral-600">
              <span>Yetkazib berish:</span>
              <span class="font-bold text-green-500">Bepul</span>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
            <span class="text-base font-bold text-neutral-900">Jami to‘lov:</span>
            <span class="text-2xl font-bold text-primary">{{ formatPrice(cartStore.totalAmount) }} so‘m</span>
          </div>

          <button
            type="button"
            @click="handleCheckout"
            :disabled="cartStore.selectedCount === 0"
            class="w-full py-4 rounded-2xl bg-primary text-white font-bold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer shadow-lg disabled:opacity-75"
          >
            Rasmiylashtirishga o‘tish
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-20">
        <div class="w-24 h-24 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <i class="icon-order text-4xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Savatchangiz bo‘sh</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">
          Bosh sahifa yoki katalogdan o‘zingizga ma’qul kitoblarni tanlab, savatchaga qo‘shing.
        </p>
        <NuxtLink
          to="/catalog"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors"
        >
          Xaridni boshlash
        </NuxtLink>
      </div>
    </div>

    <!-- Buyurtma tasdiqlash oynasi — AuthModal bilan bir xil uslub.
         Haqiqiy to'lov integratsiyasi ulanmagani uchun bu shunchaki
         vizual tasdiqlash, real buyurtma backendga yuborilmaydi. -->
    <div
      v-if="isOrderConfirmOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="closeOrderConfirm"
    >
      <div class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-400 text-white mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <h2 class="text-xl font-bold text-neutral-900 mb-2">Buyurtmangiz qabul qilindi!</h2>
        <p class="text-sm text-neutral-500 mb-6">
          Operatorimiz tez orada siz bilan bog‘lanib, buyurtmani tasdiqlaydi.
        </p>
        <button
          type="button"
          @click="closeOrderConfirm"
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer"
        >
          Tushunarli
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import type { CartItem } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { useFavoritesStore } from '~/stores/favorites'

const cartStore = useCartStore()
const authStore = useAuthStore()
const favStore = useFavoritesStore()
const router = useRouter()
const isOrderConfirmOpen = ref(false)

// Savatdagi mahsulotni Sevimlilarga ko'chirish (piyoladagi yurakcha
// tugmasi). `resolveProductImage`ning `image_urls` ustuvorligidan
// foydalanib, savatda ALLAQACHON aniqlangan haqiqiy rasmni saqlab qolamiz
// (aks holda placeholder logotipga tushib qolar edi).
function moveToFavorites(item: CartItem) {
  favStore.toggleFavorite({
    id: item.productId,
    name: item.name,
    price: item.originalPrice,
    discountPrice: item.price < item.originalPrice ? item.price : undefined,
    image_urls: [item.image]
  }, item.type)
}

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

// Savatdagi mahsulot rasmi/nomi bosilganda mahsulot sahifasiga o'tish
// uchun — ProductCard.vue'dagi bilan bir xil slug mantig'i.
function productUrl(item: CartItem) {
  const slug = (item.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return item.type === 'stationery' ? `/stationery/${item.productId}-${slug}` : `/books/${item.productId}-${slug}`
}

// MUHIM: haqiqiy to'lov integratsiyasi ULANMAGAN (loyihaning aniq shartiga
// ko'ra) — shuning uchun bu yerda haqiqiy buyurtma backendga yuborilmaydi.
// Ilgari bu joyda brauzerning standart `alert()`i ishlatilardi — bu ilova
// dizayniga mos kelmaydi. Endi shu sahifaning o'zida, qolgan modal'lar
// (masalan AuthModal) bilan bir xil uslubda tasdiqlash oynasi ko'rsatiladi.
function handleCheckout() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }
  isOrderConfirmOpen.value = true
}

function closeOrderConfirm() {
  isOrderConfirmOpen.value = false
  // Tanlangan mahsulotlarni "buyurtma qilingan" deb hisoblab savatdan
  // olib tashlaymiz — real backend integratsiyasi bo'lmasa-da, foydalanuvchi
  // uchun oqim tugallangandek his qilinishi kerak.
  cartStore.removeSelected()
}

useSeoMeta({
  title: 'Savatcha — Kitobchi',
  description: 'Tanlangan kitoblar va xaridlar savatchasi.'
})
</script>
