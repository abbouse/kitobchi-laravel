<template>
  <div class="p-4 md:p-6 h-fit rounded-3xl bg-secondary-50 lg:w-100 lg:shrink-0 lg:sticky top-24 max-lg:hidden">
    <div class="relative group/user flex items-center gap-2.5 mb-4">
      <span class="inline-flex items-center justify-center shrink-0 select-none rounded-full align-middle size-10 text-xl bg-transparent group relative">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-neutral-900 text-xl font-bold truncate m-0">{{ authStore.user?.name || 'Foydalanuvchi' }}</p>
      </div>
    </div>
    
    <nav class="relative flex gap-1.5 flex-col [&>a]:min-w-0 [&>button]:min-w-0">
      <NuxtLink to="/profile/orders" class="group relative w-full flex items-center font-medium before:absolute before:z-[-1] before:rounded-md focus:outline-none focus-visible:outline-none focus-visible:before:outline-3 before:outline-primary/25 flex-row before:inset-y-px before:inset-x-0 transition-colors before:transition-colors text-base px-4 py-3 gap-4 rounded-xl overflow-hidden" :class="active === 'orders' ? 'text-primary before:bg-[rgb(241,245,249)]' : 'text-neutral-500 hover:text-neutral-900 hover:before:bg-[rgb(241,245,249)]/50'">
        <svg class="shrink-0 size-5 transition-colors" :class="active === 'orders' ? 'text-primary' : 'text-neutral-400 group-hover:text-neutral-600'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-.75 11.25h9a2.25 2.25 0 002.25-2.25l-.75-9a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25l-.75 9a2.25 2.25 0 002.25 2.25z"/></svg>
        <span class="truncate">Buyurtmalarim</span>
      </NuxtLink>

      <NuxtLink to="/profile/comments" class="group relative w-full flex items-center font-medium before:absolute before:z-[-1] before:rounded-md focus:outline-none focus-visible:outline-none focus-visible:before:outline-3 before:outline-primary/25 flex-row before:inset-y-px before:inset-x-0 transition-colors before:transition-colors text-base px-4 py-3 gap-4 rounded-xl overflow-hidden" :class="active === 'comments' ? 'text-primary before:bg-[rgb(241,245,249)]' : 'text-neutral-500 hover:text-neutral-900 hover:before:bg-[rgb(241,245,249)]/50'">
        <svg class="shrink-0 size-5 transition-colors" :class="active === 'comments' ? 'text-primary' : 'text-neutral-400 group-hover:text-neutral-600'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12.375 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.125 12a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
        <span class="truncate">Sharhlarim</span>
      </NuxtLink>

      <NuxtLink to="/profile/info" class="group relative w-full flex items-center font-medium before:absolute before:z-[-1] before:rounded-md focus:outline-none focus-visible:outline-none focus-visible:before:outline-3 before:outline-primary/25 flex-row before:inset-y-px before:inset-x-0 transition-colors before:transition-colors text-base px-4 py-3 gap-4 rounded-xl overflow-hidden" :class="active === 'info' ? 'text-primary before:bg-[rgb(241,245,249)]' : 'text-neutral-500 hover:text-neutral-900 hover:before:bg-[rgb(241,245,249)]/50'">
        <svg class="shrink-0 size-5 transition-colors" :class="active === 'info' ? 'text-primary' : 'text-neutral-400 group-hover:text-neutral-600'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="truncate">Ma'lumotlarim</span>
      </NuxtLink>

      <button type="button" @click="handleLogout" class="group relative w-full flex items-center font-medium before:absolute before:z-[-1] before:rounded-md focus:outline-none focus-visible:outline-none focus-visible:before:outline-3 before:outline-primary/25 flex-row before:inset-y-px before:inset-x-0 transition-colors before:transition-colors text-base px-4 py-3 gap-4 rounded-xl overflow-hidden text-neutral-500 hover:text-neutral-900 hover:before:bg-[rgb(241,245,249)]/50 bg-transparent border-none cursor-pointer">
        <svg class="shrink-0 size-5 text-neutral-400 group-hover:text-neutral-600 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
        <span class="truncate">Hisobdan chiqish</span>
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  active: {
    type: String,
    default: ''
  }
})

const authStore = useAuthStore()
const router = useRouter()

function handleLogout() {
  authStore.logout()
  router.push('/')
}
</script>
