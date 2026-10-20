<template>
  <div class="py-3 md:py-6 min-h-dvh grow">
    <!-- ====== MOBILE STICKY TOP BAR (VK / Kitobchi Style) ====== -->
    <div ref="headerRef" class="md:hidden sticky top-0 z-40 mb-3 bg-white/95 backdrop-blur-md border-b border-neutral-200/80 shadow-2xs transition-all duration-300">
      <div class="px-4 py-2.5 space-y-2">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              aria-label="Orqaga"
              class="w-10 h-10 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-700 flex items-center justify-center transition-colors border-none cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-base sm:text-lg text-primary font-bold text-center m-0 truncate">
              {{ pageTitle }}
            </h1>
          </div>
          <div class="col-span-1 flex justify-end">
            <button
              type="button"
              @click="openCombinedFilter"
              aria-label="Filtr"
              class="relative w-10 h-10 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-700 flex items-center justify-center transition-colors border-none cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
              <!-- Active filter badge dot -->
              <span
                v-if="hasActiveFilters"
                class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-primary ring-2 ring-white"
              ></span>
            </button>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="relative rounded-2xl bg-[#F5F6F8] border border-neutral-200/80 px-3.5 py-2 flex items-center gap-2 focus-within:bg-white focus-within:border-primary/50 focus-within:shadow-xs transition-colors">
          <svg class="w-4 h-4 text-neutral-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
          <input
            v-model="searchInput"
            type="text"
            placeholder="Kitobchi’da izlash"
            @keyup.enter="updateSearch"
            class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 placeholder:text-neutral-400 m-0 p-0 h-full w-full"
          />
          <button
            v-if="searchInput"
            type="button"
            @click="searchInput = ''; updateSearch()"
            class="p-0.5 rounded-full text-neutral-400 hover:text-neutral-600 border-none bg-transparent cursor-pointer"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- ====== BREADCRUMB (Desktop) ====== -->
      <div class="flex items-center gap-2 pt-2 pb-3 max-md:hidden">
        <button
          type="button"
          @click="$router.back()"
          class="rounded-full w-8 h-8 flex items-center justify-center transition-colors text-neutral-700 bg-white border border-neutral-200/80 hover:bg-neutral-50 shadow-2xs cursor-pointer"
          title="Orqaga"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </button>
        <nav class="flex items-center gap-2 text-sm text-neutral-500">
          <NuxtLink to="/" class="hover:text-neutral-800 transition-colors">Asosiy</NuxtLink>
          <span class="text-neutral-300">/</span>
          <NuxtLink
            :to="activeCategory ? { path: '/catalog', query: { type: activeType } } : undefined"
            :class="[
              activeCategory ? 'hover:text-neutral-800 transition-colors' : 'text-neutral-900 font-semibold'
            ]"
          >
            Katalog
          </NuxtLink>
          <template v-if="activeCategory">
            <span class="text-neutral-300">/</span>
            <span class="text-neutral-900 font-semibold truncate">
              {{ activeCategory.name_uz || activeCategory.name }}
            </span>
          </template>
        </nav>
      </div>

      <!-- ====== HEADER ROW & SEGMENTED BAR ====== -->
      <div class="flex items-center justify-between flex-wrap gap-3 pt-1 pb-4">
        <div class="flex items-baseline flex-wrap gap-2.5">
          <h1 class="text-2xl sm:text-3xl text-neutral-900 font-bold m-0 max-md:hidden">
            {{ pageTitle }}
          </h1>
          <span class="text-xs sm:text-sm font-semibold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary">
            {{ totalCount }} ta mahsulot
          </span>
        </div>

        <!-- Type Switcher Segmented Bar (Gradient brand active style) -->
        <div class="inline-flex p-1 bg-white border border-neutral-200/80 rounded-2xl shadow-2xs">
          <button
            type="button"
            @click="setType('book')"
            :class="[
              'px-4 py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border-none cursor-pointer',
              activeType === 'book'
                ? 'bg-brand-gradient text-white shadow-xs'
                : 'text-neutral-600 hover:text-neutral-900 bg-transparent'
            ]"
          >
            Kitoblar
          </button>
          <button
            type="button"
            @click="setType('stationery')"
            :class="[
              'px-4 py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition-all border-none cursor-pointer',
              activeType === 'stationery'
                ? 'bg-brand-gradient text-white shadow-xs'
                : 'text-neutral-600 hover:text-neutral-900 bg-transparent'
            ]"
          >
            Kanselyariya
          </button>
        </div>
      </div>

      <!-- ====== HORIZONTAL CATEGORIES PILLS (VK Fast Browsing) ====== -->
      <div v-if="categoriesList.length" class="flex items-center gap-2 pb-3 overflow-x-auto no-scrollbar">
        <button
          type="button"
          @click="clearCategory"
          :class="[
            'px-4 py-2 rounded-2xl text-xs sm:text-sm font-semibold transition-all border shrink-0 cursor-pointer',
            !activeCategory
              ? 'bg-brand-gradient text-white border-transparent shadow-xs'
              : 'bg-white text-neutral-700 border-neutral-200/80 hover:bg-neutral-50 shadow-2xs'
          ]"
        >
          Barchasi
        </button>
        <button
          v-for="cat in categoriesList"
          :key="cat.id"
          type="button"
          @click="selectCategory(String(cat.id))"
          :class="[
            'px-4 py-2 rounded-2xl text-xs sm:text-sm font-semibold transition-all border shrink-0 cursor-pointer',
            activeCategory && String(activeCategory.id) === String(cat.id)
              ? 'bg-brand-gradient text-white border-transparent shadow-xs'
              : 'bg-white text-neutral-700 border-neutral-200/80 hover:bg-neutral-50 shadow-2xs'
          ]"
        >
          {{ cat.name_uz || cat.name }}
        </button>
      </div>

      <!-- ====== FILTERS & SORTING ROW ====== -->
      <div
        class="z-30 p-2 sm:p-2.5 bg-white/95 backdrop-blur-md rounded-2xl border border-neutral-200/80 shadow-2xs flex items-center gap-2 mb-6 overflow-x-auto no-scrollbar"
        :style="{ position: isMobileSticky ? 'sticky' : 'static', top: headerHeight + 'px' }"
      >
        <!-- Saralash Dropdown -->
        <CatalogSortDropdown :model-value="activeSort" @update:model-value="setSort" />

        <div class="w-px h-6 bg-neutral-200 mx-1 shrink-0"></div>

        <!-- Narx Pill -->
        <CatalogFilterPill
          label="Narx"
          mode="range"
          :is-open="activePopover === 'price'"
          :active="!!(route.query.min_price || route.query.max_price)"
          :min-value="route.query.min_price as string"
          :max-value="route.query.max_price as string"
          @toggle="togglePopover('price')"
          @close="activePopover = null"
          @apply-range="applyPriceRange"
        />

        <!-- Kitobga xos parametrlar -->
        <CatalogFilterPill
          v-if="activeType === 'book' && publisherOptions.length"
          label="Nashriyot"
          mode="checkbox"
          :is-open="activePopover === 'publisher'"
          :active="!!selectedPublisherIds.length"
          :options="publisherOptions"
          :model-value="selectedPublisherIds"
          @toggle="togglePopover('publisher')"
          @close="activePopover = null"
          @update:model-value="updatePublisherIds"
        />

        <CatalogFilterPill
          v-if="activeType === 'book' && sellerOptions.length"
          label="Do'kon"
          mode="checkbox"
          :is-open="activePopover === 'seller'"
          :active="!!selectedSellerIds.length"
          :options="sellerOptions"
          :model-value="selectedSellerIds"
          @toggle="togglePopover('seller')"
          @close="activePopover = null"
          @update:model-value="updateSellerIds"
        />

        <CatalogFilterPill
          v-if="activeType === 'book' && langTypeOptions.length"
          label="Yozuv turi"
          mode="checkbox"
          :is-open="activePopover === 'langType'"
          :active="!!selectedLangTypes.length"
          :options="langTypeOptions"
          :model-value="selectedLangTypes"
          @toggle="togglePopover('langType')"
          @close="activePopover = null"
          @update:model-value="updateLangTypes"
        />

        <CatalogFilterPill
          v-if="activeType === 'book' && coverTypeOptions.length"
          label="Muqova turi"
          mode="checkbox"
          :is-open="activePopover === 'coverType'"
          :active="!!selectedCoverTypes.length"
          :options="coverTypeOptions"
          :model-value="selectedCoverTypes"
          @toggle="togglePopover('coverType')"
          @close="activePopover = null"
          @update:model-value="updateCoverTypes"
        />

        <!-- Filtrlar tugmasi (drawer ochadi) -->
        <button
          type="button"
          @click="openCombinedFilter"
          :class="[
            'px-3.5 py-2 rounded-2xl text-xs sm:text-sm font-semibold transition-all border shrink-0 cursor-pointer inline-flex items-center gap-1.5',
            hasActiveFilters
              ? 'bg-primary text-white border-primary shadow-xs'
              : 'bg-white text-neutral-700 border-neutral-200/80 hover:bg-neutral-50 shadow-2xs'
          ]"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
          Filtrlar
          <span v-if="activeFiltersCount > 0" class="w-5 h-5 rounded-full bg-white text-primary text-xs flex items-center justify-center font-bold">
            {{ activeFiltersCount }}
          </span>
        </button>

        <!-- Filtrlarni tozalash -->
        <button
          v-if="hasActiveFilters"
          type="button"
          @click="resetAllFilters"
          class="px-3 py-2 text-xs sm:text-sm font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-2xl transition-colors inline-flex items-center gap-1 shrink-0 border-none cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          Tozalash
        </button>
      </div>

      <!-- ====== PRODUCTS GRID ====== -->
      <div v-if="products.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-10">
        <ProductCard
          v-for="(product, idx) in products"
          :key="product.id"
          :product="product"
          :type="activeType"
          :eager="idx < 2"
        />
      </div>

      <!-- ====== EMPTY STATE ====== -->
      <div v-else-if="!pending" class="bg-white rounded-3xl border border-neutral-200/80 p-8 sm:p-12 shadow-2xs text-center max-w-md mx-auto my-12">
        <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-1.5">Hech narsa topilmadi</h3>
        <p class="text-sm text-neutral-500 mb-5">Tanlangan filtrlar bo‘yicha mahsulot topilmadi. Qidiruv so‘zini o‘zgartiring yoki filtrlarni tozalang.</p>
        <button
          v-if="hasActiveFilters"
          type="button"
          @click="resetAllFilters"
          class="px-5 py-2.5 rounded-2xl bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-sm font-semibold transition-colors border-none cursor-pointer"
        >
          Filtrlarni tozalash
        </button>
      </div>

      <!-- ====== LOADING SKELETON ====== -->
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 md:gap-4 lg:gap-5 mb-10">
        <ProductCardSkeleton v-for="n in 10" :key="'catalog-skeleton-' + n" />
      </div>

      <!-- ====== YANA KO'RSATISH (LOAD MORE) ====== -->
      <div v-if="hasMore" class="flex justify-center mb-10">
        <button
          type="button"
          :disabled="isLoadingMore"
          @click="loadMore"
          class="px-6 py-3 rounded-2xl bg-white border border-neutral-200/80 hover:bg-neutral-50 text-neutral-800 font-semibold text-sm shadow-2xs hover:shadow-xs transition-all border-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center gap-2"
        >
          <svg v-if="isLoadingMore" class="animate-spin w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
          <span>{{ isLoadingMore ? 'Yuklanmoqda...' : 'Yana ko‘rsatish' }}</span>
        </button>
      </div>
    </div>

    <!-- Drawers -->
    <CatalogDrawer :is-open="isCatalogOpen" @close="isCatalogOpen = false" />

    <CatalogFilterDrawer
      :is-open="isFilterOpen"
      :min-price="route.query.min_price as string"
      :max-price="route.query.max_price as string"
      :categories="categoriesList"
      :active-category-id="route.query.category as string"
      :publishers="activeType === 'book' ? (bookFilterOptions?.publishers || []) : []"
      :sellers="activeType === 'book' ? (bookFilterOptions?.sellers || []) : []"
      :lang-types="activeType === 'book' ? (bookFilterOptions?.lang_types || []) : []"
      :cover-types="activeType === 'book' ? (bookFilterOptions?.cover_types || []) : []"
      :selected-publisher-ids="selectedPublisherIds"
      :selected-seller-ids="selectedSellerIds"
      :selected-lang-types="selectedLangTypes"
      :selected-cover-types="selectedCoverTypes"
      @close="isFilterOpen = false"
      @apply="applyFilter"
    />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const isCatalogOpen = ref(false)
