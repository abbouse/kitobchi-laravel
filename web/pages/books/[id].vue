<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow" v-if="product">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden sticky top-0 z-40 mb-3">
      <div class="py-3 rounded-b-2xl bg-white shadow-sm transition-all duration-300">
        <div class="px-4 flex items-center justify-between gap-2">
          <button
            type="button"
            @click="$router.back()"
            class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer shrink-0"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </button>
          <div class="flex-1 min-w-0 text-base font-semibold text-primary truncate text-center">
            {{ product.name }}
          </div>
          <button
            type="button"
            @click="favStore.toggleFavorite(product, 'book')"
            class="p-2 rounded-full bg-secondary-100 border-none cursor-pointer shrink-0"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="isFav ? '#ef4444' : 'none'" :stroke="isFav ? '#ef4444' : 'currentColor'" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center justify-between gap-4 mb-6 max-md:hidden">
        <div class="flex items-center gap-2 min-w-0">
          <NuxtLink to="/catalog" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </NuxtLink>
          <nav class="flex items-center gap-2 text-sm text-[#8F8FA1] min-w-0">
            <NuxtLink to="/" class="hover:text-default transition-colors shrink-0">Asosiy</NuxtLink>
            <span class="text-gray-300 shrink-0">/</span>
            <NuxtLink to="/catalog" class="hover:text-default transition-colors shrink-0">Katalog</NuxtLink>
            <span class="text-gray-300 shrink-0">/</span>
            <span class="text-neutral-900 font-semibold truncate min-w-0">{{ product.name }}</span>
          </nav>
        </div>

        <button
          type="button"
          @click="favStore.toggleFavorite(product, 'book')"
          class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-100 shadow-sm hover:bg-neutral-50 transition-all shrink-0 cursor-pointer"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="isFav ? '#ef4444' : 'none'" :stroke="isFav ? '#ef4444' : 'currentColor'" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
          </svg>
        </button>
      </div>

      <!-- Main Layout: 2 Columns (Piyola 1:1 — lg:grid lg:grid-cols-2 xl:grid-cols-3) -->
      <div class="lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-5 pb-10 lg:pb-0">
        <!-- Left: Image Gallery — thumbnail rail + main carousel w/ arrows -->
        <div class="col-span-1 xl:col-span-2 h-full mb-8">
          <div class="flex flex-col-reverse md:flex-row gap-4 h-full">
            <!-- Thumbnail rail -->
            <div
              v-if="galleryImages.length > 1"
              class="flex md:flex-col gap-3 overflow-x-auto md:overflow-y-auto md:overflow-x-hidden w-full md:w-auto md:h-0 md:min-h-full scrollbar-hide py-1 shrink-0"
            >
              <button
                v-for="(img, idx) in galleryImages"
                :key="idx"
                type="button"
                @click="activeIndex = idx"
                :aria-label="`gallery-image-selector-${idx}`"
                :class="[
                  'relative shrink-0 w-[75px] h-[100px] rounded-2xl overflow-hidden border-2 transition-all duration-300 cursor-pointer bg-[#FAFAFA] p-1 flex items-center justify-center',
                  activeIndex === idx ? 'border-primary ring-2 ring-primary/20 shadow-sm' : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <img :src="img" class="w-full h-full object-contain rounded-xl" :alt="`${product.name} ${idx + 1}`" />
              </button>
            </div>

            <!-- Main image + prev/next arrows -->
            <div class="flex-1 relative rounded-3xl min-h-0 min-w-0">
              <div class="relative w-full aspect-[3/4] sm:aspect-square lg:aspect-[4/5] max-h-[560px] rounded-3xl overflow-hidden bg-[#FAFAFA] border border-gray-100 flex items-center justify-center p-4 md:p-8">
                <!-- Top Left Discount Badge -->
                <div v-if="discountPercent > 0" class="absolute top-4 left-4 z-10 px-3 py-1 rounded-full text-xs font-bold bg-[#ED3131] text-white shadow-sm">
                  -{{ discountPercent }}%
                </div>

                <!-- Main Product Image -->
                <img
                  :src="activeImage"
                  :alt="product.name"
                  class="max-w-full max-h-full object-contain rounded-2xl drop-shadow-md select-none transition-all duration-300"
                  loading="eager"
                />

                <template v-if="galleryImages.length > 1">
                  <button
                    type="button"
                    @click="prevImage"
                    aria-label="Prev"
                    class="font-medium inline-flex items-center text-sm shadow-md bg-white/90 hover:bg-white text-primary backdrop-blur p-2 absolute rounded-full start-4 top-1/2 -translate-y-1/2 cursor-pointer border border-gray-100 hover:scale-105 active:scale-95 transition-all"
                  >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                  </button>
                  <button
                    type="button"
                    @click="nextImage"
                    aria-label="Next"
                    class="font-medium inline-flex items-center text-sm shadow-md bg-white/90 hover:bg-white text-primary backdrop-blur p-2 absolute rounded-full end-4 top-1/2 -translate-y-1/2 cursor-pointer border border-gray-100 hover:scale-105 active:scale-95 transition-all"
                  >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Info & Actions (7 cols) -->
        <div class="col-span-1 space-y-6">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 leading-snug m-0">
              {{ product.name }}
            </h1>
            <div v-if="product.author" class="text-sm font-medium text-neutral-500 mt-1">
              Muallif: <span class="text-primary font-semibold">{{ product.author }}</span>
            </div>
          </div>

          <!-- Price -->
          <div class="flex flex-col mt-2">
            <p class="text-sm text-gray-400 font-normal">Narxi</p>
            <div class="flex items-center gap-1">
              <div class="flex items-end gap-3">
                <span class="text-xl font-bold text-neutral-900">{{ formatPrice(currentPrice) }} so‘m</span>
                <span v-if="discountPercent > 0" class="text-sm line-through text-gray-400">{{ formatPrice(product.price) }} so‘m</span>
                <span v-if="discountPercent > 0" class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#ED3131] text-white">-{{ discountPercent }}%</span>
              </div>
            </div>
          </div>

          <!-- Actions Buttons (Desktop) -->
          <div class="flex items-center gap-4 max-md:hidden">
            <button
              type="button"
              @click="handleAddToCart"
              class="flex-1 py-4 rounded-2xl bg-secondary-200 hover:bg-secondary-400 text-primary font-bold text-base transition-colors border-none cursor-pointer flex items-center justify-center gap-2"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.354 2.55-6.75H5.106M7.5 14.25 5.106 5.272M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
              <span>Savatga qo‘shish</span>
            </button>
            <button
              type="button"
              @click="handleBuyNow"
              class="ios-order-btn flex-1 h-12 rounded-2xl text-base px-6 border-none cursor-pointer"
            >
              Bir bosishda xarid
            </button>
          </div>

          <!-- Xususiyatlar va tavsif (accordion) -->
          <div v-if="hasSpecs || product.description">
            <button
              type="button"
              @click="specsOpen = !specsOpen"
              class="w-full bg-secondary-300 cursor-pointer rounded-2xl p-4 md:px-6 flex items-center justify-between transition-colors duration-300 hover:bg-secondary-400 border-none"
            >
              <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                <span class="font-medium text-primary">Xususiyatlar va tavsif</span>
              </div>
              <svg
                class="w-5 h-5 text-primary shrink-0 transition-transform duration-300"
                :class="specsOpen ? 'rotate-90' : ''"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              ><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>

            <div v-show="specsOpen" class="mt-3 p-6 rounded-3xl bg-secondary-100 space-y-5">
              <div v-if="hasSpecs" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div v-if="product.publisher" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">Nashriyot:</span>
                  <span class="font-semibold text-neutral-800">{{ product.publisher.name || product.publisher }}</span>
                </div>
                <div v-if="product.pages" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">Sahifalar soni:</span>
                  <span class="font-semibold text-neutral-800">{{ product.pages }} bet</span>
                </div>
                <div v-if="product.coverType" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">Muqova:</span>
                  <span class="font-semibold text-neutral-800">{{ product.coverType }}</span>
                </div>
                <div v-if="product.lang" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">Til:</span>
                  <span class="font-semibold text-neutral-800">{{ product.lang }}</span>
                </div>
                <div v-if="product.year" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">Yil:</span>
                  <span class="font-semibold text-neutral-800">{{ product.year }}</span>
                </div>
                <div v-if="product.isbn" class="p-3 rounded-2xl bg-white">
                  <span class="text-gray-400 block text-xs">ISBN:</span>
                  <span class="font-semibold text-neutral-800">{{ product.isbn }}</span>
                </div>
              </div>

              <div v-if="product.description" class="kb-prose" v-html="product.description"></div>
            </div>
          </div>

          <!-- To'lov usuli (visual-only demo — muddatli / naqd) -->
          <div class="p-6 rounded-3xl bg-secondary-100 space-y-5">
            <div role="tablist" class="relative inline-flex bg-secondary-300 rounded-xl p-1 flex!">
              <button
                type="button" role="tab" :aria-selected="paymentTab === 'installment'"
                @click="paymentTab = 'installment'"
                :class="[
                  'text-sm px-4 py-2 flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap border-none cursor-pointer',
                  paymentTab === 'installment' ? 'bg-white text-gray-900' : 'bg-transparent text-gray-400 hover:text-gray-500'
                ]"
              >
                Muddatli to‘lov
              </button>
              <button
                type="button" role="tab" :aria-selected="paymentTab === 'cash'"
                @click="paymentTab = 'cash'"
                :class="[
                  'text-sm px-4 py-2 flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap border-none cursor-pointer',
                  paymentTab === 'cash' ? 'bg-white text-gray-900' : 'bg-transparent text-gray-400 hover:text-gray-500'
                ]"
              >
                Naqd to‘lov
              </button>
            </div>

            <!-- Installment view -->
            <div v-if="paymentTab === 'installment'" class="flex max-md:flex-col md:justify-between gap-4 w-full">
              <div>
                <p class="text-sm text-gray-400 font-normal">Muddatni tanlang</p>
                <div class="inline-flex mt-1 md:mt-2 gap-2 flex-wrap">
                  <button
                    v-for="m in installmentMonths" :key="m" type="button"
                    @click="selectedMonths = m"
                    :class="[
                      'px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors cursor-pointer',
                      selectedMonths === m ? 'bg-primary text-white border-primary' : 'bg-white text-neutral-700 border-gray-200 hover:border-primary/30'
                    ]"
                  >
                    {{ m }} oy
                  </button>
                </div>
              </div>
              <div class="flex flex-col md:items-end">
                <p class="text-sm text-gray-400 font-normal">Oylik to‘lov</p>
                <div class="flex items-end gap-3 mt-1 md:mt-4">
                  <span class="text-xl font-bold text-neutral-900">{{ formatPrice(monthlyForSelected) }}</span>
                  <span class="text-sm text-gray-400">so‘m/oyiga</span>
                </div>
              </div>
            </div>

            <!-- Cash view -->
            <div v-else class="flex flex-col">
              <p class="text-sm text-gray-400 font-normal">To‘liq narxi</p>
              <div class="flex items-end gap-3 mt-1">
                <span class="text-xl font-bold text-neutral-900">{{ formatPrice(currentPrice) }} so‘m</span>
              </div>
            </div>

            <!-- Buttons (mobile/tablet variant lives here; desktop already has its own row above) -->
            <div class="flex items-center gap-3 max-md:hidden">
              <div class="flex-1">
                <button type="button" @click="handleBuyNow" class="ios-order-btn w-full h-12 rounded-2xl text-base px-6 border-none cursor-pointer">
                  Buyurtma berish
                </button>
              </div>
              <button
                type="button" @click="handleAddToCart"
                class="text-primary bg-primary/10 hover:bg-primary/15 transition-colors h-12 px-3 rounded-2xl border-none cursor-pointer flex items-center justify-center"
                aria-label="Savatga qo'shish"
              >
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.354 2.55-6.75H5.106M7.5 14.25 5.106 5.272M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Book Club Reviews Section -->
      <ProductReviews :product-id="product.id" type="book" />
    </div>

    <!-- ====== MOBILE STICKY BUY BAR (Piyola 1:1) ====== -->
    <div class="fixed bottom-0 left-0 right-0 p-3 bg-white/90 backdrop-blur-md border-t border-gray-100 z-50 md:hidden flex items-center gap-3">
      <div class="flex-1 min-w-0">
        <div class="text-xs text-gray-400">{{ paymentTab === 'installment' ? `${selectedMonths} oyga:` : 'Narxi:' }}</div>
        <div class="text-base font-bold text-neutral-900 leading-tight truncate">
          {{ paymentTab === 'installment' ? `${formatPrice(monthlyForSelected)} so‘m/oy` : `${formatPrice(currentPrice)} so‘m` }}
        </div>
      </div>
      <button
        type="button"
        @click="handleAddToCart"
        class="px-4 py-3 rounded-2xl bg-secondary-200 text-primary font-bold text-sm border-none cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
        aria-label="Savatga qo'shish"
      >
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.354 2.55-6.75H5.106M7.5 14.25 5.106 5.272M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
      </button>
      <button
        type="button"
        @click="handleBuyNow"
        class="ios-order-btn flex-1 py-3 rounded-2xl text-sm border-none cursor-pointer"
      >
        Sotib olish
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useFavoritesStore } from '~/stores/favorites'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const cartStore = useCartStore()
const favStore = useFavoritesStore()

