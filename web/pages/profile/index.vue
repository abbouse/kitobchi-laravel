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
        <!-- Piyoladagi HAQIQIY guest kartasi bilan bittama-bitta mos (jonli
             DOM'dan piksellab olingan): tashqi karta hech qanday chegara
             (border) yoki soyaga ega emas, rounded-[20px], ichki bo'sh joy
             faqat py-4/px-4 (gap YO'Q — bo'sh joy ichki gap-3 va mt-3 orqali
             beriladi). Avatar piyolaning O'ZINING icon-font'i (icon-profile)
             bilan, inline SVG emas. Sarlavha/tavsif ranglari ham Nuxt UI
             semantik token'lari (text-highlighted, text-gray-500) — taxminiy
             neutral-900/500 emas. -->
        <div class="bg-white rounded-[20px] py-4 px-4 flex flex-col mb-3">
          <div class="flex flex-col items-center gap-3 mx-auto">
            <div class="w-16 h-16 rounded-full bg-secondary-200 flex items-center justify-center mx-auto">
              <i class="icon-profile text-gray text-3xl"></i>
            </div>
            <p class="text-highlighted text-xl font-bold m-0">Foydalanuvchi</p>
          </div>
          <div class="flex flex-col items-center">
            <p class="text-sm md:text-base text-gray-500 text-center max-w-sm leading-relaxed px-4 m-0">
              Buyurtmalarni rasmiylashtirish, bo'lib to'lash imkoniyatidan foydalanish va mahsulotlarni saqlab qo'yish uchun
            </p>
            <div class="flex gap-3 mt-3 w-full">
              <button
                type="button"
                @click="authStore.openAuthModal()"
                class="w-full h-12 rounded-2xl bg-primary text-white font-medium text-base px-6 border-none cursor-pointer hover:bg-primary/75 transition-colors"
              >
                Akkauntga kiring
              </button>
            </div>
          </div>
        </div>

        <!-- Guest holatida ham ko'rinadigan info/sozlama menyusi (piyoladan) -->
        <div v-for="(group, gi) in guestMenuGroups" :key="'guest-' + gi" class="bg-white rounded-[20px] py-1 mb-3">
          <template v-for="item in group" :key="item.key">
            <NuxtLink
              v-if="item.to"
              :to="item.to"
              class="bg-white rounded-2xl w-full flex items-center justify-between gap-3 px-4 py-2 hover:bg-neutral-50 transition-colors text-left group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" :fill="SOLID_ICON_KEYS.has(item.icon) ? 'currentColor' : 'none'" :stroke="SOLID_ICON_KEYS.has(item.icon) ? 'none' : 'currentColor'" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
                <svg class="w-4 h-4 text-neutral-300 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
              </div>
            </NuxtLink>

            <div v-else class="bg-white rounded-2xl w-full flex items-center justify-between gap-3 px-4 py-2">
              <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 text-neutral-400 shrink-0" viewBox="0 0 24 24" :fill="SOLID_ICON_KEYS.has(item.icon) ? 'currentColor' : 'none'" :stroke="SOLID_ICON_KEYS.has(item.icon) ? 'none' : 'currentColor'" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
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
          <div v-for="(group, gi) in menuGroups" :key="gi" class="bg-white rounded-[20px] py-1 mb-3">
            <template v-for="item in group" :key="item.key">
              <NuxtLink
                v-if="item.to"
                :to="item.to"
                class="bg-white rounded-2xl w-full flex items-center justify-between gap-3 px-4 py-2 hover:bg-neutral-50 transition-colors text-left group"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" :fill="SOLID_ICON_KEYS.has(item.icon) ? 'currentColor' : 'none'" :stroke="SOLID_ICON_KEYS.has(item.icon) ? 'none' : 'currentColor'" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
                  <span class="text-sm font-medium text-neutral-800 truncate">{{ item.label }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span v-if="item.value" class="text-sm text-neutral-400">{{ item.value }}</span>
                  <svg class="w-4 h-4 text-neutral-300 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </div>
              </NuxtLink>

              <div v-else class="bg-white rounded-2xl w-full flex items-center justify-between gap-3 px-4 py-2">
                <div class="flex items-center gap-3 min-w-0">
                  <svg class="w-5 h-5 text-neutral-400 shrink-0" viewBox="0 0 24 24" :fill="SOLID_ICON_KEYS.has(item.icon) ? 'currentColor' : 'none'" :stroke="SOLID_ICON_KEYS.has(item.icon) ? 'none' : 'currentColor'" stroke-width="1.5" v-html="ICONS[item.icon]"></svg>
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
  // MUHIM: quyidagi 6 ta ikonka (card/truck/info/briefcase/globe/phone)
  // piyolaning HAQIQIY guest/info menyusidan (jonli DOM'dan, iconify emas —
  // inline SVG'dan) to'g'ridan-to'g'ri olindi: piyolada bular OUTLINE emas,
  // TO'LDIRILGAN (solid, fill="currentColor") ikonkalar. Shu sabab bular
  // pastdagi `renderSolid`/ICON_SOLID ro'yxatida belgilangan.
  card: '<path d="M2.25 13.5C2.25 11.312 3.11919 9.21354 4.66637 7.66637C6.21354 6.11919 8.31196 5.25 10.5 5.25C10.6989 5.25 10.8897 5.32902 11.0303 5.46967C11.171 5.61032 11.25 5.80109 11.25 6V12.75H18C18.1989 12.75 18.3897 12.829 18.5303 12.9697C18.671 13.1103 18.75 13.3011 18.75 13.5C18.75 15.688 17.8808 17.7865 16.3336 19.3336C14.7865 20.8808 12.688 21.75 10.5 21.75C8.31196 21.75 6.21354 20.8808 4.66637 19.3336C3.11919 17.7865 2.25 15.688 2.25 13.5Z"/><path d="M12.75 3C12.75 2.80109 12.829 2.61032 12.9697 2.46967C13.1103 2.32902 13.3011 2.25 13.5 2.25C15.688 2.25 17.7865 3.11919 19.3336 4.66637C20.8808 6.21354 21.75 8.31196 21.75 10.5C21.75 10.6989 21.671 10.8897 21.5303 11.0303C21.3897 11.171 21.1989 11.25 21 11.25H13.5C13.3011 11.25 13.1103 11.171 12.9697 11.0303C12.829 10.8897 12.75 10.6989 12.75 10.5V3Z"/>',
  truck: '<path d="M13 4C13.2652 4 13.5196 4.10536 13.7071 4.29289C13.8946 4.48043 14 4.73478 14 5H18C18.1505 5 18.2992 5.034 18.4347 5.09945C18.5703 5.1649 18.6894 5.26012 18.783 5.378L18.857 5.486L21.857 10.486L21.912 10.589L21.952 10.696L21.981 10.805L21.997 10.915L22 11V17C22 17.2652 21.8946 17.5196 21.7071 17.7071C21.5196 17.8946 21.2652 18 21 18H19.829C19.622 18.5848 19.2388 19.0912 18.7322 19.4492C18.2256 19.8073 17.6204 19.9996 17 19.9996C16.3796 19.9996 15.7744 19.8073 15.2678 19.4492C14.7612 19.0912 14.378 18.5848 14.171 18H9.829C9.62198 18.5848 9.2388 19.0912 8.73217 19.4492C8.22555 19.8073 7.6204 19.9996 7 19.9996C6.3796 19.9996 5.77445 19.8073 5.26782 19.4492C4.7612 19.0912 4.37802 18.5848 4.171 18H3C2.73478 18 2.48043 17.8946 2.29289 17.7071C2.10536 17.5196 2 17.2652 2 17V6C2 5.46957 2.21071 4.96086 2.58579 4.58579C2.96086 4.21071 3.46957 4 4 4H13ZM7 16C6.73478 16 6.48043 16.1054 6.29289 16.2929C6.10536 16.4804 6 16.7348 6 17C6 17.2652 6.10536 17.5196 6.29289 17.7071C6.48043 17.8946 6.73478 18 7 18C7.26522 18 7.51957 17.8946 7.70711 17.7071C7.89464 17.5196 8 17.2652 8 17C8 16.7348 7.89464 16.4804 7.70711 16.2929C7.51957 16.1054 7.26522 16 7 16ZM17 16C16.7348 16 16.4804 16.1054 16.2929 16.2929C16.1054 16.4804 16 16.7348 16 17C16 17.2652 16.1054 17.5196 16.2929 17.7071C16.4804 17.8946 16.7348 18 17 18C17.2652 18 17.5196 17.8946 17.7071 17.7071C17.8946 17.5196 18 17.2652 18 17C18 16.7348 17.8946 16.4804 17.7071 16.2929C17.5196 16.1054 17.2652 16 17 16ZM17.434 7H14V10H19.234L17.434 7Z"/>',
  info: '<path d="M5.22386 2.25C4.72686 2.25 4.24986 2.448 3.89886 2.8L2.59886 4.098C1.92709 4.76853 1.53457 5.66884 1.50047 6.61738C1.46637 7.56592 1.79322 8.4921 2.41511 9.20913C3.037 9.92617 3.90765 10.3807 4.85147 10.4811C5.7953 10.5815 6.74208 10.3202 7.50086 9.75C8.12786 10.22 8.90686 10.5 9.75086 10.5C10.5949 10.5 11.3749 10.22 12.0009 9.75C12.6269 10.22 13.4069 10.5 14.2509 10.5C15.0949 10.5 15.8739 10.22 16.5009 9.75C17.2596 10.3202 18.2064 10.5815 19.1503 10.4811C20.0941 10.3807 20.9647 9.92617 21.5866 9.20913C22.2085 8.4921 22.5354 7.56592 22.5013 6.61738C22.4672 5.66884 22.0746 4.76853 21.4029 4.098L20.1029 2.799C19.7514 2.44764 19.2748 2.25017 18.7779 2.25H5.22386Z"/><path d="M3 20.25V11.495C4.42 12.169 6.08 12.168 7.5 11.495C8.20313 11.8285 8.97178 12.001 9.75 12C10.554 12 11.318 11.818 12 11.494C12.7031 11.8279 13.4717 12.0007 14.25 12C15.054 12 15.817 11.818 16.5 11.494C17.92 12.168 19.58 12.169 21 11.495V20.25H21.75C21.9489 20.25 22.1397 20.329 22.2803 20.4697C22.421 20.6103 22.5 20.8011 22.5 21C22.5 21.1989 22.421 21.3897 22.2803 21.5303C22.1397 21.671 21.9489 21.75 21.75 21.75H2.25C2.05109 21.75 1.86032 21.671 1.71967 21.5303C1.57902 21.3897 1.5 21.1989 1.5 21C1.5 20.8011 1.57902 20.6103 1.71967 20.4697C1.86032 20.329 2.05109 20.25 2.25 20.25H3ZM6 14.25C6 14.0511 6.07902 13.8603 6.21967 13.7197C6.36032 13.579 6.55109 13.5 6.75 13.5H9.75C9.94891 13.5 10.1397 13.579 10.2803 13.7197C10.421 13.8603 10.5 14.0511 10.5 14.25V17.25C10.5 17.4489 10.421 17.6397 10.2803 17.7803C10.1397 17.921 9.94891 18 9.75 18H6.75C6.55109 18 6.36032 17.921 6.21967 17.7803C6.07902 17.6397 6 17.4489 6 17.25V14.25ZM14.25 13.5C14.0511 13.5 13.8603 13.579 13.7197 13.7197C13.579 13.8603 13.5 14.0511 13.5 14.25V19.5C13.5 19.914 13.836 20.25 14.25 20.25H17.25C17.4489 20.25 17.6397 20.171 17.7803 20.0303C17.921 19.8897 18 19.6989 18 19.5V14.25C18 14.0511 17.921 13.8603 17.7803 13.7197C17.6397 13.579 17.4489 13.5 17.25 13.5H14.25Z"/>',
  briefcase: '<path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0 1 12 15.75c-2.73 0-5.357-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.488 49.488 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Zm-3 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"/><path d="M3 18.4v-2.796a4.3 4.3 0 0 0 .713.31A26.226 26.226 0 0 0 12 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 0 1-6.477-.427C4.047 21.128 3 19.852 3 18.4Z"/>',
  globe: '<path d="M21.7202 12.752C21.8558 11.03 21.5296 9.30294 20.7752 7.749C19.5266 8.93539 18.0506 9.85658 16.4362 10.457C16.566 12.0503 16.4941 13.6537 16.2222 15.229C18.1867 14.7342 20.0481 13.8956 21.7202 12.752ZM14.6332 15.55C14.9526 14.023 15.0643 12.4599 14.9652 10.903C14.0132 11.13 13.0202 11.25 11.9992 11.25C10.9782 11.25 9.98521 11.13 9.03321 10.903C8.93645 12.4599 9.04811 14.0227 9.36521 15.55C11.111 15.8176 12.8874 15.8176 14.6332 15.55ZM9.77121 17.119C11.2514 17.2941 12.747 17.2941 14.2272 17.119C13.7185 18.7566 12.9675 20.3088 11.9992 21.724C11.0309 20.3088 10.2799 18.7566 9.77121 17.119ZM7.77621 15.23C7.50285 13.6542 7.43094 12.05 7.56221 10.456C5.94749 9.85572 4.47113 8.93453 3.22221 7.748C2.46803 9.30233 2.14216 11.0297 2.27821 12.752C3.95035 13.8956 5.81173 14.7352 7.77621 15.23ZM21.3552 14.752C20.8503 16.4629 19.8864 18.0029 18.5681 19.2047C17.2498 20.4065 15.6274 21.2241 13.8772 21.569C14.7537 20.0953 15.4227 18.5076 15.8652 16.851C17.7947 16.4466 19.6478 15.7384 21.3552 14.753V14.752ZM2.64321 14.752C4.32521 15.723 6.17321 16.44 8.13321 16.851C8.57572 18.5076 9.24469 20.0953 10.1212 21.569C8.3711 21.2242 6.74885 20.4067 5.43055 19.2051C4.11225 18.0035 3.14826 16.4638 2.64321 14.753V14.752ZM13.8772 2.43C16.3549 2.91701 18.5473 4.34583 19.9932 6.416C18.9259 7.49438 17.6517 8.34608 16.2472 8.92C15.8688 6.62782 15.065 4.42664 13.8772 2.43ZM11.9992 2.276C13.4586 4.4081 14.4175 6.84236 14.8042 9.397C13.9072 9.627 12.9672 9.75 11.9992 9.75C11.0312 9.75 10.0912 9.628 9.19421 9.397C9.5809 6.84235 10.5398 4.40808 11.9992 2.276ZM10.1212 2.43C8.93344 4.42663 8.12962 6.62781 7.75121 8.92C6.34667 8.3461 5.07252 7.4944 4.00521 6.416C5.45125 4.34613 7.6436 2.91666 10.1212 2.43Z"/>',
  phone: '<path d="M21.89 12V19.5C21.8874 20.4937 21.4914 21.446 20.7887 22.1487C20.086 22.8514 19.1337 23.2473 18.14 23.25H12.75C12.5511 23.25 12.3603 23.171 12.2197 23.0303C12.079 22.8897 12 22.6989 12 22.5C12 22.3011 12.079 22.1103 12.2197 21.9696C12.3603 21.829 12.5511 21.75 12.75 21.75H18.14C18.7359 21.7473 19.3067 21.5095 19.7281 21.0881C20.1495 20.6667 20.3874 20.0959 20.39 19.5V19.369C20.15 19.456 19.896 19.5 19.64 19.5H18.14C17.5433 19.5 16.971 19.2629 16.549 18.841C16.1271 18.419 15.89 17.8467 15.89 17.25V13.5C15.89 12.9032 16.1271 12.3309 16.549 11.909C16.971 11.487 17.5433 11.25 18.14 11.25H20.353C20.165 9.18958 19.2096 7.27504 17.6764 5.88589C16.1431 4.49674 14.1439 3.7344 12.075 3.74998H12.065C9.99503 3.73024 7.99363 4.49118 6.45965 5.88116C4.92567 7.27114 3.97175 9.18809 3.788 11.25H6C6.59593 11.2526 7.1667 11.4905 7.58808 11.9119C8.00947 12.3333 8.24737 12.9041 8.25 13.5V17.25C8.24737 17.8459 8.00947 18.4167 7.58808 18.8381C7.1667 19.2595 6.59593 19.4973 6 19.5H4.5C3.90407 19.4973 3.3333 19.2595 2.91192 18.8381C2.49053 18.4167 2.25263 17.8459 2.25 17.25V12C2.24997 10.714 2.50433 9.44075 2.99844 8.25351C3.49254 7.06626 4.21663 5.98849 5.129 5.08225C6.04138 4.17601 7.12401 3.45921 8.31456 2.97312C9.50512 2.48704 10.7801 2.24127 12.066 2.24998H12.141C14.7259 2.25289 17.204 3.28109 19.0317 5.10896C20.8594 6.93684 21.8874 9.4151 21.89 12Z"/>',
  heart: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>',
}

interface MenuItem {
  key: string
  label: string
  icon: string
  to?: string
  value?: string
}

// card/truck/info/briefcase/globe/phone — piyoladan olingan TO'LDIRILGAN
// (solid) ikonkalar, qolganlari (order/comment/user/heart) OUTLINE bo'lib
// qoladi (piyolaning autentifikatsiyalangan shaxsiy bo'limi jonli
// tekshirilmagani sababli avvalgi holicha qoldirildi).
const SOLID_ICON_KEYS = new Set(['card', 'truck', 'info', 'briefcase', 'globe', 'phone'])

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

// Piyolada haqiqiy tekshirilgan: mehmon /profile'ga TO'G'RIDAN-TO'G'RI
// (masalan URL orqali yoki header'dagi "Kirish" tugmasi emas, balki
// to'g'ridan-to'g'ri sahifaga) kirsa — DESKTOP'da (>=768px) darhol "/" ga
// qaytarib yuboriladi (guest uchun alohida sahifa umuman ko'rsatilmaydi,
// chunki desktopda header'dagi "Kirish" tugmasi istalgan vaqt modalni ochadi).
// MOBILE'da esa piyolaning pastki navigatsiyasidagi "Profil" tugmasi orqali
// kirilgani uchun (bottom nav — desktopda mavjud emas) shu yerdagi guest
// kartasi + info menyu ko'rsatiladi. Shu sabab bu yerdagi guest UI faqat
// mobile uchun qoladi, desktop uchun emas.
onMounted(() => {
  if (!authStore.isAuthenticated && window.matchMedia('(min-width: 768px)').matches) {
    router.replace('/')
  }
})

useSeoMeta({
  title: 'Foydalanuvchi profili — Kitobchi',
  description: 'Shaxsiy kabinet, buyurtmalar tarixi va sozlamalar.'
})
</script>