const isFilterOpen = ref(false)

function openCombinedFilter() {
  isFilterOpen.value = true
}

const activeType = ref<'book' | 'stationery'>((route.query.type as any) || 'book')
const activeSort = ref<string>((route.query.sort as string) || 'popular')
const searchInput = ref<string>((route.query.search as string) || '')

// Sticky header calculation on mobile
const headerRef = ref<HTMLElement | null>(null)
const headerHeight = ref(120)
const isMobileSticky = ref(false)

function measureHeader() {
  if (headerRef.value) headerHeight.value = headerRef.value.getBoundingClientRect().height
}
function updateStickyMode() {
  isMobileSticky.value = window.matchMedia('(max-width: 767.98px)').matches
}
function handleHeaderResize() {
  measureHeader()
  updateStickyMode()
}

onMounted(() => {
  measureHeader()
  updateStickyMode()
  window.addEventListener('resize', handleHeaderResize)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', handleHeaderResize)
})

// Categories
const { data: categoriesData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'catalog-drawer-categories',
  lazy: true
})

const categoriesList = computed(() => {
  return categoriesData.value?.data?.[activeType.value] || []
})

const activeCategory = computed(() => {
  const catId = route.query.category
  if (!catId) return null
  return categoriesList.value.find((c: any) => String(c.id) === String(catId)) || null
})

