<template>
  <div class="bg-[#f0f2f5]">
    <h1 class="sr-only">Kitobchi — Online kitoblar va kanselyariya marketpleysi</h1>

    <!-- ====== HERO BANNERS CAROUSEL (Piyola 1:1 aspect-520/141) ====== -->
    <div v-if="banners.length > 0" class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto max-md:px-0 max-md:p-0!">
      <section class="md:py-6">
        <div class="relative overflow-hidden rounded-2xl lg:rounded-[30px]">
          <div class="flex items-center gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory px-4 md:px-0">
            <div
              v-for="(banner, idx) in banners"
              :key="idx"
              class="min-w-0 shrink-0 basis-full snap-center"
            >
              <a
                :href="banner.url || '/catalog'"
                class="relative w-full aspect-520/141 h-full rounded-2xl lg:rounded-[30px] overflow-hidden block group/item bg-secondary-100 shadow-sm"
              >
                <img
                  :src="banner.image"
                  :alt="banner.title || 'Kitobchi aksiya'"
                  class="w-full h-full object-cover transform transition-transform duration-700 group-hover/item:scale-105"
                  loading="eager"
                  fetchpriority="high"
                />
                <div class="absolute inset-0 bg-primary/0 group-hover/item:bg-primary/10 transition-colors duration-300 pointer-events-none"></div>
              </a>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- ====== CATEGORIES CIRCLE CAROUSEL ====== -->
    <section v-if="categories.length > 0" class="py-6 md:py-8">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <h2 class="font-bold text-xl md:text-2xl text-neutral-900 tracking-tight mb-4">
          Kataloglar
        </h2>
        <div class="flex items-start flex-row -ms-4 gap-4 md:gap-6 overflow-x-auto no-scrollbar pb-2 px-4">
          <div
            v-for="cat in categories"
            :key="cat.id"
            class="min-w-0 shrink-0 ps-4 basis-1/4 md:basis-1/6 lg:basis-1/8"
          >
            <NuxtLink
              :to="`/category/book-${cat.id}`"
              class="group flex flex-col items-center gap-2 no-underline"
            >
              <div class="w-18 h-18 md:w-24 md:h-24 rounded-full overflow-hidden border border-neutral-200/70 hover:border-neutral-400 transition-colors bg-neutral-50 flex items-center justify-center">
                <div class="relative w-full h-full flex items-center justify-center p-3">
                  <img
                    v-if="cat.image"
                    :src="cat.image"
                    :alt="cat.name"
                    class="w-full h-full object-contain"
                    loading="lazy"
                  />
                  <span
                    v-else
                    class="font-bold text-neutral-700 text-xl md:text-2xl leading-none select-none"
                  >{{ cat.letter }}</span>
                </div>
              </div>
              <span class="font-medium text-xs md:text-sm leading-tight text-center text-neutral-700 group-hover:text-neutral-950 transition-colors truncate max-w-full">
                {{ cat.name }}
              </span>
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- ====== SECTION 1: YANGI KELGAN KITOBLAR (Faqat Desktopda) ====== -->
    <section v-if="newBooks.length > 0" class="py-4 md:py-6 lg:py-8 max-md:hidden">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="w-full px-1 mb-3 md:mb-5 flex items-center justify-between gap-3">
          <h2 class="font-bold text-xl md:text-2xl tracking-tight text-neutral-900 m-0">
            Yangi kelgan kitoblar
          </h2>
          <NuxtLink
            to="/catalog?sort=new"
            class="text-xs md:text-sm font-medium text-neutral-500 hover:text-neutral-900 inline-flex items-center gap-1 transition-colors no-underline"
          >
            <span>Barchasini ko‘rish</span>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 lg:gap-5 mb-5">
          <ProductCard
            v-for="(book, idx) in newBooks.slice(0, 5)"
            :key="'new-' + book.id"
            :product="book"
            type="book"
            :eager="idx < 5"
          />
        </div>
      </div>
    </section>

    <!-- ====== SECTION 2: TAVSIYA ETAMIZ (Faqat Desktopda) ====== -->
    <section v-if="recommendedBooks.length > 0" class="py-4 md:py-6 lg:py-8 max-md:hidden">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="w-full px-1 mb-3 md:mb-5 flex items-center justify-between gap-3">
          <h2 class="font-bold text-xl md:text-2xl tracking-tight text-neutral-900 m-0">
            Tavsiya etamiz
          </h2>
          <NuxtLink
            to="/catalog?sort=popular"
            class="text-xs md:text-sm font-medium text-neutral-500 hover:text-neutral-900 inline-flex items-center gap-1 transition-colors no-underline"
          >
            <span>Barchasini ko‘rish</span>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 lg:gap-5 mb-5">
          <ProductCard
            v-for="book in recommendedBooks.slice(0, 5)"
            :key="'rec-' + book.id"
            :product="book"
            type="book"
          />
        </div>
      </div>
    </section>

    <!-- ====== KATEGORIYA QATORLARI — FAQAT DESKTOPDA (5 tadan mahsulot) ====== -->
    <section
      v-for="cat in categoryRows"
      :key="cat.category_id"
      class="py-4 md:py-6 lg:py-8 max-md:hidden"
    >
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="w-full px-1 mb-3 md:mb-5 flex items-center justify-between gap-3">
          <h2 class="font-bold text-xl md:text-2xl tracking-tight text-neutral-900 m-0">
            {{ cat.name_uz }}
          </h2>
          <NuxtLink
            :to="`/category/book-${cat.category_id}`"
            class="text-xs md:text-sm font-medium text-neutral-500 hover:text-neutral-900 inline-flex items-center gap-1 transition-colors no-underline"
          >
            <span>Barchasini ko‘rish</span>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 lg:gap-5 mb-5">
          <ProductCard
            v-for="book in cat.books.slice(0, 5)"
            :key="'cat-' + cat.category_id + '-' + book.id"
            :product="book"
            type="book"
          />
        </div>
      </div>
    </section>

    <!-- ====== BARCHA MAHSULOTLAR — FAQAT MOBILDA ====== -->
    <section v-if="mobileFeed.length > 0" class="py-4 md:hidden">
      <div class="px-4 w-full mx-auto">
        <h2 class="font-bold text-lg text-neutral-900 tracking-tight m-0 mb-3">
          Barcha mahsulotlar
        </h2>

        <div class="grid grid-cols-2 gap-2.5">
          <ProductCard
            v-for="(product, idx) in mobileFeed"
            :key="'all-' + product.id"
            :product="product"
            type="book"
            :eager="idx < 2"
          />
        </div>

        <!-- Cheksiz scroll sentinel -->
        <div ref="mobileSentinel" class="h-1 w-full" aria-hidden="true"></div>

        <!-- Shimmer loading state when fetching more items -->
        <div v-if="isMobileLoadingMore" class="grid grid-cols-2 gap-2.5 mt-2.5">
          <ProductCardSkeleton v-for="n in 4" :key="'skeleton-more-' + n" />
        </div>

        <!-- Fallback tugma -->
        <div v-else-if="mobileHasMore" class="flex justify-center py-4">
          <button
            type="button"
            @click="loadMoreMobile"
            class="px-6 py-2.5 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-semibold text-sm border-none cursor-pointer transition-colors"
          >
            Yana ko‘rsatish
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

