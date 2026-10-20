<template>
  <div class="py-4 sm:py-6 md:py-8 pb-16">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumbs & Navigation -->
      <nav aria-label="Breadcrumb" class="mb-4 sm:mb-6 flex items-center justify-between">
        <ol class="flex items-center gap-2 text-xs sm:text-sm text-neutral-500 list-none p-0 m-0">
          <li>
            <NuxtLink to="/" class="hover:text-primary transition-colors no-underline">Asosiy</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li>
            <NuxtLink to="/club" class="hover:text-primary transition-colors no-underline">Kitob klubi</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li class="text-neutral-900 font-semibold truncate max-w-[180px] sm:max-w-[240px]">
            {{ userName }}
          </li>
        </ol>

        <NuxtLink
          to="/club"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-primary transition-colors no-underline px-3 py-1.5 rounded-full bg-white border border-neutral-200/80 shadow-2xs"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
          <span>Klub lentasi</span>
        </NuxtLink>
      </nav>

      <!-- Loading State -->
      <div v-if="pending && !profileUser" class="space-y-4 max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-neutral-200/70 shadow-xs animate-pulse flex items-start justify-between gap-4">
          <div class="space-y-3 flex-1">
            <div class="h-6 bg-neutral-200 rounded w-1/3"></div>
            <div class="h-4 bg-neutral-100 rounded w-1/4"></div>
            <div class="h-10 bg-neutral-100 rounded-2xl w-2/3 mt-4"></div>
          </div>
          <div class="w-20 h-20 rounded-full bg-neutral-200 shrink-0"></div>
        </div>
      </div>

      <!-- Main Profile Layout -->
      <div v-else-if="profileUser" class="max-w-3xl mx-auto space-y-6">
        <!-- USER PROFILE HERO CARD (Matching App Screenshot) -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-neutral-200/70 shadow-xs">
          <!-- Top Row: Name & Bio bubble on left, Big Avatar on right -->
          <div class="flex items-start justify-between gap-4 sm:gap-6 mb-4">
            <div class="min-w-0 flex-1">
              <!-- Name & Verified Check -->
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <h1 class="text-xl sm:text-2xl font-extrabold text-neutral-900 tracking-tight m-0">
                  {{ userName }}
                </h1>
                <svg
                  v-if="profileUser.isVerified"
                  class="w-5 h-5 text-primary shrink-0"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  title="Tasdiqlangan kitobxon"
                >
                  <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                </svg>
              </div>

              <!-- Role subtitle -->
              <div class="flex items-center gap-1.5 text-xs sm:text-sm text-neutral-500 mb-3">
                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <span>{{ userRoleTitle }}</span>
              </div>

              <!-- Bio Speech Bubble (Matching purple/pink pill in screenshot) -->
              <div v-if="profileUser.bio" class="relative inline-block my-1 max-w-md">
                <div class="px-4 py-2 rounded-2xl rounded-tr-none bg-[#f3e6f7] text-[#4a154b] text-xs sm:text-sm font-medium leading-relaxed shadow-2xs">
                  {{ profileUser.bio }}
                </div>
              </div>
            </div>

            <!-- Large User Avatar (With Telegram Gradient fallback) -->
            <AppAvatar
              :src="profileUser.avatar"
              :name="userName"
              :user-id="profileUser.id"
              size="w-20 h-20 sm:w-24 sm:h-24 text-2xl sm:text-3xl"
              :badge="profileUser.role_emoji"
            />
          </div>

          <!-- Followers count -->
          <div class="text-xs sm:text-sm text-neutral-500 mb-4 font-medium">
            Obunachilar: <span class="font-bold text-neutral-800">{{ followersCount }}</span>
          </div>

          <!-- Action Buttons: Follow + Share -->
          <div class="flex items-center gap-3">
            <!-- Follow / Unfollow button -->
            <button
              v-if="!profileUser.is_me"
              type="button"
              @click="toggleFollow"
              :disabled="isFollowLoading"
              :class="[
                'flex-1 py-2.5 px-5 rounded-2xl font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 border shadow-2xs',
                isFollowing
                  ? 'bg-neutral-100 hover:bg-neutral-200/80 text-neutral-700 border-neutral-200'
                  : 'bg-primary hover:bg-primary-hover text-white border-primary'
              ]"
            >
              <svg v-if="isFollowLoading" class="animate-spin w-4 h-4 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <template v-else>
                <svg v-if="!isFollowing" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span>{{ isFollowing ? 'Obunani bekor qilish' : 'Obuna bo‘lish' }}</span>
              </template>
            </button>

            <!-- Current user's own profile badge -->
            <div
              v-else
              class="flex-1 py-2.5 px-4 rounded-2xl bg-neutral-100 text-neutral-700 font-bold text-xs sm:text-sm text-center border border-neutral-200"
            >
              Mening profilim
            </div>

            <!-- Share Profile Button -->
            <button
              type="button"
              @click="shareProfile"
              class="w-11 h-11 rounded-2xl bg-neutral-100 hover:bg-neutral-200/80 text-neutral-700 flex items-center justify-center transition-colors border border-neutral-200/80 cursor-pointer shadow-2xs shrink-0"
              :title="copied ? 'Nusxa olindi!' : 'Profilni ulashish'"
            >
              <span v-if="copied" class="text-xs text-emerald-600 font-bold">✓</span>
              <svg v-else class="w-4 h-4 transform rotate-45 -mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
              </svg>
            </button>
          </div>
        </div>

        <!-- TABS (Matching Screenshot: Postlar 1, Repostlar 0) -->
        <div class="bg-neutral-100/80 p-1 rounded-2xl flex items-center gap-1 border border-neutral-200/60">
          <button
            type="button"
            @click="activeTab = 'posts'"
            :class="[
              'flex-1 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all border-none cursor-pointer text-center',
              activeTab === 'posts'
                ? 'bg-white text-neutral-900 shadow-2xs'
                : 'bg-transparent text-neutral-500 hover:text-neutral-900'
            ]"
          >
            Postlar {{ posts.length }}
          </button>
          <button
            type="button"
            @click="activeTab = 'reposts'"
            :class="[
              'flex-1 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all border-none cursor-pointer text-center',
              activeTab === 'reposts'
                ? 'bg-white text-neutral-900 shadow-2xs'
                : 'bg-transparent text-neutral-500 hover:text-neutral-900'
            ]"
          >
            Repostlar {{ reposts.length }}
          </button>
        </div>

        <!-- TAB CONTENT: POSTS / REPOSTS FEED -->
        <div class="space-y-4">
          <!-- Posts Tab -->
          <template v-if="activeTab === 'posts'">
            <div v-if="posts.length > 0" class="space-y-4">
              <BookClubCard
                v-for="post in posts"
                :key="post.id"
                :post="post"
              />
            </div>
            <div
              v-else
              class="bg-white rounded-3xl p-10 text-center border border-neutral-200/70"
            >
              <p class="text-xs sm:text-sm text-neutral-400 m-0">
                Ushbu kitobxonda hozircha chop etilgan postlar yo‘q.
              </p>
            </div>
          </template>

          <!-- Reposts Tab -->
          <template v-else>
            <div v-if="reposts.length > 0" class="space-y-4">
              <BookClubCard
                v-for="post in reposts"
                :key="post.id"
                :post="post"
              />
            </div>
            <div
              v-else
              class="bg-white rounded-3xl p-10 text-center border border-neutral-200/70"
            >
              <p class="text-xs sm:text-sm text-neutral-400 m-0">
                Ushbu kitobxonda hozircha repostlar yo‘q.
              </p>
            </div>
          </template>
        </div>
      </div>

      <!-- User Not Found State -->
      <div v-else class="max-w-md mx-auto text-center py-16">
        <h2 class="text-lg font-bold text-neutral-900 mb-2">Foydalanuvchi topilmadi</h2>
        <p class="text-xs text-neutral-500 mb-4">
          Ushbu profil mavjud emas yoki o‘chirilgan bo‘lishi mumkin.
        </p>
        <NuxtLink
          to="/club"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-white font-bold text-xs no-underline hover:bg-primary-hover transition-colors"
        >
          Klub lentasiga qaytish
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const authStore = useAuthStore()

