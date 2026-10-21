<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[130] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 transition-opacity duration-300"
      @click.self="close"
    >
      <div class="relative w-full max-w-3xl bg-neutral-950 rounded-3xl overflow-hidden shadow-2xl flex flex-col border-none">
        <!-- Header -->
        <div class="px-5 py-3.5 flex items-center justify-between bg-neutral-900/80 text-white z-10 border-b border-neutral-800">
          <div class="flex items-center gap-2 min-w-0">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shrink-0"></span>
            <span class="font-semibold text-sm truncate">{{ product?.name || 'Mahsulot videosi' }}</span>
          </div>
          <button
            type="button"
            @click="close"
            class="w-8 h-8 rounded-full bg-neutral-800 hover:bg-neutral-700 text-neutral-300 flex items-center justify-center transition-colors border-none cursor-pointer"
            aria-label="Yopish"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Video Player -->
        <div class="relative w-full aspect-video sm:aspect-[16/10] bg-black flex items-center justify-center overflow-hidden">
          <video
            ref="videoEl"
            :src="videoSource"
            :poster="posterSource"
            class="w-full h-full object-contain"
            controls
            autoplay
            playsinline
          ></video>
        </div>

        <!-- Bottom bar -->
        <div class="px-5 py-3 bg-neutral-900 flex items-center justify-between text-xs text-neutral-400">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
            </svg>
            <span>Kitobchi Video sharh</span>
          </div>
          <div v-if="product?.price" class="text-white font-semibold">
            {{ formatPrice(product.price) }} so'm
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean
  product: any
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
}>()

const videoEl = ref<HTMLVideoElement | null>(null)

function close() {
  if (videoEl.value) {
    videoEl.value.pause()
  }
  emit('update:modelValue', false)
}

const videoSource = computed(() => {
  const v = props.product?.video
  if (v?.hd) return v.hd
  if (v?.sd) return v.sd
  if (typeof props.product?.video_url === 'string') return props.product.video_url
  return ''
})

const posterSource = computed(() => {
  const v = props.product?.video
  if (v?.poster) return v.poster
  if (props.product?.first_image) return props.product.first_image
  return ''
})

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.modelValue) {
      close()
    }
  }
  window.addEventListener('keydown', handleKeydown)
  onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
})
</script>
