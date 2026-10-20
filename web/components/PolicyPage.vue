<template>
  <div class="py-6 md:py-12">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 md:mb-8 overflow-x-auto whitespace-nowrap py-1" aria-label="Breadcrumb">
        <NuxtLink to="/" class="hover:text-primary transition-colors">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <NuxtLink to="/legal" class="hover:text-primary transition-colors">Huquqiy hujjatlar</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">{{ policy?.title || title }}</span>
      </nav>

      <!-- Yuklanmoqda Skeleton -->
      <div v-if="pending" class="bg-white rounded-3xl p-6 sm:p-10 md:p-12 border border-neutral-200/70 shadow-xs max-w-4xl mx-auto animate-pulse space-y-4">
        <div class="h-6 bg-neutral-100 rounded-full w-28 mb-4"></div>
        <div class="h-10 bg-neutral-100 rounded-xl w-3/4"></div>
        <div class="h-4 bg-neutral-100 rounded-lg w-1/3 mb-8"></div>
        <div class="space-y-3 pt-6 border-t border-neutral-100">
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
          <div class="h-4 bg-neutral-100 rounded w-4/5"></div>
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
          <div class="h-4 bg-neutral-100 rounded w-2/3"></div>
        </div>
      </div>

      <!-- Kontent hali qo'shilmagan (Not Found) -->
      <div v-else-if="notFound" class="bg-white rounded-3xl p-12 text-center border border-neutral-200/70 shadow-xs max-w-lg mx-auto my-12">
        <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-neutral-800 mb-2">{{ title }}</h1>
        <p class="text-sm text-neutral-500 mb-6">Ushbu sahifa mazmuni boshqaruv panelida hali kiritilmagan yoki vaqtincha faol emas.</p>
        <NuxtLink to="/legal" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-neutral-900 text-white text-sm font-semibold hover:bg-neutral-800 transition-colors">
          Barcha hujjatlarga qaytish
        </NuxtLink>
      </div>

      <!-- Asosiy Hujjat Sahifasi -->
      <div v-else-if="policy" class="space-y-8">
        <!-- Main Body: 2 Columns on Desktop (Content + Sticky Table of Contents) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          <!-- Main Content (8 cols on lg) -->
          <div class="lg:col-span-8 bg-white rounded-3xl p-6 sm:p-10 md:p-12 border border-neutral-200/70 shadow-xs">
            
            <!-- Document Header Inside Card (No extra hero card) -->
            <div class="mb-8 pb-6 border-b border-neutral-100">
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                  <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>Rasmiy huquqiy hujjat</span>
                  </div>
                  <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-neutral-900 tracking-tight leading-tight mb-3">
                    {{ policy.title }}
                  </h1>
                </div>

                <!-- Action buttons (Print & Copy) -->
                <div class="flex items-center gap-2 shrink-0 print:hidden">
                  <button
                    type="button"
                    @click="copyPageUrl"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-neutral-200/80 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                    :title="copied ? 'Nusxa olindi!' : 'Havoladan nusxa olish'"
                  >
                    <svg v-if="!copied" class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                    <svg v-else class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span>{{ copied ? 'Nusxa olindi!' : 'Ulashish' }}</span>
                  </button>

                  <button
                    type="button"
                    @click="printPage"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-neutral-200/80 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 text-xs font-semibold transition-all cursor-pointer shadow-2xs"
                    title="Hujjatni chop etish"
                  >
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656l10.5 0Z" />
                    </svg>
                    <span>Chop etish</span>
                  </button>
                </div>
              </div>

              <!-- Meta info row -->
              <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs text-neutral-500 mt-3">
                <span v-if="policy.updated_at" class="inline-flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                  </svg>
                  <span>Tahrir: {{ formatDate(policy.updated_at) }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                  <span>{{ readingTime }} daqiqalik mutolaa</span>
                </span>
                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-semibold">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                  <span>Amalda</span>
                </span>
              </div>
            </div>

            <!-- Mobile Table of Contents Dropdown -->
            <div v-if="headings.length > 2" class="lg:hidden mb-8 p-4 rounded-2xl bg-neutral-50 border border-neutral-200/70 print:hidden">
              <button
                type="button"
                @click="mobileTocOpen = !mobileTocOpen"
                class="w-full flex items-center justify-between text-left text-sm font-bold text-neutral-900"
              >
                <span class="inline-flex items-center gap-2">
                  <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                  </svg>
                  <span>Mundarija ({{ headings.length }} ta bo‘lim)</span>
                </span>
                <svg
                  class="w-4 h-4 text-neutral-400 transform transition-transform"
                  :class="mobileTocOpen ? 'rotate-180' : ''"
                  fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
              <div v-show="mobileTocOpen" class="mt-3 pt-3 border-t border-neutral-200/70 space-y-1.5 max-h-64 overflow-y-auto">
                <a
                  v-for="(h, idx) in headings"
                  :key="idx"
                  :href="`#${h.id}`"
                  @click="mobileTocOpen = false"
                  class="block text-xs py-1.5 px-2 rounded-lg text-neutral-600 hover:text-primary hover:bg-neutral-100/80 transition-colors truncate"
                >
                  {{ h.text }}
                </a>
              </div>
            </div>

            <!-- Parsed HTML Content with custom styles -->
            <div
              ref="contentContainer"
              class="kb-prose leading-relaxed text-neutral-700 text-sm sm:text-base space-y-4"
              v-html="enrichedContent"
            ></div>

            <!-- End of Document Seal & Legal Notice -->
            <div class="mt-12 pt-8 border-t border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-neutral-400 print:hidden">
              <div>
                <p class="font-semibold text-neutral-600">Kitobchi platformasi</p>
                <p>«Kitobchi» elektron savdo tizimi ma'muriyati</p>
              </div>
              <div class="flex items-center gap-3">
                <NuxtLink to="/legal" class="text-primary font-semibold hover:underline">
                  ← Barcha hujjatlar
                </NuxtLink>
                <a href="#top" class="hover:text-neutral-600 transition-colors inline-flex items-center gap-1">
                  <span>Yuqoriga</span>
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <!-- Sticky Sidebar on Desktop (4 cols on lg) -->
          <div class="hidden lg:block lg:col-span-4 sticky top-20 print:hidden space-y-6">
            <!-- Table of Contents Card -->
            <div v-if="headings.length > 2" class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-xs">
              <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-neutral-400 mb-4 pb-3 border-b border-neutral-100">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span>Hujjat mundarijasi</span>
              </div>
              <nav class="space-y-1 max-h-[calc(100vh-260px)] overflow-y-auto pr-1">
                <a
                  v-for="(h, idx) in headings"
                  :key="idx"
                  :href="`#${h.id}`"
                  :class="[
                    'block text-xs py-1.5 px-2.5 rounded-xl transition-all leading-snug truncate',
                    activeHeadingId === h.id
                      ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                      : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
                  ]"
                >
                  {{ h.text }}
                </a>
              </nav>
            </div>

            <!-- Help / Question Card: Brand Gradient -->
            <div class="bg-brand-gradient text-white rounded-3xl p-6 shadow-md">
              <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center mb-3.5 backdrop-blur-xs">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
              </div>
              <h3 class="text-base font-bold mb-1 text-white">Savolingiz bormi?</h3>
              <p class="text-xs text-white/85 leading-relaxed mb-4">
                Hujjat shartlari bo‘yicha tushunarsiz joylar bo‘lsa, mutaxassisimiz sizga tushuntirish beradi.
              </p>
              <NuxtLink
                to="/contacts"
                class="inline-flex items-center justify-center w-full h-11 px-4 rounded-2xl bg-white text-primary text-xs font-bold hover:bg-white/95 transition-all shadow-xs no-underline"
              >
                Biz bilan bog‘lanish
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Other Policies Navigation (Bottom) -->
        <div v-if="otherPolicies.length > 0" class="mt-12 pt-8 print:hidden">
          <h2 class="text-lg font-bold text-neutral-900 mb-4">Boshqa huquqiy hujjatlar</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <NuxtLink
              v-for="other in otherPolicies"
              :key="other.slug"
              :to="`/legal/${other.slug}`"
              class="group bg-white rounded-2xl p-5 border border-neutral-200/70 hover:border-primary/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between"
            >
              <div>
                <h3 class="text-sm font-bold text-neutral-900 group-hover:text-primary transition-colors leading-snug mb-1">
                  {{ other.title }}
                </h3>
              </div>
              <div class="flex items-center justify-between text-xs text-neutral-400 pt-3 border-t border-neutral-100 mt-3">
                <span>Batafsil</span>
                <svg class="w-3.5 h-3.5 text-primary group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
              </div>
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  slug: string
  title: string
}>()

