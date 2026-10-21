<template>
  <div class="py-6 md:py-10">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors no-underline text-neutral-500">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-800 font-medium truncate">Huquqiy hujjatlar</span>
      </nav>

      <!-- Sarlavha: ixcham, hero'siz -->
      <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
        <div class="min-w-0">
          <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight m-0">Huquqiy hujjatlar</h1>
          <p class="text-sm text-neutral-500 m-0 mt-1">Kitobchi’dan foydalanish shartlari, siyosatlar va shartnoma namunalari.</p>
        </div>
        <label class="relative w-full sm:w-72 shrink-0">
          <span class="sr-only">Hujjat qidirish</span>
          <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Hujjat qidirish"
            class="w-full pl-10 pr-4 h-11 bg-white border border-neutral-200 rounded-full text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
          />
        </label>
      </header>

      <!-- Quick Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-6 scrollbar-none text-sm">
        <button
          v-for="tab in filterTabs"
          :key="tab.id"
          type="button"
          @click="activeTab = tab.id"
          :class="[
            'h-9 px-4 rounded-full font-semibold transition-colors cursor-pointer whitespace-nowrap text-sm border',
            activeTab === tab.id
              ? 'bg-neutral-900 text-white border-neutral-900'
              : 'bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400 hover:text-neutral-900'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="tab.count !== undefined"
            :class="[
              'ml-1.5 text-xs px-1.5 py-0.5 rounded-full',
              activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-neutral-100 text-neutral-600'
            ]"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-3xl p-6 border border-neutral-100 animate-pulse space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-neutral-100"></div>
          <div class="h-5 bg-neutral-100 rounded w-3/4"></div>
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
          <div class="h-4 bg-neutral-100 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPolicies.length === 0" class="bg-white rounded-3xl p-12 text-center border-none max-w-lg mx-auto shadow-2xs my-8">
        <div class="w-16 h-16 rounded-2xl bg-neutral-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
        </div>
        <h2 class="text-lg font-bold text-neutral-800 mb-1">Hujjat topilmadi</h2>
        <p class="text-sm text-neutral-500 mb-4">
          "{{ searchQuery }}" so'rovi bo'yicha hech qanday huquqiy hujjat topilmadi.
        </p>
        <button
          type="button"
          @click="searchQuery = ''; activeTab = 'all'"
          class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline bg-transparent border-none cursor-pointer"
        >
          Filtrni tozalash
        </button>
      </div>

      <!-- Policies Grid: Clean Apple/Stripe Card Layout -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
        <NuxtLink
          v-for="item in filteredPolicies"
          :key="item.slug"
          :to="`/legal/${item.slug}`"
          class="group bg-white rounded-3xl p-5 sm:p-6 border border-transparent hover:border-neutral-200 transition-colors flex flex-col justify-between gap-3 no-underline"
        >
          <div>
            <!-- Top Icon & Category Tag -->
            <div class="flex items-center justify-between gap-3 mb-4">
              <div
                class="w-11 h-11 rounded-2xl flex items-center justify-center transition-transform duration-200 group-hover:scale-105"
                :class="getIconBgClass(item.slug)"
              >
                <component :is="getIconSvg(item.slug)" class="w-5 h-5" />
              </div>
              <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="getBadgeClass(item.slug)"
              >
                {{ getCategoryLabel(item.slug) }}
              </span>
            </div>

            <!-- Title -->
            <h2 class="text-base sm:text-lg font-bold text-neutral-900 group-hover:text-primary transition-colors leading-snug m-0 mb-2">
              {{ item.title }}
            </h2>

            <!-- Short Excerpt / Description -->
            <p class="text-sm text-neutral-500 leading-relaxed line-clamp-2 m-0">
              {{ getPolicyDescription(item.slug) }}
            </p>
          </div>

          <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary mt-1">
            O‘qish
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
            </svg>
          </span>
        </NuxtLink>
      </div>

      <!-- Shartnoma namunalari: bugungi sana bilan Word fayl (boshqaruv → Shartnomalar) -->
      <section v-if="contracts.length && (activeTab === 'all' || activeTab === 'partner')" class="mt-10" aria-labelledby="contracts-title">
        <div class="flex items-end justify-between gap-4 mb-4">
          <div>
            <h2 id="contracts-title" class="text-xl sm:text-2xl font-extrabold text-neutral-900 tracking-tight m-0 mb-1">Shartnoma namunalari</h2>
            <p class="text-sm text-neutral-500 m-0">Word (.docx) formatida — sana va to‘lov jadvali yuklab olingan kun bo‘yicha avtomatik to‘ldiriladi.</p>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
          <div
            v-for="c in contracts"
            :key="c.slug"
            class="bg-white rounded-3xl p-6 shadow-2xs flex flex-col justify-between gap-4"
          >
            <div class="flex items-start gap-4">
              <div class="w-11 h-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
              </div>
              <div class="min-w-0">
                <h3 class="text-base sm:text-lg font-bold text-neutral-900 leading-snug m-0 mb-1">{{ c.title }}</h3>
                <p class="text-xs sm:text-sm text-neutral-500 leading-relaxed m-0">{{ c.description }}</p>
              </div>
            </div>
            <a
              :href="`${config.public.apiBase}/v1/kitobchi/contracts/${c.slug}/download`"
              class="self-start inline-flex items-center gap-2 h-10 px-4 rounded-2xl bg-neutral-100 hover:bg-neutral-200/80 text-neutral-900 text-sm font-semibold no-underline transition-colors"
              rel="nofollow"
              download
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
              </svg>
              Word faylini yuklab olish
            </a>
          </div>
        </div>
      </section>

      <p class="mt-10 text-sm text-neutral-500 text-center">
        Hujjatlar bo‘yicha savolingiz bormi?
        <NuxtLink to="/contacts" class="font-semibold text-primary hover:underline">Biz bilan bog‘laning</NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { h } from 'vue'

