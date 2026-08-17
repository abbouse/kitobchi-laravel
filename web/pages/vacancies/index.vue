<template>
  <main class="max-md:pb-[71px]">
    <div class="vacancies-page min-h-screen overflow-hidden">
      <!-- HERO (piyola 1:1 — vacancies-hero* klasslar piyola-extra.css'da) -->
      <section class="relative vacancies-hero pt-8 pb-14 md:pt-24 md:pb-36">
        <div class="vacancies-hero__grid"></div>
        <div class="vacancies-hero__orb vacancies-hero__orb--1"></div>
        <div class="vacancies-hero__orb vacancies-hero__orb--2"></div>
        <div class="vacancies-hero__orb vacancies-hero__orb--3"></div>
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10">
          <div class="max-w-3xl mx-auto text-center">
            <div class="vacancies-hero__badge inline-flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 rounded-full text-xs md:text-sm font-medium text-white/90 mb-5 md:mb-8">
              <span class="vacancies-hero__pulse"></span>
              Jamoamizga qo'shiling
            </div>
            <h1 class="vacancies-hero__title text-[1.75rem] leading-tight sm:text-4xl md:text-6xl font-bold text-white mb-4 md:mb-6 tracking-tight">
              Vakansiyalar
            </h1>
            <p class="text-sm sm:text-base md:text-xl text-white/70 leading-relaxed max-w-2xl mx-auto mb-6 md:mb-10 px-1">
              Kitobchi jamoasiga qo'shiling va o'z karyerangizni biz bilan boshlang
            </p>
            <div v-if="!pending" class="inline-flex items-center gap-2 md:gap-3 px-4 py-2.5 md:px-6 md:py-3 rounded-xl md:rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-white text-sm md:text-base vacancies-hero__counter">
              <svg class="w-5 h-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.66.194-1.34.35-2.036.467m-4.464-8.007a3 3 0 0 0-3-2.774H9.5a3 3 0 0 0-3 2.774m10.5 0v-.9a3 3 0 0 0-3-3h-4.5a3 3 0 0 0-3 3v.9m10.5 0c-.663.03-1.328.055-1.996.076m-8.508 0a48.5 48.5 0 0 0-1.996-.076" /></svg>
              <span class="font-semibold">{{ vacancies.length }} ta ochiq lavozim</span>
            </div>
          </div>
        </div>
        <div class="vacancies-hero__wave"></div>
      </section>

      <!-- VAKANSIYALAR RO'YXATI -->
      <section class="relative pb-6 md:pb-28 -mt-2 md:-mt-4">
        <div class="vacancies-bg__blob vacancies-bg__blob--1"></div>
        <div class="vacancies-bg__blob vacancies-bg__blob--2"></div>
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10">
          <!-- Skeleton -->
          <div v-if="pending" class="grid gap-3 md:gap-4 sm:grid-cols-2 max-w-5xl mx-auto">
            <div v-for="i in 4" :key="i" class="vacancies-skeleton rounded-xl h-24"></div>
          </div>

          <!-- Bo'sh holat -->
          <div v-else-if="vacancies.length === 0" class="vacancies-empty max-w-md mx-auto rounded-2xl p-8 text-center">
            <div class="vacancies-empty__icon w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 text-neutral-400">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25" /></svg>
            </div>
            <h3 class="text-lg font-bold text-neutral-800 mb-1">Hozircha ochiq lavozimlar yo'q</h3>
            <p class="text-sm text-neutral-500">Yaqin orada yangi vakansiyalar qo'shiladi, kuzatib boring.</p>
          </div>

          <!-- Kartalar -->
          <div v-else class="grid gap-3 md:gap-4 sm:grid-cols-2 max-w-5xl mx-auto">
            <NuxtLink
              v-for="(v, idx) in vacancies"
              :key="v.id"
              :to="`/vacancies/${v.id}`"
              class="vacancy-card group block"
              :style="{ '--delay': (idx * 0.06) + 's' }"
            >
              <article class="vacancy-card__inner flex overflow-hidden rounded-xl bg-white">
                <div class="vacancy-card__rail shrink-0 flex items-center justify-center">
                  <span class="vacancy-card__number select-none">{{ String(idx + 1).padStart(2, '0') }}</span>
                </div>
                <div class="vacancy-card__body flex flex-1 min-w-0 p-3 pr-2.5 md:p-4 md:pr-3.5">
                  <div class="flex items-start gap-3 w-full">
                    <div class="flex-1 min-w-0">
                      <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-2">
                        <span class="vacancy-card__status">
                          <span class="vacancy-card__dot"></span>
                          Faol
                        </span>
                        <template v-if="v.contract_type">
                          <span class="vacancy-card__divider max-[360px]:hidden">·</span>
                          <span class="vacancy-card__company max-[360px]:hidden">{{ v.contract_type }}</span>
                        </template>
                      </div>
                      <h2 class="vacancy-card__title">{{ v.title }}</h2>
                      <div v-if="v.location" class="vacancy-card__meta">
                        <span class="vacancy-card__meta-item">
                          <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                          <span class="min-w-0 break-words">{{ v.location }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="vacancy-card__action shrink-0">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                    </div>
                  </div>
                </div>
              </article>
            </NuxtLink>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

const { data, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/vacancies`, {
  lazy: false
})

const vacancies = computed(() => data.value?.data || [])

useSeoMeta({
  title: 'Vakansiyalar — Kitobchi',
  description: "Kitobchi jamoasiga qo'shiling — ochiq ish o'rinlari va vakansiyalar."
})
</script>
