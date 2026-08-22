// Til almashtirish — MUHIM: AppHeader.vue'da "O'zbekcha" tugmasi bosilganda
// faqat `isLangOpen` degan ref o'zgarardi, lekin uni o'qiydigan/ko'rsatadigan
// HECH QANDAY dropdown menyu shablonda yo'q edi — tugma butunlay o'lik edi
// (jonli sinovda tasdiqlandi: bosilganda hech narsa ochilmasdi). Loyihada
// i18n moduli ham (`@nuxtjs/i18n`) o'rnatilmagan, `nuxt.config.ts`da til
// `htmlAttrs.lang: 'uz'` deb qattiq yozilgan edi.
//
// Bu composable: (1) tanlangan tilni cookie orqali saqlaydi (SSR-safe,
// sahifa yangilanganda ham eslab qoladi), (2) <html lang> atributini shu
// tilga moslab qo'yadi, (3) header/navigatsiya kabi umumiy UI matnlari
// uchun kichik lug'at (`t()`) beradi.
//
// MUHIM (KO'LAM): piyolada atigi 3 ta til bor (o'zbek/rus/ingliz),
// foydalanuvchi esa aynan 4 ta tilni (shu jumladan yapon) so'ragani uchun bu
// piyoladan nusxa ko'chirish emas, mustaqil qo'shilgan funksiya. Bu composable
// hozircha faqat HEADER/navigatsiya matnlarini tarjima qiladi — butun
// saytdagi barcha sahifalar matni va mahsulot (kitob) nomlari/tavsiflari
// (bular backend'da faqat o'zbekcha saqlanadi) hali tarjima qilinmagan —
// bu alohida, ancha kattaroq ish.

export type AppLocale = 'uz' | 'ru' | 'en' | 'ja'

export const LOCALE_LABELS: Record<AppLocale, string> = {
  uz: "O'zbekcha",
  ru: 'Русский',
  en: 'English',
  ja: '日本語',
}

export const LOCALE_ORDER: AppLocale[] = ['uz', 'ru', 'en', 'ja']

const dictionaries: Record<AppLocale, Record<string, string>> = {
  uz: {
    catalogs: 'Kataloglar',
    search_placeholder: 'Mahsulotni izlash...',
    search_placeholder_mobile: "Kitobchi'da izlash",
    cart: 'Savatcha',
    favorites: 'Sevimlilar',
    login: 'Kirish',
    profile: 'Profil',
  },
  ru: {
    catalogs: 'Каталоги',
    search_placeholder: 'Поиск товара...',
    search_placeholder_mobile: 'Поиск на Kitobchi',
    cart: 'Корзина',
    favorites: 'Избранное',
    login: 'Войти',
    profile: 'Профиль',
  },
  en: {
    catalogs: 'Catalogs',
    search_placeholder: 'Search for products...',
    search_placeholder_mobile: 'Search on Kitobchi',
    cart: 'Cart',
    favorites: 'Favorites',
    login: 'Log in',
    profile: 'Profile',
  },
  ja: {
    catalogs: 'カタログ',
    search_placeholder: '商品を検索...',
    search_placeholder_mobile: 'Kitobchiで検索',
    cart: 'カート',
    favorites: 'お気に入り',
    login: 'ログイン',
    profile: 'プロフィール',
  },
}

export function useLocale() {
  const locale = useCookie<AppLocale>('kc_locale', {
    default: () => 'uz',
    maxAge: 60 * 60 * 24 * 365,
    sameSite: 'lax',
  })

  // Noto'g'ri/eski qiymat cookie'da qolib ketgan bo'lsa — xavfsiz holatga
  // qaytariladi (masalan boshqa loyihadan meros bo'lib qolgan qiymat).
  if (!LOCALE_ORDER.includes(locale.value)) {
    locale.value = 'uz'
  }

  useHead({
    htmlAttrs: {
      lang: computed(() => locale.value),
    },
  })

  function setLocale(loc: AppLocale) {
    locale.value = loc
  }

  function t(key: string): string {
    return dictionaries[locale.value]?.[key] ?? dictionaries.uz[key] ?? key
  }

  const availableLocales = LOCALE_ORDER.map((code) => ({ code, label: LOCALE_LABELS[code] }))

  return {
    locale,
    localeLabel: computed(() => LOCALE_LABELS[locale.value]),
    availableLocales,
    setLocale,
    t,
  }
}
