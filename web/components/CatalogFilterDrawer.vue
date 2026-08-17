<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
      @click="$emit('close')"
    ></div>

    <!-- Drawer Panel — piyoladagi "Filtr" popoverining o'rnini bosuvchi,
         CatalogDrawer bilan bir xil (allaqachon tekshirilgan/ishlaydigan)
         slide-panel patternidan foydalanadi. Tailwind JIT faol emasligi
         sababli translate-x-full kabi klasslar ishlamaydi — shuning uchun
         CatalogDrawer'dagi kabi inline style orqali boshqariladi. -->
    <div
      class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl transition-transform duration-300 flex flex-col"
      :style="{ transform: isOpen ? 'translateX(0)' : 'translateX(100%)' }"
    >
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-bold text-primary m-0 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" y1="21" x2="4" y2="14"/>
            <line x1="4" y1="10" x2="4" y2="3"/>
            <line x1="12" y1="21" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12" y2="3"/>
            <line x1="20" y1="21" x2="20" y2="16"/>
            <line x1="20" y1="12" x2="20" y2="3"/>
            <line x1="1" y1="14" x2="7" y2="14"/>
            <line x1="9" y1="8" x2="15" y2="8"/>
            <line x1="17" y1="16" x2="23" y2="16"/>
          </svg>
          Filtr
        </h2>
        <button
          type="button"
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-secondary-100 flex items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <div>
          <h3 class="text-sm font-bold text-neutral-800 mb-3">Narx oralig‘i</h3>
          <div class="flex items-center gap-3">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-neutral-600 mb-1">Quyidagidan (so‘m)</label>
              <input
                v-model="localMin"
                type="number"
                min="0"
                inputmode="numeric"
                placeholder="0"
                class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium"
              />
            </div>
            <div class="flex-1">
              <label class="block text-xs font-semibold text-neutral-600 mb-1">Shungacha (so‘m)</label>
              <input
                v-model="localMax"
                type="number"
                min="0"
                inputmode="numeric"
                placeholder="1 000 000"
                class="w-full rounded-2xl bg-white px-4 py-3 border border-neutral-100 outline-none focus:border-primary/20 font-medium"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Piyola'dagi bilan bir xil tartib: katta asosiy tugma (Ko'rsatish)
           chapda, kichikroq ikkilamchi tugma (Filtrni tozalash) o'ngda. -->
      <div class="p-4 border-t border-gray-100 flex items-center gap-3">
        <button
          type="button"
          @click="handleApply"
          class="flex-1 py-3 rounded-2xl bg-primary text-white font-semibold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer"
        >
          Ko‘rsatish
        </button>
        <button
          type="button"
          @click="handleClear"
          class="px-6 py-3 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-sm border-none cursor-pointer hover:bg-secondary-400 transition-colors"
        >
          Filtrni tozalash
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  isOpen: boolean
  minPrice?: string | number | null
  maxPrice?: string | number | null
}>()
const emit = defineEmits<{
  close: []
  apply: [{ minPrice: string | undefined; maxPrice: string | undefined }]
}>()

const localMin = ref<string>(props.minPrice != null ? String(props.minPrice) : '')
const localMax = ref<string>(props.maxPrice != null ? String(props.maxPrice) : '')

watch(() => props.isOpen, (open) => {
  if (open) {
    localMin.value = props.minPrice != null ? String(props.minPrice) : ''
    localMax.value = props.maxPrice != null ? String(props.maxPrice) : ''
  }
})

function handleApply() {
  emit('apply', { minPrice: localMin.value || undefined, maxPrice: localMax.value || undefined })
  emit('close')
}

function handleClear() {
  localMin.value = ''
  localMax.value = ''
  emit('apply', { minPrice: undefined, maxPrice: undefined })
  emit('close')
}
</script>