// Parallel SSR Data Fetching for maximum performance and instant speed
const { data: pageData } = await useAsyncData('homepage-data', async () => {
  try {
    const [home, cat, catRows, mobileFirst] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/home`, {
        query: { limit: 10 }
      }).catch(() => null),
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`).catch(() => null),
      // MUHIM: category_limit avval 4 edi — backend tuzatilgach (faqat
      // haqiqiy, faol BookCategories ro'yxatidan olinadi, hozircha 10 ta)
      // endi BARCHA haqiqiy kategoriyalarni ko'rsatish uchun 10 ga
      // ko'tarildi (backend maksimal 12 bilan cheklaydi — kelajakda
      // kategoriya soni ko'paysa ham xavfsiz).
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/products/books-by-category`, {
        query: { type: 'recommended', category_limit: 50, per_category: 5 }
      }).catch(() => null),
      // Mobil "Barcha mahsulotlar" bo'limining 1-sahifasi — real katalog
      // qidiruv endpointi (catalog/index.vue'dagi bilan bir xil), faqat
      // kategoriya/qidiruv filtrisiz — piyola mobilidagi kabi barcha
      // kitoblarni (kategoriyalarga bo'lmasdan) ommabop tartibda beradi.
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/search/`, {
        query: { type: 'book', sort: 'popular', page: 1 }
      }).catch(() => null)
    ])
    return { home, cat, catRows, mobileFirst }
  } catch (e) {
    return { home: null, cat: null, catRows: null, mobileFirst: null }
  }
})

const homeRes = computed(() => pageData.value?.home)
const catRes = computed(() => pageData.value?.cat)
const categoryRows = computed(() => pageData.value?.catRows?.data || [])

// ====== Mobil "Barcha mahsulotlar" cheksiz-scroll holati ======
// computed emas, `ref` — chunki sahifalab (loadMoreMobile orqali)
// qo'shilib boradigan, mutatsiyalanadigan ro'yxat.
const mobileFeed = ref<any[]>([])
const mobileCurrentPage = ref(1)
const mobileHasMore = ref(false)
const isMobileLoadingMore = ref(false)

// SSR va client hydration'da bir xil natija uchun: pageData tayyor
// bo'lgach faqat BIR MARTA (mobileFeed hali bo'sh bo'lganda) to'ldiriladi —
// shundan keyin foydalanuvchi scroll qilib qo'shgan sahifalar ustidan
// yozib yubormaydi.
watchEffect(() => {
  const first = pageData.value?.mobileFirst
  if (first && mobileFeed.value.length === 0) {
    mobileFeed.value = first.data || []
    mobileHasMore.value = !!first.pagination?.has_more
  }
})

