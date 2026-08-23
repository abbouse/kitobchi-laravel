<template>
  <div class="page-wrapper flex flex-col min-h-dvh bg-secondary-300 md:bg-gray-50 text-neutral-900 font-sans">
    <AppHeader />
    <main :class="['grow', isBottomNavHidden ? '' : 'max-md:pb-[71px]']">
      <slot />
    </main>
    <AppFooter />
    <AppBottomNav />
    <AuthModal />
    <SearchOverlay />
    <AppDownloadBottomSheet />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
// TUZATILDI: /cart AppBottomNav.vue'dagi kabi shu ro'yxatdan olib
// tashlandi — piyolada savatcha sahifasida pastki navigatsiya ko'rinadi
// (jonli tekshirildi). cart/index.vue endi bu 71px joyni piyoladagidek
// o'zi min-h-[calc(100dvh-71px)] + flex-grow orqali hisobga oladi.
const isBottomNavHidden = computed(() => {
  const p = route.path
  return p.startsWith('/books/') || p.startsWith('/stationery/') || p === '/profile/edit' || p.startsWith('/profile/address')
})
</script>
