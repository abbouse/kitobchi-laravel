<template>
  <!-- Floating Bottom Nav Pill (Piyola 1:1) -->
  <div class="fixed bottom-4 left-4 right-4 z-40 max-h-15 md:hidden pointer-events-none">
    <div class="glass-card-bg rounded-full p-1 shadow-lg pointer-events-auto border border-white/15 backdrop-blur-md">
      <div class="grid grid-cols-5 items-center gap-1">
        <!-- 1. Bosh sahifa -->
        <NuxtLink
          to="/"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-2 rounded-full transition-all text-xs font-medium',
            $route.path === '/' ? 'bg-[#EDEDED] text-primary font-semibold' : 'text-neutral-600 hover:text-primary/75'
          ]"
        >
          <svg class="w-5 h-5 mb-0.5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"/>
            <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"/>
          </svg>
          <span>Asosiy</span>
        </NuxtLink>

        <!-- 2. Katalog -->
        <NuxtLink
          to="/catalog"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-2 rounded-full transition-all text-xs font-medium',
            $route.path.startsWith('/catalog') ? 'bg-[#EDEDED] text-primary font-semibold' : 'text-neutral-600 hover:text-primary/75'
          ]"
        >
          <svg class="w-5 h-5 mb-0.5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H4.5zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z"/>
          </svg>
          <span>Katalog</span>
        </NuxtLink>

        <!-- 3. Savatcha -->
        <NuxtLink
          to="/cart"
          :class="[
            'relative flex flex-col items-center justify-center py-1.5 px-2 rounded-full transition-all text-xs font-medium',
            $route.path === '/cart' ? 'bg-[#EDEDED] text-primary font-semibold' : 'text-neutral-600 hover:text-primary/75'
          ]"
        >
          <div class="relative">
            <i class="icon-order text-lg"></i>
            <span
              v-if="cartStore.totalCount > 0"
              class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] absolute -top-1 -right-2 bg-[#ED3131] text-white font-bold"
            >
              {{ cartStore.totalCount > 99 ? '99+' : cartStore.totalCount }}
            </span>
          </div>
          <span>Savat</span>
        </NuxtLink>

        <!-- 4. Sevimlilar -->
        <NuxtLink
          to="/favorites"
          :class="[
            'relative flex flex-col items-center justify-center py-1.5 px-2 rounded-full transition-all text-xs font-medium',
            $route.path === '/favorites' ? 'bg-[#EDEDED] text-primary font-semibold' : 'text-neutral-600 hover:text-primary/75'
          ]"
        >
          <div class="relative">
            <i class="icon-heart text-lg"></i>
            <span
              v-if="favStore.count > 0"
              class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] absolute -top-1 -right-2 bg-[#ED3131] text-white font-bold"
            >
              {{ favStore.count > 99 ? '99+' : favStore.count }}
            </span>
          </div>
          <span>Saralangan</span>
        </NuxtLink>

        <!-- 5. Profil -->
        <NuxtLink
          to="/profile"
          :class="[
            'flex flex-col items-center justify-center py-1.5 px-2 rounded-full transition-all text-xs font-medium',
            $route.path === '/profile' ? 'bg-[#EDEDED] text-primary font-semibold' : 'text-neutral-600 hover:text-primary/75'
          ]"
        >
          <i class="icon-profile text-lg mb-0.5"></i>
          <span>Profil</span>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useFavoritesStore } from '~/stores/favorites'

const cartStore = useCartStore()
const favStore = useFavoritesStore()
</script>
