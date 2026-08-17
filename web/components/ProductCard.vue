<template>
  <div class="group relative flex flex-col rounded-xl bg-white border border-white hover:shadow-md transition-all duration-200 overflow-hidden">
    <!-- Image with Aspect Ratio 232/309 -->
    <div class="relative w-full rounded-xl bg-white" style="aspect-ratio: 232 / 309;">
      <NuxtLink :to="productUrl" class="block w-full h-full">
        <div class="w-full h-full rounded-xl overflow-hidden bg-gray-50 flex items-center justify-center">
          <img
            :src="imageSrc"
            :alt="product.name"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
            loading="lazy"
          />
        </div>
      </NuxtLink>

      <!-- Discount Pill -->
      <div v-if="discountPercent > 0" class="absolute bottom-1.5 left-1.5 md:bottom-2 md:left-2 z-20 inline-flex items-start flex-col gap-1 pointer-events-none">
        <span class="font-medium inline-flex items-center text-xs gap-1 rounded-md px-1 py-0.5 bg-[#ED3131] text-white">
          -{{ discountPercent }}%
        </span>
      </div>

      <!-- Glass Favorite Heart Button -->
      <div class="absolute top-1.5 right-1.5 md:top-2 md:right-2 z-20">
        <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl p-0 hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm glass-card-bg">
          <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
          <button
            type="button"
            aria-label="Sevimlilar"
            @click.prevent="favStore.toggleFavorite(product, type)"
            class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 text-gray-500 hover:text-gray-900 border-none bg-transparent cursor-pointer"
          >
            <svg
              class="w-5 h-5 relative z-10 transition-colors"
              viewBox="0 0 24 24"
              :fill="isFav ? '#ef4444' : 'none'"
              :stroke="isFav ? '#ef4444' : 'currentColor'"
              stroke-width="1.6"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Product Info -->
    <div class="px-1 pt-4 pb-4 flex flex-col h-full grow">
      <NuxtLink :to="productUrl" class="group/title grow no-underline">
        <div class="text-sm leading-snug line-clamp-2 transition-colors duration-300 group-hover:text-primary-600 text-neutral-900">
          {{ product.name }}
        </div>
      </NuxtLink>

      <div class="flex items-start justify-between gap-2 mt-2">
        <p class="text-sm md:text-base text-gray-600 font-semibold leading-tight m-0">
          {{ formatPrice(currentPrice) }} so‘m
        </p>
      </div>

      <div class="mt-auto">
        <span class="inline-block px-2 py-0.5 text-xs md:text-sm font-medium bg-primary-100 text-primary rounded-full my-1">
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
  }>(),
  {
    type: 'book'
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

const imageSrc = computed(() => {
  if (props.product.medium_images && props.product.medium_images[0]) {
    return props.product.medium_images[0]
  }
  if (props.product.thumb_images && props.product.thumb_images[0]) {
    return props.product.thumb_images[0]
  }
  if (props.product.image_urls && props.product.image_urls[0]) {
    return props.product.image_urls[0]
  }
  if (props.product.first_image) {
    return props.product.first_image.startsWith('http')
      ? props.product.first_image
      : `/storage/${props.product.first_image}`
  }
  if (Array.isArray(props.product.images) && props.product.images[0]) {
    const img = props.product.images[0]
    return img.startsWith('http') ? img : `/storage/${img}`
  }
  return '/images/logo/logo_blue.png'
})

const currentPrice = computed(() => {
  const isDisc = props.type === 'book'
    ? (props.product.discountPrice > 0 && props.product.discountPrice < props.product.price)
    : (props.product.discount_price > 0 && props.product.discount_price < props.product.price)
  return isDisc
    ? (props.type === 'book' ? props.product.discountPrice : props.product.discount_price)
    : props.product.price
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
