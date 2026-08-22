<template>
  <div ref="rootRef" class="relative shrink-0">
    <!-- MUHIM: piyolada "Ommabop" saralash piyola bosilganda 4 ta variantli
         (Ommabop / Narx: pastdan yuqoriga / Narx: yuqoridan pastga / Yangi)
         kichik popover ochiladi (jonli tekshirilib tasdiqlandi). Kitobchida
         ilgari faqat "Ommabop"/"Yangi" degan ikkita alohida pill tugma bor
         edi — narx bo'yicha saralash backend'da (`search()`, sort=price_asc/
         price_desc — `applySortToQuery()`da haqiqiy DB ORDER BY ga bog'langan)
         ALLAQACHON mavjud edi, lekin frontendda umuman ko'rsatilmasdi. -->
    <button
      type="button"
      @click="isOpen = !isOpen"
      :class="[
        'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0',
        modelValue !== 'popular' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
      ]"
    >
      {{ activeLabel }}
      <svg class="w-3.5 h-3.5 shrink-0 transition-transform" :class="isOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <div
      v-if="isOpen"
      class="absolute z-30 top-full left-0 mt-2 w-60 rounded-2xl bg-white shadow-xl border border-neutral-100 py-2"
    >
      <button
        v-for="opt in options"
        :key="opt.value"
        type="button"
        @click="select(opt.value)"
        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left bg-transparent border-none cursor-pointer hover:bg-secondary-100 transition-colors"
      >
        <span
          class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
          :class="modelValue === opt.value ? 'border-primary' : 'border-neutral-300'"
        >
          <span v-if="modelValue === opt.value" class="w-2 h-2 rounded-full bg-primary"></span>
        </span>
        <span :class="modelValue === opt.value ? 'font-semibold text-neutral-900' : 'text-neutral-600'">{{ opt.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
}>()
const emit = defineEmits<{
  'update:modelValue': [string]
}>()

const isOpen = ref(false)
const rootRef = ref<HTMLElement | null>(null)
onClickOutside(rootRef, () => { isOpen.value = false })

const options = [
  { value: 'popular', label: 'Ommabop' },
  { value: 'price_asc', label: "Narx: pastdan yuqoriga" },
  { value: 'price_desc', label: "Narx: yuqoridan pastga" },
  { value: 'new', label: 'Yangi' },
]

const activeLabel = computed(() => options.find((o) => o.value === props.modelValue)?.label || 'Ommabop')

function select(value: string) {
  emit('update:modelValue', value)
  isOpen.value = false
}
</script>
