<template>
  <main class="min-h-screen bg-[#F8FAFC] pb-16 max-md:pb-[80px]">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto pt-4 md:pt-6">
      <!-- Breadcrumbs -->
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Savol-javoblar</span>
      </nav>

      <!-- Main Content Card -->
      <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-neutral-200/60 p-6 md:p-10 shadow-xs">
        <div v-if="pending" class="animate-pulse space-y-4">
          <div class="h-8 bg-neutral-100 rounded-lg w-2/3 mb-6"></div>
          <div class="h-14 bg-neutral-100 rounded-lg w-full"></div>
          <div class="h-14 bg-neutral-100 rounded-lg w-full"></div>
          <div class="h-14 bg-neutral-100 rounded-lg w-full"></div>
        </div>

        <div v-else-if="notFound" class="text-center py-16">
          <div class="w-16 h-16 rounded-full bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25h.008v.008H12v-.008Z" /></svg>
          </div>
          <h1 class="text-lg font-bold text-neutral-800 mb-1">Savol-javoblar</h1>
          <p class="text-sm text-neutral-500">Bu sahifa mazmuni hali boshqaruv panelida to'ldirilmagan.</p>
        </div>

        <template v-else-if="policy">
          <div class="border-b border-neutral-100 pb-6 mb-8">
            <span class="inline-block text-xs font-semibold text-primary uppercase tracking-wider mb-1.5">Yordam markazi</span>
            <h1 class="text-2xl md:text-3xl font-bold text-neutral-900 tracking-tight">{{ policy.title }}</h1>
            <p class="text-sm text-neutral-500 mt-1">Eng ko'p beriladigan savollarga javoblar bilan tanishing.</p>
          </div>

          <ClientOnly>
            <FaqAccordion :html="policy.content" />
            <template #fallback>
              <div class="kb-prose" v-html="policy.content"></div>
            </template>
          </ClientOnly>
        </template>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
const { policy, pending, notFound } = usePolicy('savol-javoblar')

useSeoMeta({
  title: 'Savol-javoblar — Kitobchi',
  description: 'Kitobchi marketpleysidan foydalanish bo‘yicha ko‘p beriladigan savollarga javoblar.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/faq' }],
})
</script>
