<template>
  <div class="min-h-dvh bg-[#f0f2f5] grow">
    <div ref="headerRef" class="md:hidden sticky top-0 z-40 bg-white rounded-b-2xl shadow-sm">
      <div class="px-4 py-3 grid grid-cols-5 items-center gap-2">
        <button
          type="button"
          @click="$router.back()"
          class="col-span-1 w-10 h-10 rounded-full bg-secondary-100 text-primary flex items-center justify-center border-none cursor-pointer"
          aria-label="Orqaga"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </button>
        <!-- TUZATILDI: piyolada bu sarlavha text-xl (20px) / sm:text-2xl
             (24px) — jonli getComputedStyle bilan tasdiqlandi. Bizda
             oddiy text-lg (18px) edi, bir oz kichikroq ko'rinardi. -->
        <h1 class="col-span-3 text-center text-xl sm:text-2xl font-semibold text-primary m-0 truncate">{{ pageTitle }}</h1>
        <button
          type="button"
          @click="isFilterOpen = true"
          class="col-span-1 justify-self-end w-10 h-10 rounded-full bg-secondary-100 text-primary flex items-center justify-center border-none cursor-pointer"
          aria-label="Filtr"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
        </button>
      </div>
      <!-- MUHIM: piyolaning HAR BIR sahifasida (bosh sahifa, kategoriya
           ichida ham) header ostida to'liq kenglikdagi qidiruv paneli bor
           (jonli tekshirilib tasdiqlandi: "Piyola'da izlash"), bizda esa
           bu qism faqat `/catalog` sahifasida bor edi, kategoriya
           sahifasida (aynan shu sahifada) umuman yo'q edi. `catalog/
           index.vue`dagi bilan bir xil uslub va xatti-harakat — izlash
           natijasi umumiy katalog sahifasiga olib boradi (bu sahifada
           matn bo'yicha qidiruv infratuzilmasi yo'q, faqat kategoriya
           bo'yicha). -->
      <div class="px-4 pb-3">
        <div class="relative overflow-hidden transition-shadow duration-300 rounded-[20px] px-4 py-2.5 h-11 text-gray bg-secondary-300! flex items-center gap-2.5">
          <i class="icon-search text-lg text-gray-500"></i>
          <input
            v-model="searchInput"
            type="text"
            placeholder="Kitobchi’da izlash"
            @keyup.enter="submitSearch"
            class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 m-0 p-0 h-full w-full"
          />
        </div>
      </div>
    </div>

    <main class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-4 md:py-8">
      <!-- MUHIM: `/category` (kataloglar-xaritasi) sahifasi butunlay olib
           tashlandi — foydalanuvchi so'rovi bo'yicha. Shu sabab orqaga
           strelka endi `/category`ga emas, oddiy `router.back()`ga
           (books/[id].vue'dagi kabi), breadcrumb esa ortiqcha "Kataloglar"
           bo'g'inisiz — "Asosiy / {pageTitle}" ko'rinishida (mahsulot
           sahifasidagi "Katalog" havolasini olib tashlashda qo'llangan
           bilan bir xil mantiq). -->
      <div class="hidden md:flex items-center gap-2 mb-6">
        <button
          type="button"
          @click="$router.back()"
          class="rounded-full w-9 h-9 flex items-center justify-center text-primary bg-secondary-100 hover:bg-secondary-200 transition-colors border-none cursor-pointer"
          aria-label="Orqaga"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </button>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-700">Asosiy</NuxtLink>
          <span>/</span>
          <span class="text-neutral-900 font-semibold">{{ pageTitle }}</span>
        </nav>
      </div>

      <!-- MUHIM: bu yerda ilgari `lg:grid lg:grid-cols-[260px_1fr]` ishlatilgan
           edi — bu arbitrary (qavsli) qiymatli klass, loyihadagi
           `assets/css/piyola.css` esa avtomatik qayta generatsiya qilinmaydigan,
           bir marta commit qilingan STATIK fayl (oxirgi marta generatsiya
           qilingan sana undan keyin yozilgan barcha yangi klasslardan eski).
           Shu sabab bu klass uchun CSS umuman yo'q edi — natijada
           `display:grid` ishlagan-u, lekin ustunlar kengligi belgilanmagani
           uchun aside va section bo'lim ustma-ust, TO'LIQ KENGLIKDA
           tushib qolgan edi (aynan shu www.book-6 sahifasida ko'ringan
           muammo). Endi standart, allaqachon CSS'da mavjud
           bo'lgan flex+w-64 klasslar bilan almashtirildi. -->
      <div class="flex flex-col lg:flex-row lg:items-start gap-6">
        <aside class="hidden lg:block lg:w-64 lg:shrink-0">
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

          <!-- MUHIM: piyolada mobileda header (sarlavha+qidiruv) VA filtr/
               saralash pill'lar qatori BIRGALIKDA, bitta yopishqoq blok
               sifatida qotadi (jonli scroll-tekshiruv bilan tasdiqlandi:
               ikkalasi ham ekran tepasida birga qoladi, faqat mahsulotlar
               to'ri o'zi scroll bo'ladi). Bizda ilgari faqat sarlavha+
               qidiruv qismi `sticky` edi, bu qator esa mahsulotlar bilan
               birga scroll bo'lib ketardi.

               Endi bu qator ham (faqat mobileda) yopishqoq qilindi — LEKIN
               `md:static` kabi Tailwind klassi ISHLATILMADI, chunki loyihada
               `assets/css/piyola.css` bir marta generatsiya qilingan STATIK
               fayl va bu klass (`grep` bilan tekshirildi) unda umuman
               kompilyatsiya qilinmagan (xuddi ilgari `shadow-xl`/`z-[60]`da
               bo'lgani kabi — sinab ko'rilmagan klass jim tarzda hech qanday
               stilga ega bo'lmaydi). Shu sabab `position` VA `top` ikkalasi
               ham JS orqali `:style`ga yoziladi (`isMobileSticky` — mobil/
               desktop holatini `matchMedia` bilan aniqlaydi, `headerHeight` —
               header balandligiga moslab hisoblanadi, pastda
               `measureHeader()`/`updateStickyMode()`), shrift yoki burilish
               sabab o'zgarishi mumkin bo'lgan qattiq pixel qiymat yozib
               qo'yish xavfidan qochilgan. -->
          <div
            class="z-30 bg-white flex items-center gap-2 pb-5 overflow-x-auto no-scrollbar"
            :style="{ position: isMobileSticky ? 'sticky' : 'static', top: headerHeight + 'px' }"
          >
            <!-- Saralash — piyoladagi kabi bitta dropdown (Ommabop / Narx:
                 pastdan yuqoriga / Narx: yuqoridan pastga / Yangi), avvalgi
                 ikkita alohida "Ommabop"/"Yangi" pill o'rniga. -->
            <CatalogSortDropdown :model-value="activeSort" @update:model-value="setSort" />
            <!-- "Filtr" — piyoladagi kabi HAMMASINI birlashtirgan yon panelni
                 ochadigan YAGONA umumiy tugma (Narx + Nashriyot + Do'kon +
                 Yozuv turi + Muqova turi — barchasi shu yerda ham mavjud).
                 MUHIM: piyolada bu tugma pastdagi alohida pill'lar (Narx,
                 Brendlar) tanlangan bo'lsa ham DOIM bir xil (neytral)
                 ko'rinishda qoladi — faqat o'sha aloxida pill'ning o'zi
                 faollashadi (jonli tekshirilib tasdiqlandi: Brendlar orqali
                 filtr qo'llanganda "Filtr" tugmasi emas, faqat "Brendlar"
                 tugmasi to'q rangga o'tgan edi). Shu sabab bu yerda
                 `isAnyFilterActive`ga bog'lanmagan, doim neytral. -->
            <!-- MUHIM: piyolada mobileda "Filtr" alohida MATNLI pill sifatida
                 bu qatorda umuman ko'rinmaydi — faqat header'dagi slider
                 ikonkasi orqali ochiladi (yuqorida, jonli tasdiqlandi).
                 Bizda esa ilgari ikkalasi ham bor edi (ikonka + bu pill) —
                 ortiqcha takrorlanish. Shu sabab bu pill endi `md:`dan
                 pastda (mobileda) yashirin, faqat desktopda ko'rinadi. -->
            <button
              type="button"
              @click="isFilterOpen = true"
              :class="[...chipClass(false), 'max-md:hidden']"
            >
              Filtr
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>

            <!-- Piyoladagi "Narx ⌄"/"Brendlar ⌄" naqshiga 1-1: har bir facet
                 (Narx, Nashriyot, Do'kon, Yozuv turi, Muqova turi) o'ZINING
                 kichik, mustaqil pill+popover'iga ega — yuqoridagi "Filtr"
                 hammasini bitta katta panelga TIQIB QO'YISH o'rniga (jonli
                 piyolamarket.uz'da tasdiqlangan naqsh: CatalogFilterPill.vue). -->
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
              v-for="(product, idx) in products"
              :key="`${activeType}-${product.id}`"
              :product="product"
              :type="activeType"
              :eager="idx < 2"
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
      :publishers="activeType === 'book' ? (filterOptions?.publishers || []) : []"
      :sellers="activeType === 'book' ? (filterOptions?.sellers || []) : []"
      :lang-types="activeType === 'book' ? (filterOptions?.lang_types || []) : []"
      :cover-types="activeType === 'book' ? (filterOptions?.cover_types || []) : []"
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

const slug = computed(() => String(route.params.slug || 'book-0'))
const activeType = computed<'book' | 'stationery'>(() => slug.value.startsWith('stationery-') ? 'stationery' : 'book')
const categoryId = computed(() => {
  const match = slug.value.match(/(\d+)$/)
  return match ? match[1] : ''
})
const activeSort = ref<string>((route.query.sort as string) || 'popular')
// Piyoladagi har bir sahifada (kategoriya ichida ham) bor to'liq kenglikdagi
// qidiruv paneli — bu sahifada matn qidiruv infratuzilmasi yo'q, shu sabab
// `catalog/index.vue`dagi umumiy katalog+qidiruv sahifasiga yo'naltiradi.
const searchInput = ref('')
function submitSearch() {
  if (!searchInput.value.trim()) return
  router.push({ path: '/catalog', query: { search: searchInput.value, type: activeType.value } })
}

// Mobileda filtr/saralash pill qatorini header (sarlavha+qidiruv) ostiga
// "yopishtirish" uchun — header balandligini JS orqali o'lchaydi (qattiq
// pixel qiymat emas, chunki shrift/burilish sabab o'zgarishi mumkin) va
// mobil/desktop holatini `matchMedia` bilan aniqlaydi (`md:static` kabi
// Tailwind klassi loyihaning statik CSS bundle'ida yo'q — yuqoridagi
// izohda tushuntirilgan).
const headerRef = ref<HTMLElement | null>(null)
const headerHeight = ref(112)
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

// Kategoriya o'zgarganda (masalan yon panel orqali) sarlavha matni
// o'zgarib, ba'zan boshqa qatorga o'tib ketishi (wrap) mumkin — shu sabab
// header balandligini har safar pageTitle o'zgarganda qayta o'lchaymiz.
watch(pageTitle, () => nextTick(measureHeader))

// 4-Language Dynamic SEO
const { setCategorySeo } = useAppSeo()
watchEffect(() => {
  setCategorySeo(pageTitle.value, activeType.value, slug.value)
})

// TUZATILDI: catalog/index.vue'dagi bilan bir xil — bu sahifada
// mahsulotlar ro'yxati uchun hech qanday JSON-LD yo'q edi. `ItemList`
// sxemasi qo'shildi.
useHead({
  script: [
    {
      type: 'application/ld+json',
      children: () => JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: pageTitle.value,
        url: `${config.public.siteUrl}/category/${slug.value}`,
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

// Kitobga xos filtr variantlari (Nashriyot/Do'kon/Yozuv turi/Muqova turi) —
// faqat book turida kerak, joriy kategoriyada haqiqatda mavjud
// variantlargina qaytariladi (backend: SearchController::bookFilterOptions).
const { data: filterOptionsData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/book-filter-options`, {
  query: computed(() => ({ category_id: activeType.value === 'book' ? (categoryId.value || undefined) : undefined })),
  lazy: true,
  watch: [activeType, categoryId]
})
const filterOptions = computed(() => filterOptionsData.value?.data || null)

