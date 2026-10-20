<template>
  <div class="py-6 md:py-10">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumbs & Back -->
      <nav aria-label="Breadcrumb" class="mb-6 flex items-center justify-between">
        <ol class="flex items-center gap-2 text-xs sm:text-sm text-neutral-500 list-none p-0 m-0">
          <li>
            <NuxtLink to="/" class="hover:text-primary transition-colors no-underline">Asosiy</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li>
            <NuxtLink to="/club" class="hover:text-primary transition-colors no-underline">Kitob klubi</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li class="text-neutral-900 font-semibold truncate max-w-[200px]">
            Post #{{ route.params.id }}
          </li>
        </ol>

        <NuxtLink
          to="/club"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-primary transition-colors no-underline px-3 py-1.5 rounded-full bg-white border border-neutral-200/80 shadow-2xs"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
          <span>Barcha fikrlar</span>
        </NuxtLink>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- MAIN POST & COMMENTS (8 cols) -->
        <main class="lg:col-span-8 space-y-6">
          <!-- Post Article Card -->
          <article class="bg-white rounded-3xl p-6 sm:p-8 border border-neutral-200/70 shadow-xs">
            <!-- Author Header -->
            <div class="flex items-center justify-between gap-3 mb-6">
              <div class="flex items-center gap-3.5">
                <!-- Avatar -->
                <div class="relative shrink-0">
                  <img
                    v-if="authorAvatar"
                    :src="authorAvatar"
                    :alt="authorName"
                    class="w-12 h-12 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                  />
                  <div
                    v-else
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/10 to-primary/20 text-primary font-bold text-base flex items-center justify-center border border-primary/10"
                  >
                    {{ authorInitials }}
                  </div>
                  <span
                    v-if="post?.user?.role_emoji"
                    class="absolute -bottom-1 -right-1 text-sm bg-white rounded-full px-0.5 shadow-2xs"
                  >
                    {{ post.user.role_emoji }}
                  </span>
                </div>

                <!-- Info -->
                <div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-base text-neutral-900">
                      {{ authorName }}
                    </span>
                    <svg
                      v-if="post?.user?.isVerified || post?.is_verified_author"
                      class="w-4 h-4 text-primary shrink-0"
                      viewBox="0 0 24 24" fill="currentColor"
                      title="Tasdiqlangan kitobxon"
                    >
                      <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-neutral-400 mt-0.5">
                    <span v-if="post?.user?.role_title || post?.user?.position">
                      {{ post.user?.role_title || post.user?.position }}
                    </span>
                    <span v-if="post?.user?.role_title || post?.user?.position">•</span>
                    <time :datetime="post?.created_at">{{ formattedDate }}</time>
                  </div>
                </div>
              </div>

              <!-- Theme Pill -->
              <NuxtLink
                v-if="post?.theme?.name"
                :to="`/club?theme=${post.theme.slug || post.theme.id}`"
                class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
              >
                #{{ post.theme.name }}
              </NuxtLink>
            </div>

            <!-- Full Post Text -->
            <div class="text-neutral-900 text-base sm:text-lg leading-relaxed whitespace-pre-line mb-6 font-normal">
              {{ post?.text }}
            </div>

            <!-- Images Gallery -->
            <div
              v-if="post?.images && post.images.length > 0"
              class="mb-6 grid gap-3"
              :class="post.images.length === 1 ? 'grid-cols-1' : 'grid-cols-2'"
            >
              <div
                v-for="(img, idx) in post.images"
                :key="idx"
                class="rounded-2xl overflow-hidden bg-neutral-100 border border-neutral-200/60"
              >
                <img
                  :src="resolveImageUrl(img.image)"
                  alt="Post rasmi"
                  class="w-full h-auto object-cover max-h-[500px]"
                  loading="lazy"
                />
              </div>
            </div>

            <!-- Attached Product Showcase -->
            <NuxtLink
              v-if="linkedProduct"
              :to="`/books/${linkedProduct.id}`"
              class="p-3 sm:p-4 rounded-2xl bg-neutral-50/80 hover:bg-neutral-100/90 border border-neutral-200/80 transition-all mb-6 flex items-center justify-between gap-4 no-underline group/link"
            >
              <div class="flex items-center gap-3.5 min-w-0">
                <div class="shrink-0 w-11 h-15 rounded-lg overflow-hidden bg-white border border-neutral-200/80 shadow-2xs">
                  <img
                    :src="resolveProductImage(linkedProduct)"
                    :alt="linkedProduct.name || linkedProduct.title"
                    class="w-full h-full object-cover group-hover/link:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <div class="min-w-0">
                  <span class="text-[10px] uppercase font-bold text-neutral-400 tracking-wider block">Mavzu kitobi</span>
                  <div class="font-bold text-xs sm:text-sm text-neutral-900 group-hover/link:text-primary transition-colors truncate">
                    {{ linkedProduct.name || linkedProduct.title }}
                  </div>
                  <div v-if="linkedProduct.price" class="text-xs sm:text-sm font-extrabold text-primary mt-0.5">
                    {{ formatPrice(linkedProduct.price) }} so‘m
                  </div>
                </div>
              </div>

              <div class="shrink-0 inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs transition-colors shadow-2xs">
                <span>Kitobni ko‘rish</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </div>
            </NuxtLink>

            <!-- Post Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-neutral-100 text-sm">
              <div class="flex items-center gap-5">
                <!-- Like Button -->
                <button
                  type="button"
                  @click="toggleLike"
                  :class="[
                    'inline-flex items-center gap-2 transition-colors cursor-pointer border-none bg-transparent p-0 font-semibold',
                    isLiked ? 'text-rose-600' : 'hover:text-rose-600 text-neutral-500'
                  ]"
                >
                  <svg
                    class="w-5 h-5 transition-transform active:scale-125"
                    :fill="isLiked ? 'currentColor' : 'none'"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                  <span>{{ likesCount }} ta yoqdi</span>
                </button>

                <!-- Comments Count -->
                <div class="inline-flex items-center gap-1.5 text-neutral-500 font-semibold">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                  </svg>
                  <span>{{ comments.length }} ta fikr</span>
                </div>
              </div>

              <!-- Share Button -->
              <button
                type="button"
                @click="sharePost"
                class="inline-flex items-center gap-1.5 text-neutral-500 hover:text-neutral-900 transition-colors border-none bg-transparent cursor-pointer p-0 font-semibold"
              >
                <span v-if="copied" class="text-emerald-600 font-bold">Havola nusxalandi!</span>
                <template v-else>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                  </svg>
                  <span>Ulashish</span>
                </template>
              </button>
            </div>
          </article>

          <!-- ====== COMMENTS THREAD ====== -->
          <section class="bg-white rounded-3xl p-5 sm:p-7 border border-neutral-200/70 shadow-xs">
            <h2 class="text-base sm:text-lg font-bold text-neutral-900 mb-5 flex items-center gap-2">
              <span>Fikrlar va izohlar</span>
              <span v-if="comments.length > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                {{ comments.length }}
              </span>
            </h2>

            <!-- Comment Input Bar -->
            <div class="mb-6">
              <!-- Authenticated: Clean textarea input -->
              <div v-if="authStore.isAuthenticated" class="space-y-2.5">
                <textarea
                  v-model="newCommentText"
                  rows="2"
                  placeholder="O‘z fikringizni bildiring..."
                  class="w-full rounded-2xl border border-neutral-300 p-3 text-sm focus:outline-hidden focus:border-primary resize-none bg-neutral-50/50 focus:bg-white transition-colors"
                ></textarea>
                <div class="flex justify-end">
                  <button
                    type="button"
                    @click="submitComment"
                    :disabled="isSubmittingComment || !newCommentText.trim()"
                    class="px-5 py-2 rounded-full bg-primary hover:bg-primary-hover text-white text-xs font-bold disabled:opacity-50 transition-colors border-none cursor-pointer"
                  >
                    {{ isSubmittingComment ? 'Yuborilmoqda...' : 'Yuborish' }}
                  </button>
                </div>
              </div>

              <!-- Unauthenticated: Sleek single interactive bar (No ugly nested capsule) -->
              <div
                v-else
                @click="authStore.openAuthModal()"
                class="flex items-center gap-3 p-3 rounded-2xl border border-neutral-200/80 hover:border-primary/40 bg-neutral-50/60 hover:bg-neutral-50 cursor-pointer transition-colors"
              >
                <div class="w-8 h-8 rounded-full bg-neutral-200/70 text-neutral-500 font-bold text-xs flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                  </svg>
                </div>
                <span class="text-xs sm:text-sm text-neutral-500 flex-1">Fikr bildirish uchun tizimga kiring...</span>
                <span class="text-xs font-bold text-primary px-3 py-1 rounded-full bg-primary/10">Kirish</span>
              </div>
            </div>

            <!-- Comments List -->
            <div v-if="comments.length > 0" class="space-y-4 divide-y divide-neutral-100">
              <div
                v-for="comment in comments"
                :key="comment.id"
                class="pt-4 first:pt-0"
              >
                <div class="flex items-start gap-3">
                  <!-- Commenter Avatar -->
                  <div class="shrink-0">
                    <img
                      v-if="comment.user?.avatar"
                      :src="resolveImageUrl(comment.user.avatar)"
                      :alt="comment.user?.name || 'Kitobxon'"
                      class="w-9 h-9 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                    />
                    <div
                      v-else
                      class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center"
                    >
                      {{ (comment.user?.name || 'K').slice(0, 2).toUpperCase() }}
                    </div>
                  </div>

                  <!-- Comment Body -->
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="font-bold text-xs sm:text-sm text-neutral-900 truncate">
                        {{ comment.user?.name || 'Kitobxon' }}
                      </span>
                      <svg
                        v-if="comment.user?.isVerified"
                        class="w-3.5 h-3.5 text-primary shrink-0"
                        viewBox="0 0 24 24" fill="currentColor"
                      >
                        <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                      </svg>
                      <time class="text-[11px] text-neutral-400">
                        {{ formatCommentTime(comment.created_at) }}
                      </time>
                    </div>

                    <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed whitespace-pre-line m-0">
                      {{ comment.content }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty Comments: simple subtle message (no box, no nested capsule) -->
            <p v-else class="text-center py-6 text-neutral-400 text-xs m-0">
              Hozircha izohlar yo‘q. Birinchi bo‘lib fikr qoldiring!
            </p>
          </section>
        </main>

        <!-- RIGHT: SIDEBAR (4 cols) -->
        <aside class="lg:col-span-4 space-y-6">
          <!-- Back to Club Banner -->
          <div class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-2xs">
            <h3 class="font-bold text-base text-neutral-900 mb-2">Boshqa muhokamalar</h3>
            <p class="text-xs text-neutral-500 leading-relaxed mb-4">
              Kitobxonlar klubida minglab o‘quvchilar yangi asarlar haqida fikr almashmoqda.
            </p>
            <NuxtLink
              to="/club"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-900 font-bold text-xs transition-colors no-underline"
            >
              Klub lentasiga qaytish
            </NuxtLink>
          </div>

          <!-- Product Details Widget (if attached) -->
          <div
            v-if="linkedProduct"
            class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-2xs text-center"
          >
            <div class="w-24 h-36 mx-auto mb-3 rounded-xl overflow-hidden bg-white border border-neutral-200 shadow-xs">
              <img
                :src="resolveProductImage(linkedProduct)"
                :alt="linkedProduct.name || linkedProduct.title"
                class="w-full h-full object-cover"
              />
            </div>
            <h4 class="font-bold text-sm text-neutral-900 mb-1">
              {{ linkedProduct.name || linkedProduct.title }}
            </h4>
            <div v-if="linkedProduct.price" class="text-sm font-extrabold text-primary mb-4">
              {{ formatPrice(linkedProduct.price) }} so‘m
            </div>
            <NuxtLink
              :to="`/books/${linkedProduct.id}`"
              class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs transition-colors no-underline"
            >
              Kitobni sotib olish
            </NuxtLink>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { resolveImageUrl, resolveProductImage } from '~/utils/productImage'

const route = useRoute()
const config = useRuntimeConfig()
const authStore = useAuthStore()
const postId = route.params.id

// Fetch Post & Comments in parallel
const { data: pageData } = await useAsyncData(`book-club-post-${postId}`, async () => {
  try {
    const [postRes, commentsRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/posts/${postId}`).catch(() => null),
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${postId}`).catch(() => ({ data: [] }))
    ])

    return {
      post: postRes?.data || null,
      comments: commentsRes?.data || []
    }
  } catch {
    return { post: null, comments: [] }
  }
})

