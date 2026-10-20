<template>
  <div class="py-6 md:py-8">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Clean Page Header (No huge hero banner) -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight m-0">
            Kitobxonlar Klubi
          </h1>
          <p class="text-xs sm:text-sm text-neutral-500 mt-1 m-0">
            O‘qigan kitoblaringiz haqida samimiy taassurotlar, taqrizlar va muhokamalar
          </p>
        </div>

        <button
          type="button"
          @click="handleCreatePost"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs sm:text-sm transition-all cursor-pointer border-none shadow-2xs shrink-0 self-start sm:self-auto"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          <span>Fikr bildirish</span>
        </button>
      </div>

      <!-- Sleek Responsive Themes / Category Pills -->
      <div class="mb-6">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
          <button
            type="button"
            @click="selectTheme(null)"
            :class="[
              'shrink-0 px-4 py-2 rounded-full text-xs sm:text-sm font-semibold transition-all cursor-pointer border',
              !activeThemeSlug
                ? 'bg-neutral-900 text-white border-neutral-900 shadow-2xs'
                : 'bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-neutral-200/80'
            ]"
          >
            Barchasi
          </button>

          <button
            v-for="t in themes"
            :key="t.id"
            type="button"
            @click="selectTheme(t)"
            :class="[
              'shrink-0 px-3.5 py-2 rounded-full text-xs sm:text-sm font-semibold transition-all cursor-pointer border flex items-center gap-1.5',
              activeThemeSlug === (t.slug || String(t.id))
                ? 'bg-neutral-900 text-white border-neutral-900 shadow-2xs'
                : 'bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-neutral-200/80'
            ]"
          >
            <span>#{{ t.name }}</span>
            <span v-if="t.firework" class="text-xs">🔥</span>
          </button>
        </div>
      </div>

      <!-- MAIN CONTENT: 2 COLUMNS (Feed + Useful Sidebar) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
        <!-- LEFT: FEED (8 cols) -->
        <main class="lg:col-span-8 space-y-4">
          <!-- Active filter banner if selected -->
          <div v-if="activeThemeName" class="flex items-center justify-between px-1 mb-2">
            <div class="flex items-center gap-2 text-xs sm:text-sm text-neutral-600">
              <span>Mavzu:</span>
              <span class="font-bold text-neutral-900">#{{ activeThemeName }}</span>
            </div>
            <button
              type="button"
              @click="selectTheme(null)"
              class="text-xs text-primary hover:text-primary-hover underline bg-transparent border-none cursor-pointer p-0"
            >
              Barcha fikrlarni ko‘rish
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="space-y-4">
            <div
              v-for="n in 3"
              :key="'shimmer-' + n"
              class="bg-white rounded-3xl p-6 border border-neutral-200/60 animate-pulse space-y-4"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-neutral-200"></div>
                <div class="space-y-2 flex-1">
                  <div class="h-3.5 bg-neutral-200 rounded w-1/4"></div>
                  <div class="h-3 bg-neutral-100 rounded w-1/6"></div>
                </div>
              </div>
              <div class="space-y-2">
                <div class="h-3.5 bg-neutral-100 rounded w-full"></div>
                <div class="h-3.5 bg-neutral-100 rounded w-4/5"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="posts.length === 0"
            class="bg-white rounded-3xl p-10 text-center border border-neutral-200/70"
          >
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <h3 class="text-base font-bold text-neutral-900 mb-1">Bu mavzuda hozircha fikrlar yo‘q</h3>
            <p class="text-xs text-neutral-500 max-w-sm mx-auto mb-4">
              Birinchi bo‘lib o‘z taassurotingizni qoldiring!
            </p>
            <button
              type="button"
              @click="handleCreatePost"
              class="px-4 py-2 rounded-full bg-primary text-white font-bold text-xs cursor-pointer border-none hover:bg-primary-hover transition-colors"
            >
              Fikr bildirish
            </button>
          </div>

          <!-- Posts Feed -->
          <div v-else class="space-y-4">
            <BookClubCard
              v-for="post in posts"
              :key="post.id"
              :post="post"
            />
          </div>

          <!-- Load More -->
          <div v-if="hasMore && !isLoading" class="text-center pt-3">
            <button
              type="button"
              @click="loadMore"
              :disabled="isLoadingMore"
              class="px-6 py-2.5 rounded-full bg-white hover:bg-neutral-100 text-neutral-800 font-bold text-xs sm:text-sm border border-neutral-200/80 transition-all cursor-pointer shadow-2xs inline-flex items-center gap-2"
            >
              <svg
                v-if="isLoadingMore"
                class="animate-spin -ml-1 mr-1 h-4 w-4 text-primary"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ isLoadingMore ? 'Yuklanmoqda...' : 'Ko‘proq yuklash' }}</span>
            </button>
          </div>
        </main>

        <!-- RIGHT: SIDEBAR (4 cols) -->
        <aside class="lg:col-span-4 space-y-5">
          <!-- Active Members / Faol kitobxonlar Card -->
          <div v-if="activeMembers.length > 0" class="bg-white rounded-3xl p-5 sm:p-6 border border-neutral-200/70 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-sm sm:text-base text-neutral-900 m-0 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Faol kitobxonlar</span>
              </h3>
            </div>

            <div class="space-y-3">
              <div
                v-for="member in activeMembers.slice(0, 5)"
                :key="member.id"
                class="flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="relative shrink-0">
                    <img
                      v-if="member.avatar"
                      :src="resolveImageUrl(member.avatar)"
                      :alt="member.name"
                      class="w-9 h-9 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                      loading="lazy"
                    />
                    <div
                      v-else
                      class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center"
                    >
                      {{ (member.name || 'K').slice(0, 2).toUpperCase() }}
                    </div>
                    <span
                      v-if="member.role_emoji"
                      class="absolute -bottom-1 -right-1 text-[10px] bg-white rounded-full px-0.5 shadow-2xs"
                    >
                      {{ member.role_emoji }}
                    </span>
                  </div>

                  <div class="min-w-0">
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-xs sm:text-sm text-neutral-900 truncate">
                        {{ member.name }} {{ member.lastname || '' }}
                      </span>
                      <svg
                        v-if="member.isVerified"
                        class="w-3.5 h-3.5 text-primary shrink-0"
                        viewBox="0 0 24 24" fill="currentColor"
                      >
                        <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="text-[11px] text-neutral-400 truncate">
                      {{ member.role_title || member.position || (member.posts_count ? `${member.posts_count} ta fikr` : 'Kitobxon') }}
                    </div>
                  </div>
                </div>

                <span v-if="member.posts_count" class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                  {{ member.posts_count }} post
                </span>
              </div>
            </div>
          </div>

          <!-- Trending Topics / Ommabop mavzular -->
          <div v-if="themes.length > 0" class="bg-white rounded-3xl p-5 sm:p-6 border border-neutral-200/70 shadow-2xs">
            <h3 class="font-bold text-sm sm:text-base text-neutral-900 mb-3 m-0 flex items-center gap-2">
              <span>🔥</span>
              <span>Muhokamadagi mavzular</span>
            </h3>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="t in themes.slice(0, 8)"
                :key="'side-' + t.id"
                type="button"
                @click="selectTheme(t)"
                class="text-xs px-2.5 py-1 rounded-full bg-neutral-50 hover:bg-neutral-100 text-neutral-700 hover:text-neutral-950 border border-neutral-200/70 transition-colors cursor-pointer"
              >
                #{{ t.name }}
              </button>
            </div>
          </div>

          <!-- App banner -->
          <div class="bg-white rounded-3xl p-5 border border-neutral-200/70 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-primary tracking-wider block mb-1">Kitobchi ilovasi</span>
            <h4 class="font-bold text-sm text-neutral-900 mb-1">Mobil ilovada o‘qing</h4>
            <p class="text-xs text-neutral-500 leading-relaxed mb-3">
              Klubda xabarlar almashing, yangi kitoblardan xabardor bo‘ling.
            </p>
            <div class="flex items-center gap-2">
              <a
                href="https://play.google.com/store/apps/details?id=uz.kitobchi.app"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                Google Play
              </a>
              <a
                href="https://apps.apple.com/uz/app/kitobchi/id6474136979"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                App Store
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- Create Post Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs"
      @click.self="showCreateModal = false"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base sm:text-lg font-bold text-neutral-900 m-0">Kitob klubida fikr qoldirish</h3>
          <button
            type="button"
            @click="showCreateModal = false"
            class="p-1 rounded-full text-neutral-400 hover:text-neutral-700 bg-transparent border-none cursor-pointer text-sm"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-neutral-500 mb-4 leading-relaxed">
          O‘qigan kitobingiz haqida o‘z taassurotingiz va tavsiyangizni yozing.
        </p>

        <form @submit.prevent="submitQuickPost" class="space-y-4">
          <div>
            <textarea
              v-model="newPostText"
              rows="4"
              required
              placeholder="Qaysi kitobni o‘qiyapsiz? Fikrlaringiz qanday?.."
              class="w-full rounded-2xl border border-neutral-300 p-3 text-sm focus:outline-hidden focus:border-primary resize-none"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-1">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-full text-neutral-600 text-xs sm:text-sm font-semibold hover:bg-neutral-100 transition-colors border-none bg-transparent cursor-pointer"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="isSubmitting || !newPostText.trim()"
              class="px-5 py-2 rounded-full bg-primary text-white text-xs sm:text-sm font-bold hover:bg-primary-hover disabled:opacity-50 transition-colors border-none cursor-pointer"
            >
              {{ isSubmitting ? 'Yuborilmoqda...' : 'Chop etish' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Active Theme State
const activeThemeSlug = ref<string | null>((route.query.theme as string) || null)

// SSR Fetch: Feed + Themes + Active Members
const { data: initialData } = await useAsyncData('book-club-page', async () => {
  const [feedRes, themesRes, membersRes] = await Promise.all([
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club`, {
      query: {
        per_page: 15,
        theme: activeThemeSlug.value || undefined
      }
    }).catch(() => ({ data: [], meta: {} })),
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/themes`).catch(() => ({ data: [] })),
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/suggested-users`).catch(() => ({ data: [] }))
  ])

  return {
    posts: feedRes?.data || [],
    meta: feedRes?.meta || {},
    themes: themesRes?.data || [],
    activeMembers: membersRes?.data || []
  }
})

const posts = ref<any[]>(initialData.value?.posts || [])
const themes = computed(() => initialData.value?.themes || [])
const activeMembers = computed(() => initialData.value?.activeMembers || [])
const currentPage = ref(initialData.value?.meta?.current_page || 1)
const lastPage = ref(initialData.value?.meta?.last_page || 1)
const hasMore = computed(() => currentPage.value < lastPage.value)

const activeThemeName = computed(() => {
  if (!activeThemeSlug.value) return null
  const found = themes.value.find((t: any) => t.slug === activeThemeSlug.value || String(t.id) === activeThemeSlug.value)
  return found?.name || activeThemeSlug.value
})

const isLoading = ref(false)
const isLoadingMore = ref(false)

async function selectTheme(theme: any | null) {
  if (!theme) {
    activeThemeSlug.value = null
    router.push({ query: {} })
  } else {
    activeThemeSlug.value = theme.slug || String(theme.id)
    router.push({ query: { theme: activeThemeSlug.value } })
  }

  isLoading.value = true
  try {
    const url = activeThemeSlug.value
      ? `${config.public.apiBase}/v1/kitobchi/book_club/themes/${activeThemeSlug.value}/posts`
      : `${config.public.apiBase}/v1/kitobchi/book_club`

    const res: any = await $fetch(url, {
      query: { per_page: 15 }
    })

    posts.value = res?.data || []
    currentPage.value = res?.meta?.current_page || 1
    lastPage.value = res?.meta?.last_page || 1
  } catch {
    posts.value = []
  } finally {
    isLoading.value = false
  }
}

async function loadMore() {
  if (isLoadingMore.value || !hasMore.value) return
  isLoadingMore.value = true

  try {
    const nextPage = currentPage.value + 1
    const url = activeThemeSlug.value
      ? `${config.public.apiBase}/v1/kitobchi/book_club/themes/${activeThemeSlug.value}/posts`
      : `${config.public.apiBase}/v1/kitobchi/book_club`

    const res: any = await $fetch(url, {
      query: { per_page: 15, page: nextPage }
    })

    const newItems = res?.data || []
    posts.value = [...posts.value, ...newItems]
    currentPage.value = nextPage
    lastPage.value = res?.meta?.last_page || lastPage.value
  } finally {
    isLoadingMore.value = false
  }
}

// Create Post Modal
const showCreateModal = ref(false)
const newPostText = ref('')
const isSubmitting = ref(false)

function handleCreatePost() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }
  showCreateModal.value = true
}

async function submitQuickPost() {
  if (!newPostText.value.trim() || isSubmitting.value) return
  isSubmitting.value = true

  try {
    const token = useCookie('kc_token').value
    await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        text: newPostText.value.trim(),
        product_type: 'book'
      }
    })

    showCreateModal.value = false
    newPostText.value = ''
    await selectTheme(null)
  } catch (err: any) {
    alert(err?.data?.message || 'Xatolik yuz berdi, qayta urinib ko‘ring.')
  } finally {
    isSubmitting.value = false
  }
}

useSeoMeta({
  title: 'Kitobxonlar Klubi — Kitob taqrizlari va fikrlar | Kitobchi',
  description: 'Kitobxonlar hamjamiyati: kitoblar bo‘yicha samimiy taqrizlar, baholar, iqtiboslar va jonli muhokamalar. Sevimli asaringiz haqida fikr bildiring.',
  ogTitle: 'Kitobxonlar Klubi — Kitobchi',
  ogDescription: 'Kitobxonlar hamjamiyati: samimiy taqrizlar, tavsiyalar va muhokamalar.',
  ogImage: 'https://kitobchi.com/images/og-image.png',
  ogType: 'website'
})

useHead({
  link: [
    { rel: 'canonical', href: 'https://kitobchi.com/club' }
  ]
})
</script>
