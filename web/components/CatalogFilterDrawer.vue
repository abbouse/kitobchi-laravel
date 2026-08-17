<template>
  <div>
    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
      @click="$emit('close')"
    ></div>

    <!-- Panel — MOBILE'da piyoladagi HAQIQIY "Narx" filtri jonli tekshirilib
         tasdiqlangan: pastdan chiquvchi, yuqori burchaklari yumaloqlangan
         "bottom sheet" (tortish tutqichi bilan, X tugmasisiz — orqa fon
         bosilganda yopiladi). DESKTOP'da (md:) esa piyolaning haqiqiy "Filtr"
         paneli — bu bottom sheet EMAS, balki O'NGDAN chiquvchi to'liq
         balandlikdagi panel, sarlavha qatorida X yopish tugmasi bilan (jonli
         desktop DOM'dan tasdiqlangan). Ilgari bu komponent barcha
         o'lchamlarda (shu jumladan desktopda ham) faqat bottom sheet
         ko'rinishida edi — bu piyolada yo'q, mobil andozani desktopga
         noto'g'ri qo'llash edi.
         Tailwind JIT faol emasligi sababli translate-y/translate-x kabi
         klasslar ishlamaydi — shuning uchun transform yo'nalishi JS orqali
         (isDesktop) hisoblanib, inline style bilan qo'llaniladi. -->
    <div
      class="catalog-filter-panel fixed z-50 bg-white shadow-2xl transition-transform duration-300 overflow-y-auto bottom-0 left-0 right-0 rounded-t-3xl max-h-[85vh]"
      :style="panelStyle"
    >
      <!-- Tortish tutqichi — faqat mobileda (piyoladagi kabi) -->
      <div class="catalog-filter-drag-handle flex justify-center pt-3 pb-2">
        <div class="w-10 h-1 rounded-full bg-neutral-300"></div>
      </div>

      <div class="catalog-filter-header px-5 pt-2 pb-4">
        <h2 class="text-xl font-bold text-neutral-900 m-0">{{ categories && categories.length ? 'Filtr' : 'Narx' }}</h2>
        <button
          type="button"
          @click="$emit('close')"
          aria-label="Yopish"
          class="catalog-filter-close-btn hidden w-8 h-8 rounded-full bg-secondary-100 items-center justify-center text-neutral-600 hover:bg-neutral-200 border-none cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="catalog-filter-body px-5 pb-6 space-y-6">
        <div>
          <h3 v-if="categories && categories.length" class="text-base font-bold text-neutral-900 mb-3 m-0">Narx oralig‘i</h3>
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
        </div>

        <!-- Kategoriyalar bo'limi — faqat "Filtr" (birlashtirilgan) rejimda,
             piyoladagi HAQIQIY kombinatsiyalashgan filtr sheet'idan (Narx
             oralig'i + Kategoriyalar) jonli tekshirilib olingan. Piyolada
             kategoriyalar ierarxik bo'lgani uchun faqat joriy (va uning
             qo'shni) kategoriyasi ko'rinadi; kitobchida kategoriyalar
             TEKIS (parent/child yo'q — backend'da tasdiqlandi), shuning
             uchun shu yerda joriy turdagi (kitob/kanselyariya) BARCHA
             kategoriyalar ro'yxati ko'rsatiladi va bittasini tanlab
             almashtirish mumkin. -->
        <div v-if="categories && categories.length">
          <h3 class="text-base font-bold text-neutral-900 mb-3 m-0">Kategoriyalar</h3>
          <div class="space-y-1 max-h-64 overflow-y-auto">
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              @click="localCategoryId = String(cat.id)"
              :class="[
                'block w-full text-left text-sm py-2 px-1 rounded-lg hover:bg-neutral-50 transition-colors',
                String(cat.id) === localCategoryId ? 'font-semibold text-primary' : 'font-normal text-neutral-700'
              ]"
            >
              {{ cat.name_uz || cat.name }}
            </button>
          </div>
        </div>

        <!-- Faqat biror filtr allaqachon tanlangan bo'lsagina ko'rsatiladi
             — piyolaning bo'sh holatida bu tugma umuman yo'q edi (jonli
             tekshirildi). Desktopda buning o'rniga pastdagi sticky
             qatorda to'liq kengliкdagi "Filtrni tozalash" tugmasi bor
             (jonli desktop DOM'dan tasdiqlangan), shu sababli bu yerdagi
             matn-link faqat mobileda ko'rinadi. -->
        <button
          v-if="localMin || localMax || localCategoryId"
          type="button"
          @click="handleClear"
          class="catalog-filter-clear-mobile text-sm font-semibold text-neutral-500 hover:text-neutral-600 bg-transparent border-none cursor-pointer p-0"
        >
          Filtrni tozalash
        </button>
      </div>

      <div class="catalog-filter-footer sticky bottom-0 bg-white px-5 pb-5 pt-1">
        <button
          v-if="localMin || localMax || localCategoryId"
          type="button"
          @click="handleClear"
          class="catalog-filter-clear-desktop hidden py-3.5 rounded-2xl bg-secondary-200 text-primary font-semibold text-base hover:bg-secondary-400 transition-colors border-none cursor-pointer"
        >
          Filtrni tozalash
        </button>
        <button
          type="button"
          @click="handleApply"
          class="catalog-filter-apply-btn w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer"
        >
          Ko‘rsatish
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface FilterCategory {
  id: number | string
  name_uz?: string
  name?: string
}

const props = defineProps<{
  isOpen: boolean
  minPrice?: string | number | null
  maxPrice?: string | number | null
  categories?: FilterCategory[]
  activeCategoryId?: string | number | null
}>()
const emit = defineEmits<{
  close: []
  apply: [{ minPrice: string | undefined; maxPrice: string | undefined; categoryId: string | undefined }]
}>()

const localMin = ref<string>(props.minPrice != null ? String(props.minPrice) : '')
const localMax = ref<string>(props.maxPrice != null ? String(props.maxPrice) : '')
const localCategoryId = ref<string>(props.activeCategoryId != null ? String(props.activeCategoryId) : '')

// Panel yo'nalishi (translateY — mobile bottom sheet, translateX — desktop
// o'ng panel) viewport kengligiga qarab tanlanadi. Tailwind JIT bu loyihada
// faol bo'lmagani uchun bu inline style orqali qo'lda hisoblanadi (translate-y-full
// kabi klasslar oldindan compile qilinmagan).
const isDesktop = ref(false)
function checkIsDesktop() {
  isDesktop.value = window.matchMedia('(min-width: 768px)').matches
}
onMounted(() => {
  checkIsDesktop()
  window.addEventListener('resize', checkIsDesktop)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIsDesktop)
})

const panelStyle = computed(() => {
  if (isDesktop.value) {
    return { transform: props.isOpen ? 'translateX(0)' : 'translateX(100%)' }
  }
  return { transform: props.isOpen ? 'translateY(0)' : 'translateY(100%)' }
})

watch(() => props.isOpen, (open) => {
  if (open) {
    localMin.value = props.minPrice != null ? String(props.minPrice) : ''
    localMax.value = props.maxPrice != null ? String(props.maxPrice) : ''
    localCategoryId.value = props.activeCategoryId != null ? String(props.activeCategoryId) : ''
  }
})

function handleApply() {
  emit('apply', { minPrice: localMin.value || undefined, maxPrice: localMax.value || undefined, categoryId: localCategoryId.value || undefined })
  emit('close')
}

function handleClear() {
  localMin.value = ''
  localMax.value = ''
  localCategoryId.value = ''
  emit('apply', { minPrice: undefined, maxPrice: undefined, categoryId: undefined })
  emit('close')
}
</script>
