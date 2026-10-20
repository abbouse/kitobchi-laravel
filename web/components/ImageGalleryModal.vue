<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex flex-col justify-between p-4 select-none"
        @keydown.esc="close"
        @keydown.left="prev"
        @keydown.right="next"
        tabindex="0"
        ref="modalRef"
      >
        <!-- Top bar: Counter & Close button -->
        <div class="flex items-center justify-between text-white/90 z-10 px-2 py-1">
          <div class="text-xs sm:text-sm font-semibold tracking-wider bg-white/10 px-3 py-1 rounded-full backdrop-blur-xs">
            {{ activeIndex + 1 }} / {{ images.length }}
          </div>

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

        <!-- Center: Image with Left / Right controls -->
        <div class="relative flex-1 flex items-center justify-center min-h-0 my-2">
          <!-- Previous Button -->
          <button
            v-if="images.length > 1"
            type="button"
            @click.stop="prev"
            class="absolute left-2 sm:left-6 z-10 w-11 h-11 rounded-full bg-black/50 hover:bg-black/75 text-white flex items-center justify-center transition-colors border-none cursor-pointer backdrop-blur-xs"
            aria-label="Oldingi rasm"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>

          <!-- Current Image -->
          <img
            :src="currentImageUrl"
            alt="Katta rasm"
            class="max-w-full max-h-[78vh] object-contain rounded-xl shadow-2xl transition-all duration-200"
          />

          <!-- Next Button -->
          <button
            v-if="images.length > 1"
            type="button"
            @click.stop="next"
            class="absolute right-2 sm:right-6 z-10 w-11 h-11 rounded-full bg-black/50 hover:bg-black/75 text-white flex items-center justify-center transition-colors border-none cursor-pointer backdrop-blur-xs"
            aria-label="Keyingi rasm"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <!-- Bottom: Pagination dots -->
        <div v-if="images.length > 1" class="flex items-center justify-center gap-1.5 py-2 z-10">
          <button
            v-for="(img, idx) in images"
            :key="idx"
            type="button"
            @click="activeIndex = idx"
            :class="[
              'h-2 rounded-full transition-all border-none cursor-pointer p-0',
              activeIndex === idx ? 'bg-white w-6' : 'bg-white/40 w-2 hover:bg-white/70'
            ]"
            :aria-label="`Rasm ${idx + 1}`"
          ></button>
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
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const activeIndex = ref(props.initialIndex || 0)
const modalRef = ref<HTMLElement | null>(null)

watch(
  () => props.initialIndex,
  (val) => {
    activeIndex.value = val || 0
  }
)

watch(
  () => props.isOpen,
  (open) => {
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
  if (!props.images || props.images.length === 0) return ''
  const item = props.images[activeIndex.value]
  const raw = typeof item === 'string' ? item : item?.image || item?.url
  return resolveImageUrl(raw)
})

function prev() {
  if (activeIndex.value > 0) {
    activeIndex.value--
  } else {
    activeIndex.value = props.images.length - 1
  }
}

function next() {
  if (activeIndex.value < props.images.length - 1) {
    activeIndex.value++
  } else {
    activeIndex.value = 0
  }
}

function close() {
  emit('close')
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