const config = useRuntimeConfig()

const { data, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/legal`, {
  key: 'legal-index-policies',
  lazy: false
})

const { data: contractsData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/contracts`, {
  key: 'legal-index-contracts',
  lazy: false,
})
const contracts = computed<any[]>(() => (contractsData.value?.status === 'success' ? contractsData.value.data || [] : []))

const policies = computed(() => {
  if (data.value?.status !== 'success') return []
  return data.value?.data || []
})

const searchQuery = ref('')
const activeTab = ref('all')

const filterTabs = computed(() => [
  { id: 'all', label: 'Barchasi', count: policies.value.length },
  { id: 'customer', label: 'Xaridorlar uchun' },
  { id: 'partner', label: 'Hamkorlik' },
  { id: 'security', label: 'Xavfsizlik & To‘lov' },
])

const policyCategoryMap: Record<string, string> = {
  'foydalanish-shartlari': 'customer',
  'maxfiylik-siyosati': 'security',
  'sotuvchi-shartlari': 'partner',
  'nasiya-shartlari': 'customer',
  'yetkazib-berish-va-qaytarish': 'customer',
  'tolov-va-qaytarish': 'security',
  'obuna-shartlari': 'customer',
  'cookies-siyosati': 'security',
  'omadli-maydon-qoidalari': 'customer',
}

