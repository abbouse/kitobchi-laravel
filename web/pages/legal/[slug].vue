<template>
  <PolicyPage ref="pageRef" :slug="slug" :title="slug" />
</template>

<script setup lang="ts">
const route = useRoute()
const slug = computed(() => route.params.slug as string)

const pageRef = ref()

// TUZATILDI: bu yerda canonical umuman ko'rsatilmagan edi — natijada
// nuxt.config.ts'dagi UMUMIY (bosh sahifaga qattiq yozilgan) canonical
// meros bo'lib qolardi (Google'ga "bu sahifaning asl nusxasi aslida bosh
// sahifa" degan noto'g'ri signal). Endi sahifaning o'z URL'i aniq
// ko'rsatildi.
useSeoMeta({
  title: () => `${pageRef.value?.policy?.title || 'Hujjat'} — Kitobchi`
})

useHead({
  link: [{ rel: 'canonical', href: () => `https://kitobchi.com/legal/${slug.value}` }],
})
</script>
