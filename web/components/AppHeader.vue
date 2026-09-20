<template>
  <div :class="[
    isHomePage ? '' : 'max-md:hidden!',
    'sticky top-0 z-50 w-full'
  ]">
    <!-- ====== STICKY HEADER (Kitobchi) ====== -->
    <header class="layout-sticky-header py-2.5 md:py-3 bg-white border-b border-[#e7e8ec] w-full">
      <div class="px-4 sm:px-6 lg:px-8 max-w-(--ui-container) mx-auto relative w-full bg-transparent">
        <!-- Desktop Header -->
        <div class="hidden md:flex items-center justify-between w-full gap-5">
          <!-- Left: Logo + Kataloglar -->
          <div class="flex items-center gap-4 shrink-0">
            <NuxtLink to="/" class="flex items-center shrink-0 transition-opacity hover:opacity-80" aria-label="Kitobchi">
              <img alt="Kitobchi" class="h-9 w-9 rounded-xl" src="/favicon.svg" />
            </NuxtLink>
            <button
              type="button"
              @click="isCatalogOpen = true"
              :class="[
                'h-10 px-4 rounded-full font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors border border-transparent',
                $route.path.startsWith('/catalog')
                  ? 'bg-neutral-200 text-neutral-900 font-semibold'
                  : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200/80 hover:border-neutral-200'
              ]"
            >
              <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z"/>
              </svg>
              <span class="max-lg:hidden">{{ t('catalogs') }}</span>
            </button>
          </div>

          <!-- Center: Search -->
          <button
            type="button"
            @click="searchStore.open()"
            class="h-10 px-4 rounded-full bg-neutral-100 hover:bg-neutral-200/70 border border-transparent hover:border-neutral-200/60 grow flex items-center gap-2.5 text-neutral-400 cursor-pointer transition-all text-left max-w-xl"
          >
            <i class="icon-search text-base text-neutral-400 shrink-0"></i>
            <span class="flex-1 text-sm text-neutral-500 truncate m-0 p-0">{{ t('search_placeholder') }}</span>
          </button>

          <!-- Right: Cart + Favorites + Lang + Profile -->
          <div class="flex items-center gap-1.5 shrink-0">
            <!-- Cart -->
            <NuxtLink
              to="/cart"
              :class="[
                'h-10 px-3 rounded-full flex items-center gap-2 transition-colors relative text-sm',
                $route.path === '/cart'
                  ? 'bg-neutral-100 text-neutral-900 font-semibold'
                  : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900'
              ]"
            >
              <div class="flex items-center justify-center relative">
                <i class="icon-order text-lg"></i>
                <div
                  v-if="cartStore.totalCount > 0"
                  class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] absolute -top-1 -right-2 bg-[#ED3131] text-white font-bold"
                >
                  {{ cartStore.totalCount > 99 ? '99+' : cartStore.totalCount }}
                </div>
              </div>
              <span class="max-lg:hidden leading-none">{{ t('cart') }}</span>
            </NuxtLink>

            <!-- Favorites -->
            <NuxtLink
              to="/favorites"
              :class="[
                'h-10 px-3 rounded-full flex items-center gap-2 transition-colors relative text-sm',
                $route.path === '/favorites'
                  ? 'bg-neutral-100 text-neutral-900 font-semibold'
                  : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900'
              ]"
            >
              <div class="flex items-center justify-center relative">
                <i class="icon-heart text-lg"></i>
                <div
                  v-if="favStore.count > 0"
                  class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] absolute -top-1 -right-2 bg-[#ED3131] text-white font-bold"
                >
                  {{ favStore.count > 99 ? '99+' : favStore.count }}
                </div>
              </div>
              <span class="max-lg:hidden leading-none">{{ t('favorites') }}</span>
            </NuxtLink>

            <!-- Lang Dropdown -->
            <div ref="langRootRef" class="relative">
              <button
                type="button"
                @click="isLangOpen = !isLangOpen"
                class="h-10 px-2.5 rounded-full flex items-center gap-1.5 transition-colors cursor-pointer border-none bg-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 text-sm"
              >
                <i class="icon-globe text-base text-neutral-500"></i>
                <span class="max-lg:hidden leading-none">{{ localeLabel }}</span>
              </button>

              <div
                v-if="isLangOpen"
                class="absolute z-30 top-full right-0 mt-1.5 w-40 rounded-2xl bg-white shadow-lg border border-neutral-150 py-1.5"
              >
                <button
                  v-for="opt in availableLocales"
                  :key="opt.code"
                  type="button"
                  @click="selectLocale(opt.code)"
                  class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-medium text-left bg-transparent border-none cursor-pointer hover:bg-neutral-50 transition-colors"
                  :class="locale === opt.code ? 'text-primary font-semibold' : 'text-neutral-700'"
                >
                  {{ opt.label }}
                  <svg v-if="locale === opt.code" class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </button>
              </div>
            </div>

            <!-- Profile / Login -->
            <NuxtLink
              v-if="authStore.isAuthenticated"
              to="/profile"
              :class="[
                'h-10 px-3.5 rounded-full flex items-center gap-2 transition-colors text-sm font-medium',
                $route.path.startsWith('/profile')
                  ? 'bg-neutral-100 text-neutral-900 font-semibold'
                  : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200/80'
              ]"
            >
              <i class="icon-profile text-base"></i>
              <span class="max-lg:hidden truncate max-w-[120px]">{{ authStore.user?.name || authStore.user?.phone_number || t('profile') }}</span>
            </NuxtLink>
            <button
              v-else
              type="button"
              @click="authStore.openAuthModal()"
              class="h-10 px-4 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-800 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer border-none"
            >
              <i class="icon-profile text-base"></i>
              <span class="max-lg:hidden">{{ t('login') }}</span>
            </button>
          </div>
        </div>

        <!-- Mobile Header -->
        <div class="md:hidden">
          <button
            type="button"
            @click="searchStore.open()"
            class="h-11 px-4 rounded-full bg-neutral-100 text-neutral-400 flex items-center gap-2.5 w-full border border-neutral-200/50 cursor-pointer text-left transition-colors"
          >
            <i class="icon-search text-lg text-neutral-400 shrink-0"></i>
            <span class="flex-1 text-sm text-neutral-500 truncate m-0 p-0">{{ t('search_placeholder_mobile') }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Catalog Drawer Modal -->
    <CatalogDrawer :is-open="isCatalogOpen" @close="isCatalogOpen = false" />
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useFavoritesStore } from '~/stores/favorites'
import { useAuthStore } from '~/stores/auth'
import { useSearchStore } from '~/stores/search'
import type { AppLocale } from '~/composables/useLocale'

const route = useRoute()
const cartStore = useCartStore()
const favStore = useFavoritesStore()
const authStore = useAuthStore()
const searchStore = useSearchStore()
const { locale, localeLabel, availableLocales, setLocale, t } = useLocale()

const isCatalogOpen = ref(false)
const isLangOpen = ref(false)
const langRootRef = ref<HTMLElement | null>(null)
onClickOutside(langRootRef, () => { isLangOpen.value = false })

function selectLocale(code: AppLocale) {
  setLocale(code)
  isLangOpen.value = false
}

const isHomePage = computed(() => route.path === '/')
</script>
