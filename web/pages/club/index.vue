<template>
  <div class="py-6 md:py-10">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- ====== HERO SECTION ====== -->
      <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0b0342] via-[#1a1158] to-[#2a1b7e] text-white p-6 sm:p-8 md:p-10 mb-8 shadow-md">
        <!-- Background decorative ambient circles -->
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-primary/20 blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-indigo-400/20 blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10 max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-semibold text-white/90 mb-4">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Kitobxonlar hamjamiyati
          </div>

          <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight leading-tight text-white mb-3">
            Kitobxonlar Klubi
          </h1>

          <p class="text-sm sm:text-base text-neutral-200 leading-relaxed mb-6 font-normal">
            Sevimli asarlaringiz haqida fikr almashing, yangi taqrizlarni o‘qing va minglab faol kitobxonlar bilan birga mutolaa zavqini his eting.
          </p>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              @click="handleCreatePost"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white text-[#0b0342] font-bold text-sm hover:bg-neutral-100 transition-all cursor-pointer border-none shadow-sm active:scale-95"
            >
              <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              <span>Fikr bildirish</span>
            </button>

            <a
              href="#mavzular"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold text-sm transition-colors backdrop-blur-xs no-underline border border-white/15"
            >
              Mavzular bo‘yicha
            </a>
          </div>
        </div>
      </section>

      <!-- ====== MAIN CONTENT GRID ====== -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- LEFT: FEED (8 cols) -->
        <main class="lg:col-span-8 space-y-6">
          <!-- Filter Tabs / Themes Bar -->
          <div id="mavzular" class="bg-white rounded-2xl p-2.5 border border-neutral-200/70 shadow-2xs flex items-center gap-1.5 overflow-x-auto no-scrollbar">
            <button
              type="button"
              @click="selectTheme(null)"
              :class="[
                'shrink-0 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer border-none',
                !activeThemeSlug
                  ? 'bg-primary text-white shadow-2xs'
                  : 'bg-transparent text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
              ]"
            >
              Barcha fikrlar
            </button>

            <button
              v-for="t in themes"
              :key="t.id"
              type="button"
              @click="selectTheme(t)"
              :class="[
                'shrink-0 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer border-none flex items-center gap-1.5',
                activeThemeSlug === (t.slug || String(t.id))
                  ? 'bg-primary text-white shadow-2xs'
                  : 'bg-transparent text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
              ]"
            >
              <span>#{{ t.name }}</span>
              <span v-if="t.firework" class="text-xs">🔥</span>
            </button>
          </div>

          <!-- Active Filter indicator -->
          <div v-if="activeThemeName" class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2 text-sm text-neutral-600">
              <span>Mavzu:</span>
              <span class="font-bold text-primary">#{{ activeThemeName }}</span>
            </div>
            <button
              type="button"
              @click="selectTheme(null)"
              class="text-xs text-neutral-400 hover:text-neutral-700 underline bg-transparent border-none cursor-pointer"
            >
              Filtrni tozalash
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
                <div class="w-11 h-11 rounded-full bg-neutral-200"></div>
                <div class="space-y-2 flex-1">
                  <div class="h-3.5 bg-neutral-200 rounded w-1/4"></div>
                  <div class="h-3 bg-neutral-100 rounded w-1/6"></div>
                </div>
              </div>
              <div class="space-y-2">
                <div class="h-3.5 bg-neutral-100 rounded w-full"></div>
                <div class="h-3.5 bg-neutral-100 rounded w-5/6"></div>
              </div>
            </div>
          </div>

          <!-- Empty Feed -->
          <div
            v-else-if="posts.length === 0"
            class="bg-white rounded-3xl p-12 text-center border border-neutral-200/70"
          >
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-neutral-900 mb-1">Hozircha fikrlar yo‘q</h3>
            <p class="text-sm text-neutral-500 max-w-sm mx-auto mb-6">
              Bu bo‘limda hali hech kim taqriz qoldirmadi. Birinchi bo‘lib o‘z taassurotingizni ulashing!
            </p>
            <button
              type="button"
              @click="handleCreatePost"
              class="px-5 py-2.5 rounded-full bg-primary text-white font-bold text-sm cursor-pointer border-none hover:bg-primary-hover transition-colors"
            >
              Birinchi bo‘lib yozish
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

          <!-- Load More Button -->
          <div v-if="hasMore && !isLoading" class="text-center pt-4">
            <button
              type="button"
              @click="loadMore"
              :disabled="isLoadingMore"
              class="px-6 py-3 rounded-full bg-white hover:bg-neutral-100 text-neutral-800 font-bold text-sm border border-neutral-200/80 transition-all cursor-pointer shadow-2xs inline-flex items-center gap-2"
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
              <span>{{ isLoadingMore ? 'Yuklanmoqda...' : 'Ko‘proq fikrlarni ko‘rish' }}</span>
            </button>
          </div>
        </main>

        <!-- RIGHT: SIDEBAR (4 cols) -->
        <aside class="lg:col-span-4 space-y-6">
          <!-- Club Guidelines Card -->
          <div class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-2xs">
            <div class="flex items-center gap-2.5 mb-3 text-primary">
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
              </svg>
              <h3 class="font-bold text-base text-neutral-900 m-0">Klub qoidalari</h3>
            </div>
            <ul class="text-xs text-neutral-600 space-y-2.5 list-none p-0 m-0 leading-relaxed">
              <li class="flex items-start gap-2">
                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                <span>O‘qigan kitoblaringiz haqida xolis taassurotlaringizni qoldiring.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                <span>Boshqa kitobxonlarning fikrlariga hurmat bilan yondashing.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                <span>Spam, reklama va haqoratomuz iboralarga ruxsat berilmaydi.</span>
              </li>
            </ul>
          </div>

          <!-- Mobile App Download Banner Card -->
          <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-3xl p-6 border border-primary/15 relative overflow-hidden">
            <span class="text-xs font-bold uppercase tracking-wider text-primary block mb-1">Kitobchi ilovasi</span>
            <h4 class="font-bold text-base text-neutral-900 mb-2">Telefoningizda qulayroq o‘qing</h4>
            <p class="text-xs text-neutral-600 leading-relaxed mb-4">
              Ilovada bildirishnomalar, qulay audio-pleyer va kitobxonlar bilan shaxsiy muloqot imkoniyati mavjud.
            </p>
            <div class="flex items-center gap-2">
              <a
                href="https://play.google.com/store/apps/details?id=uz.kitobchi.app"
                target="_blank"
                rel="noopener"
                class="px-3.5 py-2 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                Google Play
              </a>
              <a
                href="https://apps.apple.com/uz/app/kitobchi/id6474136979"
                target="_blank"
                rel="noopener"
                class="px-3.5 py-2 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                App Store
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- Create Post Modal / Prompt -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs"
      @click.self="showCreateModal = false"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold text-neutral-900">Kitob klubida post qoldirish</h3>
          <button
            type="button"
            @click="showCreateModal = false"
            class="p-1 rounded-full text-neutral-400 hover:text-neutral-700 bg-transparent border-none cursor-pointer"
          >
            ✕
          </button>
        </div>

        <p class="text-sm text-neutral-600 mb-5 leading-relaxed">
          O‘qigan kitobingiz haqida o‘z fikringiz va tavsiyangizni yozing. To‘liq formatlangan rasm va kitob bog‘lash imkoniyati bilan qulay nashr etish uchun mobil ilovadan ham foydalanishingiz mumkin.
        </p>

        <form @submit.prevent="submitQuickPost" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-neutral-700 mb-1">Mavzu / Taqriz matni</label>
            <textarea
              v-model="newPostText"
              rows="4"
              required
              placeholder="Qaysi kitobni o‘qiyapsiz? Fikrlaringiz qanday?.."
              class="w-full rounded-2xl border border-neutral-300 p-3 text-sm focus:outline-hidden focus:border-primary resize-none"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-full text-neutral-600 text-sm font-semibold hover:bg-neutral-100 transition-colors border-none bg-transparent cursor-pointer"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="isSubmitting || !newPostText.trim()"
              class="px-5 py-2 rounded-full bg-primary text-white text-sm font-bold hover:bg-primary-hover disabled:opacity-50 transition-colors border-none cursor-pointer"
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
const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Active Theme State
const activeThemeSlug = ref<string | null>((route.query.theme as string) || null)

// SSR Fetch: Feed + Themes
const { data: initialData, refresh } = await useAsyncData('book-club-page', async () => {
  const [feedRes, themesRes] = await Promise.all([
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club`, {
      query: {
        per_page: 15,
        theme: activeThemeSlug.value || undefined
      }
    }).catch(() => ({ data: [], meta: {} })),
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/themes`).catch(() => ({ data: [] }))
  ])

  return {
    posts: feedRes?.data || [],
    meta: feedRes?.meta || {},
    themes: themesRes?.data || []
  }
})

const posts = ref<any[]>(initialData.value?.posts || [])
const themes = computed(() => initialData.value?.themes || [])
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
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: {
        text: newPostText.value.trim(),
        product_type: 'book'
      }
    })

    showCreateModal.value = false
    newPostText.value = ''
    // Refresh feed
    await selectTheme(null)
  } catch (err: any) {
    alert(err?.data?.message || 'Xatolik yuz berdi, qayta urinib ko‘ring.')
  } finally {
    isSubmitting.value = false
  }
}

// SEO & Structured Data
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
