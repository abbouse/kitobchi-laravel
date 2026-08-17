// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },
  ssr: true,

  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],

  css: [
    '~/assets/css/piyola.css'
  ],

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
        { name: 'theme-color', content: '#0b0342' },
        {
          name: 'description',
          content: 'Kitobchi — O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. Tezkor yetkazib berish, qulay narxlar va original kitoblar.'
        },
        { property: 'og:site_name', content: 'Kitobchi Marketpleysi' },
        { property: 'og:type', content: 'website' },
        { property: 'og:locale', content: 'uz_UZ' }
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/images/logo/logo_blue.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap'
        }
      ]
    }
  }
})
