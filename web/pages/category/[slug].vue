<template>
  <div class="min-h-dvh bg-white grow">
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
        <h1 class="col-span-3 text-center text-lg font-semibold text-primary m-0 truncate">{{ pageTitle }}</h1>
        <button
          type="button"
          @click="isFilterOpen = true"
          class="col-span-1 justify-self-end w-10 h-10 rounded-full bg-secondary-100 text-primary flex items-center justify-center border-none cursor-pointer"
          aria-label="Filtr"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
        </button>
      </div>
    </div>

    <main class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-4 md:py-8">
      <div class="hidden md:flex items-center gap-2 mb-6">
        <NuxtLink to="/category" class="rounded-full w-9 h-9 flex items-center justify-center text-primary bg-secondary-100 hover:bg-secondary-200 transition-colors">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-700">Asosiy</NuxtLink>
          <span>/</span>
          <NuxtLink to="/category" class="hover:text-neutral-700">Kataloglar</NuxtLink>
          <span>/</span>
          <span class="text-neutral-900 font-semibold">{{ pageTitle }}</span>
        </nav>
      </div>

      <div class="flex flex-col lg:grid lg:grid-cols-[260px_1fr] gap-6">
        <aside class="hidden lg:block">
          <div class="sticky top-24 bg-white rounded-[20px] border border-neutral-100 p-2 shadow-xs">
            <NuxtLink
              v-for="cat in siblingCategories"
              :key="cat.id"
              :to="`/category/${activeType}-${cat.id}`"
              class="block px-4 py-3 rounded-2xl text-sm font-semibold transition-colors"
              :class="String(cat.id) === String(categoryId) ? 'bg-secondary-200 text-primary' : 'text-neutral-600 hover:text-primary hover:bg-secondary-100'"
            >
              {{ cat.name_uz || cat.name }}
            </NuxtLink>
          </div>
        </aside>

        <section class="min-w-0">
          <!-- MUHIM: piyolada sarlavha va "N ta mahsulot" bitta qatorda,
               yonma-yon turadi (jonli tekshirilib tasdiqlandi: "Ayollar
               original  0 dan 0 ni ko'rsatmoqda"), kitobchida esa
               sarlavha va son alohida-alohida qatorlarga bo'linib
               qolgan edi. -->
          <div class="mb-4 md:mb-6 flex items-end gap-3 flex-wrap">
            <h1 class="text-2xl md:text-4xl font-bold text-primary leading-none m-0">{{ pageTitle }}</h1>
            <p class="text-sm text-neutral-400 m-0">{{ totalCount }} ta mahsulot</p>
          </div>

          <div class="flex items-center gap-2 pb-5 overflow-x-auto no-scrollbar">
            <NuxtLink
              to="/category"
              class="px-4 py-2 rounded-2xl text-sm font-semibold shrink-0 bg-secondary-300 text-primary hover:bg-primary/10"
            >
              Kataloglar
            </NuxtLink>
            <!-- Saralash — piyoladagi kabi bitta dropdown (Ommabop / Narx:
                 pastdan yuqoriga / Narx: yuqoridan pastga / Yangi), avvalgi
                 ikkita alohida "Ommabop"/"Yangi" pill o'rniga. -->
            <CatalogSortDropdown :model-value="activeSort" @update:model-value="setSort" />
            <button
              type="button"
              @click="isFilterOpen = true"
              :class="chipClass(!!(route.query.min_price || route.query.max_price))"
            >
              Narx
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
          </div>

          <div class="lg:hidden flex items-center gap-2 pb-5 overflow-x-auto no-scrollbar">
            <NuxtLink
              v-for="cat in siblingCategories"
              :key="cat.id"
              :to="`/category/${activeType}-${cat.id}`"
              class="px-4 py-2 rounded-full text-sm font-semibold shrink-0 transition-colors"
              :class="String(cat.id) === String(categoryId) ? 'bg-primary text-white' : 'bg-secondary-300 text-primary'"
            >
              {{ cat.name_uz || cat.name }}
            </NuxtLink>
          </div>

          <div v-if="products.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-10">
            <ProductCard
              v-for="product in products"
              :key="`${activeType}-${product.id}`"
              :product="product"
              :type="activeType"
            />
          </div>

          <div v-else-if="!pending" class="text-center py-20 bg-[#F6F6F9] rounded-[24px]">
            <div class="w-20 h-20 rounded-full bg-white text-neutral-400 mx-auto flex items-center justify-center mb-4">
              <i class="icon-search text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-neutral-800 mb-1">Mahsulot topilmadi</h3>
            <p class="text-sm text-neutral-500">Bu bo‘limda hozircha mahsulot yo‘q.</p>
          </div>

          <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 md:gap-4 lg:gap-5 mb-10">
            <ProductCardSkeleton v-for="n in 10" :key="'category-skeleton-' + n" />
          </div>

          <div v-if="hasMore" class="flex justify-center mb-10">
            <button
              type="button"
              :disabled="isLoadingMore"
              @click="loadMore"
              class="px-6 py-3 rounded-2xl bg-secondary-200 text-primary font-semibold text-sm hover:bg-secondary-400 transition-colors border-none cursor-pointer disabled:opacity-75"
            >
              {{ isLoadingMore ? 'Yuklanmoqda...' : 'Yana ko‘rsatish' }}
            </button>
          </div>
        </section>
      </div>
    </main>

    <CatalogFilterDrawer
      :is-open="isFilterOpen"
      :min-price="route.query.min_price as string"
      :max-price="route.query.max_price as string"
      @close="isFilterOpen = false"
      @apply="applyFilter"
    />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const slug = computed(() => String(route.params.slug || 'book-0'))
const activeType = computed<'book' | 'stationery'>(() => slug.value.startsWith('stationery-') ? 'stationery' : 'book')
const categoryId = computed(() => {
  const match = slug.value.match(/(\d+)$/)
  return match ? match[1] : ''
})
const activeSort = ref<string>((route.query.sort as string) || 'popular')
const currentPage = ref(1)
const allProducts = ref<any[]>([])
const totalCount = ref(0)
const hasMore = ref(false)
const isLoadingMore = ref(false)
const isFilterOpen = ref(false)

const { data: categoriesData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'catalog-drawer-categories',
  lazy: true
})

