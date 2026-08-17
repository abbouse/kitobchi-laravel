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
        <NuxtLink to="/catalog" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-900 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Profil</span>
        </nav>
      </div>

      <!-- Auth State Check -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20">
        <div class="w-20 h-20 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <i class="icon-profile text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">
          Buyurtmalar va shaxsiy ma’lumotlarni ko‘rish uchun telefon raqamingiz orqali tizimga kiring.
        </p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <!-- Authenticated Profile (Piyola 1:1) -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6 items-start">
        <!-- Sidebar -->
        <div class="bg-secondary-50 rounded-3xl p-5 border border-secondary-100 sticky top-24 flex flex-col gap-4">
          <div class="flex items-center gap-3 pb-4 border-b border-secondary-200">
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

          <nav class="flex flex-col gap-1">
            <button
              type="button"
              @click="activeTab = 'orders'"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left border-none cursor-pointer',
                activeTab === 'orders' ? 'bg-white text-primary font-bold shadow-sm' : 'bg-transparent text-neutral-600 hover:bg-white/50'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
              <span>Buyurtmalarim</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'info'"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all text-left border-none cursor-pointer',
                activeTab === 'info' ? 'bg-white text-primary font-bold shadow-sm' : 'bg-transparent text-neutral-600 hover:bg-white/50'
              ]"
            >
              <i class="icon-profile text-lg"></i>
              <span>Ma’lumotlarim</span>
            </button>

            <button
              type="button"
              @click="handleLogout"
              class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium text-red-500 hover:bg-red-50 transition-all text-left border-none bg-transparent cursor-pointer mt-4"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
              <span>Hisobdan chiqish</span>
            </button>
          </nav>
        </div>

        <!-- Main Content Cards -->
        <div class="space-y-6">
          <!-- Orders Tab -->
          <div v-if="activeTab === 'orders'" class="p-6 rounded-3xl bg-secondary-50 border border-secondary-100">
            <h2 class="text-xl font-bold text-neutral-900 mb-4">Buyurtmalar tarixi</h2>
            <div class="text-center py-12 text-neutral-400">
              <i class="icon-order text-4xl mb-2 block"></i>
              <p class="text-sm">Hozircha hech qanday buyurtma mavjud emas</p>
            </div>
          </div>

          <!-- Info Tab -->
          <div v-if="activeTab === 'info'" class="p-6 rounded-3xl bg-secondary-50 border border-secondary-100 space-y-4">
            <h2 class="text-xl font-bold text-neutral-900 mb-4">Shaxsiy ma’lumotlar</h2>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Ism familiya</label>
                <input
                  v-model="userNameInput"
                  type="text"
                  class="w-full rounded-2xl bg-white px-4 py-3 border border-secondary-200 outline-none focus:border-primary font-medium"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1">Telefon raqam</label>
                <input
                  :value="'+' + authStore.user?.phone_number"
                  disabled
                  type="text"
                  class="w-full rounded-2xl bg-secondary-100 px-4 py-3 border border-secondary-200 text-neutral-500 font-medium cursor-not-allowed"
                />
              </div>
              <button
                type="button"
                @click="handleSaveInfo"
                class="px-6 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors"
              >
                Saqlash
              </button>
            </div>
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

const activeTab = ref<'orders' | 'info'>('orders')
const userNameInput = ref(authStore.user?.name || '')

function handleSaveInfo() {
  if (authStore.user) {
    authStore.user.name = userNameInput.value
    alert('Ma’lumotlar saqlandi!')
  }
}

function handleLogout() {
  authStore.logout()
  router.push('/')
}

useSeoMeta({
  title: 'Foydalanuvchi profili — Kitobchi',
  description: 'Shaxsiy kabinet va buyurtmalar tarixi.'
})
</script>
