<template>
  <div :class="[
    isHomePage ? '' : 'max-md:hidden!',
    'sticky top-0 z-50 w-full'
  ]">
    <!-- ====== STICKY HEADER (PiyolaMarket 1:1) ====== -->
    <header class="layout-sticky-header py-3 md:py-4 bg-white max-md:rounded-b-2xl shadow-xs md:shadow-sm transition-all duration-300 w-full">
      <div class="px-4 sm:px-6 lg:px-8 max-w-(--ui-container) mx-auto relative w-full bg-transparent">
        <!-- Desktop Header -->
        <div class="hidden md:flex items-center justify-between w-full gap-6">
          <!-- Left: Logo + Kataloglar -->
          <div class="flex-y-center gap-6">
            <!-- TUZATILDI: eski logo_blue.png (raster, kam sifatli) o'rniga
                 saytning haqiqiy brend belgisi bo'lgan favicon.svg (vektor,
                 har qanday o'lchamda aniq) + "kitobchi" wordmark birlashtirildi. -->
            <NuxtLink to="/" class="flex items-center gap-2.5 shrink-0 router-link-active router-link-exact-active">
              <img alt="Kitobchi" class="h-9 w-9 rounded-[10px]" src="/favicon.svg" />
              <span class="text-2xl font-extrabold text-primary tracking-tight leading-none">kitobchi<span class="text-primary-400">.</span></span>
            </NuxtLink>
            <button
              type="button"
              @click="isCatalogOpen = true"
              class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg p-1! h-12 cursor-pointer bg-secondary-200! border-none text-neutral-900"
            >
              <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
              <div :class="[
                'rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2',
                $route.path.startsWith('/catalog') ? 'bg-primary-100' : ''
              ]">
                <svg class="w-5 h-5 transition-all duration-300 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z"/>
                </svg>
                <span class="max-lg:hidden font-medium text-sm transition-all duration-300">{{ t('catalogs') }}</span>
              </div>
            </button>
          </div>

          <!-- Center: Search — bosilganda piyola'dagi kabi to'liq ekranli
               qidiruv overlay'i ochiladi (SearchOverlay.vue), shu yerda
               to'g'ridan-to'g'ri yozilmaydi. -->
          <button
            type="button"
            @click="searchStore.open()"
            class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg h-12 grow flex-y-center gap-2 text-gray cursor-pointer bg-secondary-200! border-none text-left"
          >
            <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
            <i class="icon-search text-lg text-gray-500"></i>
            <span class="flex-1 text-sm text-neutral-500 m-0 p-0 h-full w-full">{{ t('search_placeholder') }}</span>
          </button>

          <!-- Right: Capsule 1 & Capsule 2 -->
          <div class="flex-y-center gap-4">
            <!-- Capsule 1: Cart + Favorites + Lang -->
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg flex-y-center p-1! h-12 bg-secondary-200!">
              <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>

              <!-- Cart -->
              <NuxtLink
                to="/cart"
                :class="[
                  'rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2 group',
                  $route.path === '/cart' ? 'bg-primary-100 text-primary font-semibold' : ''
                ]"
              >
                <div class="flex-center relative">
                  <i class="icon-order group-hover:text-green-500 text-lg transition-colors duration-200"></i>
                  <div
                    v-if="cartStore.totalCount > 0"
                    class="w-4 h-4 rounded-full flex-center text-[10px] absolute translate-x-1/2 -translate-y-1/2 top-0 right-0 bg-[#ED3131] text-white border border-white font-bold"
                  >
                    {{ cartStore.totalCount > 99 ? '99+' : cartStore.totalCount }}
                  </div>
                </div>
                <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">{{ t('cart') }}</span>
              </NuxtLink>

              <!-- Favorites -->
              <NuxtLink
                to="/favorites"
                :class="[
                  'rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2 group',
                  $route.path === '/favorites' ? 'bg-primary-100 text-primary font-semibold' : ''
                ]"
              >
                <div class="flex-center relative">
                  <i class="icon-heart group-hover:text-green-500 text-lg transition-colors duration-200"></i>
                  <div
                    v-if="favStore.count > 0"
                    class="w-4 h-4 rounded-full flex-center text-[10px] absolute translate-x-1/2 -translate-y-1/2 top-0 right-0 bg-[#ED3131] text-white border border-white font-bold"
                  >
                    {{ favStore.count > 99 ? '99+' : favStore.count }}
                  </div>
                </div>
                <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">{{ t('favorites') }}</span>
              </NuxtLink>

              <!-- Lang — MUHIM: ilgari bu tugma faqat `isLangOpen`ni
                   almashtirardi, lekin uni o'qiydigan dropdown umuman yo'q
                   edi (jonli sinovda tasdiqlandi: bosilganda hech narsa
                   ochilmasdi). Endi piyoladagi kabi — tugma ostida oq,
                   soyali kartochka ochiladi, tanlangan til belgisi (✓) bilan
                   ko'rsatiladi, tashqariga bosilganda yopiladi. Piyolada 3 ta
                   til bor (uz/ru/en), kitobchida esa aynan so'ralgani uchun
                   yaponcha ham qo'shilgan (composables/useLocale.ts). -->
              <div ref="langRootRef" class="relative">
                <button
                  type="button"
                  @click="isLangOpen = !isLangOpen"
                  class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2 border-none bg-transparent cursor-pointer"
                >
                  <i class="icon-globe text-lg transition-colors duration-200 group-hover:text-green-500"></i>
                  <span class="max-lg:hidden font-normal text-sm leading-5 group-hover:text-green-500 transition-colors duration-200">{{ localeLabel }}</span>
                </button>

                <div
                  v-if="isLangOpen"
                  class="absolute z-30 top-full right-0 mt-2 w-44 rounded-2xl bg-white shadow-xl border border-neutral-100 py-2"
                >
                  <button
                    v-for="opt in availableLocales"
                    :key="opt.code"
                    type="button"
                    @click="selectLocale(opt.code)"
                    class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-sm text-left bg-transparent border-none cursor-pointer hover:bg-secondary-100 transition-colors"
                    :class="locale === opt.code ? 'text-primary font-semibold' : 'text-neutral-700'"
                  >
                    {{ opt.label }}
                    <svg v-if="locale === opt.code" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Capsule 2: Profile / Kirish -->
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] rounded-full! hover:shadow-sm hover:shadow-black/10 glass-card-bg flex-y-center h-12 p-1! cursor-pointer bg-secondary-200!">
              <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl rounded-full!"></div>
              <NuxtLink
                v-if="authStore.isAuthenticated"
                to="/profile"
                :class="[
                  'rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2',
                  $route.path === '/profile' ? 'bg-primary-100 text-primary font-semibold' : ''
                ]"
              >
                <i class="icon-profile text-lg"></i>
                <span class="font-normal text-sm leading-5 max-lg:hidden">{{ authStore.user?.name || authStore.user?.phone_number || t('profile') }}</span>
              </NuxtLink>
              <button
                v-else
                type="button"
                @click="authStore.openAuthModal()"
                class="rounded-full px-3 py-2.5 hover:bg-primary-200 transition-all duration-300 flex-y-center gap-2 border-none bg-transparent cursor-pointer"
              >
                <i class="icon-profile text-lg"></i>
                <span class="font-normal text-sm leading-5 max-lg:hidden">{{ t('login') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile Header (Piyola 1:1) -->
        <div class="md:hidden">
          <button
            type="button"
            @click="searchStore.open()"
            class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 h-12 rounded-[20px] text-gray bg-secondary-300! flex-center cursor-pointer gap-3 w-full border-none text-left"
          >
            <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
            <i class="icon-search text-xl text-gray-500"></i>
            <span class="flex-1 text-sm text-neutral-500 m-0 p-0 h-full w-full">{{ t('search_placeholder_mobile') }}</span>
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