const siblingCategories = computed(() => categoriesData.value?.data?.[activeType.value] || [])
const activeCategory = computed(() => siblingCategories.value.find((c: any) => String(c.id) === String(categoryId.value)) || null)
const pageTitle = computed(() => activeCategory.value?.name_uz || activeCategory.value?.name || (activeType.value === 'stationery' ? 'Kanselyariya' : 'Kitoblar'))

function toApiSort(sort: string) {
  return sort === 'new' ? 'newest' : sort
}

const searchQuery = computed(() => ({
  type: activeType.value,
  sort: toApiSort(activeSort.value),
  category_id: categoryId.value || undefined,
  min_price: route.query.min_price || undefined,
  max_price: route.query.max_price || undefined,
  page: 1
}))

const { data: catalogData, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/`, {
  query: searchQuery,
  watch: [() => route.params.slug, () => route.query]
})

watch(catalogData, (val) => {
  allProducts.value = val?.data || []
  totalCount.value = val?.pagination?.total ?? allProducts.value.length
  hasMore.value = !!val?.pagination?.has_more
  currentPage.value = 1
}, { immediate: true })

const products = computed(() => allProducts.value)

function chipClass(active: boolean) {
  return [
    'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0',
    active ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
  ]
}

function setSort(sort: string) {
  activeSort.value = sort
  router.push({ query: { ...route.query, sort } })
}

async function loadMore() {
  if (isLoadingMore.value || !hasMore.value) return
  isLoadingMore.value = true
  try {
    const nextPage = currentPage.value + 1
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/search/`, {
      query: { ...searchQuery.value, page: nextPage }
    })
    allProducts.value = [...allProducts.value, ...(res?.data || [])]
    totalCount.value = res?.pagination?.total ?? totalCount.value
    hasMore.value = !!res?.pagination?.has_more
    currentPage.value = nextPage
  } finally {
    isLoadingMore.value = false
  }
}

function applyFilter(payload: { minPrice: string | undefined; maxPrice: string | undefined }) {
  router.push({
    query: {
      ...route.query,
      min_price: payload.minPrice,
      max_price: payload.maxPrice
    }
  })
}

useSeoMeta({
  title: () => `${pageTitle.value} — Kitobchi`,
  description: () => `${pageTitle.value} bo‘limidagi mahsulotlar Kitobchi marketpleysida.`
})
</script>
