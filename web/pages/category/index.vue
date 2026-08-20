<template>
  <div class="min-h-dvh bg-[#F6F6F9] grow">
    <div class="md:hidden sticky top-0 z-40 bg-white rounded-b-2xl shadow-sm">
      <div class="px-4 py-3 grid grid-cols-5 items-center gap-2">
        <button
          type="button"
          @click="$router.back()"
          class="col-span-1 w-10 h-10 rounded-full bg-secondary-100 text-primary flex items-center justify-center border-none cursor-pointer"
          aria-label="Orqaga"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="col-span-3 text-center text-lg font-semibold text-primary m-0">Kataloglar</h1>
        <div class="col-span-1"></div>
      </div>
    </div>

    <main class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-4 md:py-8">
      <div class="hidden md:flex items-center gap-2 mb-6">
        <NuxtLink to="/" class="rounded-full w-9 h-9 flex items-center justify-center text-primary bg-white hover:bg-secondary-100 transition-colors">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-700">Asosiy</NuxtLink>
          <span>/</span>
          <span class="text-neutral-900 font-semibold">Kataloglar</span>
        </nav>
      </div>

      <section class="bg-white rounded-[20px] md:rounded-[28px] p-4 md:p-6 shadow-xs">
        <div class="flex items-end justify-between gap-4 mb-4 md:mb-6">
          <div>
            <h2 class="text-2xl md:text-4xl font-bold text-primary leading-none m-0">Kataloglar</h2>
            <p class="text-sm text-neutral-400 mt-2 mb-0">Kitoblar va kanselyariya bo‘limlari</p>
          </div>
          <NuxtLink to="/catalog" class="text-sm font-semibold text-primary hover:underline shrink-0">Barcha mahsulotlar</NuxtLink>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-5">
          <NuxtLink
            v-for="cat in categoryCards"
            :key="`${cat.type}-${cat.id}`"
            :to="`/category/${cat.type}-${cat.id}`"
            class="group/item block rounded-[20px] bg-[#F6F6F9] p-4 md:p-5 h-[118px] md:h-[164px] relative overflow-hidden hover:bg-secondary-200 transition-all"
          >
            <div class="relative z-10 max-w-[70%]">
              <span class="inline-flex px-2.5 py-1 rounded-full bg-white text-[11px] font-semibold text-primary shadow-xs mb-2">
                {{ cat.type === 'book' ? 'Kitoblar' : 'Kanselyariya' }}
              </span>
              <h3 class="text-base md:text-xl font-bold text-neutral-900 leading-tight m-0 line-clamp-2 group-hover/item:text-primary transition-colors">
                {{ cat.name }}
              </h3>
            </div>

            <div class="absolute right-3 bottom-3 w-20 h-20 md:w-28 md:h-28 rounded-2xl bg-white flex items-center justify-center overflow-hidden shadow-sm transition-transform duration-500 group-hover/item:scale-105 group-hover/item:-rotate-2">
              <img :src="cat.image" :alt="cat.name" class="w-full h-full object-contain p-3" loading="lazy" />
            </div>
          </NuxtLink>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

const { data: categoriesData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'category-hub-categories',
  lazy: true
})

function categoryImage(cat: any) {
  const icon = cat?.icon || cat?.image || ''
  if (icon && String(icon).startsWith('http')) return icon
  if (icon) return `/storage/${icon}`
  return '/images/logo/logo_blue.png'
}

const categoryCards = computed(() => {
  const book = (categoriesData.value?.data?.book || []).map((cat: any) => ({
    id: cat.id,
    type: 'book',
    name: cat.name_uz || cat.name || 'Kitoblar',
    image: categoryImage(cat)
  }))
  const stationery = (categoriesData.value?.data?.stationery || []).map((cat: any) => ({
    id: cat.id,
    type: 'stationery',
    name: cat.name_uz || cat.name || 'Kanselyariya',
    image: categoryImage(cat)
  }))
  return [...book, ...stationery]
})

useSeoMeta({
  title: 'Kataloglar — Kitobchi',
  description: 'Kitobchi kataloglari: kitoblar, janrlar va kanselyariya bo‘limlari.'
})
</script>
