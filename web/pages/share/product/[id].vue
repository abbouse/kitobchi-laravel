<template>
  <div class="py-12 min-h-dvh bg-secondary-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 md:p-8 max-w-md w-full text-center shadow-xl space-y-4">
      <div class="w-32 h-44 rounded-2xl bg-secondary-100 mx-auto overflow-hidden shadow-md flex items-center justify-center">
        <img
          v-if="product"
          :src="product.first_image ? `/storage/${product.first_image}` : '/images/logo/logo_blue.png'"
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
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors no-underline block"
        >
          Kitobchi ilovasida ochish
        </a>
        <NuxtLink
          :to="`/books/${$route.params.id}`"
          class="w-full py-3 rounded-2xl bg-secondary-100 text-primary font-semibold text-sm hover:bg-secondary-200 transition-colors no-underline block"
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

const { data: productData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/products/book`, {
  query: { id: route.params.id }
})

const product = computed(() => productData.value?.product || productData.value?.data || null)

onMounted(() => {
  // Try deep-link redirect
  const appUrl = `kitobchi://share/product/${route.params.id}`
  window.location.href = appUrl
})

useSeoMeta({
  title: () => `${product.value?.name || 'Mahsulot'} — Kitobchi ilovasida ochish`,
  description: () => product.value?.description || 'Kitobchi ilovasida ushbu mahsulotni ko‘ring',
  ogImage: () => product.value?.first_image ? `/storage/${product.value.first_image}` : '/images/logo/logo_blue.png'
})
</script>