// Book filter options
const { data: bookFilterOptionsData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/book-filter-options`, {
  query: computed(() => ({ category_id: activeType.value === 'book' ? (route.query.category || undefined) : undefined })),
  lazy: true,
  watch: [activeType, () => route.query.category]
})
const bookFilterOptions = computed(() => bookFilterOptionsData.value?.data || null)

function parseCsvQuery(val: unknown): string[] {
  if (!val) return []
  const raw = Array.isArray(val) ? val.join(',') : String(val)
  return raw.split(',').map((v) => v.trim()).filter(Boolean)
}

const selectedPublisherIds = computed(() => parseCsvQuery(route.query.publisher_ids))
const selectedSellerIds = computed(() => parseCsvQuery(route.query.seller_ids))
const selectedLangTypes = computed(() => parseCsvQuery(route.query.lang_types))
const selectedCoverTypes = computed(() => parseCsvQuery(route.query.cover_types))

const activePopover = ref<string | null>(null)
function togglePopover(key: string) {
  activePopover.value = activePopover.value === key ? null : key
}

const { langTypeLabel, coverTypeLabel } = useBookFilterLabels()

const publisherOptions = computed(() =>
  (bookFilterOptions.value?.publishers || []).map((p: any) => ({ value: String(p.id), label: p.name }))
)
const sellerOptions = computed(() =>
  (bookFilterOptions.value?.sellers || []).map((s: any) => ({ value: String(s.id), label: s.name }))
)
const langTypeOptions = computed(() =>
  (bookFilterOptions.value?.lang_types || []).map((lt: string) => ({ value: lt, label: langTypeLabel(lt) }))
)
const coverTypeOptions = computed(() =>
  (bookFilterOptions.value?.cover_types || []).map((ct: string) => ({ value: ct, label: coverTypeLabel(ct) }))
)

function applyPriceRange(payload: { min: string | undefined; max: string | undefined }) {
  router.push({ query: { ...route.query, min_price: payload.min, max_price: payload.max } })
}
function updatePublisherIds(vals: string[]) {
  router.push({ query: { ...route.query, publisher_ids: vals.join(',') || undefined } })
}
function updateSellerIds(vals: string[]) {
  router.push({ query: { ...route.query, seller_ids: vals.join(',') || undefined } })
}
function updateLangTypes(vals: string[]) {
  router.push({ query: { ...route.query, lang_types: vals.join(',') || undefined } })
}
function updateCoverTypes(vals: string[]) {
  router.push({ query: { ...route.query, cover_types: vals.join(',') || undefined } })
}

const pageTitle = computed(() => {
  if (route.query.search) return `Qidiruv: ${route.query.search}`
  if (activeCategory.value) return activeCategory.value.name_uz || activeCategory.value.name
  if (activeType.value === 'stationery') return 'Kanselyariya'
  if (activeSort.value === 'new') return 'Yangi kelgan kitoblar'
  if (activeSort.value === 'popular') return 'Ommabop kitoblar'
  return 'Kitoblar katalogi'
})

watch(pageTitle, () => nextTick(measureHeader))

function selectCategory(catId: string) {
  if (String(route.query.category) === String(catId)) {
    clearCategory()
  } else {
    router.push({ query: { ...route.query, category: catId } })
  }
}

function clearCategory() {
  const query = { ...route.query }
  delete query.category
  router.push({ query })
}

// Active filters count and check
const hasActiveFilters = computed(() => {
  return !!(
    route.query.min_price ||
    route.query.max_price ||
    selectedPublisherIds.value.length ||
    selectedSellerIds.value.length ||
    selectedLangTypes.value.length ||
    selectedCoverTypes.value.length
  )
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (route.query.min_price || route.query.max_price) count++
  if (selectedPublisherIds.value.length) count += selectedPublisherIds.value.length
  if (selectedSellerIds.value.length) count += selectedSellerIds.value.length
  if (selectedLangTypes.value.length) count += selectedLangTypes.value.length
  if (selectedCoverTypes.value.length) count += selectedCoverTypes.value.length
  return count
})

function resetAllFilters() {
  const query: Record<string, any> = {
    type: activeType.value
  }
  if (route.query.category) query.category = route.query.category
  if (route.query.search) query.search = route.query.search
  router.push({ query })
}

// Products fetching & pagination
const currentPage = ref(1)
const allProducts = ref<any[]>([])
const totalCount = ref(0)
const hasMore = ref(false)
const isLoadingMore = ref(false)

function toApiSort(sort: string) {
  return sort === 'new' ? 'newest' : sort
}

const searchQuery = computed(() => ({
  type: activeType.value,
  sort: toApiSort(activeSort.value),
  q: route.query.search || undefined,
  category_id: route.query.category || undefined,
  min_price: route.query.min_price || undefined,
  max_price: route.query.max_price || undefined,
  publisher_ids: selectedPublisherIds.value.join(',') || undefined,
  seller_ids: selectedSellerIds.value.join(',') || undefined,
  lang_types: selectedLangTypes.value.join(',') || undefined,
  cover_types: selectedCoverTypes.value.join(',') || undefined,
  page: 1
}))

const { data: catalogData, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/`, {
  query: searchQuery,
  watch: [() => route.query]
})