// Route query'dagi vergul bilan ajratilgan qatorlarni massivga o'giradi
// (masalan "1,2,3" → ['1','2','3']). Bitta-bitta parametr ham,
// tasodifan massiv ham kelib qolishi mumkin (Vue Router xatti-harakati) —
// ikkalasini ham qo'llab-quvvatlaydi.
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
// bir vaqtda faqat BITTA popover ochiq turadi (CatalogFilterPill.vue).
const activePopover = ref<string | null>(null)
function togglePopover(key: string) {
  activePopover.value = activePopover.value === key ? null : key
}

const { langTypeLabel, coverTypeLabel } = useBookFilterLabels()

const publisherOptions = computed(() =>
  (filterOptions.value?.publishers || []).map((p: any) => ({ value: String(p.id), label: p.name }))
)
const sellerOptions = computed(() =>
  (filterOptions.value?.sellers || []).map((s: any) => ({ value: String(s.id), label: s.name }))
)
const langTypeOptions = computed(() =>
  (filterOptions.value?.lang_types || []).map((lt: string) => ({ value: lt, label: langTypeLabel(lt) }))
)
const coverTypeOptions = computed(() =>
  (filterOptions.value?.cover_types || []).map((ct: string) => ({ value: ct, label: coverTypeLabel(ct) }))
)

