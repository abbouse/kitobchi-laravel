<template>
  <div class="py-6 md:py-12">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Huquqiy hujjatlar</span>
      </nav>

      <!-- Hero Header Section -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-white via-primary-50/40 to-blue-50/60 border border-neutral-200/70 p-6 sm:p-10 md:p-12 mb-8 md:mb-12 shadow-xs">
        <div class="max-w-2xl relative z-10">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-4">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <span>Rasmiy huquqiy markaz</span>
          </div>
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-neutral-900 tracking-tight mb-3">
            Huquqiy hujjatlar va qoidalar
          </h1>
          <p class="text-sm md:text-base text-neutral-600 leading-relaxed mb-6">
            Kitobchi platformasida xavfsiz va ishonchli xarid qilishingiz uchun barcha shartlar, ommaviy oferta hamda maxfiylik kafolatlari bilan tanishing.
          </p>

          <!-- Search & Filter Input -->
          <div class="relative max-w-lg">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Hujjatlar orasidan qidirish (masalan: oferta, to'lov, nasiya)..."
              class="w-full pl-10 pr-9 py-2.5 bg-white border border-neutral-200/90 rounded-xl text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-xs"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Decorative background circle -->
        <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-primary/5 blur-2xl pointer-events-none" aria-hidden="true"></div>
      </div>

      <!-- Quick Filter Tags -->
      <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-6 scrollbar-none text-sm">
        <button
          v-for="tab in filterTabs"
          :key="tab.id"
          type="button"
          @click="activeTab = tab.id"
          :class="[
            'px-4 py-2 rounded-xl font-medium transition-all cursor-pointer whitespace-nowrap',
            activeTab === tab.id
              ? 'bg-brand-gradient text-white shadow-xs'
              : 'bg-white text-neutral-600 border border-neutral-200/70 hover:bg-neutral-50 hover:text-neutral-900'
          ]"
        >
          {{ tab.label }}
          <span
            v-if="tab.count !== undefined"
            :class="[
              'ml-1.5 text-xs px-1.5 py-0.5 rounded-full',
              activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-neutral-100 text-neutral-500'
            ]"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl p-6 border border-neutral-100 animate-pulse space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-neutral-100"></div>
          <div class="h-5 bg-neutral-100 rounded w-3/4"></div>
          <div class="h-4 bg-neutral-100 rounded w-full"></div>
          <div class="h-4 bg-neutral-100 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPolicies.length === 0" class="bg-white rounded-3xl p-12 text-center border border-neutral-200/70 max-w-lg mx-auto shadow-xs my-8">
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
          class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline"
        >
          Filtrni tozalash
        </button>
      </div>

      <!-- Policies Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <NuxtLink
          v-for="item in filteredPolicies"
          :key="item.slug"
          :to="`/legal/${item.slug}`"
          class="group bg-white rounded-2xl p-6 border border-neutral-200/70 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 transition-all duration-200 flex flex-col justify-between hover:-translate-y-0.5"
        >
          <div>
            <!-- Top Icon & Category Tag -->
            <div class="flex items-center justify-between gap-3 mb-4">
              <div
                class="w-12 h-12 rounded-2xl flex items-center justify-center transition-transform duration-200 group-hover:scale-105"
                :class="getIconBgClass(item.slug)"
              >
                <component :is="getIconSvg(item.slug)" class="w-6 h-6" />
              </div>
              <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="getBadgeClass(item.slug)"
              >
                {{ getCategoryLabel(item.slug) }}
              </span>
            </div>

            <!-- Title -->
            <h2 class="text-base sm:text-lg font-bold text-neutral-900 group-hover:text-primary transition-colors leading-snug mb-2">
              {{ item.title }}
            </h2>

            <!-- Short Excerpt / Description -->
            <p class="text-xs sm:text-sm text-neutral-500 leading-relaxed line-clamp-2 mb-4">
              {{ getPolicyDescription(item.slug) }}
            </p>
          </div>

          <!-- Bottom Footer of Card -->
          <div class="flex items-center justify-between text-xs font-medium text-neutral-400 pt-4 border-t border-neutral-100 mt-2">
            <span class="inline-flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
              <span>Tahrir: 20.10.2026</span>
            </span>
            <span class="inline-flex items-center gap-1 text-primary font-semibold group-hover:translate-x-1 transition-transform">
              <span>O‘qish</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </span>
          </div>
        </NuxtLink>
      </div>

      <!-- Bottom Trust / Assistance Banner -->
      <div class="mt-12 md:mt-16 bg-white rounded-3xl border border-neutral-200/70 p-6 sm:p-8 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-neutral-900 mb-1">Shaffoflik & Qonuniylik</h3>
              <p class="text-xs text-neutral-500 leading-relaxed">
                Barcha shartlar O‘zbekiston Respublikasi Fuqarolik kodeksi va elektron tijorat qonunchiligiga to‘liq mos keladi.
              </p>
            </div>
          </div>

          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-neutral-900 mb-1">Ma'lumotlar himoyasi</h3>
              <p class="text-xs text-neutral-500 leading-relaxed">
                To‘lovlar litsenziyalangan to‘lov tashkilotlari orqali amalga oshiriladi, shaxsiy ma'lumotlar to‘liq shifrlanadi.
              </p>
            </div>
          </div>

          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-neutral-900 mb-1">Savolingiz bormi?</h3>
              <p class="text-xs text-neutral-500 leading-relaxed mb-2">
                Huquqiy hujjatlar bo‘yicha savollaringiz bo‘lsa, qo‘llab-quvvatlash xizmati yordam beradi.
              </p>
              <NuxtLink to="/contacts" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                Bog‘lanish markazi
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
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
