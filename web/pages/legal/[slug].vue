<template>
  <PolicyPage :slug="slug" :title="slug" />
</template>

<script setup lang="ts">
const route = useRoute()
const slug = computed(() => route.params.slug as string)

const { policy } = usePolicy(slug)

useSeoMeta({
  title: () => `${policy.value?.title || 'Huquqiy hujjat'} — Kitobchi`,
  description: () => policy.value?.title
    ? `${policy.value.title} — Kitobchi platformasining rasmiy huquqiy hujjati va shartlari.`
    : 'Kitobchi platformasining rasmiy huquqiy hujjatlari va qoidalari.',
  ogTitle: () => `${policy.value?.title || 'Huquqiy hujjat'} — Kitobchi`,
  ogDescription: () => policy.value?.title
    ? `${policy.value.title} — Kitobchi platformasining rasmiy huquqiy hujjati va shartlari.`
    : 'Kitobchi platformasining rasmiy huquqiy hujjatlari va qoidalari.',
})

useHead({
  link: [{ rel: 'canonical', href: () => `https://kitobchi.com/legal/${slug.value}` }],
})
</script>
