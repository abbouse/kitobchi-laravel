<template>
  <article class="bg-white rounded-3xl p-5 sm:p-6 shadow-2xs hover:shadow-xs transition-all duration-200 border-none flex flex-col justify-between">
    <div>
      <!-- Author Header -->
      <div class="flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3 min-w-0">
          <!-- Avatar linking to Profile -->
          <NuxtLink
            v-if="post.user?.id"
            :to="`/club/user/${post.user.id}`"
            class="shrink-0 no-underline group/avatar"
            :title="`${authorName} profilini ko‘rish`"
          >
            <AppAvatar
              :src="post.user?.avatar"
              :name="authorName"
              :user-id="post.user?.id"
              size="w-11 h-11 text-sm"
              :badge="post.user?.role_emoji"
            />
          </NuxtLink>
          <div v-else class="shrink-0">
            <AppAvatar
              :src="post.user?.avatar"
              :name="authorName"
              :user-id="post.user?.id"
              size="w-11 h-11 text-sm"
              :badge="post.user?.role_emoji"
            />
          </div>

          <!-- Name & Role -->
          <div class="min-w-0">
            <div class="flex items-center gap-1.5 flex-wrap">
              <NuxtLink
                v-if="post.user?.id"
                :to="`/club/user/${post.user.id}`"
                class="font-bold text-sm text-neutral-900 hover:text-primary transition-colors no-underline truncate"
              >
                {{ authorName }}
              </NuxtLink>
              <span v-else class="font-bold text-sm text-neutral-900 truncate">
                {{ authorName }}
              </span>
              <svg
                v-if="post.user?.isVerified || post.is_verified_author"
                class="w-4 h-4 text-primary shrink-0"
                viewBox="0 0 24 24" fill="currentColor"
                title="Tasdiqlangan kitobxon"
              >
                <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
              </svg>

              <!-- Attached Book Link next to author name (Matching screenshot) -->
              <template v-if="linkedProduct">
                <span class="text-neutral-400 font-normal select-none">›</span>
                <NuxtLink
                  :to="`/books/${linkedProduct.id}`"
                  class="font-semibold text-sm text-primary hover:underline truncate max-w-[160px] sm:max-w-[260px] inline-block no-underline"
                  :title="linkedProduct.name || linkedProduct.title"
                >
                  {{ linkedProduct.name || linkedProduct.title }}
                </NuxtLink>
              </template>
            </div>
            <div class="flex items-center gap-2 text-xs text-neutral-400">
              <span v-if="post.user?.role_title || post.user?.position">
                {{ post.user?.role_title || post.user?.position }}
              </span>
              <span v-if="post.user?.role_title || post.user?.position">•</span>
              <time :datetime="post.created_at">{{ relativeTime }}</time>
            </div>
          </div>
        </div>

        <!-- Theme / Category Pill -->
        <NuxtLink
          v-if="post.theme?.name"
          :to="`/club?theme=${post.theme.slug || post.theme.id}`"
          class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
        >
          #{{ post.theme.name }}
        </NuxtLink>
      </div>

      <!-- Post Text -->
      <NuxtLink :to="`/club/${post.id}`" class="block text-neutral-800 text-sm sm:text-base leading-relaxed mb-4 hover:text-neutral-950 transition-colors">
        <p :class="[compact ? 'line-clamp-3' : 'line-clamp-6']" class="whitespace-pre-line m-0">
          {{ post.text }}
        </p>
      </NuxtLink>

      <!-- Attached Photos Gallery -->
      <div
        v-if="post.images && post.images.length > 0"
        class="mb-4 rounded-2xl overflow-hidden"
      >
        <!-- Single Image: max height 320px capped -->
        <div
          v-if="post.images.length === 1"
          @click="openGallery(0)"
          class="block max-h-72 sm:max-h-80 w-full bg-neutral-100 overflow-hidden group cursor-pointer rounded-2xl"
        >
          <img
            :src="resolveImageUrl(post.images[0].image)"
            alt="Kitobxon surati"
            class="w-full h-full max-h-72 sm:max-h-80 object-cover group-hover:scale-102 transition-transform duration-300"
            loading="lazy"
          />
        </div>

        <!-- Multiple Images (2 or more) -->
        <div
          v-else
          class="grid gap-2 max-h-72 sm:max-h-80 overflow-hidden rounded-2xl"
          :class="post.images.length === 2 ? 'grid-cols-2 aspect-[16/10]' : 'grid-cols-3 aspect-[16/10]'"
        >
          <div
            v-for="(img, i) in post.images.slice(0, 3)"
            :key="i"
            @click="openGallery(i)"
            class="relative bg-neutral-100 overflow-hidden group rounded-xl cursor-pointer h-full"
          >
            <img
              :src="resolveImageUrl(img.image)"
              alt="Kitobxon surati"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
            <div
              v-if="i === 2 && post.images.length > 3"
              class="absolute inset-0 bg-neutral-900/60 text-white font-bold text-sm flex items-center justify-center backdrop-blur-xs"
            >
              +{{ post.images.length - 3 }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions & Stats Footer -->
    <div class="flex items-center justify-between pt-3 border-t border-neutral-100 text-xs text-neutral-500 mt-2">
      <div class="flex items-center gap-4">
        <!-- Like Button -->
        <button
          type="button"
          @click="toggleLike"
          :class="[
            'inline-flex items-center gap-1.5 transition-colors cursor-pointer border-none bg-transparent p-0 font-medium',
            isLiked ? 'text-rose-600 font-bold' : 'hover:text-rose-600 text-neutral-500'
          ]"
        >
          <svg
            class="w-4 h-4 transition-transform active:scale-125"
            :fill="isLiked ? 'currentColor' : 'none'"
            stroke="currentColor"
            stroke-width="1.8"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
          </svg>
          <span>{{ likesCount }}</span>
        </button>

        <!-- Comments Count & Link -->
        <NuxtLink
          :to="`/club/${post.id}`"
          class="inline-flex items-center gap-1.5 hover:text-primary transition-colors text-neutral-500 font-medium"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
          </svg>
          <span>{{ post.comments_count || 0 }}</span>
        </NuxtLink>
      </div>

      <!-- Share / Copy Link -->
      <button
        type="button"
        @click="copyPostLink"
        class="inline-flex items-center gap-1 text-neutral-400 hover:text-neutral-700 transition-colors border-none bg-transparent cursor-pointer p-0 font-medium"
        :title="copied ? 'Nusxa olindi!' : 'Ulashish'"
      >
        <span v-if="copied" class="text-emerald-600 font-semibold">Nusxa olindi!</span>
        <template v-else>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
          </svg>
          <span>Ulashish</span>
        </template>
      </button>
    </div>

    <!-- Fullscreen Image Lightbox Modal -->
    <ImageGalleryModal
      :is-open="isGalleryOpen"
      :images="galleryImages"
      :initial-index="galleryIndex"
      @close="isGalleryOpen = false"
    />
  </article>
