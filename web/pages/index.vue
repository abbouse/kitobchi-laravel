<template>
  <div class="bg-white">
    <h1 class="sr-only">Kitobchi — Online kitoblar va kanselyariya marketpleysi</h1>

    <!-- ====== HERO BANNERS CAROUSEL ====== -->
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto max-md:px-0 max-md:p-0!">
      <section class="md:py-6">
        <div class="relative overflow-hidden rounded-2xl lg:rounded-[30px]">
          <div class="flex items-center gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory">
            <div
              v-for="(banner, idx) in banners"
              :key="idx"
              class="min-w-0 shrink-0 basis-[90%] md:basis-full snap-center"
            >
              <a
                :href="banner.url || '/catalog'"
                class="relative w-full aspect-520/141 h-full rounded-2xl lg:rounded-[30px] overflow-hidden block group"
              >
                <img
                  :src="banner.image"
                  :alt="banner.title || 'Kitobchi aksiya'"
                  class="w-full h-full object-cover transform transition-transform duration-700 group-hover:scale-105"
                  loading="eager"
                  fetchpriority="high"
                />
                <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/10 transition-colors duration-300 pointer-events-none"></div>
              </a>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- ====== CATEGORIES CIRCLE CAROUSEL (Piyola 1:1) ====== -->
    <section v-if="categories.length > 0" class="py-6 md:py-10">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <h2 class="font-bold text-[24px] md:text-[36px] text-primary leading-[100%] capitalize mb-4">
          Kataloglar
        </h2>
        <div class="flex items-start flex-row -ms-4 gap-[20px] overflow-x-auto no-scrollbar pb-2">
          <div
            v-for="cat in categories"
            :key="cat.id"
            class="min-w-0 shrink-0 ps-4 basis-1/4 md:basis-1/6 lg:basis-1/8"
          >
            <NuxtLink
              :to="`/catalog?category=${cat.id}`"
              class="group/item flex flex-col items-center gap-2 no-underline"
            >
              <div class="min-w-[90px] min-h-[90px] w-full h-full max-w-[192px] max-h-[192px] rounded-full overflow-hidden border border-transparent group-hover/item:border-primary transition-colors duration-300 bg-secondary-100 flex items-center justify-center">
                <div class="relative w-full aspect-square">
                  <img
                    :src="cat.image ? (cat.image.startsWith('http') ? cat.image : `/storage/${cat.image}`) : '/images/logo/logo_blue.png'"
                    :alt="cat.name || cat.name_uz"
                    class="w-full h-full object-cover transform transition-transform duration-500 group-hover/item:scale-110"
                    loading="lazy"
                  />
                </div>
              </div>
              <span class="font-medium md:font-semibold group-hover/item:font-bold group-hover/item:underline text-sm md:text-base leading-6 text-center text-neutral-900 group-hover/item:text-primary transition-all duration-300 truncate max-w-full">
                {{ cat.name || cat.name_uz }}
              </span>
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- ====== SECTION 1: YANGI KELGAN KITOBLAR ====== -->
    <section v-if="newBooks.length > 0" class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-3 md:mb-5 lg:mb-8">
          <h2 class="font-bold text-xl md:text-4xl leading-[100%] text-primary m-0 capitalize flex items-center gap-2">
            Yangi kelgan kitoblar
          </h2>
          <NuxtLink to="/catalog?sort=new" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 no-underline">
            Barchasi
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-4 md:mb-6">
          <ProductCard
            v-for="book in newBooks"
            :key="'new-' + book.id"
            :product="book"
            type="book"
          />
        </div>

        <div class="flex justify-center">
          <NuxtLink
            to="/catalog?sort=new"
            class="font-medium items-center py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/75 h-12 flex justify-center sm:min-w-40 rounded-2xl text-base px-6 max-md:w-full no-underline transition-colors"
          >
            Barchasini ko‘rish
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- ====== SECTION 2: TAVSIYA ETAMIZ ====== -->
    <section v-if="recommendedBooks.length > 0" class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-3 md:mb-5 lg:mb-8">
          <h2 class="font-bold text-xl md:text-4xl leading-[100%] text-primary m-0 capitalize flex items-center gap-2">
            Tavsiya etamiz
          </h2>
          <NuxtLink to="/catalog?sort=popular" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 no-underline">
            Barchasi
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-4 md:mb-6">
          <ProductCard
            v-for="book in recommendedBooks"
            :key="'rec-' + book.id"
            :product="book"
            type="book"
          />
        </div>

        <div class="flex justify-center">
          <NuxtLink
            to="/catalog?sort=popular"
            class="font-medium items-center py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/75 h-12 flex justify-center sm:min-w-40 rounded-2xl text-base px-6 max-md:w-full no-underline transition-colors"
          >
            Barchasini ko‘rish
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

// SSR Data Fetching for maximum SEO
const { data: homeData } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/home`, {
  lazy: false
})

const banners = computed(() => {
  const raw = homeData.value?.banners || []
  if (raw.length > 0) {
    return raw.map((b: any) => ({
      image: b.image ? (b.image.startsWith('http') ? b.image : `/storage/${b.image}`) : '/images/banner.png',
      url: b.url || '/catalog',
      title: b.title || 'Kitobchi aksiya'
    }))
  }
  return [
    { image: '/images/screenshots/kitobchi_b.png', url: '/catalog', title: 'Kitobchi' }
  ]
})

const categories = computed(() => {
  return homeData.value?.categories || homeData.value?.category || []
})

const newBooks = computed(() => {
  return homeData.value?.new_books || homeData.value?.newBooks || []
})

const recommendedBooks = computed(() => {
  return homeData.value?.recommended_books || homeData.value?.topBooks || []
})

// Rich SEO Metadata & JSON-LD
useSeoMeta({
  title: 'Kitobchi — Online kitoblar va kanselyariya marketpleysi',
  description: 'Kitobchi — O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. Tezkor yetkazib berish, qulay narxlar va original kitoblar.',
  ogTitle: 'Kitobchi — Online kitoblar va kanselyariya marketpleysi',
  ogDescription: 'O‘zbekistondagi eng katta online kitoblar va kanselyariya marketpleysi. 10 000 dan ortiq original kitoblar.',
  ogImage: '/images/logo/logo_blue.png',
  ogType: 'website'
})

useHead({
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Kitobchi Marketpleysi',
        url: 'https://kitobchi.com',
        potentialAction: {
          '@type': 'SearchAction',
          target: 'https://kitobchi.com/catalog?search={search_term_string}',
          'query-input': 'required name=search_term_string'
        }
      })
    }
  ]
})
</script>