const { policy, pending, notFound } = usePolicy(toRef(props, 'slug'))
const config = useRuntimeConfig()

// Boshqa hujjatlar ro'yxati
const { data: allDocsData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/legal`, {
  key: 'legal-all-docs-for-sidebar',
  lazy: true
})

const otherPolicies = computed(() => {
  if (allDocsData.value?.status !== 'success' || !allDocsData.value?.data) return []
  return allDocsData.value.data.filter((item: any) => item.slug !== props.slug).slice(0, 3)
})

// O'qish vaqti
const readingTime = computed(() => {
  if (!policy.value?.content) return 2
  const plainText = policy.value.content.replace(/<[^>]+>/g, '')
  const words = plainText.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 180))
})

// Table of Contents (Mundarija) avtomatik ajratish
const headings = ref<{ id: string; text: string }[]>([])
const activeHeadingId = ref<string>('')
const mobileTocOpen = ref(false)

const enrichedContent = computed(() => {
  if (!policy.value?.content) return ''
  let html = policy.value.content
  const parsed: { id: string; text: string }[] = []

  let counter = 0
  // h2 teglarni id bilan boyitish
  html = html.replace(/<h2([^>]*)>(.*?)<\/h2>/gi, (match, attrs, content) => {
    counter++
    const textOnly = content.replace(/<[^>]+>/g, '').trim()
    const id = `section-${counter}`
    parsed.push({ id, text: textOnly })
    return `<h2 id="${id}" class="scroll-mt-24"${attrs}>${content}</h2>`
  })

  headings.value = parsed
  return html
})

// Scroll Spy: qaysi bo'limda turganini aniqlash
onMounted(() => {
  if (process.client && window.IntersectionObserver) {
    const observer = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          activeHeadingId.value = entry.target.id
        }
      }
    }, { rootMargin: '-80px 0px -60% 0px' })

    nextTick(() => {
      document.querySelectorAll('.kb-prose h2[id]').forEach((el) => {
        observer.observe(el)
      })
    })
  }
})

// Nusxa olish & Chop etish
const copied = ref(false)
function copyPageUrl() {
  if (process.client) {
    navigator.clipboard.writeText(window.location.href)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

function printPage() {
  if (process.client) {
    window.print()
  }
}

function formatDate(iso: string) {
  try {
    return new Date(iso).toLocaleDateString('uz-UZ', { year: 'numeric', month: 'long', day: 'numeric' })
  } catch {
    return '20.10.2026'
  }
}

defineExpose({ policy, pending, notFound })
</script>