const post = ref<any>(pageData.value?.post)
const comments = ref<any[]>(pageData.value?.comments || [])

if (!post.value) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Post topilmadi yoki o‘chirilgan',
    fatal: true
  })
}

// Author details
const authorName = computed(() => {
  const u = post.value?.user
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
  const av = post.value?.user?.avatar
  if (!av) return null
  return resolveImageUrl(av)
})

const linkedProduct = computed(() => post.value?.product_snapshot || null)

// Date formatting
const formattedDate = computed(() => {
  if (!post.value?.created_at) return ''
  try {
    return new Date(post.value.created_at).toLocaleDateString('uz-UZ', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return ''
  }
})

function formatCommentTime(dateStr: string) {
  if (!dateStr) return ''
  try {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
    if (diff < 60) return 'Hozirgina'
    if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`
    if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' })
  } catch {
    return ''
  }
}

// Like handling
const isLiked = ref(post.value?.liked_by_me || false)
const likesCount = ref(post.value?.likes_count || 0)

async function toggleLike() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  isLiked.value = !isLiked.value
  likesCount.value += isLiked.value ? 1 : -1

  try {
    const token = useCookie('kc_token').value
    await $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/like`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: { post_id: post.value.id }
    })
  } catch {
    // Revert
    isLiked.value = !isLiked.value
    likesCount.value += isLiked.value ? 1 : -1
  }
}

