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
                aria-label="Orqaga"
                class="relative overflow-hidden transition-shadow duration-300 rounded-full! px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 glass-card-bg h-11 w-11 flex-center p-0! cursor-pointer border-none text-primary"
              >
                <div class="absolute inset-0 pointer-events-none glass-border rounded-full!"></div>
                <svg class="w-5 h-5 relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
              </button>
            </div>
            <div class="col-span-3">
              <h1 class="text-lg sm:text-xl text-primary font-semibold text-center m-0 truncate">
                {{ pageTitle }}
              </h1>
            </div>
            <div class="col-span-1 flex justify-end">
              <!-- Piyolada kategoriya ichiga kirilganda header'dagi o'ng
                   tugma "Kataloglar" (barcha kategoriyalarni ko'rish) EMAS,
                   balki "Filtr" (sozlamalar/slider ikonkasi) ga almashadi —
                   bosilganda Narx oralig'i + Kategoriyalar birlashtirilgan
                   bottom sheet ochiladi (jonli tekshirilib tasdiqlandi).
                   Kategoriya tanlanmagan holatda esa avvalgidek "Kataloglar"
                   drawer'i ochiladi. -->
              <button
                v-if="activeCategory"
                type="button"
                @click="openCombinedFilter"
                aria-label="Filtr"
                class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
              </button>
              <button
                v-else
                type="button"
                @click="isCatalogOpen = true"
                aria-label="Kataloglar"
                class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9m-9 6h9m-9 6h9M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12.75h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 18.75h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
              </button>
            </div>
          </div>

          <div>
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-[20px] px-4 py-2.5 h-11 text-gray bg-secondary-300! flex items-center gap-2.5">
              <i class="icon-search text-lg text-gray-500"></i>
              <input
                v-model="searchInput"
                type="text"
                placeholder="Kitobchi’da izlash"
                @keyup.enter="updateSearch"
                class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 m-0 p-0 h-full w-full"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 pt-4 pb-2 max-md:hidden">
        <NuxtLink to="/" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400" title="Asosiy">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Katalog</span>
        </nav>
      </div>

      <!-- Header Row — MUHIM: piyolada sarlavha va "N ta mahsulot" bitta
           qatorda, yonma-yon (chapga to'plangan holda) turadi; ilgari bu
           yerda `justify-between` bo'lib, son sarlavhadan uzoq, o'ng
           chetga surilib ketardi. -->
      <div class="flex items-end flex-wrap gap-3 pt-2 pb-4">
        <h1 class="text-2xl sm:text-3xl text-primary font-bold m-0 max-md:hidden">
          {{ pageTitle }}
        </h1>
        <span class="text-sm text-neutral-400 font-medium">
          {{ totalCount }} ta mahsulot
        </span>
      </div>

      <!-- Filters & Sorting Pills -->
      <div class="flex items-center gap-2 pb-6 overflow-x-auto no-scrollbar">
        <!-- Faol kategoriya chip'i — piyolada kategoriya ichiga kirilganda
             sahifa konteksti (nom) doim ko'rinib turadi; kitobchida bunday
             ko'rinish yo'q edi, foydalanuvchi qaysi kategoriyada ekanini
             va uni qanday tozalashni bilmasdi. -->
        <button
          v-if="activeCategory"
          type="button"
          @click="clearCategory"
          class="px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0 bg-primary text-white"
        >
          {{ activeCategory.name_uz || activeCategory.name }}
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div v-if="activeCategory" class="w-px h-6 bg-secondary-200 mx-1 shrink-0"></div>

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

        <!-- Saralash — piyoladagi kabi bitta dropdown (Ommabop / Narx:
             pastdan yuqoriga / Narx: yuqoridan pastga / Yangi), avvalgi
             ikkita alohida "Ommabop"/"Yangi" pill o'rniga. -->
        <CatalogSortDropdown :model-value="activeSort" @update:model-value="setSort" />

        <div class="w-px h-6 bg-secondary-200 mx-1 shrink-0"></div>

        <!-- Narx filtri — piyoladagi mobil "Narx ⌄" chip'iga mos (jonli
             tekshirilib tasdiqlangan: chevron-down ikonkali pill, bosilganda
             pastdan chiquvchi "bottom sheet" ochiladi — generic "Filtr"
             so'zi emas, aynan "Narx" nomi bilan). -->
        <button
          type="button"
          @click="openPriceFilter"
          :class="[
            'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0',
            isFilterActive ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
          ]"
        >
          Narx
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <!-- Desktopda piyola "Filtr" tugmasini alohida chip sifatida
             qator ichida ko'rsatadi (mobile'da esa header'dagi slider
             ikonkasi orqali, yuqoridagi kabi) — faqat kategoriya
             tanlanganda ko'rinadi (jonli tekshirilib tasdiqlandi). -->
        <button
          v-if="activeCategory"
          type="button"
          @click="openCombinedFilter"
          class="max-md:hidden px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0 bg-secondary-300 text-primary hover:bg-primary/10"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
          Filtr
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
      <div v-else-if="!pending" class="text-center py-20">
        <div class="w-20 h-20 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <i class="icon-search text-3xl"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-800 mb-1">Hech narsa topilmadi</h3>
        <p class="text-sm text-neutral-500">Qidiruv so‘zini o‘zgartirib ko‘ring yoki filtrlarni tozalang</p>
      </div>

      <!-- Loading skeleton (birinchi yuklanish) -->
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 md:gap-4 lg:gap-5 mb-10">
        <ProductCardSkeleton v-for="n in 10" :key="'catalog-skeleton-' + n" />
      </div>

      <!-- Yana ko'rsatish -->
      <div v-if="hasMore" class="flex justify-center mb-10">
        <button
          type="button"
          :disabled="isLoadingMore"
          @click="loadMore"
          class="px-6 py-3 rounded-2xl bg-secondary-200 text-primary font-semibold text-sm hover:bg-secondary-400 transition-colors border-none cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
        >
          {{ isLoadingMore ? 'Yuklanmoqda...' : 'Yana ko‘rsatish' }}
        </button>
      </div>
    </div>

    <CatalogDrawer :is-open="isCatalogOpen" @close="isCatalogOpen = false" />

    <CatalogFilterDrawer
      :is-open="isFilterOpen"
      :min-price="route.query.min_price as string"
      :max-price="route.query.max_price as string"
      :categories="filterDrawerMode === 'combined' ? (categoriesData?.data?.[activeType] || []) : undefined"
      :active-category-id="route.query.category as string"
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
// "Narx" chip'i faqat narx maydonlarini ochadi ('price'), header/desktop
// "Filtr" tugmasi esa Narx+Kategoriyalar birlashtirilgan ko'rinishni
// ochadi ('combined') — piyolada bular ikki xil sheet edi (jonli
// tekshirilib tasdiqlandi).
const filterDrawerMode = ref<'price' | 'combined'>('price')
function openPriceFilter() {
  filterDrawerMode.value = 'price'
  isFilterOpen.value = true
}
function openCombinedFilter() {
  filterDrawerMode.value = 'combined'
  isFilterOpen.value = true
}
const isFilterActive = computed(() => !!(route.query.min_price || route.query.max_price))
const activeType = ref<'book' | 'stationery'>((route.query.type as any) || 'book')
const activeSort = ref<string>((route.query.sort as string) || 'popular')
const searchInput = ref<string>((route.query.search as string) || '')

