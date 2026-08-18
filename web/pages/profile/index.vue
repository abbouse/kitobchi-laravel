<template>
  <div class="flex flex-col min-h-dvh bg-secondary-300 md:bg-gray-50 grow py-3 md:py-6">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto space-y-2">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-4 max-md:hidden">
        <NuxtLink to="/catalog" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Profil</span>
        </nav>
      </div>

      <!-- ========================================================================= -->
      <!--  1. GUEST HOLATI (Piyola Market 1:1)                                      -->
      <!-- ========================================================================= -->
      <div v-if="!authStore.isAuthenticated" class="space-y-2">
        <!-- Guest Foydalanuvchi kartasi (bg-white rounded-[20px]) -->
        <div class="bg-white rounded-2xl py-4 flex flex-col rounded-[20px]! px-4 sm:px-6">
          <div class="relative flex flex-col gap-3 mx-auto">
            <span class="inline-flex items-center justify-center shrink-0 select-none rounded-full align-middle size-16 text-3xl mx-auto relative bg-[#F6F6F9]">
              <svg class="w-8 h-8 text-neutral-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </span>
            <div>
              <p class="text-neutral-900 text-xl font-bold text-center m-0">Foydalanuvchi</p>
            </div>
          </div>
          <div class="flex flex-col items-center mt-2">
            <p class="text-sm md:text-base text-gray-500 text-center max-w-sm leading-relaxed px-4 m-0">
              Buyurtmalarni rasmiylashtirish, bo‘lib to‘lash imkoniyatidan foydalanish va mahsulotlarni saqlab qo‘yish uchun
            </p>
            <div class="flex gap-3 mt-3 w-full">
              <button
                type="button"
                @click="authStore.openAuthModal()"
                class="font-medium items-center transition-colors py-1.5 gap-1.5 w-full text-white bg-primary hover:bg-primary/75 h-12 flex justify-center rounded-2xl text-base px-6 border-none cursor-pointer"
              >
                <span class="truncate">Akkauntga kiring</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Guest menyu guruhlari (bg-white rounded-[20px]) -->
        <div v-for="(group, gi) in guestMenuGroups" :key="'guest-group-' + gi" class="py-1 sm:py-2 bg-white rounded-[20px] px-4">
          <template v-for="item in group" :key="item.key">
            <NuxtLink
              v-if="item.to"
              :to="item.to"
              class="bg-white rounded-2xl py-2 block group w-full text-left transition-colors"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="text-2xl text-neutral-400 group-hover:text-primary transition-all duration-300 flex justify-center items-center">
                    <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="text-2xl" v-html="PIYOLA_ICONS[item.key] || ICONS[item.icon]"></svg>
                  </div>
                  <p class="font-normal text-neutral-800 m-0 text-sm md:text-base">{{ item.label }}</p>
                </div>
                <div class="flex items-center gap-3">
                  <span v-if="item.value" class="text-neutral-400 text-sm">{{ item.value }}</span>
                  <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-all duration-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </div>
              </div>
            </NuxtLink>

            <button
              v-else
              type="button"
              class="bg-white rounded-2xl py-2 block group w-full text-left border-none bg-transparent cursor-pointer p-0"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="text-2xl text-neutral-400 group-hover:text-primary transition-all duration-300 flex justify-center items-center">
                    <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="text-2xl" v-html="PIYOLA_ICONS[item.key] || ICONS[item.icon]"></svg>
                  </div>
                  <p class="font-normal text-neutral-800 m-0 text-sm md:text-base">{{ item.label }}</p>
                </div>
                <div class="flex items-center gap-3">
                  <span v-if="item.value" class="text-neutral-400 text-sm">{{ item.value }}</span>
                  <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-all duration-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </div>
              </div>
            </button>
          </template>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!--  2. AUTHENTICATED HOLATI (Piyola Market 1:1)                              -->
      <!-- ========================================================================= -->
      <div v-else class="space-y-2 lg:flex lg:items-start lg:gap-5 lg:space-y-0">
        <!-- Desktop Sidebar -->
        <ProfileSidebar active="" class="max-lg:hidden" />

        <div class="flex-1 min-w-0 space-y-2">
          <!-- Mobil Foydalanuvchi kartasi (Piyola 1:1) -->
          <NuxtLink to="/profile/info" class="lg:hidden bg-white rounded-2xl py-3 rounded-[20px]! px-4 flex items-center justify-between gap-3 transition-colors hover:bg-neutral-50/80">
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <span class="inline-flex items-center justify-center shrink-0 select-none rounded-full align-middle size-12 text-2xl bg-[#F6F6F9] relative">
                <svg class="w-6 h-6 text-neutral-400" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-neutral-900 text-base font-semibold truncate m-0">
                  {{ authStore.user?.name || 'Foydalanuvchi' }}
                </p>
                <p class="text-sm text-neutral-500 truncate m-0 mt-0.5">
                  {{ formatPhone(authStore.user?.phone_number) }}
                </p>
              </div>
            </div>
            <svg class="w-5 h-5 text-neutral-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
          </NuxtLink>

          <!-- Menu guruhlari (Piyola 1:1) — MUHIM: faqat mobil/planshetda
               ko'rinadi (lg:hidden). Piyola desktopida /profile darhol
               /profile/orders'ga yo'naltiriladi (pastdagi onMounted'ga
               qarang) va o'rniga chap tomonda ProfileSidebar + o'ng
               tomonda buyurtmalar kontenti ko'rsatiladi — bu menyu ro'yxati
               chalkash/ortiqcha bo'lib qolardi. -->
          <div v-for="(group, gi) in menuGroups" :key="'auth-group-' + gi" class="lg:hidden py-1 sm:py-2 bg-white rounded-[20px] px-4">
            <template v-for="item in group" :key="item.key">
              <NuxtLink
                v-if="item.to"
                :to="item.to"
                class="bg-white rounded-2xl py-2 block group w-full text-left transition-colors"
              >
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div class="text-2xl text-neutral-400 group-hover:text-primary transition-all duration-300 flex justify-center items-center">
                      <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="text-2xl" v-html="PIYOLA_ICONS[item.key] || ICONS[item.icon]"></svg>
                    </div>
                    <p class="font-normal text-neutral-800 m-0 text-sm md:text-base">{{ item.label }}</p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span v-if="item.value" class="text-neutral-400 text-sm">{{ item.value }}</span>
                    <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-all duration-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                  </div>
                </div>
              </NuxtLink>

              <button
                v-else
                type="button"
                class="bg-white rounded-2xl py-2 block group w-full text-left border-none bg-transparent cursor-pointer p-0"
              >
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div class="text-2xl text-neutral-400 group-hover:text-primary transition-all duration-300 flex justify-center items-center">
                      <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="text-2xl" v-html="PIYOLA_ICONS[item.key] || ICONS[item.icon]"></svg>
                    </div>
                    <p class="font-normal text-neutral-800 m-0 text-sm md:text-base">{{ item.label }}</p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span v-if="item.value" class="text-neutral-400 text-sm">{{ item.value }}</span>
                    <svg class="w-5 h-5 text-neutral-400 group-hover:text-primary transition-all duration-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                  </div>
                </div>
              </button>
            </template>
          </div>

          <!-- Chiqish tugmasi (Mobil) -->
          <div class="lg:hidden w-full pt-1">
            <button
              type="button"
              @click="handleLogout"
              class="font-medium inline-flex items-center transition-colors px-3 py-2.5 text-sm gap-2 w-full justify-center text-primary hover:bg-primary/10 active:bg-primary/10 rounded-xl border-none bg-transparent cursor-pointer"
            >
              <span class="w-5 h-5 text-neutral-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
              </span>
              <span class="truncate font-semibold">Hisobdan chiqish</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

