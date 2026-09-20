<template>
  <main class="max-md:pb-[71px] grow py-6 md:py-12 bg-[#F8FAFC]">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Huquqiy hujjatlar</span>
      </nav>

      <!-- Header Section -->
      <div class="max-w-2xl mb-8 md:mb-12">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-neutral-900 tracking-tight mb-3">
          Huquqiy hujjatlar va qoidalar
        </h1>
        <p class="text-sm md:text-base text-neutral-500 leading-relaxed">
          Kitobchi platformasidan foydalanish tartibi, ma'lumotlar xavfsizligi, buyurtmalarni yetkazib berish va ommaviy oferta shartlari bilan tanishing.
        </p>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl p-6 border border-neutral-100 animate-pulse space-y-3">
          <div class="w-10 h-10 rounded-xl bg-neutral-100"></div>
          <div class="h-5 bg-neutral-100 rounded w-3/4"></div>
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!policies || policies.length === 0" class="bg-white rounded-3xl p-12 text-center border border-neutral-100 max-w-lg mx-auto">
        <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
        </div>
        <h2 class="text-lg font-bold text-neutral-800 mb-1">Hujjatlar topilmadi</h2>
        <p class="text-sm text-neutral-500">Hozircha tizimga huquqiy hujjatlar joylanmagan.</p>
      </div>

      <!-- Policies Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <NuxtLink
          v-for="item in policies"
          :key="item.slug"
          :to="`/legal/${item.slug}`"
          class="group bg-white rounded-2xl p-6 border border-neutral-200/60 hover:border-primary/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between"
        >
          <div>
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4 group-hover:scale-105 transition-transform duration-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-neutral-900 group-hover:text-primary transition-colors leading-snug mb-2">
              {{ item.title }}
            </h2>
          </div>

          <div class="flex items-center gap-1.5 text-sm font-semibold text-primary mt-4 pt-3 border-t border-neutral-100">
            <span>Batafsil o‘qish</span>
            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </div>
        </NuxtLink>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

const { data, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/legal`, {
  key: 'legal-index-policies',
  lazy: false
})

const policies = computed(() => {
  if (data.value?.status !== 'success') return []
  return data.value?.data || []
})

useSeoMeta({
  title: 'Huquqiy hujjatlar va qoidalar — Kitobchi',
  description: 'Kitobchi platformasi huquqiy hujjatlari: foydalanish shartlari, maxfiylik siyosati, yetkazib berish va qaytarish qoidalari.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/legal' }]
})
</script>
