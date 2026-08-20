<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
      @click="$emit('close')"
    ></div>

    <!-- Drawer Panel (PiyolaMarket 1:1) -->
    <!-- MUHIM: translate-x-0 / -translate-x-full klasslari piyola.css'da
         mavjud emas edi (Tailwind JIT bu loyihada faol emas) — shuning
         uchun drawer hech qachon yashirinmasdi. Shu sabab inline style
         orqali transform qo'lda boshqariladi. -->
    <div
      class="fixed top-0 left-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl transition-transform duration-300 flex flex-col"
      :style="{ transform: isOpen ? 'translateX(0)' : 'translateX(-100%)' }"
    >
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-bold text-primary m-0 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="currentColor">
            <path d="M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H4.5zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z"/>
          </svg>
          Kataloglar
        </h2>
        <button
          type="button"
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Type Switch Tabs -->
      <div class="flex border-b border-gray-100 p-2 gap-2 bg-secondary-50">
        <button
          type="button"
          @click="activeType = 'book'"
          :class="[
            'flex-1 py-2 rounded-xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeType === 'book' ? 'bg-primary text-white shadow-sm' : 'bg-transparent text-neutral-600'
          ]"
        >
          Kitoblar
        </button>
        <button
          type="button"
          @click="activeType = 'stationery'"
          :class="[
            'flex-1 py-2 rounded-xl text-sm font-semibold transition-all border-none cursor-pointer',
            activeType === 'stationery' ? 'bg-primary text-white shadow-sm' : 'bg-transparent text-neutral-600'
          ]"
        >
          Kanselyariya
        </button>
      </div>

      <!-- Categories List -->
      <div class="flex-1 overflow-y-auto p-4 space-y-1">
        <NuxtLink
          v-for="cat in filteredCategories"
          :key="cat.id"
          :to="`/category/${activeType}-${cat.id}`"
          @click="$emit('close')"
          class="flex items-center justify-between p-3 rounded-2xl hover:bg-neutral-50 transition-colors group text-neutral-800"
        >
          <span class="text-sm font-medium group-hover:text-primary transition-colors">
            {{ cat.name || cat.name_uz }}
          </span>
          <svg class="w-4 h-4 text-gray-400 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
          </svg>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  isOpen: boolean
}>()

defineEmits(['close'])

const activeType = ref<'book' | 'stationery'>('book')
const config = useRuntimeConfig()

const { data: categoriesData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'catalog-drawer-categories',
  lazy: true
})

// API javobi turkum bo'yicha kalitlangan: { data: { book: [...], stationery: [...] } }
const filteredCategories = computed(() => {
  return categoriesData.value?.data?.[activeType.value] || []
})
</script>
