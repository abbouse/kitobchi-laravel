<template>
  <div class="min-h-dvh bg-[#f0f2f5] grow">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-4 md:py-8">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm text-[#8F8FA1] pb-4 flex-wrap">
        <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
        <span class="text-gray-300">/</span>
        <NuxtLink to="/kolleksiya" class="hover:text-neutral-600 transition-colors">Kolleksiyalar</NuxtLink>
        <template v-if="collection">
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">{{ collection.title }}</span>
        </template>
      </nav>

      <template v-if="collection">
        <h1 class="text-2xl sm:text-3xl text-primary font-bold m-0 mb-3">{{ collection.title }}</h1>

        <!-- Muharrirlik matni — mavzu bo'yicha unikal kontent (Google uchun
             ham, foydalanuvchi uchun ham: bir xil kitobni sotuvchi boshqa
             do'konlarda BUNDAY matn yo'q). -->
        <p v-if="collection.intro" class="text-neutral-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-6 whitespace-pre-line">
          {{ collection.intro }}
        </p>

        <p class="text-sm text-neutral-400 font-medium mb-4">{{ products.length }} ta mahsulot</p>

        <div v-if="products.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-10">
          <ProductCard
            v-for="(product, idx) in products"
            :key="`${product.product_type}-${product.id}`"
            :product="product"
            :type="product.product_type"
            :eager="idx < 2"
          />
        </div>
        <div v-else class="text-center py-16 text-neutral-500 text-sm">
          Bu kolleksiyada hozircha mahsulot yo'q.
        </div>
      </template>

      <!-- Topilmadi holati -->
      <div v-else-if="!pending" class="text-center py-24">
        <h1 class="text-xl font-bold text-neutral-800 mb-2">Kolleksiya topilmadi</h1>
        <p class="text-sm text-neutral-500 mb-6">Bu havola eskirgan yoki kolleksiya o'chirilgan bo'lishi mumkin.</p>
        <NuxtLink to="/kolleksiya" class="text-primary font-semibold hover:underline">Barcha kolleksiyalarni ko'rish &rarr;</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const siteUrl = (config.public?.siteUrl as string) || 'https://kitobchi.com'
const slug = String(route.params.slug || '')

const { data: response, pending } = await useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/collections/${slug}`,
  { lazy: false }
)

const collection = computed(() => response.value?.data || null)
const products = computed(() => collection.value?.products || [])

const canonicalUrl = computed(() => `${siteUrl}/kolleksiya/${slug}`)

// Topilmagan kolleksiya uchun noindex — bo'sh/xato sahifa Google'ga
// indekslanishi kerak emas (aks holda "soft 404" signal beradi).
useHead({
  meta: [
    { name: 'robots', content: () => (collection.value ? 'index, follow' : 'noindex, nofollow') },
  ],
})

useSeoMeta({
  title: () => collection.value ? `${collection.value.title} — Kitobchi` : 'Kolleksiya topilmadi — Kitobchi',
  description: () => collection.value?.description || collection.value?.intro?.slice(0, 155) || 'Kitobchi marketpleysidagi mavzuiy kitob va kanselyariya to‘plamlari.',
  ogTitle: () => collection.value ? `${collection.value.title} — Kitobchi` : 'Kitobchi',
  ogDescription: () => collection.value?.description || '',
  ogUrl: () => canonicalUrl.value,
  ogType: 'website',
})

useHead({
  link: [
    { rel: 'canonical', href: () => canonicalUrl.value },
  ],
  script: [
    {
      type: 'application/ld+json',
      children: () => {
        if (!collection.value) return ''
        return JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'CollectionPage',
          name: collection.value.title,
          description: collection.value.description || undefined,
          url: canonicalUrl.value,
          mainEntity: {
            '@type': 'ItemList',
            numberOfItems: products.value.length,
            itemListElement: products.value.slice(0, 40).map((p: any, idx: number) => ({
              '@type': 'ListItem',
              position: idx + 1,
              url: `${siteUrl}/${p.product_type}/${p.id}-${String(p.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')}`,
              name: p.name,
            })),
          },
        })
      },
    },
    {
      type: 'application/ld+json',
      children: () => JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
          { '@type': 'ListItem', position: 1, name: 'Bosh sahifa', item: siteUrl },
          { '@type': 'ListItem', position: 2, name: 'Kolleksiyalar', item: `${siteUrl}/kolleksiya` },
          { '@type': 'ListItem', position: 3, name: collection.value?.title || slug, item: canonicalUrl.value },
        ],
      }),
    },
  ],
})
</script>
