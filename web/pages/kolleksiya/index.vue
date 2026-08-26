<template>
  <div class="min-h-dvh bg-white grow">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-4 md:py-8">
      <nav class="flex items-center gap-2 text-sm text-[#8F8FA1] pb-4">
        <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
        <span class="text-gray-300">/</span>
        <span class="text-neutral-900 font-semibold">Kolleksiyalar</span>
      </nav>

      <h1 class="text-2xl sm:text-3xl text-primary font-bold m-0 mb-2">Kolleksiyalar</h1>
      <p class="text-neutral-500 text-sm sm:text-base mb-6 max-w-2xl">
        Mavzu bo'yicha tanlangan kitob va kanselyariya to'plamlari — sizga mos kitobni tezroq topishga yordam beradi.
      </p>

      <div v-if="collections.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <NuxtLink
          v-for="col in collections"
          :key="col.slug"
          :to="`/kolleksiya/${col.slug}`"
          class="block p-5 rounded-2xl bg-secondary-50 hover:bg-secondary-100 transition-colors border-none"
        >
          <h2 class="text-lg font-bold text-neutral-900 m-0 mb-1">{{ col.title }}</h2>
          <p v-if="col.description" class="text-sm text-neutral-500 m-0 line-clamp-2">{{ col.description }}</p>
        </NuxtLink>
      </div>
      <div v-else-if="!pending" class="text-center py-16 text-neutral-500 text-sm">
        Hozircha kolleksiyalar yo'q.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const siteUrl = (config.public?.siteUrl as string) || 'https://kitobchi.com'

const { data: response, pending } = await useFetch<any>(
  `${config.public.apiBase}/v1/kitobchi/collections`,
  { lazy: false }
)

const collections = computed(() => response.value?.data || [])

useSeoMeta({
  title: 'Kolleksiyalar — mavzu bo‘yicha kitob va kanselyariya to‘plamlari | Kitobchi',
  description: 'Kitobchi marketpleysidagi mavzuiy kitob va kanselyariya to‘plamlari — janr, yosh va qiziqish bo‘yicha tanlangan eng sara mahsulotlar.',
  ogTitle: 'Kolleksiyalar — Kitobchi',
  ogUrl: `${siteUrl}/kolleksiya`,
  ogType: 'website',
})

useHead({
  link: [
    { rel: 'canonical', href: `${siteUrl}/kolleksiya` },
  ],
})
</script>
