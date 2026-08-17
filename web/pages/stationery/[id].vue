<template>
  <div class="py-4 md:py-6 min-h-dvh bg-white grow" v-if="product">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden sticky top-0 z-40 mb-3">
      <div class="py-3 rounded-b-2xl bg-white shadow-sm transition-all duration-300">
        <div class="px-4 flex items-center justify-between gap-2">
          <button
            type="button"
            @click="$router.back()"
            class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </button>
          <div class="text-base font-semibold text-primary truncate max-w-[200px]">
            {{ product.name }}
          </div>
          <button
            type="button"
            @click="favStore.toggleFavorite(product, 'book')"
            class="p-2 rounded-full bg-secondary-100 border-none cursor-pointer"
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
          <NuxtLink to="/catalog" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300 shrink-0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </NuxtLink>
          <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
            <NuxtLink to="/" class="hover:text-neutral-900 transition-colors">Asosiy</NuxtLink>
            <span class="text-gray-300">/</span>
            <NuxtLink to="/catalog" class="hover:text-neutral-900 transition-colors">Katalog</NuxtLink>
            <span class="text-gray-300">/</span>
            <span class="text-neutral-900 font-semibold truncate max-w-xs">{{ product.name }}</span>
          </nav>
        </div>

        <button
          type="button"
          @click="favStore.toggleFavorite(product, 'book')"
          class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-secondary-200 shadow-sm hover:bg-neutral-50 transition-all shrink-0 cursor-pointer"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="isFav ? '#ef4444' : 'none'" :stroke="isFav ? '#ef4444' : 'currentColor'" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
          </svg>
        </button>
      </div>

      <!-- Main Layout: 2 Columns (Piyola 1:1) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 pb-24 md:pb-12">
        <!-- Left: Image Gallery (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          <div class="relative w-full aspect-square md:aspect-[3/4] rounded-3xl bg-secondary-50 overflow-hidden border border-secondary-100 flex items-center justify-center">
            <img
              :src="activeImage"
              :alt="product.name"
              class="w-full h-full object-contain p-4"
              loading="eager"
            />
          </div>

          <!-- Thumbnails -->
          <div v-if="galleryImages.length > 1" class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
            <button
              v-for="(img, idx) in galleryImages"
              :key="idx"
              type="button"
              @click="activeImage = img"
              :class="[
                'w-16 h-16 rounded-2xl overflow-hidden border-2 transition-all p-1 bg-white shrink-0 cursor-pointer',
                activeImage === img ? 'border-primary shadow-sm' : 'border-transparent opacity-60 hover:opacity-100'
              ]"
            >
              <img :src="img" class="w-full h-full object-contain" />
            </button>
          </div>
        </div>

        <!-- Right: Info & Actions (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 leading-snug m-0">
              {{ product.name }}
            </h1>
            <div v-if="product.author" class="text-sm font-medium text-neutral-500 mt-1">
              Muallif: <span class="text-primary font-semibold">{{ product.author }}</span>
            </div>
          </div>

          <!-- Price Box (Piyola 1:1) -->
          <div class="p-5 rounded-3xl bg-secondary-50 border border-secondary-100 space-y-3">
            <div class="flex items-baseline gap-3">
              <span class="text-2xl md:text-3xl font-black text-neutral-900">
                {{ formatPrice(currentPrice) }} so‘m
              </span>
              <span v-if="discountPercent > 0" class="text-sm line-through text-gray-400">
                {{ formatPrice(product.price) }} so‘m
              </span>
              <span v-if="discountPercent > 0" class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#ED3131] text-white">
                -{{ discountPercent }}%
              </span>
            </div>

            <div class="inline-block px-3 py-1 rounded-full bg-primary/10 text-primary text-sm font-semibold">
              Oyiga {{ formatPrice(monthlyPrice) }} so‘mdan muddatli to‘lov
            </div>
          </div>

          <!-- Actions Buttons (Desktop) -->
          <div class="flex items-center gap-4 max-md:hidden">
            <button
              type="button"
              @click="handleAddToCart"
              class="flex-1 py-4 rounded-2xl bg-secondary-200 hover:bg-secondary-300 text-primary font-bold text-base transition-colors border-none cursor-pointer flex items-center justify-center gap-2"
            >
              <i class="icon-order text-xl"></i>
              <span>Savatga qo‘shish</span>
            </button>
            <button
              type="button"
              @click="handleBuyNow"
              class="flex-1 py-4 rounded-2xl bg-primary hover:bg-primary/90 text-white font-bold text-base transition-colors border-none cursor-pointer flex items-center justify-center gap-2 shadow-lg shadow-primary/20"
            >
              <span>Bir bosishda xarid</span>
            </button>
          </div>

          <!-- Specs Table (Piyola 1:1) -->
          <div class="space-y-3 pt-4 border-t border-gray-100">
            <h3 class="text-lg font-bold text-neutral-900 m-0">Xususiyatlar</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div v-if="product.publisher" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">Nashriyot:</span>
                <span class="font-semibold text-neutral-800">{{ product.publisher.name || product.publisher }}</span>
              </div>
              <div v-if="product.pages" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">Sahifalar soni:</span>
                <span class="font-semibold text-neutral-800">{{ product.pages }} bet</span>
              </div>
              <div v-if="product.coverType" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">Muqova:</span>
                <span class="font-semibold text-neutral-800">{{ product.coverType }}</span>
              </div>
              <div v-if="product.lang" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">Til:</span>
                <span class="font-semibold text-neutral-800">{{ product.lang }}</span>
              </div>
              <div v-if="product.year" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">Yil:</span>
                <span class="font-semibold text-neutral-800">{{ product.year }}</span>
              </div>
              <div v-if="product.isbn" class="p-3 rounded-2xl bg-secondary-50">
                <span class="text-gray-400 block text-xs">ISBN:</span>
                <span class="font-semibold text-neutral-800">{{ product.isbn }}</span>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="product.description" class="space-y-2 pt-4 border-t border-gray-100">
            <h3 class="text-lg font-bold text-neutral-900 m-0">Tavsif</h3>
            <div class="text-sm leading-relaxed text-neutral-600 prose prose-sm max-w-none" v-html="product.description"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== MOBILE STICKY BUY BAR (Piyola 1:1) ====== -->
    <div class="fixed bottom-0 left-0 right-0 p-3 bg-white/95 backdrop-blur-md border-t border-gray-100 z-50 md:hidden flex items-center gap-3">
      <div class="flex-1">
        <div class="text-xs text-gray-400">Narxi:</div>
        <div class="text-base font-bold text-neutral-900 leading-tight">
          {{ formatPrice(currentPrice) }} so‘m
        </div>
      </div>
      <button
        type="button"
        @click="handleAddToCart"
        class="px-4 py-3 rounded-2xl bg-secondary-200 text-primary font-bold text-sm border-none cursor-pointer flex items-center justify-center gap-1.5"
      >
        <i class="icon-order text-lg"></i>
      </button>
      <button
        type="button"
        @click="handleBuyNow"
        class="flex-1 py-3 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer flex items-center justify-center gap-1.5 shadow-md"
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

const { data: productData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/products/book`, {
  query: computed(() => ({ id: rawId.value })),
  lazy: false
})

const product = computed(() => {
  return productData.value?.product || productData.value?.data || productData.value || null
})

const isFav = computed(() => product.value ? favStore.isFavorited(product.value.id, 'book') : false)

const galleryImages = computed(() => {
  if (!product.value) return []
  if (Array.isArray(product.value.images) && product.value.images.length > 0) {
    return product.value.images.map((img: string) => img.startsWith('http') ? img : `/storage/${img}`)
  }
  if (product.value.first_image) {
    return [product.value.first_image.startsWith('http') ? product.value.first_image : `/storage/${product.value.first_image}`]
  }
  return ['/images/logo/logo_blue.png']
})

const activeImage = ref('')

watchEffect(() => {
  if (galleryImages.value.length > 0 && !activeImage.value) {
    activeImage.value = galleryImages.value[0]
  }
})

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

const monthlyPrice = computed(() => {
  return Math.ceil(Number(currentPrice.value || 0) * 1.44 / 12)
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
  ogType: 'product'
})

useHead({
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify({
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