// Extract numeric ID from param like "123-slug-nomi"
const rawId = computed(() => {
  const param = String(route.params.id || '')
  return param.split('-')[0]
})

// Yagona mahsulot ma'lumoti — real backend: ShareController::product
// (GET v1/kitobchi/share/product/{id}?type=book), ProductPayloadFormatter
// orqali 'detail' rejimida formatlanadi (description/isbn/pages/publisher/
// lang/coverType/year maydonlari shu rejimda qo'shiladi).
const { data: productData } = await useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/share/product/${rawId.value}`,
  {
    query: { type: 'book' },
    lazy: false
  }
)

const product = computed(() => {
  return productData.value?.data || productData.value?.product || null
})

const isFav = computed(() => product.value ? favStore.isFavorited(product.value.id, 'book') : false)

const hasSpecs = computed(() => {
  const p = product.value
  if (!p) return false
  return !!(p.publisher || p.pages || p.coverType || p.lang || p.year || p.isbn)
})

function resolveImg(src: string) {
  if (!src) return ''
  return src.startsWith('http') || src.startsWith('data:') ? src : `/storage/${src}`
}

// Backend bir nechta o'lchamda rasm qaytaradi (medium_images/thumb_images/
// image_urls) — ProductCard.vue'dagi bilan bir xil ustuvorlik tartibi.
const galleryImages = computed(() => {
  const p = product.value
  if (!p) return []
  const lists = [p.medium_images, p.image_urls, p.thumb_images, p.images]
  for (const list of lists) {
    if (Array.isArray(list) && list.length > 0) {
      const resolved = list.map(resolveImg).filter(Boolean)
      if (resolved.length > 0) return resolved
    }
  }
  if (p.first_image) {
    return [resolveImg(p.first_image)]
  }
  return ['/images/logo/logo_blue.png']
})

const activeIndex = ref(0)

watchEffect(() => {
  if (activeIndex.value >= galleryImages.value.length) {
    activeIndex.value = 0
  }
})

const activeImage = computed(() => galleryImages.value[activeIndex.value] || galleryImages.value[0] || '/images/logo/logo_blue.png')

function prevImage() {
  const len = galleryImages.value.length
  if (len < 2) return
  activeIndex.value = (activeIndex.value - 1 + len) % len
}

function nextImage() {
  const len = galleryImages.value.length
  if (len < 2) return
  activeIndex.value = (activeIndex.value + 1) % len
}

// Accordion: Xususiyatlar va tavsif
const specsOpen = ref(false)

// To'lov usuli (visual-only demo, no real installment/payment integration)
const paymentTab = ref<'installment' | 'cash'>('installment')
const installmentMonths = [3, 6, 12]
const selectedMonths = ref(12)

const currentPrice = computed(() => {
  if (!product.value) return 0
  const isDisc = product.value.discountPrice > 0 && product.value.discountPrice < product.value.price
  return isDisc ? product.value.discountPrice : product.value.price
})

const discountPercent = computed(() => {
  if (!product.value) return 0
  const base = Number(product.value.price || 0)
  const curr = Number(currentPrice.value || 0)
  if (base > 0 && curr < base) {
    return Math.round(((base - curr) / base) * 100)
  }
  return 0
})

const monthlyForSelected = computed(() => {
  return Math.ceil(Number(currentPrice.value || 0) * 1.44 / selectedMonths.value)
})

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function handleAddToCart() {
  if (product.value) {
    cartStore.addItem(product.value, 'book', 1)
  }
}

function handleBuyNow() {
  if (product.value) {
    cartStore.addItem(product.value, 'book', 1)
    router.push('/cart')
  }
}

// SEO & Schema.org JSON-LD Rich Snippet for Google
useSeoMeta({
  title: () => `${product.value?.name || 'Kitob'} — Kitobchi`,
  description: () => `${product.value?.name || 'Kitob'} muallif: ${product.value?.author || ''}. Tezkor yetkazib berish va arzon narxlar Kitobchi marketpleysida.`,
  ogTitle: () => `${product.value?.name || 'Kitob'} | Kitobchi`,
  ogImage: () => activeImage.value || '/images/logo/logo_blue.png',
  ogType: 'product' as any
})

useHead({
  script: [
    {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org/',
        '@type': 'Product',
        name: product.value?.name,
        image: activeImage.value,
        description: product.value?.description ? product.value.description.replace(/<[^>]*>?/gm, '') : product.value?.name,
        offers: {
          '@type': 'Offer',
          url: `https://kitobchi.com/books/${route.params.id}`,
          priceCurrency: 'UZS',
          price: currentPrice.value,
          availability: 'https://schema.org/InStock'
        }
      })
    }
  ]
})
</script>
