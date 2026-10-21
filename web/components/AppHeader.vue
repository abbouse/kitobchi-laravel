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
            <NuxtLink
              to="/club"
              :class="[
                'h-10 px-3.5 rounded-full font-medium text-sm flex items-center gap-1.5 transition-colors border border-transparent no-underline',
                $route.path.startsWith('/club')
                  ? 'bg-neutral-900 text-white font-semibold'
                  : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200/80 hover:text-neutral-950'
              ]"
            >
              <svg class="w-4 h-4 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
              </svg>
              <span class="max-lg:hidden">Kitob klubi</span>
            </NuxtLink>
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
              <!-- Savatcha/Sevimlilar bilan bir xil uslub: shaffof fon, hover'da
                   neutral-100, ochiq holatda faol element kabi. Bayroq emoji
                   o'rniga globus — Windows'da bayroqlar harf bo'lib chiqadi. -->
              <button
                type="button"
                @click="isLangOpen = !isLangOpen"
                :aria-expanded="isLangOpen"
                aria-haspopup="true"
                :aria-label="`Til: ${localeLabel}`"
                :class="[
                  'h-10 px-3 rounded-full flex items-center gap-2 transition-colors relative text-sm cursor-pointer border-none select-none',
                  isLangOpen
                    ? 'bg-neutral-100 text-neutral-900 font-semibold'
                    : 'bg-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900'
                ]"
              >
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <circle cx="12" cy="12" r="9" />
                  <path d="M3 12h18" />
                  <path d="M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z" />
                </svg>
                <span class="max-lg:hidden leading-none">{{ localeLabel }}</span>
              </button>

              <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                enter-to-class="transform scale-100 opacity-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="transform scale-100 opacity-100 translate-y-0"
                leave-to-class="transform scale-95 opacity-0 -translate-y-1"
              >
                <div
                  v-if="isLangOpen"
                  class="absolute z-50 top-full right-0 mt-2 w-48 rounded-2xl bg-white shadow-xl border border-neutral-100 p-1.5 focus:outline-none"
                  role="menu"
                >
                  <div class="px-3 py-1.5 text-[11px] font-bold text-neutral-400 uppercase tracking-wider">
                    Tilni tanlang
                  </div>
                  <button
                    v-for="opt in availableLocalesWithMeta"
                    :key="opt.code"
                    type="button"
                    @click="selectLocale(opt.code)"
                    role="menuitem"
                    class="w-full flex items-center justify-between gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-left transition-all border-none cursor-pointer"
                    :class="locale === opt.code ? 'bg-blue-50/80 text-primary font-bold' : 'text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900'"
                  >
                    <div class="flex items-center gap-2.5">
                      <span class="text-base leading-none">{{ opt.flag }}</span>
                      <span>{{ opt.label }}</span>
                    </div>
                    <svg
                      v-if="locale === opt.code"
                      class="w-4 h-4 text-primary shrink-0"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </Transition>
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
        <div class="md:hidden flex items-center gap-2">
          <button
            type="button"
            @click="searchStore.open()"
            class="h-11 px-4 rounded-full bg-neutral-100 text-neutral-400 flex items-center gap-2.5 flex-1 border border-neutral-200/50 cursor-pointer text-left transition-colors"
          >
            <i class="icon-search text-lg text-neutral-400 shrink-0"></i>
            <span class="flex-1 text-sm text-neutral-500 truncate m-0 p-0">{{ t('search_placeholder_mobile') }}</span>
          </button>

          <!-- Mobile Lang Selector -->
          <div ref="mobileLangRootRef" class="relative shrink-0">
            <button
              type="button"
              @click="isMobileLangOpen = !isMobileLangOpen"
              class="h-11 px-3 rounded-full bg-neutral-100 hover:bg-neutral-200/70 text-neutral-800 flex items-center gap-1.5 border border-neutral-200/50 cursor-pointer text-xs font-semibold"
              aria-label="Tilni tanlash"
            >
              <span class="text-base leading-none">{{ currentLocaleFlag }}</span>
              <span class="uppercase text-[11px] font-bold text-neutral-700">{{ locale }}</span>
            </button>

            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0 -translate-y-1"
              enter-to-class="transform scale-100 opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100 translate-y-0"
              leave-to-class="transform scale-95 opacity-0 -translate-y-1"
            >
              <div
                v-if="isMobileLangOpen"
                class="absolute z-50 top-full right-0 mt-2 w-44 rounded-2xl bg-white shadow-xl border border-neutral-100 p-1.5 focus:outline-none"
              >
                <button
                  v-for="opt in availableLocalesWithMeta"
                  :key="'mob-' + opt.code"
                  type="button"
                  @click="selectMobileLocale(opt.code)"
                  class="w-full flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-xs font-medium text-left transition-all border-none cursor-pointer"
                  :class="locale === opt.code ? 'bg-blue-50/80 text-primary font-bold' : 'text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900'"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base leading-none">{{ opt.flag }}</span>
                    <span>{{ opt.label }}</span>
                  </div>
                  <svg
                    v-if="locale === opt.code"
                    class="w-4 h-4 text-primary shrink-0"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </Transition>
          </div>
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

const LOCALE_FLAGS: Record<AppLocale, string> = {
  uz: '🇺🇿',
  ru: '🇷🇺',
  en: '🇬🇧',
  ja: '🇯🇵'
}

const currentLocaleFlag = computed(() => LOCALE_FLAGS[locale.value as AppLocale] || '🌐')

const availableLocalesWithMeta = computed(() => {
  return availableLocales.map(l => ({
    ...l,
    flag: LOCALE_FLAGS[l.code as AppLocale] || '🌐'
  }))
})

const isCatalogOpen = ref(false)
const isLangOpen = ref(false)
const isMobileLangOpen = ref(false)
const langRootRef = ref<HTMLElement | null>(null)
const mobileLangRootRef = ref<HTMLElement | null>(null)

onClickOutside(langRootRef, () => { isLangOpen.value = false })
onClickOutside(mobileLangRootRef, () => { isMobileLangOpen.value = false })

function selectLocale(code: AppLocale) {
  setLocale(code)
  isLangOpen.value = false
}

function selectMobileLocale(code: AppLocale) {
  setLocale(code)
  isMobileLangOpen.value = false
}

const isHomePage = computed(() => route.path === '/')
</script>