// Piyola Market'dagi haqiqiy SVG ikonkalar
const PIYOLA_ICONS: Record<string, string> = {
  orders: '<path fill-rule="evenodd" clip-rule="evenodd" d="M7.50027 6V6.75H5.51327C4.55327 6.75 3.74927 7.474 3.64827 8.429L2.38527 20.429C2.3578 20.6903 2.38558 20.9545 2.46679 21.2045C2.548 21.4544 2.68084 21.6844 2.85669 21.8797C3.03254 22.075 3.24748 22.2311 3.48755 22.338C3.72763 22.4448 3.98749 22.5 4.25027 22.5H19.7503C20.0131 22.5 20.2729 22.4448 20.513 22.338C20.7531 22.2311 20.968 22.075 21.1439 21.8797C21.3197 21.6844 21.4525 21.4544 21.5338 21.2045C21.615 20.9545 21.6427 20.6903 21.6153 20.429L20.3523 8.429C20.3038 7.96815 20.0865 7.54155 19.7421 7.23151C19.3977 6.92146 18.9507 6.74993 18.4873 6.75H16.5003V6C16.5003 4.80653 16.0262 3.66193 15.1823 2.81802C14.3383 1.97411 13.1937 1.5 12.0003 1.5C10.8068 1.5 9.66221 1.97411 8.81829 2.81802C7.97438 3.66193 7.50027 4.80653 7.50027 6ZM12.0003 3C11.2046 3 10.4416 3.31607 9.87895 3.87868C9.31634 4.44129 9.00027 5.20435 9.00027 6V6.75H15.0003V6C15.0003 5.20435 14.6842 4.44129 14.1216 3.87868C13.559 3.31607 12.7959 3 12.0003 3ZM9.00027 11.25C9.00027 12.0456 9.31634 12.8087 9.87895 13.3713C10.4416 13.9339 11.2046 14.25 12.0003 14.25C12.7959 14.25 13.559 13.9339 14.1216 13.3713C14.6842 12.8087 15.0003 12.0456 15.0003 11.25V10.5C15.0003 10.3011 15.0793 10.1103 15.2199 9.96967C15.3606 9.82902 15.5514 9.75 15.7503 9.75C15.9492 9.75 16.14 9.82902 16.2806 9.96967C16.4213 10.1103 16.5003 10.3011 16.5003 10.5V11.25C16.5003 12.4435 16.0262 13.5881 15.1823 14.432C14.3383 15.2759 13.1937 15.75 12.0003 15.75C10.8068 15.75 9.66221 15.2759 8.81829 14.432C7.97438 13.5881 7.50027 12.4435 7.50027 11.25V10.5C7.50027 10.3011 7.57929 10.1103 7.71994 9.96967C7.86059 9.82902 8.05136 9.75 8.25027 9.75C8.44918 9.75 8.63995 9.82902 8.7806 9.96967C8.92125 10.1103 9.00027 10.3011 9.00027 10.5V11.25Z" fill="currentColor"></path>',
  comments: '<path d="M4.913 2.658C6.988 2.388 9.103 2.25 11.25 2.25C13.397 2.25 15.512 2.389 17.587 2.658C19.509 2.908 20.878 4.519 20.992 6.385C20.6577 6.27362 20.3112 6.20278 19.96 6.174C17.1582 5.94141 14.3418 5.94141 11.54 6.174C9.182 6.37 7.5 8.364 7.5 10.608V14.894C7.49906 15.7178 7.72577 16.5258 8.15512 17.2288C8.58446 17.9319 9.19973 18.5026 9.933 18.878L7.28 21.53C7.17511 21.6348 7.04153 21.7061 6.89614 21.735C6.75074 21.7638 6.60004 21.749 6.46308 21.6923C6.32611 21.6356 6.20903 21.5395 6.12661 21.4163C6.04419 21.2931 6.00013 21.1482 6 21V16.97C5.6372 16.9314 5.27484 16.8888 4.913 16.842C2.905 16.58 1.5 14.833 1.5 12.862V6.638C1.5 4.668 2.905 2.919 4.913 2.658Z" fill="currentColor"></path><path d="M15.75 7.5C14.374 7.5 13.011 7.557 11.664 7.669C10.124 7.797 9 9.103 9 10.609V14.894C9 16.401 10.128 17.708 11.67 17.834C12.913 17.936 14.17 17.991 15.438 17.999L18.22 20.78C18.3249 20.8848 18.4585 20.9561 18.6039 20.985C18.7493 21.0139 18.9 20.999 19.0369 20.9423C19.1739 20.8856 19.291 20.7896 19.3734 20.6663C19.4558 20.5431 19.4999 20.3982 19.5 20.25V17.86L19.83 17.834C21.372 17.709 22.5 16.401 22.5 14.894V10.608C22.5 9.103 21.375 7.797 19.836 7.668C18.4769 7.55562 17.1137 7.49957 15.75 7.5Z" fill="currentColor"></path>',
  favorites: '<path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>',
  info: '<path fill-rule="evenodd" clip-rule="evenodd" d="M4.5 3.75C3.70435 3.75 2.94129 4.06607 2.37868 4.62868C1.81607 5.19129 1.5 5.95435 1.5 6.75V17.25C1.5 18.0456 1.81607 18.8087 2.37868 19.3713C2.94129 19.9339 3.70435 20.25 4.5 20.25H19.5C20.2956 20.25 21.0587 19.9339 21.6213 19.3713C22.1839 18.8087 22.5 18.0456 22.5 17.25V6.75C22.5 5.95435 22.1839 5.19129 21.6213 4.62868C21.0587 4.06607 20.2956 3.75 19.5 3.75H4.5ZM8.625 6.75C8.02826 6.75 7.45597 6.98705 7.03401 7.40901C6.61205 7.83097 6.375 8.40326 6.375 9C6.375 9.59674 6.61205 10.169 7.03401 10.591C7.45597 11.0129 8.02826 11.25 8.625 11.25C9.22174 11.25 9.79403 11.0129 10.216 10.591C10.6379 10.169 10.875 9.59674 10.875 9C10.875 8.40326 10.6379 7.83097 10.216 7.40901C9.79403 6.98705 9.22174 6.75 8.625 6.75ZM4.752 15.453C5.04323 14.6601 5.57078 13.9757 6.2634 13.4923C6.95602 13.0088 7.78034 12.7496 8.625 12.7496C9.46966 12.7496 10.294 13.0088 10.9866 13.4923C11.6792 13.9757 12.2068 14.6601 12.498 15.453C12.5603 15.6229 12.5591 15.8096 12.4946 15.9787C12.4301 16.1477 12.3067 16.2878 12.147 16.373C11.063 16.9504 9.85325 17.2517 8.625 17.25C7.39675 17.2517 6.18705 16.9504 5.103 16.373C4.94335 16.2878 4.81988 16.1477 4.75538 15.9787C4.69087 15.8096 4.68967 15.6229 4.752 15.453ZM15 8.25C14.8011 8.25 14.6103 8.32902 14.4697 8.46967C14.329 8.61032 14.25 8.80109 14.25 9C14.25 9.19891 14.329 9.38968 14.4697 9.53033C14.6103 9.67098 14.8011 9.75 15 9.75H18.75C18.9489 9.75 19.1397 9.67098 19.2803 9.53033C19.421 9.38968 19.5 9.19891 19.5 9C19.5 8.80109 19.421 8.61032 19.2803 8.46967C19.1397 8.32902 18.9489 8.25 18.75 8.25H15ZM14.25 12C14.25 11.8011 14.329 11.6103 14.4697 11.4697C14.6103 11.329 14.8011 11.25 15 11.25H18.75C18.9489 11.25 19.1397 11.329 19.2803 11.4697C19.421 11.6103 19.5 11.8011 19.5 12C19.5 12.1989 19.421 12.3897 19.2803 12.5303C19.1397 12.671 18.9489 12.75 18.75 12.75H15C14.8011 12.75 14.6103 12.671 14.4697 12.5303C14.329 12.3897 14.25 12.1989 14.25 12ZM15 14.25C14.8011 14.25 14.6103 14.329 14.4697 14.4697C14.329 14.6103 14.25 14.8011 14.25 15C14.25 15.1989 14.329 15.3897 14.4697 15.5303C14.6103 15.671 14.8011 15.75 15 15.75H18.75C18.9489 15.75 19.1397 15.671 19.2803 15.5303C19.421 15.3897 19.5 15.1989 19.5 15C19.5 14.8011 19.421 14.6103 19.2803 14.4697C19.1397 14.329 18.9489 14.25 18.75 14.25H15Z" fill="currentColor"></path>',
  installment: '<path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 13.5C2.25 11.312 3.11919 9.21354 4.66637 7.66637C6.21354 6.11919 8.31196 5.25 10.5 5.25C10.6989 5.25 10.8897 5.32902 11.0303 5.46967C11.171 5.61032 11.25 5.80109 11.25 6V12.75H18C18.1989 12.75 18.3897 12.829 18.5303 12.9697C18.671 13.1103 18.75 13.3011 18.75 13.5C18.75 15.688 17.8808 17.7865 16.3336 19.3336C14.7865 20.8808 12.688 21.75 10.5 21.75C8.31196 21.75 6.21354 20.8808 4.66637 19.3336C3.11919 17.7865 2.25 15.688 2.25 13.5Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M12.75 3C12.75 2.80109 12.829 2.61032 12.9697 2.46967C13.1103 2.32902 13.3011 2.25 13.5 2.25C15.688 2.25 17.7865 3.11919 19.3336 4.66637C20.8808 6.21354 21.75 8.31196 21.75 10.5C21.75 10.6989 21.671 10.8897 21.5303 11.0303C21.3897 11.171 21.1989 11.25 21 11.25H13.5C13.3011 11.25 13.1103 11.171 12.9697 11.0303C12.829 10.8897 12.75 10.6989 12.75 10.5V3Z" fill="currentColor"></path>',
  delivery: '<path d="M13 4C13.2652 4 13.5196 4.10536 13.7071 4.29289C13.8946 4.48043 14 4.73478 14 5H18C18.1505 5 18.2992 5.034 18.4347 5.09945C18.5703 5.1649 18.6894 5.26012 18.783 5.378L18.857 5.486L21.857 10.486L21.912 10.589L21.952 10.696L21.981 10.805L21.997 10.915L22 11V17C22 17.2652 21.8946 17.5196 21.7071 17.7071C21.5196 17.8946 21.2652 18 21 18H19.829C19.622 18.5848 19.2388 19.0912 18.7322 19.4492C18.2256 19.8073 17.6204 19.9996 17 19.9996C16.3796 19.9996 15.7744 19.8073 15.2678 19.4492C14.7612 19.0912 14.378 18.5848 14.171 18H9.829C9.62198 18.5848 9.2388 19.0912 8.73217 19.4492C8.22555 19.8073 7.6204 19.9996 7 19.9996C6.3796 19.9996 5.77445 19.8073 5.26782 19.4492C4.7612 19.0912 4.37802 18.5848 4.171 18H3C2.73478 18 2.48043 17.8946 2.29289 17.7071C2.10536 17.5196 2 17.2652 2 17V6C2 5.46957 2.21071 4.96086 2.58579 4.58579C2.96086 4.21071 3.46957 4 4 4H13ZM7 16C6.73478 16 6.48043 16.1054 6.29289 16.2929C6.10536 16.4804 6 16.7348 6 17C6 17.2652 6.10536 17.5196 6.29289 17.7071C6.48043 17.8946 6.73478 18 7 18C7.26522 18 7.51957 17.8946 7.70711 17.7071C7.89464 17.5196 8 17.2652 8 17C8 16.7348 7.89464 16.4804 7.70711 16.2929C7.51957 16.1054 7.26522 16 7 16ZM17 16C16.7348 16 16.4804 16.1054 16.2929 16.2929C16.1054 16.4804 16 16.7348 16 17C16 17.2652 16.1054 17.5196 16.2929 17.7071C16.4804 17.8946 16.7348 18 17 18C17.2652 18 17.5196 17.8946 17.7071 17.7071C17.8946 17.5196 18 17.2652 18 17C18 16.7348 17.8946 16.4804 17.7071 16.2929C17.5196 16.1054 17.2652 16 17 16ZM17.434 7H14V10H19.234L17.434 7Z" fill="currentColor"></path>',
  about: '<path d="M5.22386 2.25C4.72686 2.25 4.24986 2.448 3.89886 2.8L2.59886 4.098C1.92709 4.76853 1.53457 5.66884 1.50047 6.61738C1.46637 7.56592 1.79322 8.4921 2.41511 9.20913C3.037 9.92617 3.90765 10.3807 4.85147 10.4811C5.7953 10.5815 6.74208 10.3202 7.50086 9.75C8.12786 10.22 8.90686 10.5 9.75086 10.5C10.5949 10.5 11.3749 10.22 12.0009 9.75C12.6269 10.22 13.4069 10.5 14.2509 10.5C15.0949 10.5 15.8739 10.22 16.5009 9.75C17.2596 10.3202 18.2064 10.5815 19.1503 10.4811C20.0941 10.3807 20.9647 9.92617 21.5866 9.20913C22.2085 8.4921 22.5354 7.56592 22.5013 6.61738C22.4672 5.66884 22.0746 4.76853 21.4029 4.098L20.1029 2.799C19.7514 2.44764 19.2748 2.25017 18.7779 2.25H5.22386Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M3 20.25V11.495C4.42 12.169 6.08 12.168 7.5 11.495C8.20313 11.8285 8.97178 12.001 9.75 12C10.554 12 11.318 11.818 12 11.494C12.7031 11.8279 13.4717 12.0007 14.25 12C15.054 12 15.817 11.818 16.5 11.494C17.92 12.168 19.58 12.169 21 11.495V20.25H21.75C21.9489 20.25 22.1397 20.329 22.2803 20.4697C22.421 20.6103 22.5 20.8011 22.5 21C22.5 21.1989 22.421 21.3897 22.2803 21.5303C22.1397 21.671 21.9489 21.75 21.75 21.75H2.25C2.05109 21.75 1.86032 21.671 1.71967 21.5303C1.57902 21.3897 1.5 21.1989 1.5 21C1.5 20.8011 1.57902 20.6103 1.71967 20.4697C1.86032 20.329 2.05109 20.25 2.25 20.25H3ZM6 14.25C6 14.0511 6.07902 13.8603 6.21967 13.7197C6.36032 13.579 6.55109 13.5 6.75 13.5H9.75C9.94891 13.5 10.1397 13.579 10.2803 13.7197C10.421 13.8603 10.5 14.0511 10.5 14.25V17.25C10.5 17.4489 10.421 17.6397 10.2803 17.7803C10.1397 17.921 9.94891 18 9.75 18H6.75C6.55109 18 6.36032 17.921 6.21967 17.7803C6.07902 17.6397 6 17.4489 6 17.25V14.25ZM14.25 13.5C14.0511 13.5 13.8603 13.579 13.7197 13.7197C13.579 13.8603 13.5 14.0511 13.5 14.25V19.5C13.5 19.914 13.836 20.25 14.25 20.25H17.25C17.4489 20.25 17.6397 20.171 17.7803 20.0303C17.921 19.8897 18 19.6989 18 19.5V14.25C18 14.0511 17.921 13.8603 17.7803 13.7197C17.6397 13.579 17.4489 13.5 17.25 13.5H14.25Z" fill="currentColor"></path>',
  vacancies: '<path fill-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0 1 12 15.75c-2.73 0-5.357-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.488 49.488 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Zm-3 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"></path><path d="M3 18.4v-2.796a4.3 4.3 0 0 0 .713.31A26.226 26.226 0 0 0 12 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 0 1-6.477-.427C4.047 21.128 3 19.852 3 18.4Z" fill="currentColor"></path>',
  lang: '<path d="M21.7202 12.752C21.8558 11.03 21.5296 9.30294 20.7752 7.749C19.5266 8.93539 18.0506 9.85658 16.4362 10.457C16.566 12.0503 16.4941 13.6537 16.2222 15.229C18.1867 14.7342 20.0481 13.8956 21.7202 12.752ZM14.6332 15.55C14.9526 14.023 15.0643 12.4599 14.9652 10.903C14.0132 11.13 13.0202 11.25 11.9992 11.25C10.9782 11.25 9.98521 11.13 9.03321 10.903C8.93645 12.4599 9.04811 14.0227 9.36521 15.55C11.111 15.8176 12.8874 15.8176 14.6332 15.55ZM9.77121 17.119C11.2514 17.2941 12.747 17.2941 14.2272 17.119C13.7185 18.7566 12.9675 20.3088 11.9992 21.724C11.0309 20.3088 10.2799 18.7566 9.77121 17.119ZM7.77621 15.23C7.50285 13.6542 7.43094 12.05 7.56221 10.456C5.94749 9.85572 4.47113 8.93453 3.22221 7.748C2.46803 9.30233 2.14216 11.0297 2.27821 12.752C3.95035 13.8956 5.81173 14.7352 7.77621 15.23ZM21.3552 14.752C20.8503 16.4629 19.8864 18.0029 18.5681 19.2047C17.2498 20.4065 15.6274 21.2241 13.8772 21.569C14.7537 20.0953 15.4227 18.5076 15.8652 16.851C17.7947 16.4466 19.6478 15.7384 21.3552 14.753V14.752ZM2.64321 14.752C4.32521 15.723 6.17321 16.44 8.13321 16.851C8.57572 18.5076 9.24469 20.0953 10.1212 21.569C8.3711 21.2242 6.74885 20.4067 5.43055 19.2051C4.11225 18.0035 3.14826 16.4638 2.64321 14.753V14.752ZM13.8772 2.43C16.3549 2.91701 18.5473 4.34583 19.9932 6.416C18.9259 7.49438 17.6517 8.34608 16.2472 8.92C15.8688 6.62782 15.065 4.42664 13.8772 2.43ZM11.9992 2.276C13.4586 4.4081 14.4175 6.84236 14.8042 9.397C13.9072 9.627 12.9672 9.75 11.9992 9.75C11.0312 9.75 10.0912 9.628 9.19421 9.397C9.5809 6.84235 10.5398 4.40808 11.9992 2.276ZM10.1212 2.43C8.93344 4.42663 8.12962 6.62781 7.75121 8.92C6.34667 8.3461 5.07252 7.4944 4.00521 6.416C5.45125 4.34613 7.6436 2.91666 10.1212 2.43Z" fill="currentColor"></path>',
  contacts: '<path d="M21.89 12V19.5C21.8874 20.4937 21.4914 21.446 20.7887 22.1487C20.086 22.8514 19.1337 23.2473 18.14 23.25H12.75C12.5511 23.25 12.3603 23.171 12.2197 23.0303C12.079 22.8897 12 22.6989 12 22.5C12 22.3011 12.079 22.1103 12.2197 21.9696C12.3603 21.829 12.5511 21.75 12.75 21.75H18.14C18.7359 21.7473 19.3067 21.5095 19.7281 21.0881C20.1495 20.6667 20.3874 20.0959 20.39 19.5V19.369C20.15 19.456 19.896 19.5 19.64 19.5H18.14C17.5433 19.5 16.971 19.2629 16.549 18.841C16.1271 18.419 15.89 17.8467 15.89 17.25V13.5C15.89 12.9032 16.1271 12.3309 16.549 11.909C16.971 11.487 17.5433 11.25 18.14 11.25H20.353C20.165 9.18958 19.2096 7.27504 17.6764 5.88589C16.1431 4.49674 14.1439 3.7344 12.075 3.74998H12.065C9.99503 3.73024 7.99363 4.49118 6.45965 5.88116C4.92567 7.27114 3.97175 9.18809 3.788 11.25H6C6.59593 11.2526 7.1667 11.4905 7.58808 11.9119C8.00947 12.3333 8.24737 12.9041 8.25 13.5V17.25C8.24737 17.8459 8.00947 18.4167 7.58808 18.8381C7.1667 19.2595 6.59593 19.4973 6 19.5H4.5C3.90407 19.4973 3.3333 19.2595 2.91192 18.8381C2.49053 18.4167 2.25263 17.8459 2.25 17.25V12C2.24997 10.714 2.50433 9.44075 2.99844 8.25351C3.49254 7.06626 4.21663 5.98849 5.129 5.08225C6.04138 4.17601 7.12401 3.45921 8.31456 2.97312C9.50512 2.48704 10.7801 2.24127 12.066 2.24998H12.141C14.7259 2.25289 17.204 3.28109 19.0317 5.10896C20.8594 6.93684 21.8874 9.4151 21.89 12Z" fill="currentColor"></path>',
}

