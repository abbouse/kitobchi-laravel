<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow">
    <!-- ====== MOBILE STICKY TOP BAR (PiyolaMarket 1:1) ====== -->
    <div class="md:hidden sticky top-0 z-40 mb-3">
      <div class="py-3 rounded-b-2xl bg-white shadow-sm transition-all duration-300">
        <div class="px-4 space-y-2">
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
              <h1 class="text-lg sm:text-xl text-primary font-semibold text-center m-0 truncate">
                {{ pageTitle }}
              </h1>
            </div>
            <div class="col-span-1 flex justify-end"></div>
          </div>

          <div>
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-[20px] px-4 py-2.5 h-11 text-gray bg-secondary-300! flex items-center gap-2.5">
              <i class="icon-search text-lg text-gray-500"></i>
              <input
                v-model="searchInput"
                type="text"
                placeholder="Kitobchi’da izlash"
                @keyup.enter="updateSearch"
                class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 font-inherit m-0 p-0 h-full w-full"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 pt-4 pb-2 max-md:hidden">
        <NuxtLink to="/" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300" title="Asosiy">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-900 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Katalog</span>
        </nav>
      </div>

      <!-- Header Row -->
      <div class="flex items-center justify-between flex-wrap gap-3 pt-2 pb-4">
        <h1 class="text-2xl sm:text-3xl text-primary font-bold m-0 max-md:hidden">
          {{ pageTitle }}
        </h1>
        <span class="text-sm text-neutral-400 font-medium">
          {{ products.length }} ta mahsulot
        </span>
      </div>

      <!-- Filters & Sorting Pills -->
      <div class="flex items-center gap-2 pb-6 overflow-x-auto no-scrollbar flex-nowrap">
        <!-- Type Pill -->
        <button
          type="button"
          @click="setType('book')"
          :class="[
            'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeType === 'book' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
          ]"
        >
          Kitoblar
        </button>
        <button
          type="button"
          @click="setType('stationery')"
          :class="[
            'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeType === 'stationery' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
          ]"
        >
          Kanselyariya
        </button>

        <div class="w-px h-6 bg-secondary-200 mx-1 shrink-0"></div>

        <!-- Sort Pills -->
        <button
          type="button"
          @click="setSort('popular')"
          :class="[
            'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeSort === 'popular' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
          ]"
        >
          Ommabop
        </button>
        <button
          type="button"
          @click="setSort('new')"
          :class="[
            'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeSort === 'new' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
          ]"
        >
          Yangi
        </button>
      </div>

      <!-- Products Grid -->
      <div v-if="products.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-10">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          :type="activeType"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-20">
        <div class="w-20 h-20 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <i class="icon-search text-3xl"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-800 mb-1">Hech narsa topilmadi</h3>
        <p class="text-sm text-neutral-500">Qidiruv so‘zini o‘zgartirib ko‘ring yoki filtrlarni tozalang</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const activeType = ref<'book' | 'stationery'>((route.query.type as any) || 'book')
const activeSort = ref<string>((route.query.sort as string) || 'popular')
const searchInput = ref<string>((route.query.search as string) || '')

const pageTitle = computed(() => {
  if (route.query.search) return `Qidiruv: ${route.query.search}`
  if (activeType.value === 'stationery') return 'Kanselyariya'
  if (activeSort.value === 'new') return 'Yangi kelgan kitoblar'
  if (activeSort.value === 'popular') return 'Ommabop kitoblar'
  return 'Kitoblar katalogi'
})

const { data: catalogData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/products/search`, {
  query: computed(() => ({
    type: activeType.value,
    sort: activeSort.value,
    search: route.query.search || undefined,
    category: route.query.category || undefined
  })),
  watch: [() => route.query]
})

const products = computed(() => {
  return catalogData.value?.data || catalogData.value?.products || []
})

function setType(type: 'book' | 'stationery') {
  activeType.value = type
  router.push({ query: { ...route.query, type } })
}

function setSort(sort: string) {
  activeSort.value = sort
  router.push({ query: { ...route.query, sort } })
}

function updateSearch() {
  router.push({ query: { ...route.query, search: searchInput.value || undefined } })
}

useSeoMeta({
  title: () => `${pageTitle.value} — Kitobchi`,
  description: () => `${pageTitle.value} bo'yicha sifatli va hamyonbop mahsulotlar Kitobchi marketpleysida.`
})
</script>
