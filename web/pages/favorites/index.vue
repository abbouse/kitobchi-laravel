<template>
  <div class="py-3 md:py-6 min-h-dvh bg-[#f0f2f5] grow">
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
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Sevimlilar</h1>
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
          <span class="text-neutral-900 font-semibold">Sevimlilar</span>
        </nav>
      </div>

      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl sm:text-3xl text-primary font-bold m-0 max-md:hidden">
          Sevimlilar
        </h1>
        <span class="text-sm text-neutral-400 font-medium">
          {{ favStore.count }} ta mahsulot
        </span>
      </div>

      <!-- Grid (If has favorites) -->
      <div v-if="favStore.items.length > 0" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-10">
        <ProductCard
          v-for="item in favStore.items"
          :key="item.productId"
          :product="{
            id: item.productId,
            name: item.name,
            price: item.price,
            discountPrice: item.discountPrice,
            image_urls: [item.image],
            medium_images: [item.image],
            first_image: item.image
          }"
          :type="item.type"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-20">
        <!-- MUHIM (bug tuzatildi): bu yerda oldin `<i class="icon-heart">`
             icon-font klassi ishlatilgan edi, lekin loyihada bunday icon
             font UMUMAN ulanmagan (repo bo'ylab qidiruv tasdiqladi) —
             natijada bo'sh doira ko'rinardi (ikonka butunlay yo'qolgan).
             Boshqa barcha bo'sh-holat ikonkalari kabi inline SVG'ga
             almashtirildi. -->
        <div class="w-24 h-24 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <svg class="w-12 h-12 text-primary" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </div>
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Sevimlilar ro‘yxati bo‘sh</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">
          O‘zingizga yoqqan kitoblarni yurakcha tugmasi orqali bu yerga saqlab qo‘yishingiz mumkin.
        </p>
        <NuxtLink
          to="/catalog"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors"
        >
          Katalogga o‘tish
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useFavoritesStore } from '~/stores/favorites'

const favStore = useFavoritesStore()

useSeoMeta({
  title: 'Sevimlilar — Kitobchi',
  description: 'Saqlangan sevimli kitoblar va mahsulotlar.'
})
</script>
