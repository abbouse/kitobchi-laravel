<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow">
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
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Profil</h1>
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
          <span class="text-neutral-900 font-semibold">Profil</span>
        </nav>
      </div>

      <!-- Auth State Check — piyoladagi HAQIQIY guest profil sahifasidan
           (jonli tekshirilib olingan: /profile ga tizimga kirmasdan kirilganda)
           to'g'ridan-to'g'ri olingan tuzilma: avatar + "Foydalanuvchi" sarlavha +
           izoh matni + to'liq kengliкdagi "Akkauntga kiring" tugmasi, BIR XIL
           oq rangli rounded-3xl kartada — va pastida piyolada guest holatida
           ham ko'rinadigan info/sozlama ro'yxati (Muddatli to'lov, Yetkazib
           berish, Biz haqimizda, Karyera, Ilova tili, Biz bilan bog'lanish).
           Ilgari bu yerda faqat markazlashtirilgan "Tizimga kiring" matni
           bo'lib, pastdagi menyu umuman ko'rinmas edi. -->
      <div v-if="!authStore.isAuthenticated">
        <div class="bg-white rounded-3xl border border-neutral-100 py-8 px-6 flex flex-col items-center gap-3 mb-3">
          <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 flex items-center justify-center">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <p class="text-xl font-bold text-neutral-900 m-0">Foydalanuvchi</p>
          <p class="text-sm text-neutral-500 text-center max-w-sm leading-relaxed m-0">
            Buyurtmalarni rasmiylashtirish, bo'lib to'lash imkoniyatidan foydalanish va mahsulotlarni saqlab qo'yish uchun
          </p>
          <button
            type="button"
            @click="authStore.openAuthModal()"
            class="w-full h-12 rounded-2xl bg-primary text-white font-semibold text-base border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors mt-1"
          >
            Akkauntga kiring
          </button>
        </div>

        <!-- Guest holatida ham ko'rinadigan info/sozlama menyusi (piyoladan) -->
        <div v-for="(group, gi) in guestMenuGroups" :key="'guest-' + gi" class="bg-white rounded-3xl border border-neutral-100 divide-y divide-neutral-100 overflow-hidden mb-3">
          <template v-for="item in group" :key="item.key">
            <NuxtLink
              v-if="item.to"
              :to="item.to"
              class="w-full flex items-center justify-between gap-3 px-5 py-4 hover:bg-neutral-50 transition-colors text-left group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
                <svg class="w-4 h-4 text-neutral-300 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
              </div>
            </NuxtLink>

            <div v-else class="w-full flex items-center justify-between gap-3 px-5 py-4">
              <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 text-neutral-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Authenticated Profile Hub (Piyola style) -->
      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <ProfileSidebar active="" />

        <div class="flex-1 min-w-0">
          <!-- Mobile-only user summary card -->
          <div class="lg:hidden bg-white border border-neutral-100 rounded-3xl p-4 mb-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0">
              {{ (authStore.user?.name || authStore.user?.phone_number || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-base font-bold text-neutral-900 truncate leading-snug">
                {{ authStore.user?.name || 'Foydalanuvchi' }}
              </div>
              <div class="text-xs text-neutral-500 truncate mt-0.5">
                +{{ authStore.user?.phone_number }}
              </div>
            </div>
          </div>

          <!-- Menu groups -->
          <div v-for="(group, gi) in menuGroups" :key="gi" class="bg-white rounded-3xl border border-neutral-100 divide-y divide-neutral-100 overflow-hidden mb-3">
            <template v-for="item in group" :key="item.key">
              <NuxtLink
                v-if="item.to"
                :to="item.to"
                class="w-full flex items-center justify-between gap-3 px-5 py-4 hover:bg-neutral-50 transition-colors text-left group"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                  <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
                  <svg class="w-4 h-4 text-neutral-300 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </div>
              </NuxtLink>

              <div v-else class="w-full flex items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-3 min-w-0">
                  <svg class="w-5 h-5 text-neutral-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                  <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
                </div>
              </div>
            </template>
          </div>

          <!-- Logout (mobile — desktop uses sidebar) -->
          <button
            type="button"
            @click="handleLogout"
            class="lg:hidden w-full flex items-center justify-center gap-2 px-5 py-4 rounded-3xl bg-white border border-neutral-100 text-red-500 text-sm font-semibold hover:bg-neutral-100 transition-colors mb-3"
          >
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            <span>Hisobdan chiqish</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const ICONS: Record<string, string> = {
  order: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-.75 11.25h9a2.25 2.25 0 002.25-2.25l-.75-9a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25l-.75 9a2.25 2.25 0 002.25 2.25z"/>',
  comment: '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12.375 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.125 12a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>',
  user: '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>',
  card: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>',
  truck: '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.797c.75 0 1.44.398 1.816 1.045l2.15 3.696c.196.336.297.72.297 1.11v3.399m-3 4.5h-9m3-8.25V6.75"/>',
  info: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>',
  briefcase: '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25M16.5 6.75V4.5A2.25 2.25 0 0014.25 2.25h-4.5A2.25 2.25 0 007.5 4.5v2.25m9 0h2.25A2.25 2.25 0 0121 9v6.75a2.25 2.25 0 01-.659 1.591L16.5 21H7.5l-3.841-3.659A2.25 2.25 0 013 15.75V9a2.25 2.25 0 012.25-2.25H7.5m9 0h-9"/>',
  globe: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A8.959 8.959 0 013 12c0-1.605.42-3.113 1.157-4.418"/>',
  phone: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>',
  heart: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>',
}

interface MenuItem {
  key: string
  label: string
  icon: string
  to?: string
  value?: string
}

const menuGroups: MenuItem[][] = [
  [
    { key: 'orders', label: 'Buyurtmalarim', icon: 'order', to: '/profile/orders' },
    { key: 'comments', label: 'Sharhlarim', icon: 'comment', to: '/profile/comments' },
    // Piyola'ning haqiqiy (autentifikatsiyalangan) profil sahifasidan
    // tasdiqlangan tartib: Buyurtmalarim → Sharhlarim → Sevimlilar →
    // Ma'lumotlarim (/tmp/piyola_extract/profile_text.txt). Ilgari bu
    // yerda "Sevimlilar" umuman yo'q edi.
    { key: 'favorites', label: 'Sevimlilar', icon: 'heart', to: '/favorites' },
    { key: 'info', label: "Ma'lumotlarim", icon: 'user', to: '/profile/info' },
  ],
  [
    { key: 'installment', label: "Muddatli to'lov haqida", icon: 'card', to: '/legal/tolov-va-qaytarish' },
    { key: 'delivery', label: 'Yetkazib berish haqida', icon: 'truck', to: '/legal/yetkazib-berish-va-qaytarish' },
    { key: 'about', label: 'Biz haqimizda', icon: 'info', to: '/about' },
    { key: 'vacancies', label: 'Karyera', icon: 'briefcase', to: '/vacancies' },
  ],
  [
    { key: 'lang', label: 'Ilova tili', icon: 'globe', value: "O'zbekcha" },
    { key: 'contacts', label: "Biz bilan bog'lanish", icon: 'phone', to: '/contacts' },
  ],
]

// Guest holatida piyoladagi kabi faqat info/sozlama guruhlari ko'rinadi
// (shaxsiy — Buyurtmalarim/Sharhlarim/Sevimlilar/Ma'lumotlarim — guruhi
// autentifikatsiya talab qilgani uchun bu yerda emas).
const guestMenuGroups: MenuItem[][] = [menuGroups[1], menuGroups[2]]

function handleLogout() {
  authStore.logout()
  router.push('/')
}

useSeoMeta({
  title: 'Foydalanuvchi profili — Kitobchi',
  description: 'Shaxsiy kabinet, buyurtmalar tarixi va sozlamalar.'
})
</script>
