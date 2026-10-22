<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[150] bg-black/95 backdrop-blur-md flex flex-col justify-between p-3 sm:p-5 select-none touch-none"
        @keydown.esc="close"
        @keydown.left="prev"
        @keydown.right="next"
        tabindex="0"
        ref="modalRef"
        @touchstart="onTouchStart"
        @touchend="onTouchEnd"
      >
        <!-- Top bar: Counter, Zoom toggle & Close button -->
        <div class="flex items-center justify-between text-white/90 z-20 px-2 py-1 shrink-0">
          <div class="text-xs sm:text-sm font-semibold tracking-wider bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-xs flex items-center gap-2">
            <span>{{ activeIndex + 1 }} / {{ normalizedImages.length }}</span>
            <span v-if="title" class="hidden sm:inline text-neutral-400 font-normal">| {{ title }}</span>
          </div>

          <div class="flex items-center gap-2">
            <!-- Zoom toggle button -->
            <button
              type="button"
              @click="isZoomed = !isZoomed"
              class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors border-none cursor-pointer"
              :title="isZoomed ? 'Kichiklashtirish' : 'Kattalashtirish'"
              aria-label="Kattalashtirish"
            >
              <svg v-if="!isZoomed" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM7.5 10.5h6" />
              </svg>
            </button>

            <!-- Close button -->
            <button
              type="button"
              @click="close"
              class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors border-none cursor-pointer"
              aria-label="Yopish"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Center: Image with Left / Right controls -->
        <div
          class="relative flex-1 flex items-center justify-center min-h-0 my-2 overflow-auto"
          @click.self="close"
        >
          <!-- Previous Button -->
          <button
            v-if="normalizedImages.length > 1"
            type="button"
            @click.stop="prev"
            class="absolute left-2 sm:left-4 z-20 w-11 h-11 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-transform hover:scale-105 active:scale-95 border-none cursor-pointer backdrop-blur-xs"
            aria-label="Oldingi rasm"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>

          <!-- Current Image Container -->
          <div
            class="relative flex items-center justify-center max-w-full max-h-full cursor-zoom-in transition-transform duration-200"
            :class="isZoomed ? 'scale-150 cursor-zoom-out' : 'scale-100'"
            @click="isZoomed = !isZoomed"
          >
            <img
              :src="currentImageUrl"
              :alt="title || 'Katta rasm'"
              class="max-w-[92vw] max-h-[72vh] object-contain rounded-xl shadow-2xl select-none"
              draggable="false"
            />
          </div>

          <!-- Next Button -->
          <button
            v-if="normalizedImages.length > 1"
            type="button"
            @click.stop="next"
            class="absolute right-2 sm:right-4 z-20 w-11 h-11 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-transform hover:scale-105 active:scale-95 border-none cursor-pointer backdrop-blur-xs"
            aria-label="Keyingi rasm"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <!-- Bottom Thumbnails Strip -->
        <div
          v-if="normalizedImages.length > 1"
          class="flex items-center justify-center gap-2 py-2 px-4 z-20 overflow-x-auto no-scrollbar shrink-0"
        >
          <button
            v-for="(img, idx) in normalizedImages"
            :key="'preview-thumb-' + idx"
            type="button"
            @click="activeIndex = idx"
            :class="[
              'relative rounded-lg overflow-hidden w-12 h-14 sm:w-14 sm:h-16 shrink-0 transition-all border-2 cursor-pointer p-0.5 bg-neutral-900',
              activeIndex === idx ? 'border-primary scale-105 opacity-100 ring-2 ring-primary/40' : 'border-transparent opacity-60 hover:opacity-100'
            ]"
            :aria-label="`Rasm ${idx + 1}`"
          >
            <img :src="img" :alt="`Thumbnail ${idx + 1}`" class="w-full h-full object-contain pointer-events-none" />
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

const props = defineProps<{
  images: any[]
  initialIndex?: number
  isOpen: boolean
  title?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const activeIndex = ref(props.initialIndex || 0)
const isZoomed = ref(false)
const modalRef = ref<HTMLElement | null>(null)

// Touch swipe variables
let touchStartX = 0
let touchStartY = 0

function onTouchStart(e: TouchEvent) {
  if (e.touches.length > 0) {
    touchStartX = e.touches[0].clientX
    touchStartY = e.touches[0].clientY
  }
}

function onTouchEnd(e: TouchEvent) {
  if (e.changedTouches.length > 0) {
    const diffX = e.changedTouches[0].clientX - touchStartX
    const diffY = e.changedTouches[0].clientY - touchStartY
    // Faqat gorizontal surish bo'lsa va yetarli masofada bo'lsa (50px)
    if (Math.abs(diffX) > 50 && Math.abs(diffX) > Math.abs(diffY)) {
      if (diffX < 0) {
        next()
      } else {
        prev()
      }
    }
  }
}

const normalizedImages = computed<string[]>(() => {
  if (!Array.isArray(props.images) || props.images.length === 0) return []
  return props.images
    .map((item) => {
      if (!item) return ''
      if (typeof item === 'string') return resolveImageUrl(item)
      const raw = item.url || item.image || item.path || item.medium || item.original
      return resolveImageUrl(raw)
    })
    .filter(Boolean)
})

watch(
  () => props.initialIndex,
  (val) => {
    activeIndex.value = typeof val === 'number' ? val : 0
    isZoomed.value = false
  }
)

watch(
  () => props.isOpen,
  (open) => {
    isZoomed.value = false
    if (open) {
      nextTick(() => {
        modalRef.value?.focus()
      })
      if (process.client) {
        document.body.style.overflow = 'hidden'
      }
    } else {
      if (process.client) {
        document.body.style.overflow = ''
      }
    }
  }
)

onUnmounted(() => {
  if (process.client) {
    document.body.style.overflow = ''
  }
})

const currentImageUrl = computed(() => {
  const list = normalizedImages.value
  if (list.length === 0) return ''
  return list[activeIndex.value] || list[0] || ''
})

function prev() {
  isZoomed.value = false
  const len = normalizedImages.value.length
  if (len < 2) return
  activeIndex.value = (activeIndex.value - 1 + len) % len
}

function next() {
  isZoomed.value = false
  const len = normalizedImages.value.length
  if (len < 2) return
  activeIndex.value = (activeIndex.value + 1) % len
}

function close() {
  isZoomed.value = false
  emit('close')
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
