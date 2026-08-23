<template>
  <PolicyPage slug="biz-haqimizda" title="Biz haqimizda" />
</template>

<script setup lang="ts">
// TUZATILDI: bu yerda canonical umuman ko'rsatilmagan edi — natijada
// nuxt.config.ts'dagi UMUMIY (bosh sahifaga qattiq yozilgan) canonical
// meros bo'lib qolardi (Google'ga "bu sahifaning asl nusxasi aslida bosh
// sahifa" degan noto'g'ri signal). Endi sahifaning o'z URL'i aniq
// ko'rsatildi.
useSeoMeta({
  title: 'Biz haqimizda — Kitobchi',
  description: 'Kitobchi — O‘zbekistondagi online kitoblar va kanselyariya marketpleysi haqida.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/about' }],
})
</script>
