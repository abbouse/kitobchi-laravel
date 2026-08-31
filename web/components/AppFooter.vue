<template>
  <!-- MUHIM: foydalanuvchi tasdiqladi — piyolaning mobil bosh sahifasida
       footer YO'QLIGI xato emas, ATAYLAB shunday qilingan (mobilda pastki
       navigatsiya — AppBottomNav — asosiy navigatsiya vazifasini o'tayapti,
       an'anaviy ko'p-ustunli footer esa faqat desktopda kerak). Shu sabab
       footer endi faqat md+ (planshet/desktop)da ko'rinadi, mobilda esa
       butunlay yashirilgan — piyola bilan bir xil. -->
  <footer class="max-md:hidden relative text-white pt-8 md:pt-20 pb-24 overflow-hidden" style="background: linear-gradient(145deg, #10488f 0%, #1e6ecb 48%, #1453a2 100%);">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
        <!-- 1. Umumiy -->
        <div>
          <h3 class="font-bold text-[24px] mb-6">Umumiy</h3>
          <ul class="space-y-4">
            <li><NuxtLink to="/about" class="text-white hover:underline">Biz haqimizda</NuxtLink></li>
            <li><NuxtLink to="/contacts" class="text-white hover:underline">Aloqa</NuxtLink></li>
            <li><NuxtLink to="/vacancies" class="text-white hover:underline">Karyera</NuxtLink></li>
            <li><NuxtLink to="/kolleksiya" class="text-white hover:underline">Kolleksiyalar</NuxtLink></li>
          </ul>
        </div>

        <!-- 2. Kataloglar -->
        <div>
          <h3 class="font-bold text-[24px] mb-6">Kataloglar</h3>
          <ul class="space-y-4">
            <li v-for="cat in footerCategories" :key="cat.id">
              <NuxtLink :to="`/catalog?category=${cat.id}&type=book`" class="text-white hover:underline transition-all duration-200">
                {{ cat.name || cat.name_uz }}
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/catalog" class="flex-y-center gap-1 text-white hover:underline group transition-all duration-200">
                <span>Hammasini ko'rish</span>
                <svg class="w-4 h-4 group-hover:translate-x-2 transition-all duration-200" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                </svg>
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- 3. Mijozlar xizmati -->
        <div v-if="footerPolicies.length">
          <h3 class="font-bold text-[24px] mb-6">Mijozlar xizmati</h3>
          <ul class="space-y-4">
            <li v-for="item in footerPolicies" :key="item.slug">
              <NuxtLink :to="policyLink(item.slug)" class="text-white hover:underline">{{ item.title }}</NuxtLink>
            </li>
          </ul>
        </div>

        <!-- 4. Ijtimoiy tarmoqlar -->
        <div>
          <h3 class="font-bold text-[24px] mb-6">Ijtimoiy tarmoqlar</h3>
          <div class="flex gap-3 mb-8">
            <a
              :href="SITE_LINKS.telegram" target="_blank" rel="noopener"
              class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:shadow-lg"
              aria-label="Telegram"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
            </a>
            <a
              :href="SITE_LINKS.instagram" target="_blank" rel="noopener"
              class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:shadow-lg"
              aria-label="Instagram"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
          </div>

          <a
            v-if="kitobchiPhone"
            :href="`tel:${kitobchiPhone}`"
            class="font-bold text-[20px] md:text-[24px] text-white hover:underline inline-block"
          >{{ kitobchiPhone }}</a>

          <!-- MUHIM: avvalgi versiya shaffof/glass fon (bg-white/12) ustida
               oq ikonka ishlatgan edi — do'kon nishonchalarining rasmiy
               ko'rinishi (Apple/Google) doim OFFICIAL ravishda quyuq/qora
               fonda bo'ladi, shu sabab endi shu konvensiyaga o'tkazildi:
               solid qora badge + aniqroq (sodda, lekin to'g'ri chizilgan)
               ikonkalar. -->
          <div class="flex flex-wrap gap-2.5 mt-6">
            <a
              :href="SITE_LINKS.googlePlay" target="_blank" rel="noopener"
              class="inline-flex items-center gap-2.5 bg-black hover:bg-neutral-800 rounded-2xl px-3.5 py-2 text-white transition-all duration-300 hover:scale-105 hover:shadow-md"
              aria-label="Yuklab olish Google Play"
            >
              <svg class="h-6 w-6 shrink-0 fill-current" viewBox="0 0 24 24">
                <path d="M22.018 13.298l-3.919 2.218-3.515-3.493 3.543-3.521 3.891 2.202a1.49 1.49 0 0 1 0 2.594zM1.337.924a1.486 1.486 0 0 0-.112.568v21.017c0 .217.045.419.124.6l11.155-11.087L1.337.924zm12.207 10.065l3.258-3.238L3.45.195a1.466 1.466 0 0 0-.946-.179l11.04 10.973zm0 2.067l-11 10.933c.298.036.612-.016.906-.183l13.324-7.54-3.23-3.21z"/>
              </svg>
              <div class="flex flex-col leading-tight text-white">
                <span class="text-[9px] uppercase tracking-wider font-semibold text-white/70">Yuklab olish</span>
                <span class="text-[13px] font-bold text-white tracking-tight">Google Play</span>
              </div>
            </a>
            <a
              :href="SITE_LINKS.appStore" target="_blank" rel="noopener"
              class="inline-flex items-center gap-2.5 bg-black hover:bg-neutral-800 rounded-2xl px-3.5 py-2 text-white transition-all duration-300 hover:scale-105 hover:shadow-md"
              aria-label="Yuklab olish App Store"
            >
              <svg class="h-6 w-6 shrink-0 fill-current" viewBox="0 0 24 24">
                <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zm3.415-3.156c.836-1.012 1.4-2.427 1.245-3.831-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701z"/>
              </svg>
              <div class="flex flex-col leading-tight text-white">
                <span class="text-[9px] uppercase tracking-wider font-semibold text-white/70">Yuklab olish</span>
                <span class="text-[13px] font-bold text-white tracking-tight">App Store</span>
              </div>
            </a>
          </div>
        </div>
      </div>

      <div class="border-t border-white/10 mt-10 pt-5 text-xs text-gray-300 flex items-center gap-2">
        <img alt="Kitobchi" class="h-4 w-4 rounded-[4px] shrink-0" src="/favicon.svg" />
        <span>© {{ new Date().getFullYear() }} Kitobchi. Barcha huquqlar himoyalangan.</span>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const { kitobchiPhone } = useSiteSettings()

