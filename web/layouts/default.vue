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
const isBottomNavHidden = computed(() => {
  const p = route.path
  return p === '/cart' || p.startsWith('/books/') || p.startsWith('/stationery/') || p === '/profile/edit' || p.startsWith('/profile/address')
})
</script>
