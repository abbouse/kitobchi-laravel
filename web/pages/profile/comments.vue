<template>
  <main class="max-md:grow h-full md:min-h-dvh bg-[#f1f1f1] lg:bg-white">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
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
            <h1 class="text-xl sm:text-xl text-primary font-bold text-center m-0">Sharhlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>
    
    <div class="py-6 min-h-dvh">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        
        <div class="pb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button @click="$router.back()" type="button" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25 border-none bg-transparent cursor-pointer">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="relative min-w-0">
              <ol class="flex items-center gap-2 p-0 m-0 list-none">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <NuxtLink to="/" class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-medium transition-colors text-[#8F8FA1] text-sm no-underline hover:text-neutral-700">
                    <span class="truncate">Asosiy</span>
                  </NuxtLink>
                </li>
                <li class="flex"><span class="text-neutral-400 text-xs"> / </span></li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <span class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-semibold text-[#8F8FA1] text-sm text-neutral-900">
                    <span class="truncate">Profil</span>
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>

        <div v-if="!authStore.isAuthenticated" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl mt-4 max-w-2xl mx-auto shadow-sm">
          <div class="w-24 h-24 bg-[#F6F6F9] rounded-full flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <h2 class="text-2xl font-bold text-neutral-900 mb-2 m-0">Avtorizatsiya</h2>
          <p class="text-neutral-500 mb-8 max-w-xs text-center m-0">Shaxsiy kabinetga kirish uchun tizimga kiring</p>
          <button @click="authStore.openAuthModal()" class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer">
            Kirish
          </button>
        </div>

        <div v-else class="flex flex-col lg:flex-row gap-5">
          <ProfileSidebar active="comments" />
          
          <div class="w-full">
            <div class="max-md:min-h-dvh flex flex-col max-md:pb-2">
              <div class="p-4 md:p-6 rounded-3xl bg-secondary-50">
                <!-- Tabs: Postlar / Repostlar -->
                <div class="flex items-center gap-2 mb-5">
                  <button
                    type="button"
                    @click="switchSegment('posts')"
                    class="font-medium items-center transition-colors py-1.5 text-sm gap-1.5 h-10 flex justify-center rounded-xl px-4 border-none cursor-pointer"
                    :class="segment === 'posts' ? 'bg-primary text-white' : 'bg-white text-neutral-500 hover:text-neutral-900'"
                  >
                    Postlar
                  </button>
                  <button
                    type="button"
                    @click="switchSegment('reposts')"
                    class="font-medium items-center transition-colors py-1.5 text-sm gap-1.5 h-10 flex justify-center rounded-xl px-4 border-none cursor-pointer"
                    :class="segment === 'reposts' ? 'bg-primary text-white' : 'bg-white text-neutral-500 hover:text-neutral-900'"
                  >
                    Repostlar
                  </button>
                </div>

                <!-- Loading -->
                <div v-if="pending" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div v-for="n in 6" :key="n" class="bg-white rounded-2xl aspect-square animate-pulse"></div>
                </div>

                <!-- Error -->
                <div v-else-if="loadError" class="flex flex-col items-center py-12 text-center">
                  <p class="text-neutral-500 mb-4">Postlarni yuklashda xatolik yuz berdi</p>
                  <button @click="fetchPosts(1)" type="button" class="text-primary font-medium border-none bg-transparent cursor-pointer">Qayta urinish</button>
                </div>

                <!-- Empty -->
                <div v-else-if="posts.length === 0" class="flex flex-col items-center py-16 text-center">
                  <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12.375 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.125 12a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                  </div>
                  <p class="text-neutral-500 font-medium">Postlar yo'q</p>
                </div>

                <!-- Posts grid -->
                <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div
                    v-for="post in posts"
                    :key="post.id"
                    class="bg-white rounded-2xl overflow-hidden aspect-square relative group"
                  >
                    <img
                      v-if="postImage(post)"
                      :src="postImage(post)"
                      :alt="post.title || 'Post'"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center bg-secondary-50 p-3">
                      <p class="text-neutral-600 text-xs text-center line-clamp-4">{{ post.body || post.title }}</p>
                    </div>
                    <!-- Hover overlay -->
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                      <span class="text-white text-sm font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                        {{ likesCount(post) }}
                      </span>
                      <span class="text-white text-sm font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/></svg>
                        {{ commentsCount(post) }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Load more -->
                <div v-if="!pending && meta && meta.current_page < meta.last_page" class="flex justify-center mt-4">
                  <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    type="button"
                    class="font-medium items-center transition-colors py-1.5 text-sm gap-1.5 text-primary bg-primary/10 hover:bg-primary/15 h-10 flex justify-center rounded-xl px-6 border-none cursor-pointer disabled:opacity-60"
                  >
                    {{ loadingMore ? 'Yuklanmoqda...' : 'Ko\'proq ko\'rsatish' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  
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
