<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
      @click="$emit('close')"
    ></div>

    <!-- Bottom Sheet — piyoladagi HAQIQIY mobil "Narx" filtri jonli
         tekshirilib tasdiqlandi: bu o'ngdan chiquvchi to'liq balandlikdagi
         panel EMAS, balki PASTDAN CHIQUVCHI, yuqori burchaklari
         yumaloqlangan "bottom sheet" (o'rtada tortish tutqichi bilan,
         yopish (X) tugmasisiz — orqa fon bosilganda yopiladi). Tailwind
         JIT faol emasligi sababli translate-y kabi klasslar ishlamaydi —
         shuning uchun inline style orqali boshqariladi (CatalogDrawer'dagi
         translateX pattern'iga o'xshash, faqat translateY). -->
    <div
      class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl shadow-2xl transition-transform duration-300 max-h-[85vh] overflow-y-auto"
      :style="{ transform: isOpen ? 'translateY(0)' : 'translateY(100%)' }"
    >
      <!-- Tortish tutqichi (piyoladagi kabi) -->
      <div class="flex justify-center pt-3 pb-2">
        <div class="w-10 h-1 rounded-full bg-neutral-300"></div>
      </div>

      <div class="px-5 pt-2 pb-4">
        <h2 class="text-xl font-bold text-neutral-900 m-0">Narx</h2>
      </div>

      <div class="px-5 pb-6 space-y-6">
        <div class="flex items-center gap-3">
          <div class="flex-1">
            <label class="block text-xs font-semibold text-neutral-500 mb-1.5">Quyidagidan</label>
            <div class="flex items-center rounded-2xl bg-secondary-100 px-4 py-3 border border-neutral-100 focus-within:border-gray">
              <input
                v-model="localMin"
                type="number"
                min="0"
                inputmode="numeric"
                placeholder="Min"
                class="flex-1 min-w-0 bg-transparent border-none outline-none font-medium text-neutral-900"
              />
              <span class="text-sm text-neutral-400 shrink-0">so‘m</span>
            </div>
          </div>
          <div class="flex-1">
            <label class="block text-xs font-semibold text-neutral-500 mb-1.5">Shungacha</label>
            <div class="flex items-center rounded-2xl bg-secondary-100 px-4 py-3 border border-neutral-100 focus-within:border-gray">
              <input
                v-model="localMax"
                type="number"
                min="0"
                inputmode="numeric"
                placeholder="Max"
                class="flex-1 min-w-0 bg-transparent border-none outline-none font-medium text-neutral-900"
              />
              <span class="text-sm text-neutral-400 shrink-0">so‘m</span>
            </div>
          </div>
        </div>

        <!-- Faqat biror filtr allaqachon tanlangan bo'lsagina ko'rsatiladi
             — piyolaning bo'sh holatida bu tugma umuman yo'q edi (jonli
             tekshirildi). -->
        <button
          v-if="localMin || localMax"
          type="button"
          @click="handleClear"
          class="text-sm font-semibold text-neutral-500 hover:text-neutral-600 bg-transparent border-none cursor-pointer p-0"
        >
          Filtrni tozalash
        </button>
      </div>

      <div class="sticky bottom-0 bg-white px-5 pb-5 pt-1">
        <button
          type="button"
          @click="handleApply"
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer"
        >
          Ko‘rsatish
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