const ICONS: Record<string, string> = PIYOLA_ICONS

interface MenuItem {
  key: string
  label: string
  icon: string
  to?: string
  value?: string
}

const menuGroups: MenuItem[][] = [
  [
    { key: 'orders', label: 'Buyurtmalarim', icon: 'orders', to: '/profile/orders' },
    { key: 'comments', label: 'Sharhlarim', icon: 'comments', to: '/profile/comments' },
    { key: 'favorites', label: 'Sevimlilar', icon: 'favorites', to: '/favorites' },
    { key: 'info', label: "Ma'lumotlarim", icon: 'info', to: '/profile/info' },
  ],
  [
    { key: 'installment', label: "Muddatli to‘lov haqida", icon: 'installment', to: '/legal/tolov-va-qaytarish' },
    { key: 'delivery', label: 'Yetkazib berish haqida', icon: 'delivery', to: '/legal/yetkazib-berish-va-qaytarish' },
    { key: 'about', label: 'Biz haqimizda', icon: 'about', to: '/about' },
    { key: 'vacancies', label: 'Karyera', icon: 'vacancies', to: '/vacancies' },
  ],
  [
    { key: 'lang', label: 'Ilova tili', icon: 'lang', value: "O’zbekcha" },
    { key: 'contacts', label: "Biz bilan bog‘lanish", icon: 'contacts', to: '/contacts' },
  ],
]

