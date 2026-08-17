<template>
  <PolicyPage ref="pageRef" :slug="slug" :title="slug" />
</template>

<script setup lang="ts">
const route = useRoute()
const slug = computed(() => route.params.slug as string)

const pageRef = ref()

useSeoMeta({
  title: () => `${pageRef.value?.policy?.title || 'Hujjat'} — Kitobchi`
})
</script>
