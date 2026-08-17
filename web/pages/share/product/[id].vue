<template>
  <div class="py-12 min-h-dvh bg-secondary-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full text-center shadow-2xl space-y-4">
      <div class="w-32 h-44 rounded-2xl bg-secondary-100 mx-auto overflow-hidden shadow-md flex items-center justify-center">
        <img
          v-if="product"
          :src="previewImage"
          :alt="product.name"
          class="w-full h-full object-cover"
        />
      </div>

      <h1 class="text-xl font-bold text-neutral-900 m-0">
        {{ product?.name || 'Mahsulot' }}
      </h1>

      <div class="flex flex-col gap-2 pt-2">
        <a
          :href="`kitobchi://share/product/${$route.params.id}`"
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors block"
        >
          Kitobchi ilovasida ochish
        </a>
        <NuxtLink
          :to="viewOnSiteUrl"
          class="w-full py-3 rounded-2xl bg-secondary-100 text-primary font-semibold text-sm hover:bg-secondary-400 transition-colors block"
        >
          Saytda ko‘rish
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

function resolveImg(src: string) {
  if (!src) return ''
  return src.startsWith('http') || src.startsWith('data:') ? src : `/storage/${src}`
}

// Ulashilgan havolada mahsulot turi (book/stationery) yo'q — real backend
// (routes/web.php'dagi /share/product/{id} Blade route) qanday hal qilsa,
// shu tartibda: avval kitob sifatida qidiramiz, topilmasa — kanselyariya.
const { data: result } = await useAsyncData(`share-product-${route.params.id}`, async () => {
  const id = route.params.id
  for (const type of ['book', 'stationery'] as const) {
    try {
      const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/share/product/${id}`, {
        query: { type }
      })
      if (res?.status === 'success' && res?.data) {
        return { product: res.data, type }
      }
    } catch {
      // topilmadi — keyingi turni sinaymiz
    }
  }
  return { product: null, type: null as null | 'book' | 'stationery' }
})

const product = computed(() => result.value?.product || null)
const productType = computed(() => result.value?.type || 'book')

const previewImage = computed(() => {
  const p = product.value
  if (!p) return '/images/logo/logo_blue.png'
  const lists = [p.medium_images, p.image_urls, p.thumb_images, p.images]
  for (const list of lists) {
    if (Array.isArray(list) && list.length > 0) {
      const resolved = resolveImg(list[0])
      if (resolved) return resolved
    }
  }
  return '/images/logo/logo_blue.png'
})

const viewOnSiteUrl = computed(() => {
  const id = route.params.id
  return productType.value === 'stationery' ? `/stationery/${id}` : `/books/${id}`
})

onMounted(() => {
  // Try deep-link redirect
  const appUrl = `kitobchi://share/product/${route.params.id}`
  window.location.href = appUrl
})

useSeoMeta({
  title: () => `${product.value?.name || 'Mahsulot'} — Kitobchi ilovasida ochish`,
  description: () => (product.value?.description ? product.value.description.replace(/<[^>]*>?/gm, '') : 'Kitobchi ilovasida ushbu mahsulotni ko‘ring'),
  ogImage: () => previewImage.value
})
</script>