const guestMenuGroups: MenuItem[][] = [menuGroups[1], menuGroups[2]]

function formatPhone(phone?: string) {
  if (!phone) return ''
  const clean = phone.replace(/\D/g, '')
  if (clean.length === 12 && clean.startsWith('998')) {
    return `+998 ${clean.slice(3, 5)} ${clean.slice(5, 8)} ${clean.slice(8, 10)} ${clean.slice(10, 12)}`
  }
  if (clean.length === 9) {
    return `+998 ${clean.slice(0, 2)} ${clean.slice(2, 5)} ${clean.slice(5, 7)} ${clean.slice(7, 9)}`
  }
  return phone.startsWith('+') ? phone : `+${phone}`
}

function handleLogout() {
  authStore.logout()
  router.push('/')
}

onMounted(() => {
  // MUHIM: piyola'da /profile (desktop) darhol /profile/orders'ga
  // yo'naltiriladi — jonli tekshirilgan (piyolamarket.uz/profile ochilganda
  // URL avtomatik /profile/orders'ga o'zgaradi). `lg:` breakpoint
  // ProfileSidebar'ning `max-lg:hidden` klassi bilan mos keladi.
  const isDesktop = window.matchMedia('(min-width: 1024px)').matches
  if (!authStore.isAuthenticated && window.matchMedia('(min-width: 768px)').matches) {
    router.replace('/')
    authStore.openAuthModal()
  } else if (authStore.isAuthenticated && isDesktop) {
    router.replace('/profile/orders')
  }
})

useSeoMeta({
  title: 'Foydalanuvchi profili — Kitobchi',
  description: 'Shaxsiy kabinet, buyurtmalar tarixi va sozlamalar.'
})
</script>
