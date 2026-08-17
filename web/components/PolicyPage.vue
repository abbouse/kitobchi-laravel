<template>
  <main class="max-md:pb-[71px]">
    <div class="py-10 md:py-20">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="max-w-3xl mx-auto">
          <!-- Yuklanmoqda -->
          <div v-if="pending" class="animate-pulse space-y-4">
            <div class="h-8 bg-secondary-100 rounded-lg w-2/3"></div>
            <div class="h-4 bg-secondary-100 rounded-lg w-1/3 mb-6"></div>
            <div class="h-4 bg-secondary-100 rounded-lg w-full"></div>
            <div class="h-4 bg-secondary-100 rounded-lg w-full"></div>
            <div class="h-4 bg-secondary-100 rounded-lg w-3/4"></div>
          </div>

          <!-- Kontent hali qo'shilmagan — FABRIKATSIYA qilinmaydi -->
          <div v-else-if="notFound" class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 0 0 2.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
            </div>
            <h1 class="text-lg font-bold text-neutral-800 mb-1">{{ title }}</h1>
            <p class="text-sm text-neutral-500">Bu sahifa mazmuni hali boshqaruv panelida to'ldirilmagan.</p>
          </div>

          <!-- Haqiqiy kontent -->
          <template v-else-if="policy">
            <h1 class="text-3xl md:text-4xl font-bold mb-4 text-primary">{{ policy.title }}</h1>
            <p v-if="policy.updated_at" class="text-neutral-500 mb-10">
              Oxirgi yangilanish: {{ formatDate(policy.updated_at) }}
            </p>
            <div class="kb-prose" v-html="policy.content"></div>
          </template>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
const props = defineProps<{
  slug: string
  title: string
}>()

// toRef bilan bog'lanadi — slug prop o'zgarsa (masalan /legal/[slug]
// sahifasida bir hujjatdan ikkinchisiga client-side navigatsiya qilinsa),
// usePolicy buni ilg'ab qayta so'rov yuboradi.
const { policy, pending, notFound } = usePolicy(toRef(props, 'slug'))

function formatDate(iso: string) {
  try {
    return new Date(iso).toLocaleDateString('uz-UZ', { year: 'numeric', month: 'long', day: 'numeric' })
  } catch {
    return ''
  }
}

defineExpose({ policy, pending, notFound })
</script>
