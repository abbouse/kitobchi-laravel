<template>
  <!-- MUHIM: foydalanuvchi tasdiqladi — piyolaning mobil bosh sahifasida
       footer YO'QLIGI xato emas, ATAYLAB shunday qilingan (mobilda pastki
       navigatsiya — AppBottomNav — asosiy navigatsiya vazifasini o'tayapti,
       an'anaviy ko'p-ustunli footer esa faqat desktopda kerak). Shu sabab
       footer endi faqat md+ (planshet/desktop)da ko'rinadi, mobilda esa
       butunlay yashirilgan — piyola bilan bir xil. -->
  <footer class="max-md:hidden relative bg-primary text-white pt-8 md:pt-24 pb-24 overflow-hidden">
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

        <!-- 3. Mijozlar xizmati — boshqaruv panelidagi Policy ro'yxatidan
             avtomatik, oxirgi 4 tasi (sort_order bo'yicha tartiblangan
             ro'yxatning oxiri) chiqariladi. Statik/qotib qolgan matn yo'q —
             admin policy qo'shsa/o'chirsa/tartibini o'zgartirsa shu yerda
             ham avtomatik yangilanadi. -->
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
              class="liquidGlass-wrapper shrink-0 flex-center rounded-full! h-10 w-10"
              aria-label="Telegram"
            >
              <div class="liquidGlass-effect"></div>
              <div class="liquidGlass-tint"></div>
              <div class="liquidGlass-shine"></div>
              <div class="relative inset-0 size-full z-50 rounded-full! h-10 w-10 flex-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
              </div>
            </a>
            <a
              :href="SITE_LINKS.instagram" target="_blank" rel="noopener"
              class="liquidGlass-wrapper shrink-0 flex-center rounded-full! h-10 w-10"
              aria-label="Instagram"
            >
              <div class="liquidGlass-effect"></div>
              <div class="liquidGlass-tint"></div>
              <div class="liquidGlass-shine"></div>
              <div class="relative inset-0 size-full z-50 rounded-full! h-10 w-10 flex-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
              </div>
            </a>
          </div>

          <a
            v-if="kitobchiPhone"
            :href="`tel:${kitobchiPhone}`"
            class="font-bold text-[20px] md:text-[24px] text-white hover:underline"
          >{{ kitobchiPhone }}</a>

          <div class="flex flex-wrap gap-2 mt-6">
            <a
              :href="SITE_LINKS.googlePlay" target="_blank" rel="noopener"
              class="inline-flex items-center gap-2 bg-black rounded-xl px-3 py-2 hover:opacity-90 transition-opacity"
              aria-label="Yuklab olish Google Play"
            >
              <svg class="h-6 w-6 shrink-0 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 2.5c-.4.4-.6.9-.6 1.6v15.8c0 .7.2 1.2.6 1.6l.1.1L13.7 12v-.2L4.6 2.4l-.1.1z"/><path d="M16.8 15.1l-3.1-3.1v-.2l3.1-3.1 3.6 2.1c1 .6 1 1.6 0 2.2l-3.6 2.1z" opacity=".85"/><path d="M16.8 15.1L13.6 12 4.5 21.5c.4.4 1 .4 1.7 0l10.6-6.4z" opacity=".7"/><path d="M16.8 8.9L6.2 2.5c-.7-.4-1.3-.4-1.7 0L13.6 12l3.2-3.1z" opacity=".95"/></svg>
              <div class="flex flex-col leading-tight text-white">
                <span class="text-[10px]">Yuklab olish</span>
                <span class="text-sm font-bold">Google Play</span>
              </div>
            </a>
            <a
              :href="SITE_LINKS.appStore" target="_blank" rel="noopener"
              class="inline-flex items-center gap-2 bg-black rounded-xl px-3 py-2 hover:opacity-90 transition-opacity"
              aria-label="Yuklab olish App Store"
            >
              <svg class="h-6 w-6 shrink-0 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M16.365 1.43c0 1.14-.493 2.27-1.177 3.08-.744.9-1.99 1.57-2.987 1.57-.12 0-.23-.02-.3-.03-.01-.06-.04-.22-.04-.39 0-1.15.572-2.27 1.206-2.98.804-.94 2.142-1.64 3.248-1.68.03.13.05.28.05.43zm4.565 15.71c-.03.07-.463 1.58-1.518 3.12-.945 1.34-1.94 2.71-3.43 2.71-1.517 0-1.9-.88-3.63-.88-1.698 0-2.302.91-3.67.91-1.377 0-2.332-1.26-3.428-2.8-1.287-1.82-2.323-4.63-2.323-7.28 0-4.28 2.797-6.55 5.552-6.55 1.448 0 2.675.95 3.5.95.865 0 2.222-1 3.86-1 .613 0 2.886.06 4.375 2.19-.115.07-2.612 1.53-2.612 4.71 0 3.79 3.32 5.11 3.34 5.11z"/></svg>
              <div class="flex flex-col leading-tight text-white">
                <span class="text-[10px]">Yuklab olish</span>
                <span class="text-sm font-bold">App Store</span>
              </div>
            </a>
          </div>
        </div>
      </div>

      <div class="border-t border-white/10 mt-10 pt-5 text-xs text-gray-300">
        © {{ new Date().getFullYear() }} Kitobchi. Barcha huquqlar himoyalangan.
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
