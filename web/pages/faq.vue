<template>
  <main class="max-md:pb-[71px]">
    <div class="py-10 md:py-20">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="max-w-3xl mx-auto">
          <div v-if="pending" class="animate-pulse space-y-4">
            <div class="h-8 bg-secondary-100 rounded-lg w-2/3"></div>
            <div class="h-14 bg-secondary-100 rounded-lg w-full mt-8"></div>
            <div class="h-14 bg-secondary-100 rounded-lg w-full"></div>
            <div class="h-14 bg-secondary-100 rounded-lg w-full"></div>
          </div>

          <div v-else-if="notFound" class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25h.008v.008H12v-.008Z" /></svg>
            </div>
            <h1 class="text-lg font-bold text-neutral-800 mb-1">Savol-javoblar</h1>
            <p class="text-sm text-neutral-500">Bu sahifa mazmuni hali boshqaruv panelida to'ldirilmagan.</p>
          </div>

          <template v-else-if="policy">
            <h1 class="text-3xl md:text-4xl font-bold mb-8 text-primary">{{ policy.title }}</h1>

            <ClientOnly>
              <FaqAccordion :html="policy.content" />
              <template #fallback>
                <div class="kb-prose" v-html="policy.content"></div>
              </template>
            </ClientOnly>
          </template>
        </div>
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
</script>