// Kategoriya nomlari — CatalogDrawer.vue bilan bir xil kalit ('catalog-drawer-
// categories'), shuning uchun Nuxt payload keshi orqali ikkalasi bitta so'rovni
// ulashadi (qayta-qayta fetch qilinmaydi). MUHIM: bu yerga kirilganda (ya'ni
// biror kategoriya bosilganda) piyolada sahifa sarlavhasi va breadcrumb HAQIQIY
// kategoriya nomini ko'rsatadi ("Erkaklar original" kabi) — ilgari kitobchida
// `category` query parametri sarlavhada umuman aks etmas, foydalanuvchi qaysi
// kategoriyada ekanini bilolmas edi.
const { data: categoriesData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'catalog-drawer-categories',
  lazy: true
})

const activeCategory = computed(() => {
  const catId = route.query.category
  if (!catId) return null
  const list = categoriesData.value?.data?.[activeType.value] || []
  return list.find((c: any) => String(c.id) === String(catId)) || null
})

const pageTitle = computed(() => {
  if (route.query.search) return `Qidiruv: ${route.query.search}`
  if (activeCategory.value) return activeCategory.value.name_uz || activeCategory.value.name
  if (activeType.value === 'stationery') return 'Kanselyariya'
  if (activeSort.value === 'new') return 'Yangi kelgan kitoblar'
  if (activeSort.value === 'popular') return 'Ommabop kitoblar'
  return 'Kitoblar katalogi'
})

function clearCategory() {
  const query = { ...route.query }
  delete query.category
  router.push({ query })
}

// MUHIM: `/v1/kitobchi/products/search` degan endpoint HAQIQATDA MAVJUD
// EMAS edi — u aslida `products/{col}` route'iga tushib, {col}='search'
// (int)ga o'girilganda 0 (LIMIT 0) bo'lib, doim BO'SH natija qaytargan
// (shuning uchun kategoriyaga kirilganda mahsulotlar umuman ko'rinmasdi).
// To'g'ri, real qidiruv/katalog endpointi — SearchController::search():
// GET /v1/kitobchi/search/  (params: q, category_id, type, sort, page,
// min_price, max_price...). Backend browse-rejimini (matn/teg/kategoriyasiz,
// faqat sort bilan) qo'llab-quvvatlashi uchun ham moslashtirildi.
const currentPage = ref(1)
const allProducts = ref<any[]>([])
const totalCount = ref(0)
const hasMore = ref(false)
const isLoadingMore = ref(false)

// Bu sahifadagi "new" qiymati (tugma/URL uchun, boshqa sahifalar ham shu
// bilan link beradi: /catalog?sort=new) backendning "newest" enumiga mos
// kelmaydi — shu yerda tarjima qilinadi, boshqa hech narsa o'zgarmaydi.
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
  router.push({ query: { ...route.query, type } })
}

function setSort(sort: string) {
  activeSort.value = sort
  router.push({ query: { ...route.query, sort } })
}

function updateSearch() {
  router.push({ query: { ...route.query, search: searchInput.value || undefined } })
}

function applyFilter(payload: { minPrice: string | undefined; maxPrice: string | undefined; categoryId?: string | undefined }) {
  const query: Record<string, any> = {
    ...route.query,
    min_price: payload.minPrice,
    max_price: payload.maxPrice
  }
  // categoryId faqat "Filtr" (birlashtirilgan) rejimda keladi — "Narx"
  // rejimida bu maydon undefined bo'lib qoladi, shuning uchun joriy
  // kategoriya query'da o'zgarishsiz saqlanadi.
  if (filterDrawerMode.value === 'combined') {
    if (payload.categoryId) {
      query.category = payload.categoryId
    } else {
      delete query.category
    }
  }
  router.push({ query })
}

useSeoMeta({
  title: () => `${pageTitle.value} — Kitobchi`,
  description: () => `${pageTitle.value} bo'yicha sifatli va hamyonbop mahsulotlar Kitobchi marketpleysida.`
})
</script>
