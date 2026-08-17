<template>
  <div class="py-12 min-h-dvh bg-secondary-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 md:p-8 max-w-md w-full text-center shadow-xl space-y-4">
      <div class="w-16 h-16 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
        <i class="icon-order text-3xl"></i>
      </div>

      <h1 class="text-xl font-bold text-neutral-900 m-0">Ulashilgan Savat</h1>
      <p class="text-sm text-neutral-500">Kitobchi ilovasida bu savatchani ko‘rishingiz mumkin</p>

      <div class="flex flex-col gap-2 pt-2">
        <a
          :href="`kitobchi://share/cart/${$route.params.slug}`"
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors no-underline block"
        >
          Kitobchi ilovasida ochish
        </a>
        <NuxtLink
          to="/catalog"
          class="w-full py-3 rounded-2xl bg-secondary-100 text-primary font-semibold text-sm hover:bg-secondary-200 transition-colors no-underline block"
        >
          Katalogga o‘tish
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()

onMounted(() => {
  window.location.href = `kitobchi://share/cart/${route.params.slug}`
})

useSeoMeta({
  title: 'Ulashilgan Savat — Kitobchi',
  description: 'Kitobchi ilovasida ulashilgan savatni oching'
})
</script>
