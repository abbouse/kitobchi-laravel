<template>
  <div class="page-wrapper flex flex-col min-h-dvh bg-secondary-300 md:bg-gray-50 text-neutral-900 font-sans">
    <AppHeader />
    <main class="grow max-md:pb-[71px]">
      <slot />
    </main>
    <AppFooter />
    <AppBottomNav />
    <AuthModal />
    <SearchOverlay />
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  authStore.fetchUser()
})
</script>
