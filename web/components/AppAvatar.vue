<template>
  <div class="relative shrink-0 select-none inline-block">
    <!-- Image Avatar (if available and loaded successfully) -->
    <img
      v-if="resolvedUrl && !hasError"
      :src="resolvedUrl"
      :alt="displayName"
      :class="[
        sizeClass,
        'rounded-full object-cover border border-neutral-100/80 bg-neutral-100 shadow-2xs'
      ]"
      loading="lazy"
      @error="handleError"
    />

    <!-- Telegram-Style Gradient Fallback Avatar -->
    <div
      v-else
      :class="[
        sizeClass,
        paletteClass,
        'rounded-full flex items-center justify-center font-bold text-white shadow-2xs tracking-wider uppercase'
      ]"
    >
      {{ initials }}
    </div>

    <!-- Optional Role / Badge Emoji -->
    <span
      v-if="badge"
      class="absolute -bottom-1 -right-1 text-xs bg-white rounded-full px-0.5 shadow-2xs leading-none border border-neutral-100"
    >
      {{ badge }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

const props = withDefaults(
  defineProps<{
    src?: string | null
    name?: string | null
    userId?: number | string | null
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | string
    badge?: string | null
  }>(),
  {
    src: null,
    name: 'Kitobxon',
    userId: null,
    size: 'md',
    badge: null
  }
)

const hasError = ref(false)

const displayName = computed(() => {
  return props.name?.trim() || 'Kitobxon'
})

const resolvedUrl = computed(() => {
  if (!props.src || hasError.value) return null
  return resolveImageUrl(props.src)
})

watch(
  () => props.src,
  () => {
    hasError.value = false
  }
)

function handleError() {
  hasError.value = true
}

const initials = computed(() => {
  const str = displayName.value.replace(/[^a-zA-Zа-яА-ЯёЁoʻgʻOʻGʻ\s]/g, '').trim() || displayName.value
  const parts = str.split(/\s+/).filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return str.slice(0, 2).toUpperCase() || 'K'
})

// Telegram-authentic vibrant gradient palettes
const TELEGRAM_PALETTES = [
  'bg-gradient-to-br from-[#e17076] to-[#ff885e]', // Coral / Red
  'bg-gradient-to-br from-[#faa774] to-[#ffbe6b]', // Orange / Amber
  'bg-gradient-to-br from-[#b694f9] to-[#806bf6]', // Purple / Violet
  'bg-gradient-to-br from-[#6ec9cb] to-[#45afbc]', // Cyan / Teal
  'bg-gradient-to-br from-[#65aadd] to-[#4f84c4]', // Blue / Sky
  'bg-gradient-to-br from-[#ee7aae] to-[#d8568c]', // Pink / Rose
  'bg-gradient-to-br from-[#82d174] to-[#5cb85c]', // Green / Emerald
  'bg-gradient-to-br from-[#f39c12] to-[#e67e22]'  // Gold / Deep Orange
]

const paletteClass = computed(() => {
  if (props.userId && !isNaN(Number(props.userId))) {
    const idx = Math.abs(Number(props.userId)) % TELEGRAM_PALETTES.length
    return TELEGRAM_PALETTES[idx]
  }

  let hash = 0
  const str = displayName.value
  for (let i = 0; i < str.length; i++) {
    hash = (hash << 5) - hash + str.charCodeAt(i)
    hash |= 0
  }
  const idx = Math.abs(hash) % TELEGRAM_PALETTES.length
  return TELEGRAM_PALETTES[idx]
})

const sizeClass = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'w-6 h-6 text-[10px]'
    case 'sm':
      return 'w-8 h-8 text-xs'
    case 'md':
      return 'w-10 h-10 text-sm'
    case 'lg':
      return 'w-12 h-12 text-base'
    case 'xl':
      return 'w-16 h-16 text-xl'
    case '2xl':
      return 'w-20 h-20 text-2xl'
    default:
      // Allow custom classes if passed, or default to md
      return props.size || 'w-10 h-10 text-sm'
  }
})
</script>