async function loadMoreMobile() {
  if (isMobileLoadingMore.value || !mobileHasMore.value) return
  isMobileLoadingMore.value = true
  try {
    const nextPage = mobileCurrentPage.value + 1
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/search/`, {
      query: { type: 'book', sort: 'popular', page: nextPage }
    })
    mobileFeed.value = [...mobileFeed.value, ...(res?.data || [])]
    mobileHasMore.value = !!res?.pagination?.has_more
    mobileCurrentPage.value = nextPage
  } finally {
    isMobileLoadingMore.value = false
  }
}

// Piyoladagi kabi tugmasiz, o'zi scroll qilgani sari yuklanadigan
// cheksiz-scroll — sentinel elementi ekranga kirganda keyingi sahifa
// so'raladi. Faqat client'da ishlaydi (IntersectionObserver SSR'da yo'q).
const mobileSentinel = ref<HTMLElement | null>(null)
let mobileObserver: IntersectionObserver | null = null

onMounted(() => {
  if (!mobileSentinel.value || typeof IntersectionObserver === 'undefined') return
  mobileObserver = new IntersectionObserver((entries) => {
    if (entries[0]?.isIntersecting) {
      loadMoreMobile()
    }
  }, { rootMargin: '600px 0px' })
  mobileObserver.observe(mobileSentinel.value)
})

onBeforeUnmount(() => {
  mobileObserver?.disconnect()
  mobileObserver = null
})

const banners = computed(() => {
  const data = homeRes.value?.data || homeRes.value || {}
  const raw = data.top_banners || data.banners || data.center_banners || []
  if (Array.isArray(raw) && raw.length > 0) {
    return raw.map((b: any) => {
      let img = b.imgUrl || b.image || ''
      if (img && !img.startsWith('http')) {
        img = `/storage/${img}`
      }
      return {
        image: img || '/images/banner.png',
        url: b.url || (b.action_id ? `/catalog?collection=${b.action_id}` : '/catalog'),
        title: b.title || 'Kitobchi aksiya'
      }
    })
  }
  return []
})

const categories = computed(() => {
  const books = catRes.value?.data?.book || []
  if (!Array.isArray(books) || books.length === 0) return []

  return books.map((c: any) => {
    const raw = String(c.icon || '')
    // Eski ma'lumotlarda `icon` emoji bo'lishi mumkin — u rasm yo'li emas,
    // shuning uchun faqat nuqta yoki slash bor qiymat rasm deb qaraladi.
    const isPath = raw !== '' && /[/.]/.test(raw)
    const name = c.name_uz || c.name || 'Katalog'
    return {
      id: c.id,
      name,
      // Rasm bo'lmasa umumiy logotip emas, kategoriya nomining bosh harfi
      // ko'rsatiladi — shunda kataloglar qatori bo'sh ko'rinmaydi.
      image: isPath ? (raw.startsWith('http') ? raw : `/storage/${raw}`) : '',
      letter: String(name).trim().charAt(0).toUpperCase() || '#'
    }
  })
})

const newBooks = computed(() => {
  const data = homeRes.value?.data || homeRes.value || {}
  return data.new_products || data.new_books || data.newBooks || []
})

const recommendedBooks = computed(() => {
  const data = homeRes.value?.data || homeRes.value || {}
  return data.recommended_products || data.recommended_books || data.topBooks || []
})

// TUZATILDI: bu yerda ilgari `ogImage: '/images/logo/logo_blue.png'`
// (nisbiy yo'l, ijtimoiy tarmoqlar ko'pincha nisbiy rasm URL'ini
// ochirolmaydi) yozilgan edi — bu `nuxt.config.ts`dagi umumiy, to'g'ri
// sozlangan (1200x630, to'liq URL) `og-image.png`ni Nuxt'ning sahifa-daraja
// ustunligi tufayli BOSIB QO'YARDI. Natijada bosh sahifa ijtimoiy
// tarmoqlarda (Telegram/Facebook/WhatsApp) ulashilganda mo'ljallangan
// banner o'rniga kichik logotip ko'rinardi (yoki umuman ko'rinmasdi, chunki
// nisbiy URL). Endi bosh sahifa umumiy sozlamalarni MEROS QILIB OLADI —
// title/description/ogImage/JSON-LD (WebSite+Organization) allaqachon
// `nuxt.config.ts`da to'g'ri, to'liq holda bor, shu sabab bu yerda ularni
// TAKRORLASH (va yomonroq qiymat bilan ustidan yozish) shart emas. Faqat
// canonical aniq ko'rsatildi (umumiy sozlamada ham bor, lekin sahifa
// darajasida aniq belgilash yaxshi amaliyot).
useHead({
  link: [
    { rel: 'canonical', href: 'https://kitobchi.com/' },
  ],
})
</script>
