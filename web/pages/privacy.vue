<template>
  <PolicyPage slug="maxfiylik-siyosati" title="Maxfiylik siyosati" />
</template>

<script setup lang="ts">
// TUZATILDI: bu yerda canonical umuman ko'rsatilmagan edi — natijada
// nuxt.config.ts'dagi UMUMIY (bosh sahifaga qattiq yozilgan) canonical
// meros bo'lib qolardi (Google'ga "bu sahifaning asl nusxasi aslida bosh
// sahifa" degan noto'g'ri signal). Endi sahifaning o'z URL'i aniq
// ko'rsatildi.
useSeoMeta({
  title: 'Maxfiylik siyosati — Kitobchi',
  description: 'Kitobchi marketpleysida foydalanuvchi ma’lumotlarini yig‘ish va qayta ishlash tartibi.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/privacy' }],
})
</script>
