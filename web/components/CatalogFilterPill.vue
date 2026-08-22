<!--
  Piyoladagi "Narx ⌄" / "Brendlar ⌄" kabi ALOHIDA, FOKUSLANGAN pill+popover
  naqshiga 1-1 mos komponent (jonli piyolamarket.uz'da tekshirilib
  tasdiqlandi: har bir filtr o'z pill'i ostida KICHIK, mustaqil popover
  ochadi — katta yon panelning bir bo'limi emas). Bitta "hamma narsani bitta
  joyga tiqish" panel o'rniga — har bir facet (Narx, Nashriyot, Do'kon,
  Yozuv turi, Muqova turi) o'ZINING pill'i va o'ZINING kichik popover'iga
  ega bo'ladi; to'liq "Filtr" paneli (CatalogFilterDrawer.vue) alohida,
  piyoladagi kabi HAMMASINI birlashtirgan qo'shimcha variant sifatida qoladi.

  Interaktsiya (jonli piyolada tasdiqlangan):
    - "range" rejimi (Narx): Min/Max + "Qo'llash" tugmasi — matn kiritish
      commit nuqtasini talab qiladi.
    - "checkbox" rejimi (Nashriyot/Do'kon/Yozuv turi/Muqova turi): checkbox
      bosilishi bilanoq DARHOL qo'llanadi (piyolada "Brendlar" checkbox'i
      jonli tekshirildi — checkbox bosilgach alohida tugmasiz mahsulotlar
      ro'yxati va URL darhol yangilandi), alohida "Qo'llash" tugmasisiz.

  Popover joylashuvi CSS klass emas — <Teleport to="body"> + JS orqali
  hisoblangan `position: fixed` inline style bilan. Sabab ikkita:
    1. `assets/css/piyola.css` STATIK (avtomatik qayta generatsiya
       qilinmaydi) — arbitrary/yangi klasslar ishlamaydi.
    2. Pill qatori `overflow-x-auto` ichida (mobil sticky panelda) — agar
       popover shu qatorning FARZANDI bo'lsa, CSS spec bo'yicha
       `overflow-x-auto` `overflow-y`ni ham cheklaydi va popover pastga
       "kesilib" ko'rinmay qoladi. Teleport bu muammoni butunlay chetlab
       o'tadi.
-->
<template>
  <div class="relative inline-block shrink-0">
    <button
      ref="triggerRef"
      type="button"
      @click="emit('toggle')"
      :class="pillClasses"
    >
      {{ label }}
      <svg
        :class="['w-3.5 h-3.5 transition-transform duration-200', isOpen ? 'rotate-180' : '']"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
      ><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="popoverRef"
        class="fixed bg-white rounded-2xl border border-neutral-100 shadow-2xl p-4"
        :style="popoverStyle"
      >
        <template v-if="mode === 'range'">
          <div class="flex items-center gap-2">
            <input
              v-model="localMin"
              type="number" min="0" inputmode="numeric" placeholder="Min"
              class="w-24 rounded-xl bg-secondary-100 border border-neutral-100 px-3 py-2 text-sm font-medium text-neutral-900 outline-none"
            />
            <span class="text-neutral-300">–</span>
            <input
              v-model="localMax"
              type="number" min="0" inputmode="numeric" placeholder="Max"
              class="w-24 rounded-xl bg-secondary-100 border border-neutral-100 px-3 py-2 text-sm font-medium text-neutral-900 outline-none"
            />
          </div>
          <button
            type="button"
            @click="applyRange"
            class="mt-3 w-full py-2.5 rounded-xl bg-primary text-white font-semibold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer"
          >
            Qo‘llash
          </button>
        </template>

        <template v-else>
          <div class="space-y-1 max-h-64 overflow-y-auto" style="min-width: 200px">
            <label
              v-for="opt in options || []"
              :key="opt.value"
              class="flex items-center gap-3 text-sm py-2 px-1 rounded-lg hover:bg-neutral-50 transition-colors cursor-pointer"
            >
              <input
                type="checkbox"
                :value="opt.value"
                :checked="(modelValue || []).includes(opt.value)"
                @change="toggleOption(opt.value)"
                class="w-4 h-4 rounded border-neutral-300 text-primary shrink-0"
              />
              <span class="text-neutral-800">{{ opt.label }}</span>
            </label>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
interface PillOption {
  value: string
  label: string
}

const props = defineProps<{
  label: string
  mode: 'range' | 'checkbox'
  isOpen: boolean
  active?: boolean
  minValue?: string | number | null
  maxValue?: string | number | null
  options?: PillOption[]
  modelValue?: string[]
}>()

const emit = defineEmits<{
  toggle: []
  close: []
  applyRange: [{ min: string | undefined; max: string | undefined }]
  'update:modelValue': [string[]]
}>()

const triggerRef = ref<HTMLElement | null>(null)
const popoverRef = ref<HTMLElement | null>(null)
const popoverStyle = ref<Record<string, string>>({})

const localMin = ref(props.minValue != null ? String(props.minValue) : '')
const localMax = ref(props.maxValue != null ? String(props.maxValue) : '')

watch(() => [props.minValue, props.maxValue], () => {
  localMin.value = props.minValue != null ? String(props.minValue) : ''
  localMax.value = props.maxValue != null ? String(props.maxValue) : ''
})

const pillClasses = computed(() => [
  'px-4 py-2 rounded-2xl text-sm font-semibold transition-all border-none cursor-pointer inline-flex items-center gap-1.5 shrink-0',
  props.active ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10'
])

function applyRange() {
  emit('applyRange', { min: localMin.value || undefined, max: localMax.value || undefined })
  emit('close')
}

function toggleOption(value: string) {
  const current = [...(props.modelValue || [])]
  const idx = current.indexOf(value)
  if (idx === -1) current.push(value)
  else current.splice(idx, 1)
  emit('update:modelValue', current)
}

function positionPopover() {
  const el = triggerRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const popW = props.mode === 'range' ? 220 : 240
  let left = rect.left
  const maxLeft = window.innerWidth - popW - 12
  if (left > maxLeft) left = Math.max(12, maxLeft)
  if (left < 12) left = 12
  popoverStyle.value = {
    top: `${rect.bottom + 8}px`,
    left: `${left}px`,
    zIndex: '60'
  }
}

function handleOutsideClick(e: MouseEvent) {
  const target = e.target as Node
  if (triggerRef.value?.contains(target)) return
  if (popoverRef.value?.contains(target)) return
  emit('close')
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

function handleReposition() {
  if (props.isOpen) positionPopover()
}

watch(() => props.isOpen, async (open) => {
  if (open) {
    await nextTick()
    positionPopover()
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
</script>
