<template>
  <div class="product-card group relative flex flex-col rounded-2xl bg-white overflow-hidden transition-all duration-200">
    <!-- Image Slider -->
    <div class="relative w-full rounded-xl bg-neutral-50 overflow-hidden" style="aspect-ratio: 3 / 4;">
      <div
        ref="trackEl"
        class="flex w-full h-full overflow-x-auto no-scrollbar snap-x snap-mandatory rounded-xl"
        @scroll="onTrackScroll"
      >
        <NuxtLink
          v-for="(img, idx) in images"
          :key="idx"
          :to="productUrl"
          class="block w-full h-full shrink-0 snap-center"
        >
          <div class="w-full h-full rounded-xl overflow-hidden bg-neutral-50 flex items-center justify-center">
            <img
              :src="img"
              :alt="product.name"
              class="w-full h-full object-cover"
              :loading="eager && idx === 0 ? 'eager' : 'lazy'"
              :fetchpriority="eager && idx === 0 ? 'high' : 'auto'"
            />
          </div>
        </NuxtLink>
      </div>

      <!-- Slide dots -->
      <div
        v-if="images.length > 1"
        class="absolute bottom-1.5 inset-x-0 z-20 flex items-center justify-center gap-1 pointer-events-none"
        aria-label="Rasmni tanlash"
      >
        <button
          v-for="(img, idx) in images"
          :key="idx"
          type="button"
          :aria-label="`${idx + 1}-rasm`"
          class="pointer-events-auto rounded-full transition-all duration-200 border-none cursor-pointer p-0"
          :class="idx === activeIndex ? 'w-2.5 h-1 bg-white' : 'w-1 h-1 bg-white/60'"
          @click.prevent="goToSlide(idx)"
        ></button>
      </div>

      <!-- Discount Pill -->
      <div v-if="discountPercent > 0" class="absolute bottom-1.5 left-1.5 md:bottom-2 md:left-2 z-20 inline-flex items-start flex-col gap-1 pointer-events-none">
        <span class="font-semibold inline-flex items-center text-[11px] rounded-md px-1.5 py-0.5 bg-[#ED3131] text-white">
          -{{ discountPercent }}%
        </span>
      </div>

      <!-- Clean Favorite Heart Button -->
      <div class="absolute top-1.5 right-1.5 md:top-2 md:right-2 z-20">
        <button
          type="button"
          aria-label="Sevimlilar"
          @click.prevent="favStore.toggleFavorite(product, type)"
          class="w-8 h-8 rounded-full bg-white/90 backdrop-blur-xs flex items-center justify-center text-neutral-400 hover:text-neutral-700 transition-colors border border-black/5 shadow-xs cursor-pointer"
        >
          <svg
            class="w-4 h-4 transition-colors"
            viewBox="0 0 24 24"
            :fill="isFav ? '#ef4444' : 'none'"
            :stroke="isFav ? '#ef4444' : 'currentColor'"
            stroke-width="1.8"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Product Info -->
    <div class="product-card__info flex flex-col h-full grow pt-2 pb-1">
      <NuxtLink :to="productUrl" class="group grow no-underline">
        <div class="text-sm leading-snug line-clamp-2 text-neutral-800 hover:text-neutral-950 font-normal transition-colors">
          {{ product.name }}
        </div>
      </NuxtLink>

      <div class="flex items-start justify-between gap-2 mt-1.5">
        <p class="text-sm md:text-base text-neutral-900 font-bold leading-tight m-0">
          {{ formatPrice(currentPrice) }} so‘m
        </p>
      </div>

      <div class="mt-1.5">
        <span class="inline-block px-2 py-0.5 text-[11px] font-medium bg-neutral-100 text-neutral-600 rounded-md">
          {{ formatPrice(monthlyPrice) }} so‘m/oyiga
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useFavoritesStore } from '~/stores/favorites'

const props = withDefaults(
  defineProps<{
    product: any
    type?: 'book' | 'stationery'
    // Ekranning yuqori qismida (LCP'ga ta'sir qiluvchi) chiqadigan
    // kartochkalar uchun sahifadan `true` beriladi — pastdagi <img>'ga
    // qarang.
    eager?: boolean
  }>(),
  {
    type: 'book',
    eager: false
  }
)

const favStore = useFavoritesStore()

const isFav = computed(() => favStore.isFavorited(props.product.id, props.type))

const productUrl = computed(() => {
  const slug = (props.product.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return props.type === 'stationery'
    ? `/stationery/${props.product.id}-${slug}`
    : `/books/${props.product.id}-${slug}`
})

// Mahsulotning barcha rasmlari — piyoladagi kabi karusel uchun. Bir nechta
// maydon nomi tekshiriladi (backend turli endpointlarda turlicha nomlagan),
// birinchi mavjud bo'lgan RO'YXAT ishlatiladi (faqat bitta emas — hammasi).
// Logika savat/sevimlilar bilan bir xil bo'lishi uchun utils/productImage.ts
// ga chiqarilgan (bitta joyda tuzatish — hammasida tuzaladi).
const images = computed(() => resolveProductImages(props.product))

const trackEl = ref<HTMLElement | null>(null)
const activeIndex = ref(0)
let scrollRaf = 0

function onTrackScroll() {
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  scrollRaf = requestAnimationFrame(() => {
    const el = trackEl.value
    if (!el || el.clientWidth === 0) return
    activeIndex.value = Math.round(el.scrollLeft / el.clientWidth)
  })
}

function goToSlide(idx: number) {
  const el = trackEl.value
  if (!el) return
  el.scrollTo({ left: idx * el.clientWidth, behavior: 'smooth' })
  activeIndex.value = idx
}

// Backend (ProductPayloadFormatter) har doim `discountPrice` (camelCase)
// maydonini qaytaradi — kitob ham, stationery ham. `discount_price` faqat
// xavfsizlik uchun fallback sifatida tekshiriladi.
const currentPrice = computed(() => {
  const discountPrice = props.product.discountPrice ?? props.product.discount_price ?? 0
  const isDisc = discountPrice > 0 && discountPrice < props.product.price
  return isDisc ? discountPrice : props.product.price
})

const discountPercent = computed(() => {
  const base = Number(props.product.price || 0)
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
</script>
