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

<!--
  MUHIM: bu yerda ilgari har bir sahifa ochilganda `authStore.fetchUser()`
  chaqirilardi — lekin haqiqiy backendda ishlaydigan mos endpoint yo'qligi
  (stores/auth.ts'dagi izohga qarang) tufayli bu chaqiruv HAR DOIM
  muvaffaqiyatsiz bo'lar, foydali hech narsa qilmasdi. `user` endi
  cookie'da saqlanadi (token bilan bir xil), shuning uchun sahifa qayta
  ochilganda ham darhol mavjud — alohida so'rov shart emas.
-->