</template>

<script setup lang="ts">
import { resolveImageUrl, resolveProductImage } from '~/utils/productImage'

const props = withDefaults(
  defineProps<{
    post: any
    compact?: boolean
  }>(),
  {
    compact: false
  }
)

const config = useRuntimeConfig()
const authStore = useAuthStore()

// Author details
const authorName = computed(() => {
  const u = props.post?.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

const authorInitials = computed(() => {
  const name = authorName.value.trim()
  const parts = name.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

const authorAvatar = computed(() => {
  const av = props.post?.user?.avatar
  if (!av) return null
  return resolveImageUrl(av)
})

// Time formatting
const relativeTime = computed(() => {
  if (!props.post?.created_at) return ''
  try {
    const diff = (Date.now() - new Date(props.post.created_at).getTime()) / 1000
    if (diff < 60) return 'Hozirgina'
    if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`
    if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} kun oldin`
    return new Date(props.post.created_at).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' })
  } catch {
    return ''
  }
})

// Attached product
const linkedProduct = computed(() => {
  return props.post?.product_snapshot || null
})

// Like handling
const isLiked = ref(props.post?.liked_by_me || false)
const likesCount = ref(props.post?.likes_count || 0)

async function toggleLike() {
  if (!authStore.isAuthenticated) {
    authStore.isAuthModalOpen = true
    return
  }

  isLiked.value = !isLiked.value
  likesCount.value += isLiked.value ? 1 : -1

  try {
    const token = useCookie('kc_token').value
    await $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/like`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: { post_id: props.post.id }
    })
  } catch {
    // Revert on failure
    isLiked.value = !isLiked.value
    likesCount.value += isLiked.value ? 1 : -1
  }
}

// Copy link
const copied = ref(false)
function copyPostLink() {
  if (process.client) {
    const url = `${window.location.origin}/club/${props.post.id}`
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

function formatPrice(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  if (isNaN(num)) return '0'
  return Math.round(num).toLocaleString('uz-UZ').replace(/,/g, ' ')
}

// Lightbox gallery
const isGalleryOpen = ref(false)
const galleryIndex = ref(0)
const galleryImages = computed(() => {
  if (!props.post?.images) return []
  return props.post.images
})

function openGallery(idx: number) {
  galleryIndex.value = idx
  isGalleryOpen.value = true
}
</script>
