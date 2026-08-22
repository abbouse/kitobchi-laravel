<template>
  <div class="relative shrink-0">
    <!-- MUHIM: piyolada "Ommabop" saralash piyola bosilganda 4 ta variantli
         (Ommabop / Narx: pastdan yuqoriga / Narx: yuqoridan pastga / Yangi)
         kichik popover ochiladi (jonli tekshirilib tasdiqlandi). Kitobchida
         ilgari faqat "Ommabop"/"Yangi" degan ikkita alohida pill tugma bor
         edi — narx bo'yicha saralash backend'da (`search()`, sort=price_asc/
         price_desc — `applySortToQuery()`da haqiqiy DB ORDER BY ga bog'langan)
         ALLAQACHON mavjud edi, lekin frontendda umuman ko'rsatilmasdi.

         MUHIM BUG TUZATILDI (2026-08-22, jonli mobil tekshiruvda topildi):
         bu popover ilgari oddiy `<div class="absolute top-full ...">` edi —
         qatorning o'zi (`.overflow-x-auto` pill qatori) CSS spec bo'yicha
         `overflow-y`ni ham cheklaydi, shu sabab popover HAQIQATDA ochilardi
         (holat to'g'ri o'zgarardi, matn ham bor edi) lekin VIZUAL RAVISHDA
         qator balandligidan tashqariga chiqib KESILIB, umuman ko'rinmasdi —
         foydalanuvchiga xuddi "bosilganda hech narsa bo'lmayapti"dek
         tuyulardi (`getBoundingClientRect`+`elementFromPoint` bilan jonli
         tasdiqlandi: popover o'rnida pastdagi boshqa qator elementi
         chiqqan). Endi `CatalogFilterPill.vue`dagi bilan bir xil yechim:
         <Teleport to="body"> + JS orqali hisoblangan `position:fixed`. -->
    <button
      ref="triggerRef"
      type="button"
      @click="toggleOpen"
      :class="[
        'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0',
        modelValue !== 'popular' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
      ]"
    >
      {{ activeLabel }}
      <svg class="w-3.5 h-3.5 shrink-0 transition-transform" :class="isOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="menuRef"
        class="fixed w-60 rounded-2xl bg-white shadow-2xl border border-neutral-100 py-2"
        :style="menuStyle"
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
    </Teleport>
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
const triggerRef = ref<HTMLElement | null>(null)
const menuRef = ref<HTMLElement | null>(null)
const menuStyle = ref<Record<string, string>>({})

const options = [
  { value: 'popular', label: 'Ommabop' },
  { value: 'price_asc', label: "Narx: pastdan yuqoriga" },
  { value: 'price_desc', label: "Narx: yuqoridan pastga" },
  { value: 'new', label: 'Yangi' },
]

const activeLabel = computed(() => options.find((o) => o.value === props.modelValue)?.label || 'Ommabop')

function positionMenu() {
  const el = triggerRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const menuW = 240
  let left = rect.left
  const maxLeft = window.innerWidth - menuW - 12
  if (left > maxLeft) left = Math.max(12, maxLeft)
  if (left < 12) left = 12
  menuStyle.value = { top: `${rect.bottom + 8}px`, left: `${left}px`, zIndex: '60' }
}

function toggleOpen() {
  isOpen.value = !isOpen.value
}

function handleOutsideClick(e: MouseEvent) {
  const target = e.target as Node
  if (triggerRef.value?.contains(target)) return
  if (menuRef.value?.contains(target)) return
  isOpen.value = false
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') isOpen.value = false
}

function handleReposition() {
  if (isOpen.value) positionMenu()
}

watch(isOpen, async (open) => {
  if (open) {
    await nextTick()
    positionMenu()
    document.addEventListener('mousedown', handleOutsideClick)
    document.addEventListener('keydown', handleEscape)
    window.addEventListener('resize', handleReposition)
    window.addEventListener('scroll', handleReposition, true)
  } else {
    document.removeEventListener('mousedown', handleOutsideClick)
    document.removeEventListener('keydown', handleEscape)
    window.removeEventListener('resize', handleReposition)
    window.removeEventListener('scroll', handleReposition, true)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleOutsideClick)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('resize', handleReposition)
  window.removeEventListener('scroll', handleReposition, true)
})

function select(value: string) {
  emit('update:modelValue', value)
  isOpen.value = false
}
</script>
