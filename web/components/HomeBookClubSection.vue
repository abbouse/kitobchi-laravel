<template>
  <section v-if="posts && posts.length > 0" class="py-6 md:py-8">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Section Header: No badge, no 'all' link, only clean title + subtitle + carousel arrows -->
      <div class="flex items-end justify-between gap-4 mb-4">
        <div>
          <h2 class="font-bold text-xl md:text-2xl tracking-tight text-neutral-900 m-0">
            Kitobxonlar fikrlari va taqrizlari
          </h2>
          <p class="text-xs md:text-sm text-neutral-500 mt-1 m-0">
            O‘quvchilarimiz tomonidan yozilgan samimiy taassurotlar va tavsiyalar
          </p>
        </div>

        <!-- Carousel arrows (Desktop) -->
        <div class="hidden sm:flex items-center gap-1.5 shrink-0">
          <button
            type="button"
            @click="scrollPrev"
            aria-label="Oldingi"
            class="w-8 h-8 rounded-full bg-white hover:bg-neutral-100 text-neutral-700 hover:text-neutral-950 flex items-center justify-center transition-colors cursor-pointer shadow-2xs border-none"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
          </button>
          <button
            type="button"
            @click="scrollNext"
            aria-label="Keyingi"
            class="w-8 h-8 rounded-full bg-white hover:bg-neutral-100 text-neutral-700 hover:text-neutral-950 flex items-center justify-center transition-colors cursor-pointer shadow-2xs border-none"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Uniform Horizontal Carousel: All cards exact same size, text-only with ellipsis -->
      <div
        ref="carouselRef"
        class="flex items-stretch gap-3.5 sm:gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory pb-2 -mx-4 px-4 sm:mx-0 sm:px-0"
      >
        <article
          v-for="post in posts"
          :key="post.id"
          class="shrink-0 w-[280px] sm:w-[320px] md:w-[340px] h-[200px] sm:h-[210px] snap-start bg-white rounded-3xl p-5 shadow-2xs border-none flex flex-col justify-between first:ml-4 sm:first:ml-0"
        >
          <!-- Author Header -->
          <div class="flex items-center justify-between gap-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
              <!-- Avatar linking to Profile -->
              <NuxtLink
                v-if="post.user?.id"
                :to="`/club/user/${post.user.id}`"
                class="shrink-0 no-underline"
              >
                <AppAvatar
                  :src="getAvatar(post)"
                  :name="getName(post)"
                  :user-id="post.user?.id"
                  size="w-9 h-9 text-xs"
                  :badge="post.user?.role_emoji"
                />
              </NuxtLink>
              <div v-else class="shrink-0">
                <AppAvatar
                  :src="getAvatar(post)"
                  :name="getName(post)"
                  :user-id="post.user?.id"
                  size="w-9 h-9 text-xs"
                  :badge="post.user?.role_emoji"
                />
              </div>

              <!-- Name & Time -->
              <div class="min-w-0">
                <div class="flex items-center gap-1 flex-wrap">
                  <NuxtLink
                    v-if="post.user?.id"
                    :to="`/club/user/${post.user.id}`"
                    class="font-bold text-xs sm:text-sm text-neutral-900 hover:text-primary transition-colors no-underline truncate"
                  >
                    {{ getName(post) }}
                  </NuxtLink>
                  <span v-else class="font-bold text-xs sm:text-sm text-neutral-900 truncate">
                    {{ getName(post) }}
                  </span>
                  <svg
                    v-if="post.user?.isVerified || post.is_verified_author"
                    class="w-3.5 h-3.5 text-primary shrink-0"
                    viewBox="0 0 24 24" fill="currentColor"
                  >
                    <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                  </svg>

                  <!-- Attached Book Link next to author name (Matching screenshot) -->
                  <template v-if="post.product || post.product_snapshot">
                    <span class="text-neutral-400 font-normal select-none">›</span>
                    <NuxtLink
                      :to="`/books/${(post.product || post.product_snapshot).id}`"
                      class="font-semibold text-xs text-primary hover:underline truncate max-w-[120px] sm:max-w-[160px] inline-block no-underline"
                      :title="(post.product || post.product_snapshot).name || (post.product || post.product_snapshot).title"
                    >
                      {{ (post.product || post.product_snapshot).name || (post.product || post.product_snapshot).title }}
                    </NuxtLink>
                  </template>
                </div>
                <div class="text-[11px] text-neutral-400">
                  <time :datetime="post.created_at">{{ formatRelativeTime(post.created_at) }}</time>
                </div>
              </div>
            </div>

            <!-- Theme Pill (if any) -->
            <span
              v-if="post.theme?.name"
              class="shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 truncate max-w-[100px]"
            >
              #{{ post.theme.name }}
            </span>
          </div>

          <!-- Post Text: fixed 3-4 lines with ellipsis -->
          <NuxtLink
            :to="`/club/${post.id}`"
            class="block my-2 text-neutral-700 hover:text-neutral-950 text-xs sm:text-sm leading-relaxed no-underline grow overflow-hidden"
          >
            <p class="line-clamp-3 sm:line-clamp-4 m-0">
              {{ post.text }}
            </p>
          </NuxtLink>

          <!-- Card Footer: Stats & Read More Link -->
          <div class="flex items-center justify-between pt-2.5 border-t border-neutral-100 text-xs text-neutral-400">
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center gap-1 font-medium">
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <span>{{ post.likes_count || 0 }}</span>
              </span>
              <span class="inline-flex items-center gap-1 font-medium">
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                </svg>
                <span>{{ post.comments_count || 0 }}</span>
              </span>
            </div>

            <NuxtLink
              :to="`/club/${post.id}`"
              class="font-semibold text-primary hover:text-primary-hover transition-colors no-underline inline-flex items-center gap-0.5 text-[11px]"
            >
              <span>O‘qish</span>
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </NuxtLink>
          </div>
        </article>

        <!-- CTA Card to write / join club: Exact same size with brand gradient -->
        <article
          class="shrink-0 w-[280px] sm:w-[320px] md:w-[340px] h-[200px] sm:h-[210px] snap-start bg-brand-gradient text-white rounded-3xl p-5 shadow-xs flex flex-col justify-between"
        >
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-white text-[11px] font-semibold mb-2 backdrop-blur-xs">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              <span>Kitobxonlar klubi</span>
            </div>
            <h3 class="font-extrabold text-sm sm:text-base text-white m-0 leading-snug">
              Siz ham fikringizni ulashing!
            </h3>
            <p class="text-xs text-white/90 line-clamp-2 mt-1.5 mb-0">
              O‘qigan kitoblaringiz haqida yozing va minglab kitobxonlar davrasiga qo‘shiling.
            </p>
          </div>

          <NuxtLink
            to="/club"
            class="w-full py-2.5 px-4 rounded-full bg-white text-primary font-bold text-xs text-center block no-underline hover:bg-neutral-50 transition-colors shadow-2xs"
          >
            Fikr yozish
          </NuxtLink>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

defineProps<{
  posts: any[]
}>()

const carouselRef = ref<HTMLElement | null>(null)

function scrollPrev() {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: -340, behavior: 'smooth' })
  }
}

function scrollNext() {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: 340, behavior: 'smooth' })
  }
}

function getName(post: any) {
  const u = post?.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
}

function getInitials(post: any) {
  const name = getName(post).trim()
  const parts = name.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

function getAvatar(post: any) {
  const av = post?.user?.avatar
  if (!av) return null
  return resolveImageUrl(av)
}

function formatRelativeTime(dateStr: string) {
  if (!dateStr) return ''
  try {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
    if (diff < 60) return 'Hozirgina'
    if (diff < 3600) return `${Math.floor(diff / 60)}m oldin`
    if (diff < 86400) return `${Math.floor(diff / 3600)}s oldin`
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)}k oldin`
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' })
  } catch {
    return ''
  }
}
</script>