// Share post
const copied = ref(false)
function sharePost() {
  if (process.client) {
    const url = window.location.href
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

// Comment Submission
const newCommentText = ref('')
const isSubmittingComment = ref(false)

async function submitComment() {
  if (!newCommentText.value.trim() || isSubmittingComment.value) return
  isSubmittingComment.value = true

  try {
    const token = useCookie('kc_token').value
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        post_id: post.value.id,
        content: newCommentText.value.trim()
      }
    })

    // Fetch fresh comments
    const updated: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${post.value.id}`)
    comments.value = updated?.data || []
    newCommentText.value = ''
  } catch (err: any) {
    alert(err?.data?.message || 'Xatolik yuz berdi')
  } finally {
    isSubmittingComment.value = false
  }
}

function formatPrice(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  if (isNaN(num)) return '0'
  return Math.round(num).toLocaleString('uz-UZ').replace(/,/g, ' ')
}

// Rich SEO Metadata
const postSnippet = computed(() => {
  const t = post.value?.text || ''
  return t.length > 150 ? t.slice(0, 147) + '...' : t
})

const pageTitle = computed(() => {
  return `${authorName.value}: "${postSnippet.value.slice(0, 50)}" — Kitobxonlar Klubi | Kitobchi`
})

const shareImage = computed(() => {
  if (post.value?.images && post.value.images[0]) {
    return resolveImageUrl(post.value.images[0].image)
  }
  if (linkedProduct.value) {
    return resolveProductImage(linkedProduct.value)
  }
  return 'https://kitobchi.com/images/og-image.png'
})

useSeoMeta({
  title: pageTitle.value,
  description: postSnippet.value,
  ogTitle: pageTitle.value,
  ogDescription: postSnippet.value,
  ogImage: shareImage.value,
  ogType: 'article'
})

// Schema.org DiscussionForumPosting JSON-LD Structured Data
const jsonLd = computed(() => {
  const schema: any = {
    '@context': 'https://schema.org',
    '@type': 'DiscussionForumPosting',
    '@id': `https://kitobchi.com/club/${post.value?.id}`,
    headline: postSnippet.value.slice(0, 100),
    articleBody: post.value?.text || '',
    datePublished: post.value?.created_at,
    author: {
      '@type': 'Person',
      name: authorName.value,
      image: authorAvatar.value || undefined
    },
    interactionStatistic: [
      {
        '@type': 'InteractionCounter',
        interactionType: 'https://schema.org/LikeAction',
        userInteractionCount: likesCount.value
      },
      {
        '@type': 'InteractionCounter',
        interactionType: 'https://schema.org/CommentAction',
        userInteractionCount: comments.value.length
      }
    ],
    comment: comments.value.map((c: any) => ({
      '@type': 'Comment',
      text: c.content,
      dateCreated: c.created_at,
      author: {
        '@type': 'Person',
        name: c.user?.name || 'Kitobxon'
      }
    }))
  }

  if (linkedProduct.value) {
    schema.about = {
      '@type': 'Book',
      name: linkedProduct.value.name || linkedProduct.value.title,
      url: `https://kitobchi.com/books/${linkedProduct.value.id}`
    }
  }

  return schema
})

useHead({
  link: [
    { rel: 'canonical', href: `https://kitobchi.com/club/${postId}` }
  ],
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify(jsonLd.value)
    }
  ]
})
</script>