const filteredPolicies = computed(() => {
  let list = policies.value

  if (activeTab.value !== 'all') {
    list = list.filter((p: any) => policyCategoryMap[p.slug] === activeTab.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((p: any) => {
      const title = (p.title || '').toLowerCase()
      const desc = (getPolicyDescription(p.slug) || '').toLowerCase()
      return title.includes(q) || desc.includes(q)
    })
  }

  return list
})

function getCategoryLabel(slug: string): string {
  switch (slug) {
    case 'foydalanish-shartlari': return 'Ommaviy oferta'
    case 'maxfiylik-siyosati': return 'Maxfiylik'
    case 'sotuvchi-shartlari': return 'Hamkorlar'
    case 'nasiya-shartlari': return 'Nasiya'
    case 'yetkazib-berish-va-qaytarish': return 'Yetkazib berish'
    case 'tolov-va-qaytarish': return 'To‘lov & Chek'
    case 'obuna-shartlari': return 'Obuna'
    case 'cookies-siyosati': return 'Cookie'
    case 'omadli-maydon-qoidalari': return 'Aksiya'
    default: return 'Hujjat'
  }
}

function getPolicyDescription(slug: string): string {
  switch (slug) {
    case 'foydalanish-shartlari':
      return 'Kitobchi platformasidan foydalanishning asosiy qoidalari, buyurtma berish, foydalanuvchi va operator huquq va majburiyatlari.'
    case 'maxfiylik-siyosati':
      return 'Shaxsiy ma\'lumotlarni yig‘ish, saqlash, qayta ishlash va ularning daxlsizligini kafolatlash tartibi.'
    case 'sotuvchi-shartlari':
      return 'Marketpleysda kitob va kanselyariya mahsulotlarini joylashtirish, sotish va komissiya hisob-kitob qoidalari.'
    case 'nasiya-shartlari':
      return 'Kitobchi nasiya (bo‘lib to‘lash) xizmati talablari, to‘lov grafigi va foydalanuvchi majburiyatlari.'
    case 'yetkazib-berish-va-qaytarish':
      return 'O‘zbekiston bo‘ylab yetkazib berish muddatlari, narxlari va nuqsonli tovarlarni qaytarish tartibi.'
    case 'tolov-va-qaytarish':
      return 'Bank kartalari orqali to‘lov qilish, fiskal cheklar va bekor qilingan buyurtma pullarini qaytarish.'
    case 'obuna-shartlari':
      return 'Qo‘shimcha pullik xizmatlar, obunalar va ularni to‘xtatish tartibi.'
    case 'cookies-siyosati':
      return 'Platformada foydalanuvchi tajribasini yaxshilash va tahlil qilish uchun cookie texnologiyalaridan foydalanish.'
    case 'omadli-maydon-qoidalari':
      return 'Omadli maydon sovrinli o‘yinida ishtirok etish, tangalar va sovg‘alarni qo‘lga kiritish qoidalari.'
    default:
      return 'Kitobchi platformasi rasmiy huquqiy hujjati bilan to‘liq tanishib chiqing.'
  }
}

function getIconBgClass(slug: string): string {
  switch (slug) {
    case 'foydalanish-shartlari': return 'bg-blue-50 text-blue-600'
    case 'maxfiylik-siyosati': return 'bg-emerald-50 text-emerald-600'
    case 'sotuvchi-shartlari': return 'bg-purple-50 text-purple-600'
    case 'nasiya-shartlari': return 'bg-amber-50 text-amber-600'
    case 'yetkazib-berish-va-qaytarish': return 'bg-sky-50 text-sky-600'
    case 'tolov-va-qaytarish': return 'bg-teal-50 text-teal-600'
    case 'obuna-shartlari': return 'bg-indigo-50 text-indigo-600'
    case 'cookies-siyosati': return 'bg-orange-50 text-orange-600'
    case 'omadli-maydon-qoidalari': return 'bg-rose-50 text-rose-600'
    default: return 'bg-neutral-100 text-neutral-600'
  }
}

function getBadgeClass(slug: string): string {
  switch (slug) {
    case 'foydalanish-shartlari': return 'bg-blue-50 text-blue-700'
    case 'maxfiylik-siyosati': return 'bg-emerald-50 text-emerald-700'
    case 'sotuvchi-shartlari': return 'bg-purple-50 text-purple-700'
    case 'nasiya-shartlari': return 'bg-amber-50 text-amber-700'
    case 'yetkazib-berish-va-qaytarish': return 'bg-sky-50 text-sky-700'
    case 'tolov-va-qaytarish': return 'bg-teal-50 text-teal-700'
    case 'obuna-shartlari': return 'bg-indigo-50 text-indigo-700'
    case 'cookies-siyosati': return 'bg-orange-50 text-orange-700'
    case 'omadli-maydon-qoidalari': return 'bg-rose-50 text-rose-700'
    default: return 'bg-neutral-100 text-neutral-700'
  }
}

function getIconSvg(slug: string) {
  // SVG icon definition
  return {
    render() {
      switch (slug) {
        case 'maxfiylik-siyosati':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z' })
          ])
        case 'sotuvchi-shartlari':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.614A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.015a2.993 2.993 0 0 0 2.25 1.015c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 0c.315.088.646.135.986.135.97 0 1.86-.464 2.427-1.191a3.03 3.03 0 0 0 .427-.677M14.25 9.75c.34 0 .671-.047.986-.135m0 0a3.03 3.03 0 0 0 .427.677c.567.727 1.456 1.191 2.427 1.191.34 0 .671-.047.986-.135' })
          ])
        case 'nasiya-shartlari':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' })
          ])
        case 'yetkazib-berish-va-qaytarish':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.375c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75M8.25 21h8.25' })
          ])
        case 'tolov-va-qaytarish':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z' })
          ])
        case 'omadli-maydon-qoidalari':
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a4.5 4.5 0 0 0 4.475-4.045l.235-2.348A2.25 2.25 0 0 0 16.975 6H7.025a2.25 2.25 0 0 0-2.235 2.477l.235 2.348A4.5 4.5 0 0 0 9.497 14.25' })
          ])
        default:
          return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', strokeWidth: '1.8' }, [
            h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z' })
          ])
      }
    }
  }
}

useSeoMeta({
  title: 'Huquqiy hujjatlar va qoidalar — Kitobchi',
  description: 'Kitobchi platformasi huquqiy hujjatlari: foydalanish shartlari, maxfiylik siyosati, yetkazib berish va qaytarish qoidalari.',
  ogTitle: 'Huquqiy hujjatlar va qoidalar — Kitobchi',
  ogDescription: 'Kitobchi platformasi huquqiy hujjatlari: foydalanish shartlari, maxfiylik siyosati, yetkazib berish va qaytarish qoidalari.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/legal' }]
})
</script>