// Kategoriyalar va huquqiy hujjatlar (Policy) ro'yxatini parallel
// so'raymiz (ketma-ket useFetch waterfall yaratmaslik uchun) — ikkalasi
// ham bir-biriga bog'liq emas.
const categoriesPromise = useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/categories`, {
  key: 'footer-categories',
  lazy: true
})
const legalPromise = useFetch<any>(`${config.public.apiBase}/v1/kitobchi/legal`, {
  key: 'footer-legal',
  lazy: true
})
const { data: categoriesData } = await categoriesPromise
const { data: legalData } = await legalPromise

const footerCategories = computed(() => {
  const books = categoriesData.value?.data?.book || []
  return books.slice(0, 4)
})

// Ba'zi policy'lar Nuxt'da alohida (SEO-optimallashtirilgan) sahifaga ega
// (masalan /faq, /privacy) — shu slug'lar shu maxsus yo'llarga, qolgani
// generic /legal/{slug} sahifasiga yo'naltiriladi.
const SPECIAL_POLICY_ROUTES: Record<string, string> = {
  'savol-javoblar': '/faq',
  'maxfiylik-siyosati': '/privacy',
  'biz-haqimizda': '/about'
}

function policyLink(slug: string) {
  return SPECIAL_POLICY_ROUTES[slug] || `/legal/${slug}`
}

const footerPolicies = computed(() => {
  const list = legalData.value?.data || []
  // "oxirgi 4 tasi" — backend sort_order/id bo'yicha tartiblab qaytaradi,
  // biz shu tartibning oxirgi 4 tasini olamiz (tartib buzilmaydi).
  return list.slice(-4)
})
</script>
