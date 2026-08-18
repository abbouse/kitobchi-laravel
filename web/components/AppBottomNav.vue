<template>
  <!-- Floating Bottom Nav Pill — piyoladagi HAQIQIY kodidan (jonli DOM'dan)
       bevosita olingan struktura: fixed bottom-4 left-0 right-0 (chetlardan
       masofa margin emas, balki padding — px-4 orqali), pill ichi CSS grid
       emas, flex (har bir band flex-1 h-full). Piyolada JAMI 4 TA BAND bor
       — "Sevimlilar" (Saralangan) piyolaning pastki navigatsiyasida umuman
       yo'q (jonli tekshirilib tasdiqlandi: Asosiy / Kataloglar / Savatcha /
       Profil). Ikonkalar — piyolaning @iconify/heroicons-solid orqali
       render qilingan haqiqiy SVG path'laridan bevosita olingan (o'zimiz
       chizgan taxminiy ikonkalar emas). Faol havolaning ikonkasi piyolada
       maxsus ochroq ko'k rangda (#4C8CE4) — matn esa asosiy (primary)
       rangda, bu piyolaning aynan o'zidagi nuance. -->
  <div
    v-if="!isHiddenPage"
    class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto fixed bottom-4 left-0 right-0 z-40 max-h-15 md:hidden pointer-events-none"
    style="padding-bottom: env(safe-area-inset-bottom, 0px)"
  >
    <div class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm hover:shadow-black/10 glass-card-bg flex items-center justify-between p-1 bg-white/70 backdrop-blur-sm pointer-events-auto">
      <!-- glass-border — piyoladagi nozik shisha-effekti chegarasi -->
      <div class="absolute inset-0 pointer-events-none glass-border rounded-full"></div>

      <!-- 1. Bosh sahifa -->
      <NuxtLink
        to="/"
        :class="[
          'relative flex flex-col items-center justify-center p-1.5 flex-1 h-full transition-colors',
          $route.path === '/' ? 'bg-[#EDEDED] rounded-full text-primary font-semibold' : 'text-gray font-medium'
        ]"
      >
        <div class="relative inline-flex">
          <svg class="w-5 h-5" :class="$route.path === '/' ? 'text-[#4C8CE4]' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center">Asosiy</span>
      </NuxtLink>

      <!-- 2. Kataloglar -->
      <NuxtLink
        to="/catalog"
        :class="[
          'relative flex flex-col items-center justify-center p-1.5 flex-1 h-full transition-colors',
          $route.path.startsWith('/catalog') ? 'bg-[#EDEDED] rounded-full text-primary font-semibold' : 'text-gray font-medium'
        ]"
      >
        <div class="relative inline-flex">
          <svg class="w-5 h-5" :class="$route.path.startsWith('/catalog') ? 'text-[#4C8CE4]' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.127 3.502L5.25 3.5h9.5c.041 0 .082 0 .123.002A2.251 2.251 0 0012.75 2h-5.5a2.25 2.25 0 00-2.123 1.502zM1 10.25A2.25 2.25 0 013.25 8h13.5A2.25 2.25 0 0119 10.25v5.5A2.25 2.25 0 0116.75 18H3.25A2.25 2.25 0 011 15.75v-5.5zM3.25 6.5c-.04 0-.082 0-.123.002A2.25 2.25 0 015.25 5h9.5c.98 0 1.814.627 2.123 1.502a3.819 3.819 0 00-.123-.002H3.25z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center">Kataloglar</span>
      </NuxtLink>

      <!-- 3. Savatcha -->
      <NuxtLink
        to="/cart"
        :class="[
          'relative flex flex-col items-center justify-center p-1.5 flex-1 h-full transition-colors',
          $route.path === '/cart' ? 'bg-[#EDEDED] rounded-full text-primary font-semibold' : 'text-gray font-medium'
        ]"
      >
        <div class="relative inline-flex">
          <svg class="w-5 h-5" :class="$route.path === '/cart' ? 'text-[#4C8CE4]' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4m2 5V6a2 2 0 10-4 0v1zm-6 3a1 1 0 112 0 1 1 0 01-2 0m7-1a1 1 0 100 2 1 1 0 000-2"/>
          </svg>
          <span
            v-if="cartStore.totalCount > 0"
            class="absolute right-0 top-0 translate-x-1/2 -translate-y-1/2 border border-white text-[10px] flex items-center justify-center text-white w-4 h-4 bg-red-500 rounded-full"
          >
            {{ cartStore.totalCount > 99 ? '99+' : cartStore.totalCount }}
          </span>
        </div>
        <span class="text-xs font-medium text-center">Savatcha</span>
      </NuxtLink>

      <!-- 4. Profil -->
      <NuxtLink
        to="/profile"
        :class="[
          'relative flex flex-col items-center justify-center p-1.5 flex-1 h-full transition-colors',
          $route.path === '/profile' ? 'bg-[#EDEDED] rounded-full text-primary font-semibold' : 'text-gray font-medium'
        ]"
      >
        <div class="relative inline-flex">
          <svg class="w-5 h-5" :class="$route.path === '/profile' ? 'text-[#4C8CE4]' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6m-7 9a7 7 0 1114 0z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center">Profil</span>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'

const cartStore = useCartStore()
const route = useRoute()

// Piyolada mahsulot va savatcha sahifalarida (books/[id].vue,
// stationery/[id].vue, cart/index.vue) pastki pill-navigatsiya
// UMUMAN RENDER QILINMAYDI — chunki ularda pastda o'zining "buyurtma berish"
// / "rasmiylashtirish" sticky panellari bor.
const isHiddenPage = computed(() => {
  const p = route.path
  return p === '/cart' || p.startsWith('/books/') || p.startsWith('/stationery/') || p === '/profile/edit' || p.startsWith('/profile/address')
})
</script>
