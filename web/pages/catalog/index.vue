<template>
  <div class="py-4 md:py-6 min-h-dvh bg-[#f0f2f5] grow">
    <!-- ====== MOBILE STICKY TOP BAR (PiyolaMarket 1:1) ====== -->
    <div ref="headerRef" class="md:hidden sticky top-0 z-40 mb-3">
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
              <!-- TUZATILDI: piyolada bu sarlavha text-xl (20px) / sm:text-2xl
                   (24px) — category/[slug].vue bilan bir xil qilindi (jonli
                   tekshirilib tasdiqlandi). -->
              <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0 truncate">
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
        <h1 class="text-2xl sm:text-3xl text-neutral-900 font-bold m-0 max-md:hidden">
          {{ pageTitle }}
        </h1>
        <span class="text-sm text-neutral-400 font-medium">
          {{ totalCount }} ta mahsulot
        </span>
      </div>

      <!-- Filters & Sorting Pills — MUHIM: piyolada bu qator ham header
           (sarlavha+qidiruv) bilan BIRGA yopishqoq bo'lib qoladi (jonli
           scroll-tekshiruv bilan tasdiqlandi, `category/[slug].vue`dagi
           bilan bir xil naqsh/sabab — batafsil izoh o'sha faylda). `md:static`
           kabi Tailwind klassi ISHLATILMAYDI (loyihaning statik CSS
           bundle'ida kompilyatsiya qilinmagan), shu sabab `position` va
           `top` JS orqali `:style`ga yoziladi. -->
      <div
        class="z-30 bg-white flex items-center gap-2 pb-6 overflow-x-auto no-scrollbar"
        :style="{ position: isMobileSticky ? 'sticky' : 'static', top: headerHeight + 'px' }"
      >
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

        <!-- Piyoladagi "Narx ⌄"/"Brendlar ⌄" naqshiga 1-1: har bir facet
             o'ZINING kichik, mustaqil pill+popover'iga ega — bitta katta
             panelga TIQIB QO'YISH o'rniga (jonli piyolamarket.uz'da
             tasdiqlangan naqsh: CatalogFilterPill.vue). Pastdagi "Filtr"
             tugmasi esa piyoladagi kabi HAMMASINI birlashtirgan qo'shimcha
             (ixtiyoriy) yon panelni ochadi — bu ikkisi bir-birini
             takrorlaydi, xuddi piyolada ham shunday (jonli tasdiqlandi). -->
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

        <!-- Desktopda piyola "Filtr" tugmasini alohida chip sifatida
             qator ichida ko'rsatadi (mobile'da esa header'dagi slider
             ikonkasi orqali, yuqoridagi kabi) — faqat kategoriya
             tanlanganda ko'rinadi (jonli tekshirilib tasdiqlandi). MUHIM:
             piyolada bu tugma pastdagi alohida pill'lar tanlangan bo'lsa
             ham DOIM neytral ko'rinishda qoladi (jonli tasdiqlandi) —
             shu sabab statik klass, hech qanday "active" holatga
             bog'lanmagan. -->
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
          v-for="(product, idx) in products"
          :key="product.id"
          :product="product"
          :type="activeType"
          :eager="idx < 2"
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
      :categories="categoriesData?.data?.[activeType] || []"
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
// MUHIM: piyolada "Narx" endi alohida kichik popover (CatalogFilterPill,
// pastda), yon panel EMAS — shu sabab bu yerdagi "Filtr" endi doim
// Kategoriyalar+hammasi birlashtirilgan yagona rejimda ochiladi (avvalgi
// 'price'/'combined' ikki xil rejim keragi qolmadi).
function openCombinedFilter() {
  isFilterOpen.value = true
}
const activeType = ref<'book' | 'stationery'>((route.query.type as any) || 'book')
const activeSort = ref<string>((route.query.sort as string) || 'popular')
const searchInput = ref<string>((route.query.search as string) || '')

// Mobileda filtr/saralash pill qatorini header (sarlavha+qidiruv) ostiga
// "yopishtirish" uchun — `category/[slug].vue`dagi bilan bir xil mantiq
// (u yerdagi izohda batafsil tushuntirilgan: header balandligini JS orqali
// o'lchash, `matchMedia` bilan mobil/desktop holatini aniqlash — `md:static`
// Tailwind klassi loyihaning statik CSS bundle'ida yo'q).
const headerRef = ref<HTMLElement | null>(null)
const headerHeight = ref(150)
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

// Kitobga xos filtr variantlari (Nashriyot/Do'kon/Yozuv turi/Muqova turi) —
// category/[slug].vue'dagi bilan bir xil mantiq (SearchController::
// bookFilterOptions). category tanlanmagan bo'lsa — BARCHA kitoblar
// bo'yicha mavjud variantlar qaytariladi.
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

// Piyoladagi alohida "Narx ⌄"/"Brendlar ⌄" pill+popover naqshi uchun —
// bir vaqtda faqat BITTA popover ochiq turadi (CatalogFilterPill.vue),
// category/[slug].vue'dagi bilan bir xil mantiq.
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

// Checkbox facet'lar DARHOL qo'llanadi (piyolada "Brendlar" jonli
// tekshirilib tasdiqlandi), Narx esa "Qo'llash" tugmasi orqali.
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

// Sarlavha o'zgarganda (qidiruv, kategoriya, tur) header balandligi ham
// o'zgarishi mumkin (matn boshqa qatorga o'tib ketishi/wrap) — shu sabab
// qayta o'lchaymiz.
watch(pageTitle, () => nextTick(measureHeader))

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
  router.push({ query: { ...route.query, type } })
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
  // Drawer endi doim Kategoriyalar bilan birga ochiladi (Narx alohida
  // kichik popoverga ko'chirilgani sabab bu yerda faqat "combined" rejim
  // qoldi), shuning uchun categoryId har doim shu yerda qo'llanadi.
  if (payload.categoryId) {
    query.category = payload.categoryId
  } else {
    delete query.category
  }
  router.push({ query })
}

// TUZATILDI: bu yerda faqat title+description bor edi, canonical umuman
// yo'q edi — natijada `nuxt.config.ts`dagi UMUMIY (bosh sahifaga qattiq
// yozilgan) canonical meros bo'lib qolardi, ya'ni Google'ga "bu sahifaning
// asl nusxasi aslida bosh sahifa" degan noto'g'ri signal ketardi (bu
// katalog — saytning eng muhim, ko'p mahsulotli sahifasi — reytingga
// chiqishiga xalaqit berishi mumkin edi). Endi aniq belgilandi: `sort`/
// `search`/`min_price`/`publisher_ids` kabi filtr parametrlari OLIB
// TASHLANADI (ular yuzlab "deyarli bir xil" URL variantlari yaratadi —
// bularning barchasi bitta toza `type`ga bog'langan URL'ga ko'rsatiladi,
// Google'ning e'tibori tarqalib ketmasligi uchun), faqat `type` (kitob/
// kanselyariya haqiqatan boshqa-boshqa kontent) saqlanadi.
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
  // TUZATILDI: katalog (saytdagi eng katta mahsulotlar ro'yxati) uchun
  // hech qanday JSON-LD yo'q edi. `ItemList` sxemasi qo'shildi — Google'ga
  // bu sahifa nima haqida ekanini (mahsulotlar ro'yxati) va joriy 20-40 ta
  // mahsulotning nomi/havolasini aniq ko'rsatadi.
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
