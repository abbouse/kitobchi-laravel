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
      <div v-if="pending" class="bg-white rounded-3xl p-6 sm:p-10 md:p-12 max-w-4xl animate-pulse space-y-4">
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
      <div v-else-if="notFound" class="bg-white rounded-3xl p-12 text-center max-w-lg mx-auto my-12">
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
      <div v-else-if="policy">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
          <article class="lg:col-span-8 bg-white rounded-3xl p-5 sm:p-10 md:p-12">
            <header class="flex items-start justify-between gap-4 mb-6">
              <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-neutral-900 tracking-tight leading-tight m-0">
                {{ policy.title }}
              </h1>
              <div class="flex items-center gap-1.5 shrink-0 print:hidden">
                <button
                  type="button"
                  @click="copyPageUrl"
                  class="w-10 h-10 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-700 flex items-center justify-center border-none cursor-pointer transition-colors"
                  :aria-label="copied ? 'Havola nusxalandi' : 'Havolani nusxalash'"
                  :title="copied ? 'Havola nusxalandi' : 'Havolani nusxalash'"
                >
                  <svg v-if="!copied" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                  </svg>
                  <svg v-else class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                  </svg>
                </button>
                <button
                  type="button"
                  @click="printPage"
                  class="w-10 h-10 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-700 flex items-center justify-center border-none cursor-pointer transition-colors"
                  aria-label="Chop etish yoki PDF saqlash"
                  title="Chop etish yoki PDF saqlash"
                >
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                  </svg>
                </button>
              </div>
            </header>

            <!-- Mobil mundarija -->
            <details v-if="headings.length > 2" class="lg:hidden mb-6 rounded-2xl bg-neutral-50 print:hidden group">
              <summary class="list-none flex items-center justify-between cursor-pointer px-4 py-3 text-sm font-semibold text-neutral-900">
                <span>Mundarija · {{ headings.length }} bo‘lim</span>
                <svg class="w-4 h-4 text-neutral-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </summary>
              <nav class="px-2 pb-3 max-h-72 overflow-y-auto" aria-label="Mundarija">
                <a
                  v-for="h in headings"
                  :key="h.id"
                  :href="`#${h.id}`"
                  class="block text-sm py-2 px-2 rounded-lg text-neutral-700 hover:bg-white hover:text-neutral-900 no-underline"
                >{{ h.text }}</a>
              </nav>
            </details>

            <div
              ref="contentContainer"
              class="kb-prose leading-relaxed text-neutral-700 text-[15px] sm:text-base max-w-[72ch]"
              v-html="enrichedContent"
            ></div>

            <footer class="mt-10 pt-6 border-t border-neutral-100 flex flex-wrap items-center justify-between gap-3 text-sm print:hidden">
              <NuxtLink to="/legal" class="inline-flex items-center gap-1.5 font-semibold text-neutral-700 hover:text-neutral-900 no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7" /></svg>
                Barcha hujjatlar
              </NuxtLink>
              <button type="button" @click="scrollTop" class="inline-flex items-center gap-1.5 text-neutral-500 hover:text-neutral-900 bg-transparent border-none cursor-pointer text-sm">
                Yuqoriga
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 15 7-7 7 7" /></svg>
              </button>
            </footer>
          </article>

          <!-- Desktop: sticky mundarija -->
          <aside class="hidden lg:block lg:col-span-4 sticky top-24 print:hidden">
            <div v-if="headings.length > 2" class="bg-white rounded-3xl p-5">
              <p class="text-xs font-bold uppercase tracking-wider text-neutral-500 m-0 mb-3 px-2">Mundarija</p>
              <nav class="space-y-0.5 max-h-[calc(100vh-220px)] overflow-y-auto pr-1" aria-label="Mundarija">
                <a
                  v-for="h in headings"
                  :key="h.id"
                  :href="`#${h.id}`"
                  :aria-current="activeHeadingId === h.id ? 'location' : undefined"
                  :class="[
                    'block text-sm py-2 px-3 rounded-xl leading-snug no-underline transition-colors',
                    activeHeadingId === h.id
                      ? 'bg-neutral-100 text-neutral-900 font-semibold'
                      : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50'
                  ]"
                >{{ h.text }}</a>
              </nav>
            </div>
            <p class="text-sm text-neutral-500 mt-4 px-2">
              Savolingiz bormi?
              <NuxtLink to="/contacts" class="font-semibold text-primary hover:underline">Bog‘laning</NuxtLink>
            </p>
          </aside>
        </div>

        <!-- Boshqa hujjatlar -->
        <section v-if="otherPolicies.length > 0" class="mt-10 print:hidden" aria-labelledby="other-docs">
          <h2 id="other-docs" class="text-lg font-bold text-neutral-900 m-0 mb-4">Boshqa hujjatlar</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
            <NuxtLink
              v-for="other in otherPolicies"
              :key="other.slug"
              :to="`/legal/${other.slug}`"
              class="group bg-white rounded-2xl px-5 py-4 border border-transparent hover:border-neutral-200 transition-colors flex items-center justify-between gap-3 no-underline"
            >
              <span class="text-sm font-semibold text-neutral-900 leading-snug">{{ other.title }}</span>
              <svg class="w-4 h-4 text-neutral-400 shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
              </svg>
            </NuxtLink>
          </div>
        </section>
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
  return allDocsData.value.data.filter((item: any) => item.slug !== props.slug).slice(0, 6)
})

// Table of Contents (Mundarija) avtomatik ajratish
const headings = ref<{ id: string; text: string }[]>([])
const activeHeadingId = ref<string>('')

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

function scrollTop() {
  if (process.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}

defineExpose({ policy, pending, notFound })
</script>