watch(catalogData, (val) => {
  allProducts.value = val?.data || []
  totalCount.value = val?.pagination?.total ?? allProducts.value.length
  hasMore.value = !!val?.pagination?.has_more
  currentPage.value = 1
}, { immediate: true })

const products = computed(() => allProducts.value)

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

function setType(type: 'book' | 'stationery') {
  activeType.value = type
  const query = { ...route.query, type }
  // Agar kategoriya boshqa turga tegishli bo'lsa tozalash
  delete query.category
  router.push({ query })
}

function setSort(sort: string) {
  activeSort.value = sort
  router.push({ query: { ...route.query, sort } })
}

function updateSearch() {
  router.push({ query: { ...route.query, search: searchInput.value || undefined } })
}

function applyFilter(payload: {
  minPrice: string | undefined
  maxPrice: string | undefined
  categoryId?: string | undefined
  publisherIds?: string[]
  sellerIds?: string[]
  langTypes?: string[]
  coverTypes?: string[]
}) {
  const query: Record<string, any> = {
    ...route.query,
    min_price: payload.minPrice,
    max_price: payload.maxPrice,
    publisher_ids: payload.publisherIds?.join(',') || undefined,
    seller_ids: payload.sellerIds?.join(',') || undefined,
    lang_types: payload.langTypes?.join(',') || undefined,
    cover_types: payload.coverTypes?.join(',') || undefined
  }
  if (payload.categoryId) {
    query.category = payload.categoryId
  } else {
    delete query.category
  }
  router.push({ query })
}

const catalogCanonicalUrl = computed(() => {
  const base = `${config.public.siteUrl}/catalog`
  return activeType.value === 'stationery' ? `${base}?type=stationery` : base
})

useSeoMeta({
  title: () => `${pageTitle.value} — Kitobchi`,
  description: () => `${pageTitle.value} bo'yicha sifatli va hamyonbop mahsulotlar Kitobchi marketpleysida.`,
  ogTitle: () => `${pageTitle.value} — Kitobchi`,
  ogUrl: () => catalogCanonicalUrl.value,
  ogType: 'website'
})

useHead({
  link: [
    { rel: 'canonical', href: () => catalogCanonicalUrl.value },
  ],
  script: [
    {
      type: 'application/ld+json',
      children: () => JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: pageTitle.value,
        url: catalogCanonicalUrl.value,
        numberOfItems: totalCount.value || products.value.length,
        itemListElement: products.value.slice(0, 40).map((p: any, idx: number) => ({
          '@type': 'ListItem',
          position: idx + 1,
          url: `${config.public.siteUrl}/${activeType.value}/${p.id}-${String(p.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')}`,
          name: p.name,
        })),
      }),
    },
  ],
})
</script>
