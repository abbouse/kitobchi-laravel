<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[130] flex items-center justify-center bg-black/85 backdrop-blur-md p-3 sm:p-4 transition-opacity duration-300"
      @click.self="close"
    >
      <div class="relative w-full max-w-3xl bg-neutral-950 rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl flex flex-col border border-neutral-800">
        <!-- Header -->
        <div class="px-4 sm:px-5 py-3 sm:py-3.5 flex items-center justify-between bg-neutral-900/90 text-white z-10 border-b border-neutral-800">
          <div class="flex items-center gap-2 min-w-0 pr-3">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shrink-0"></span>
            <span class="font-semibold text-xs sm:text-sm truncate">{{ product?.name || 'Mahsulot videosi' }}</span>
          </div>
          <button
            type="button"
            @click="close"
            class="w-8 h-8 rounded-full bg-neutral-800 hover:bg-neutral-700 text-neutral-300 flex items-center justify-center transition-colors border-none cursor-pointer shrink-0"
            aria-label="Yopish"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Video Player / Embed -->
        <div class="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden">
          <!-- YouTube / Vimeo Iframe Embed -->
          <iframe
            v-if="embedUrl"
            :src="embedUrl"
            class="w-full h-full border-none"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>

          <!-- Native HTML5 Video -->
          <video
            v-else-if="videoSource"
            ref="videoEl"
            :src="videoSource"
            :poster="posterSource"
            class="w-full h-full object-contain"
            controls
            autoplay
            playsinline
          ></video>

          <!-- No Video Fallback -->
          <div v-else class="text-neutral-400 text-xs sm:text-sm flex flex-col items-center gap-2 p-6 text-center">
            <svg class="w-10 h-10 text-neutral-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9A2.25 2.25 0 0 0 13.5 5.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z"/>
            </svg>
            <span>Ushbu mahsulot uchun video hozircha mavjud emas</span>
          </div>
        </div>

        <!-- Bottom bar -->
        <div class="px-4 sm:px-5 py-2.5 sm:py-3 bg-neutral-900 flex items-center justify-between text-xs text-neutral-400">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
            </svg>
            <span class="truncate">Kitobchi Video sharh</span>
          </div>
          <div v-if="product?.price" class="text-white font-semibold shrink-0">
            {{ formatPrice(product.price) }} so'm
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

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

// Raw video URL stringini aniqlash
const rawVideoUrl = computed<string>(() => {
  const p = props.product
  if (!p) return ''
  const v = p.video
  if (typeof v === 'string' && v.trim()) return v.trim()
  if (v?.hd && typeof v.hd === 'string') return v.hd.trim()
  if (v?.sd && typeof v.sd === 'string') return v.sd.trim()
  if (v?.url && typeof v.url === 'string') return v.url.trim()
  if (typeof p.video_url === 'string' && p.video_url.trim()) return p.video_url.trim()
  return ''
})

// YouTube yoki Vimeo uchun embed URL aniqlash
const embedUrl = computed<string>(() => {
  const url = rawVideoUrl.value
  if (!url) return ''

  // YouTube standard or short link
  // e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ or https://youtu.be/dQw4w9WgXcQ or /shorts/dQw4w9WgXcQ
  const ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/)
  if (ytMatch && ytMatch[1]) {
    return `https://www.youtube-nocookie.com/embed/${ytMatch[1]}?autoplay=1&rel=0`
  }

  // Vimeo
  const vimeoMatch = url.match(/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)/)
  if (vimeoMatch && vimeoMatch[1]) {
    return `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`
  }

  return ''
})

// Oddiy video manbasi (MP4, WebM, HLS va b.)
const videoSource = computed<string>(() => {
  if (embedUrl.value) return ''
  const url = rawVideoUrl.value
  if (!url) return ''
  return resolveImageUrl(url)
})

const posterSource = computed<string>(() => {
  const p = props.product
  if (!p) return ''
  const v = p.video
  if (v?.poster && typeof v.poster === 'string') return resolveImageUrl(v.poster)
  if (p.first_image) return resolveImageUrl(p.first_image)
  if (Array.isArray(p.medium_images) && p.medium_images[0]) return resolveImageUrl(p.medium_images[0])
  return ''
})

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

watch(
  () => props.modelValue,
  (val) => {
    if (process.client) {
      if (val) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    }
  }
)

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.modelValue) {
      close()
    }
  }
  window.addEventListener('keydown', handleKeydown)
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
    if (process.client) {
      document.body.style.overflow = ''
    }
  })
})
</script>
