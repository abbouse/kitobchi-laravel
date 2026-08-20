<template>
  <div class="py-3 md:py-6 min-h-dvh bg-[#f1f1f1] grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Sharhlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-600 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Sharhlarim</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Sharhlaringizni ko'rish uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <ProfileSidebar active="comments" />

        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-neutral-900 max-md:hidden">Sharhlarim</h1>

            <!-- Segment tabs -->
            <div class="flex items-center gap-1 p-1 rounded-xl bg-secondary-100 w-full md:w-auto">
              <button
                type="button"
                @click="switchSegment('posts')"
                :class="segment === 'posts' ? 'bg-white text-primary shadow-sm' : 'text-neutral-500'"
                class="flex-1 md:flex-none px-4 py-1.5 rounded-lg text-sm font-semibold transition-all border-none cursor-pointer"
              >
                Postlarim
              </button>
              <button
                type="button"
                @click="switchSegment('reposts')"
                :class="segment === 'reposts' ? 'bg-white text-primary shadow-sm' : 'text-neutral-500'"
                class="flex-1 md:flex-none px-4 py-1.5 rounded-lg text-sm font-semibold transition-all border-none cursor-pointer"
              >
                Repostlarim
              </button>
            </div>
          </div>

          <!-- Loading skeleton -->
          <div v-if="pending" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="n in 4" :key="n" class="shimmer h-40 w-full rounded-2xl"></div>
          </div>

          <!-- Error -->
          <div v-else-if="loadError" class="text-center py-16">
            <p class="text-sm text-neutral-500 mb-4">Sharhlarni yuklab bo'lmadi. Birozdan so'ng qayta urinib ko'ring.</p>
            <button type="button" @click="fetchPosts(1)" class="px-5 py-2.5 rounded-xl bg-secondary-100 text-sm font-semibold text-neutral-700 border-none cursor-pointer hover:bg-secondary-400 transition-colors">
              Qayta urinish
            </button>
          </div>

          <!-- Empty -->
          <div v-else-if="!posts.length" class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12.375 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.125 12a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
            </div>
            <p class="text-sm text-neutral-500">
              {{ segment === 'posts' ? "Hozircha hech qanday post mavjud emas" : "Hozircha hech qanday repost mavjud emas" }}
            </p>
          </div>

          <!-- Posts grid -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="post in posts" :key="post.id" class="bg-white border border-neutral-100 rounded-2xl p-4 flex flex-col gap-2 shadow-sm">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs text-neutral-400">{{ formatUzDate(post.created_at) || post.formatted_created_at }}</span>
                <span v-if="post.repost" class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full">Repost</span>
              </div>
              <p class="text-sm text-neutral-800 line-clamp-3 whitespace-pre-wrap break-words">{{ post.text || post.content || '' }}</p>
              <div v-if="postImage(post)" class="mt-1 rounded-xl overflow-hidden aspect-square bg-secondary-50 max-w-[120px]">
                <img :src="postImage(post)" class="w-full h-full object-cover" loading="lazy" alt="" />
              </div>
              <div class="mt-auto pt-2 flex items-center gap-4 text-xs text-neutral-400">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                  {{ likesCount(post) }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193a48.03 48.03 0 01-1.653.106l-2.868 2.868a.75.75 0 01-1.28-.53v-2.421a48.578 48.578 0 01-4.256-.322c-1.133-.093-1.98-1.057-1.98-2.193V10.608c0-.97.616-1.813 1.5-2.097M6.75 6.75h10.5"/></svg>
                  {{ commentsCount(post) }}
                </span>
              </div>
            </div>
          </div>

          <button
            v-if="meta && meta.current_page < meta.last_page"
            type="button"
            @click="loadMore"
            :disabled="loadingMore"
            class="w-full mt-4 py-3 rounded-2xl bg-secondary-100 text-neutral-700 text-sm font-semibold hover:bg-secondary-400 transition-colors border-none cursor-pointer disabled:opacity-75"
          >
            {{ loadingMore ? 'Yuklanmoqda...' : 'Yana yuklash' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()

const segment = ref<'posts' | 'reposts'>('posts')
const posts = ref<any[]>([])
const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)
const pending = ref(true)
const loadingMore = ref(false)
const loadError = ref(false)

async function fetchPosts(page = 1) {
  if (page === 1) {
    pending.value = true
    loadError.value = false
  }
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/profile-posts`, {
      query: { segment: segment.value, page, per_page: 12 },
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const data = res?.data || []
    posts.value = page === 1 ? data : [...posts.value, ...data]
    meta.value = res?.meta || null
  } catch (e) {
    loadError.value = true
  } finally {
    pending.value = false
    loadingMore.value = false
  }
}

function switchSegment(seg: 'posts' | 'reposts') {
  if (segment.value === seg) return
  segment.value = seg
  fetchPosts(1)
}

async function loadMore() {
  if (!meta.value || loadingMore.value) return
  loadingMore.value = true
  await fetchPosts(meta.value.current_page + 1)
}

function resolveImg(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.apiBase.replace('/api', '')}/storage/${path}`
}

function postImage(post: any): string | undefined {
  const raw = post.images?.[0]?.url || post.images?.[0]?.image || post.images?.[0] || post.image || null
  if (!raw) return undefined
  return typeof raw === 'string' ? resolveImg(raw) : undefined
}

function likesCount(post: any) {
  return post.likes_count ?? (Array.isArray(post.likes) ? post.likes.length : 0)
}

function commentsCount(post: any) {
  return post.comments_count ?? (Array.isArray(post.comments) ? post.comments.length : 0)
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchPosts(1)
  } else {
    pending.value = false
  }
})

useSeoMeta({ title: 'Sharhlarim — Kitobchi' })
</script>
