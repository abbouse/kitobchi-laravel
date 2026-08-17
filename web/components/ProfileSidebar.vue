<template>
  <div class="hidden lg:block w-64 shrink-0">
    <div class="bg-secondary-50 rounded-3xl p-5 border border-neutral-100 sticky top-24 flex flex-col gap-4">
      <div class="flex items-center gap-3 pb-4 border-b border-neutral-100">
        <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0">
          {{ initial }}
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
        <NuxtLink to="/profile/orders" :class="rowClass('orders')">
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-.75 11.25h9a2.25 2.25 0 002.25-2.25l-.75-9a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25l-.75 9a2.25 2.25 0 002.25 2.25z"/></svg>
          <span>Buyurtmalarim</span>
        </NuxtLink>

        <NuxtLink to="/profile/comments" :class="rowClass('comments')">
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12.375 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.125 12a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
          <span>Sharhlarim</span>
        </NuxtLink>

        <!-- Piyola'ning haqiqiy profil sahifasida ("2 more siblings"
             o'rniga to'liq matndan tasdiqlangan): Buyurtmalarim → Sharhlarim
             → Sevimlilar → Ma'lumotlarim. Ilgari bu yerda "Sevimlilar"
             yo'q edi. -->
        <NuxtLink to="/favorites" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all border-none cursor-pointer bg-transparent text-neutral-600 hover:bg-white">
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
          <span>Sevimlilar</span>
        </NuxtLink>

        <NuxtLink to="/profile/info" :class="rowClass('info')">
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span>Ma'lumotlarim</span>
        </NuxtLink>

        <button
          type="button"
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium text-red-500 hover:bg-neutral-100 transition-all text-left border-none bg-transparent cursor-pointer mt-2"
        >
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
          <span>Hisobdan chiqish</span>
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  active?: 'orders' | 'comments' | 'info' | ''
}>()

const authStore = useAuthStore()
const router = useRouter()

const initial = computed(() => (authStore.user?.name || authStore.user?.phone_number || 'U').charAt(0).toUpperCase())

function rowClass(tab: string) {
  const isActive = props.active === tab
  return [
    'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-all border-none cursor-pointer',
    isActive ? 'bg-white text-primary font-bold shadow-sm' : 'bg-transparent text-neutral-600 hover:bg-white'
  ]
}

function handleLogout() {
  authStore.logout()
  router.push('/')
}
</script>