// Alohida pill'lardan kelgan o'zgarishlar — checkbox facet'lar DARHOL
// qo'llanadi (piyolada "Brendlar" checkbox'i jonli tekshirilib tasdiqlandi:
// bosilishi bilanoq, alohida tugmasiz natija yangilangan edi), Narx esa
// "Qo'llash" tugmasi orqali (matn kiritish commit nuqtasini talab qiladi).
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

function toApiSort(sort: string) {
  return sort === 'new' ? 'newest' : sort
}

const searchQuery = computed(() => ({
  type: activeType.value,
  sort: toApiSort(activeSort.value),
  category_id: categoryId.value || undefined,
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

function applyFilter(payload: {
  minPrice: string | undefined
  maxPrice: string | undefined
  publisherIds?: string[]
  sellerIds?: string[]
  langTypes?: string[]
  coverTypes?: string[]
}) {
  router.push({
    query: {
      ...route.query,
      min_price: payload.minPrice,
      max_price: payload.maxPrice,
      publisher_ids: payload.publisherIds?.join(',') || undefined,
      seller_ids: payload.sellerIds?.join(',') || undefined,
      lang_types: payload.langTypes?.join(',') || undefined,
      cover_types: payload.coverTypes?.join(',') || undefined
    }
  })
}

useSeoMeta({
  title: () => `${pageTitle.value} — Kitobchi`,
  description: () => `${pageTitle.value} bo‘limidagi mahsulotlar Kitobchi marketpleysida.`
})
</script>