const userId = route.params.id as string

// Fetch profile & posts
const { data: profileData, pending } = await useAsyncData(`book-club-profile-${userId}`, async () => {
  try {
    const token = useCookie('kc_token').value
    const headers: Record<string, string> = {}
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/get-profile`, {
      headers,
      query: { user_id: userId }
    })

    return res?.data || null
  } catch {
    return null
  }
})

const profileUser = ref<any>(profileData.value?.user || null)
const posts = ref<any[]>(profileData.value?.posts || [])
const reposts = ref<any[]>(profileData.value?.reposts || [])

const activeTab = ref<'posts' | 'reposts'>('posts')

// User Display Name
const userName = computed(() => {
  const u = profileUser.value
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

const userRoleTitle = computed(() => {
  const u = profileUser.value
  if (!u) return 'Kitobxon'
  if (u.role_title) return u.role_title
  if (u.position) return u.position
  if (u.staff_role) return 'Основатель • Kitobchi'
  return 'Kitobxon'
})

// Follow state
const isFollowing = ref(profileUser.value?.is_following || false)
const followersCount = ref(profileUser.value?.followers_count || 0)
const isFollowLoading = ref(false)

async function toggleFollow() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  if (isFollowLoading.value || !profileUser.value) return
  isFollowLoading.value = true

  const prevFollowing = isFollowing.value
  const prevCount = followersCount.value

  // Optimistic update
  isFollowing.value = !prevFollowing
  followersCount.value = isFollowing.value ? prevCount + 1 : Math.max(0, prevCount - 1)

  try {
    const token = useCookie('kc_token').value
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/follow`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: { user_id: profileUser.value.id }
    })

    if (res?.follow_status) {
      isFollowing.value = res.follow_status === 'followed'
      followersCount.value = res.followers_count ?? followersCount.value
    }
  } catch (err: any) {
    // Revert
    isFollowing.value = prevFollowing
    followersCount.value = prevCount
    alert(err?.data?.message || 'Xatolik yuz berdi')
  } finally {
    isFollowLoading.value = false
  }
}

// Share profile
const copied = ref(false)
function shareProfile() {
  if (process.client) {
    const url = window.location.href
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

// SEO
useSeoMeta({
  title: `${userName.value} — Kitobxon profili | Kitobchi`,
  description: profileUser.value?.bio || `${userName.value}ning Kitobxonlar klubidagi fikrlari va taqrizlari`,
  ogTitle: `${userName.value} — Kitobxon profili`,
  ogDescription: profileUser.value?.bio || `${userName.value}ning taqrizlari va fikrlari`,
  ogType: 'profile'
})

useHead({
  link: [
    { rel: 'canonical', href: `https://kitobchi.com/club/user/${userId}` }
  ]
})
</script>
