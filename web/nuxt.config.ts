// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },
  ssr: true,

  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt',
    '@nuxtjs/tailwindcss'
  ],

  // Tailwind JIT ENDI HAQIQIY yoqilgan (2026-08-31'da). MUHIM: v3 emas, v4
  // ishlatiladi — chunki piyola.css (piyolamarket.uz'dan olingan statik
  // CSS) o'zi ham HAQIQIY Tailwind v4 chiqishi ekan (native @layer,
  // preflight va hokazo). v3 bilan sinab ko'rilganda build butunlay
  // buzilgan edi ("@layer base is used but no matching @tailwind base
  // directive"), sabab: v3'ning @layer tizimi PostCSS-direktiv-asosli
  // (har bir faylda alohida), v4'niki esa CSS-native, fayllararo ishlaydi.
  // v4'da alohida tailwind.config.js SHART EMAS (content avtomatik
  // aniqlanadi).
  //
  // cssPath ATAYIN '~/assets/css/tailwind.css'ga ko'rsatilgan (modulning
  // standart avtomatik-aniqlash logikasi v4 bilan buzilgan edi: agar biz
  // o'zimizning cssPath'imizni bermasak, modul o'zining ESKI/v3'ga mo'ljallangan
  // fallback yo'lidan borib, 'tailwindcss/tailwind.css' degan MAVJUD BO'LMAGAN
  // faylni import qilishga urinar edi — v4 paketida bunday fayl umuman yo'q,
  // shuning uchun build "Rollup failed to resolve import" xatosi bilan
  // qular edi). Bu faylda FAQAT theme + utilities import qilingan, preflight
  // YO'Q — chunki piyola.css'da preflight allaqachon bor (rasmiy Tailwind v4
  // "preflightsiz ishlatish" usuli: theme.css + utilities.css alohida-alohida
  // import qilinadi, preflight.css esa import qilinmaydi).
  tailwindcss: {
    cssPath: '~/assets/css/tailwind.css',
    exposeConfig: false,
    experimental: {
      tailwindcss4: true
    }
  },

  css: [
    '~/assets/css/piyola.css',
    '~/assets/css/piyola-extra.css'
  ],

  routeRules: {
    '/': { swr: 60 },
    '/catalog/**': { swr: 30 },
    '/about': { prerender: true },
    '/contacts': { prerender: true },
    '/privacy': { prerender: true },
    '/faq': { prerender: true },
    '/legal/**': { swr: 300 },
    '/sitemap.xml': { swr: 3600 },
    '/google-merchant.xml': { swr: 3600 }
  },

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'https://kitobchi.com/api',
      siteUrl: 'https://kitobchi.com'
    }
  },

  app: {
    head: {
      htmlAttrs: {
        lang: 'uz',
        class: 'light'
      },
      title: 'Kitobchi — Online kitoblar va kanselyariya marketpleysi',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=5' },
        { name: 'format-detection', content: 'telephone=no' },

        // Theme colors for Google & Apple browsers
        { name: 'theme-color', content: '#2980DD' },
        { name: 'msapplication-TileColor', content: '#2980DD' },
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-status-bar-style', content: 'default' },
        { name: 'apple-mobile-web-app-title', content: 'Kitobchi' },

        // Primary SEO Meta Tags
        {
          name: 'description',
          content: 'Kitobchi — O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. Tezkor yetkazib berish, qulay narxlar va original kitoblar.'
        },
        {
          name: 'keywords',
          content: 'kitobchi, kitoblar, online kitob do‘koni, uzbekistan kitoblar, kanselyariya, badiiy kitoblar, diniy kitoblar, bolalar kitoblari, dasturlash kitoblari, uzbek kitobchi'
        },
        { name: 'robots', content: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' },
        { name: 'googlebot', content: 'index, follow' },
        { name: 'yandex', content: 'index, follow' },

        // OpenGraph Meta Tags (Facebook, Telegram, WhatsApp, LinkedIn)
        { property: 'og:site_name', content: 'Kitobchi' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: 'https://kitobchi.com/' },
        { property: 'og:title', content: 'Kitobchi — Online kitoblar va kanselyariya marketpleysi' },
        {
          property: 'og:description',
          content: 'O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. Tezkor yetkazib berish va qulay narxlar.'
        },
        { property: 'og:image', content: 'https://kitobchi.com/og-image.png' },
        { property: 'og:image:secure_url', content: 'https://kitobchi.com/og-image.png' },
        { property: 'og:image:type', content: 'image/png' },
        { property: 'og:image:width', content: '1200' },
        { property: 'og:image:height', content: '630' },
        { property: 'og:locale', content: 'uz_UZ' },
        { property: 'og:locale:alternate', content: 'ru_RU' },
        { property: 'og:locale:alternate', content: 'en_US' },
        { property: 'og:locale:alternate', content: 'ja_JP' },

        // Twitter Card Meta Tags
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Kitobchi — Online kitoblar va kanselyariya marketpleysi' },
        {
          name: 'twitter:description',
          content: 'O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. Tezkor yetkazib berish va qulay narxlar.'
        },
        { name: 'twitter:image', content: 'https://kitobchi.com/og-image.png' }
      ],
      link: [
        // Standard Favicon Icons (Yandex & Google Compatible)
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg?v=20260822_2' },
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png?v=20260822_2' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png?v=20260822_2' },
        { rel: 'icon', type: 'image/png', sizes: '48x48', href: '/favicon-48x48.png?v=20260822_2' },
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico?v=20260822_2' },

        // Apple Touch Icon
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png?v=20260822_2' },

        // Manifest & Yandex Tableau
        { rel: 'manifest', href: '/site.webmanifest?v=20260822_2' },
        { rel: 'yandex-tableau-widget', href: '/yandex-tableau.json' },

        // Canonical URL
        { rel: 'canonical', href: 'https://kitobchi.com/' },

        // Fonts
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap'
        }
      ],
      script: [
        // Structured Data (JSON-LD) for Google & Yandex Rich Snippets
        {
          type: 'application/ld+json',
          children: JSON.stringify({
            '@context': 'https://schema.org',
            '@graph': [
              {
                '@type': 'WebSite',
                '@id': 'https://kitobchi.com/#website',
                'url': 'https://kitobchi.com/',
                'name': 'Kitobchi',
                'description': 'Online kitoblar va kanselyariya marketpleysi',
                'publisher': {
                  '@id': 'https://kitobchi.com/#organization'
                },
                'potentialAction': {
                  '@type': 'SearchAction',
                  'target': 'https://kitobchi.com/search?q={search_term_string}',
                  'query-input': 'required name=search_term_string'
                },
                'inLanguage': 'uz-UZ'
              },
              {
                '@type': 'Organization',
                '@id': 'https://kitobchi.com/#organization',
                'name': 'Kitobchi',
                'url': 'https://kitobchi.com/',
                'logo': {
                  '@type': 'ImageObject',
                  'url': 'https://kitobchi.com/android-chrome-512x512.png',
                  'caption': 'Kitobchi Logo'
                },
                'sameAs': [
                  'https://t.me/kitobchicom',
                  'https://instagram.com/kitobchicom'
                ]
              }
            ]
          })
        }
      ]
    }
  }
})
