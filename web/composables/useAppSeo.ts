import { computed } from 'vue'
import { useLocale, type AppLocale } from '~/composables/useLocale'
import { latinToCyrillic, cyrillicToLatin, getDualScriptKeywords } from '~/utils/translit'

export interface ProductSeoOptions {
  name: string
  description?: string
  image?: string
  price?: number | string
  discountPrice?: number | string
  currency?: string
  inStock?: boolean
  type?: 'book' | 'stationery'
  author?: string
  publisher?: string
  isbn?: string
  rating?: number | string
  reviewsCount?: number
  categoryName?: string
  urlPath: string
}

export function useAppSeo() {
  const { locale } = useLocale()
  const config = useRuntimeConfig()
  const siteUrl = (config.public?.siteUrl as string) || 'https://kitobchi.com'

  /**
   * Set 4-language dynamic SEO for books and stationery products.
   */
  function setProductSeo(options: ProductSeoOptions) {
    const canonicalUrl = `${siteUrl}${options.urlPath}`
    const imgUrl = options.image ? (options.image.startsWith('http') ? options.image : `${siteUrl}${options.image.startsWith('/') ? '' : '/'}${options.image}`) : `${siteUrl}/favicon.svg`

    const priceNum = Number(options.discountPrice || options.price || 0)
    const isBook = options.type === 'book' || !options.type

    // Multi-language title and description templates
    const seoData = computed(() => {
      const currentLoc = locale.value as AppLocale
      const cleanName = options.name || 'Mahsulot'
      const cleanAuthor = options.author ? ` (${options.author})` : ''
      const cyrillicName = latinToCyrillic(cleanName)

      let title = ''
      let description = ''
      let keywords: string[] = []

      switch (currentLoc) {
        case 'ru':
          title = isBook
            ? `Купить книгу «${cleanName}»${cleanAuthor} в Ташкенте — Цена, Отзывы | Kitobchi`
            : `Купить «${cleanName}» в Ташкенте — Цена, Доставка | Kitobchi`
          description = isBook
            ? `Купить книгу «${cleanName}»${cleanAuthor} по выгодной цене в онлайн-маркетплейсе Kitobchi. Быстрая доставка по Ташкенту и всему Узбекистану, бесплатная эстетичная упаковка.`
            : `Канцелярские товары «${cleanName}» по выгодной цене с доставкой по Узбекистану в интернет-магазине Kitobchi.`
          keywords = [cleanName, cyrillicName, options.author, 'купить книгу Ташкент', 'книги в Узбекистане', 'онлайн книжный магазин'].filter(Boolean) as string[]
          break

        case 'en':
          title = isBook
            ? `Buy "${cleanName}"${cleanAuthor} Online in Uzbekistan — Best Price | Kitobchi`
            : `Buy "${cleanName}" Online — Price & Delivery | Kitobchi`
          description = isBook
            ? `Buy "${cleanName}"${cleanAuthor} online at the best price on Kitobchi Marketplace. Fast nationwide delivery across Uzbekistan, free aesthetic gift packaging.`
            : `Shop "${cleanName}" stationery online on Kitobchi Marketplace with fast delivery across Uzbekistan.`
          keywords = [cleanName, options.author, 'buy books online Uzbekistan', 'Tashkent bookshop', 'Kitobchi'].filter(Boolean) as string[]
          break

        case 'ja':
          title = isBook
            ? `「${cleanName}」${cleanAuthor} を購入 — 価格・レビュー | Kitobchi`
            : `「${cleanName}」を購入 — 価格・配送 | Kitobchi`
          description = isBook
            ? `Kitobchiマーケットプレイスで「${cleanName}」${cleanAuthor}を最安値でオンライン購入。ウズベキスタン全土への迅速な配送、無料ギフト包装対応。`
            : `Kitobchiで「${cleanName}」をオンライン購入。ウズベキスタン全土への配送。`
          keywords = [cleanName, options.author, 'ウズベキスタン本屋', 'Kitobchi', '本オンライン購入'].filter(Boolean) as string[]
          break

        case 'uz':
        default:
          title = isBook
            ? `${cleanName}${cleanAuthor} — Narxi, Sharhlar va Sotib olish | Kitobchi`
            : `${cleanName} — Narxi va Yetkazib berish | Kitobchi`
          description = isBook
            ? `${cleanName}${cleanAuthor} kitobini eng qulay narxda Kitobchi marketpleysidan xarid qiling. O‘zbekiston bo‘ylab tez yetkazib berish, bepul estetik sovg‘a o‘rami!`
            : `${cleanName} kanselyariya mahsulotini arzon narxda Kitobchi marketpleysidan xarid qiling. Butun O‘zbekiston bo‘ylab tez yetkazib berish.`
          keywords = [
            ...getDualScriptKeywords(cleanName, options.author),
            'kitob sotib olish',
            'kitoblar narxi',
            'toshkent kitob dokon',
            'kitobchi uz',
            'yetkazib berish',
          ]
          break
      }

      return {
        title,
        description,
        keywords: keywords.join(', '),
      }
    })

    // Schema.org JSON-LD Structured Data
    const schemaJsonLd = computed(() => {
      const schemas: any[] = []

      // 1. Product / Book Schema
      const productSchema: any = {
        '@context': 'https://schema.org',
        '@type': isBook ? 'Book' : 'Product',
        '@id': `${canonicalUrl}#product`,
        name: options.name,
        image: [imgUrl],
        description: options.description ? options.description.replace(/<[^>]*>?/gm, '').slice(0, 250) : seoData.value.description,
        sku: options.isbn || `KC-${options.type || 'prod'}-${options.urlPath.replace(/[^0-9]/g, '')}`,
        offers: {
          '@type': 'Offer',
          priceCurrency: options.currency || 'UZS',
          price: priceNum,
          priceValidUntil: `${new Date().getFullYear() + 1}-12-31`,
          availability: options.inStock !== false ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
          itemCondition: 'https://schema.org/NewCondition',
          url: canonicalUrl,
          seller: {
            '@type': 'Organization',
            name: 'Kitobchi Marketplace',
            url: siteUrl,
          },
        },
      }

      if (isBook) {
        if (options.author) {
          productSchema.author = {
            '@type': 'Person',
            name: options.author,
          }
        }
        if (options.publisher) {
          productSchema.publisher = {
            '@type': 'Organization',
            name: options.publisher,
          }
        }
        if (options.isbn) {
          productSchema.isbn = options.isbn
        }
      }

      const ratingVal = Number(options.rating || 5.0)
      const reviewCnt = Number(options.reviewsCount || 1)
      if (reviewCnt > 0) {
        productSchema.aggregateRating = {
          '@type': 'AggregateRating',
          ratingValue: ratingVal.toFixed(1),
          reviewCount: reviewCnt,
          bestRating: '5',
          worstRating: '1',
        }
      }

      schemas.push(productSchema)

      // 2. BreadcrumbList Schema
      const breadcrumbList: any = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
          {
            '@type': 'ListItem',
            position: 1,
            name: 'Bosh sahifa',
            item: siteUrl,
          },
          {
            '@type': 'ListItem',
            position: 2,
            name: isBook ? 'Kitoblar' : 'Kanselyariya',
            item: `${siteUrl}/catalog?type=${options.type || 'book'}`,
          },
        ],
      }

      if (options.categoryName) {
        breadcrumbList.itemListElement.push({
          '@type': 'ListItem',
          position: 3,
          name: options.categoryName,
          item: `${siteUrl}/catalog?type=${options.type || 'book'}`,
        })
        breadcrumbList.itemListElement.push({
          '@type': 'ListItem',
          position: 4,
          name: options.name,
          item: canonicalUrl,
        })
      } else {
        breadcrumbList.itemListElement.push({
          '@type': 'ListItem',
          position: 3,
          name: options.name,
          item: canonicalUrl,
        })
      }

      schemas.push(breadcrumbList)

      return schemas
    })

    // Apply useHead and useSeoMeta
    useHead({
      title: computed(() => seoData.value.title),
      meta: [
        { name: 'description', content: computed(() => seoData.value.description) },
        { name: 'keywords', content: computed(() => seoData.value.keywords) },
        // Multi-language alternative hints
        { name: 'language', content: computed(() => locale.value) },
      ],
      // TUZATILDI: bu yerda ilgari `?lang=uz/ru/en/ja` bilan tugaydigan
      // hreflang alternate havolalar bor edi. Bular NOTO'G'RI edi — `locale`
      // aslida cookie (`kc_locale`) orqali saqlanadi, URL query parametri
      // orqali EMAS (useLocale.ts'ga qarang), va mahsulot nomi/tavsifi kabi
      // asosiy kontent hali faqat o'zbekcha (faqat header navigatsiya
      // matnlari tarjima qilingan). Ya'ni bu 4 ta "til varianti" aslida bir
      // xil (o'zbekcha) kontentga olib borardi — Google buni yolg'on/aldov
      // signali sifatida qabul qilishi yoki e'tiborsiz qoldirishi mumkin
      // edi, hech qanday foyda keltirmasdan. To'g'ri, real ko'p tilli
      // kontent (backendda tarjima qilingan mahsulot nomi/tavsifi) qo'shilib,
      // haqiqatan HAR BIR til uchun alohida URL/render paydo bo'lgandagina
      // hreflang qaytarib qo'yish kerak.
      link: [
        { rel: 'canonical', href: canonicalUrl },
      ],
      script: [
        {
          type: 'application/ld+json',
          children: computed(() => JSON.stringify(schemaJsonLd.value)),
        },
      ],
    })

    useSeoMeta({
      title: computed(() => seoData.value.title),
      description: computed(() => seoData.value.description),
      ogTitle: computed(() => seoData.value.title),
      ogDescription: computed(() => seoData.value.description),
      ogImage: imgUrl,
      ogUrl: canonicalUrl,
      ogType: isBook ? 'book' : 'website',
      ogSiteName: 'Kitobchi',
      twitterCard: 'summary_large_image',
      twitterTitle: computed(() => seoData.value.title),
      twitterDescription: computed(() => seoData.value.description),
      twitterImage: imgUrl,
    })
  }

  /**
   * Set Category page 4-language SEO
   */
  function setCategorySeo(categoryName: string, type: 'book' | 'stationery' = 'book', slug: string) {
    const canonicalUrl = `${siteUrl}/category/${slug}`
    const cyrillicCat = latinToCyrillic(categoryName)
    const isBookCat = type !== 'stationery'

    // TUZATILDI: bu yerda til shablonlari HAR DOIM "kitoblar" so'zi bilan
    // yozilgan edi — `type` parametri qabul qilinsa-da, matnda umuman
    // ishlatilmasdi. Natijada kanselyariya kategoriyalari (masalan
    // "Daftarlar") uchun ham sarlavha "... kitoblari ..." deb chiqardi.
    // Endi `isBookCat`ga qarab ikkala holat uchun ham to'g'ri so'z tanlanadi.
    const titles: Record<AppLocale, string> = isBookCat ? {
      uz: `${categoryName} kitoblari — Narxlar va Yetkazib berish | Kitobchi`,
      ru: `Книги категории «${categoryName}» в Ташкенте — Купить в Kitobchi`,
      en: `${categoryName} Books in Uzbekistan — Buy Online | Kitobchi`,
      ja: `「${categoryName}」カテゴリーの本 — オンライン購入 | Kitobchi`,
    } : {
      uz: `${categoryName} — Narxlar va Yetkazib berish | Kitobchi`,
      ru: `«${categoryName}» в Ташкенте — Купить в Kitobchi`,
      en: `${categoryName} in Uzbekistan — Buy Online | Kitobchi`,
      ja: `「${categoryName}」— オンライン購入 | Kitobchi`,
    }

    const descriptions: Record<AppLocale, string> = isBookCat ? {
      uz: `${categoryName} bo‘limidagi eng sara kitoblar va yangi nashrlar Kitobchi marketpleysida. O‘zbekiston bo‘ylab tez yetkazib berish.`,
      ru: `Большой выбор книг в категории «${categoryName}» по выгодным ценам. Быстрая доставка по Ташкенту и всему Узбекистану на Kitobchi.`,
      en: `Explore top books in "${categoryName}" category at Kitobchi Marketplace. Fast delivery across Uzbekistan.`,
      ja: `Kitobchiで「${categoryName}」カテゴリーの本を多数取り揃えています。ウズベキスタン全土への配送。`,
    } : {
      uz: `${categoryName} bo‘limidagi kanselyariya mahsulotlari Kitobchi marketpleysida. O‘zbekiston bo‘ylab tez yetkazib berish.`,
      ru: `Большой выбор канцтоваров в категории «${categoryName}» по выгодным ценам на Kitobchi. Быстрая доставка по Узбекистану.`,
      en: `Explore "${categoryName}" stationery at Kitobchi Marketplace. Fast delivery across Uzbekistan.`,
      ja: `Kitobchiで「${categoryName}」の文房具を取り揃えています。ウズベキスタン全土への配送。`,
    }

    useHead({
      title: computed(() => titles[locale.value as AppLocale] || titles.uz),
      meta: [
        { name: 'description', content: computed(() => descriptions[locale.value as AppLocale] || descriptions.uz) },
        { name: 'keywords', content: `${categoryName}, ${cyrillicCat}, kitoblar, toifa, online kitob dokon, sotib olish` },
      ],
      // TUZATILDI: setProductSeo'dagi bilan bir xil sabab — yolg'on
      // `?lang=` hreflang havolalar olib tashlandi (izoh yuqorida).
      link: [
        { rel: 'canonical', href: canonicalUrl },
      ],
    })

    // TUZATILDI: bu yerda JSON-LD (BreadcrumbList) umuman yo'q edi —
    // faqat mahsulot sahifasida (setProductSeo) bor edi. Kategoriya
    // sahifalari ham Google'ning "breadcrumb" rich-natijasida to'g'ri
    // chiqishi uchun shu yerga ham qo'shildi.
    const breadcrumbSchema = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Bosh sahifa', item: siteUrl },
        { '@type': 'ListItem', position: 2, name: type === 'stationery' ? 'Kanselyariya' : 'Kitoblar', item: `${siteUrl}/catalog?type=${type}` },
        { '@type': 'ListItem', position: 3, name: categoryName, item: canonicalUrl },
      ],
    }

    useHead({
      script: [
        {
          type: 'application/ld+json',
          children: JSON.stringify(breadcrumbSchema),
        },
      ],
    })

    useSeoMeta({
      title: computed(() => titles[locale.value as AppLocale] || titles.uz),
      description: computed(() => descriptions[locale.value as AppLocale] || descriptions.uz),
      ogTitle: computed(() => titles[locale.value as AppLocale] || titles.uz),
      ogDescription: computed(() => descriptions[locale.value as AppLocale] || descriptions.uz),
      ogUrl: canonicalUrl,
      ogType: 'website',
    })
  }

  return {
    setProductSeo,
    setCategorySeo,
  }
}
