<template>
  <section v-if="posts && posts.length > 0" class="py-6 md:py-10">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Section Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
        <div>
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold mb-2">
            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
            Kitobxonlar Klubi
          </div>
          <h2 class="font-extrabold text-xl sm:text-2xl md:text-3xl tracking-tight text-neutral-900 m-0">
            Kitobxonlar fikrlari va taqrizlari
          </h2>
          <p class="text-xs sm:text-sm text-neutral-500 mt-1 m-0">
            O‘quvchilarimiz tomonidan yozilgan samimiy taassurotlar va tavsiyalar
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Scroll Navigation Buttons (Desktop) -->
          <div class="hidden sm:flex items-center gap-1.5">
            <button
              type="button"
              @click="scrollPrev"
              aria-label="Oldingi"
              class="w-9 h-9 rounded-full bg-white hover:bg-neutral-100 border border-neutral-200/80 text-neutral-700 hover:text-neutral-950 flex items-center justify-center transition-colors cursor-pointer shadow-2xs"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
              </svg>
            </button>
            <button
              type="button"
              @click="scrollNext"
              aria-label="Keyingi"
              class="w-9 h-9 rounded-full bg-white hover:bg-neutral-100 border border-neutral-200/80 text-neutral-700 hover:text-neutral-950 flex items-center justify-center transition-colors cursor-pointer shadow-2xs"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>

          <!-- All Link -->
          <NuxtLink
            to="/club"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-primary hover:text-primary-hover transition-colors no-underline px-4 py-2 rounded-full bg-primary/10 hover:bg-primary/15"
          >
            <span>Klubga o‘tish</span>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </NuxtLink>
        </div>
      </div>

      <!-- Horizontal Carousel -->
      <div
        ref="carouselRef"
        class="flex items-stretch gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory pb-3 -mx-4 px-4 sm:mx-0 sm:px-0"
      >
        <div
          v-for="post in posts"
          :key="post.id"
          class="shrink-0 w-[280px] sm:w-[340px] md:w-[380px] snap-start flex"
        >
          <BookClubCard
            :post="post"
            :compact="true"
            class="w-full h-full"
          />
        </div>

        <!-- Join Club CTA Card at the end -->
        <div class="shrink-0 w-[260px] sm:w-[300px] snap-start flex">
          <div class="w-full bg-gradient-to-br from-[#0b0342] via-[#170e56] to-[#2a1b7e] rounded-3xl p-6 text-white flex flex-col justify-between shadow-xs relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-primary/30 blur-2xl pointer-events-none"></div>

            <div class="relative z-10">
              <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </div>
              <h3 class="font-extrabold text-lg text-white mb-2 leading-snug">
                Siz ham fikringizni ulashing!
              </h3>
              <p class="text-xs text-neutral-200 leading-relaxed font-normal">
                O‘qigan kitoblaringiz haqida yozing, boshqalar bilan samimiy bahslashish zavqini his qiling.
              </p>
            </div>

            <NuxtLink
              to="/club"
              class="relative z-10 w-full mt-6 py-2.5 px-4 rounded-full bg-white text-[#0b0342] font-bold text-xs sm:text-sm text-center block no-underline hover:bg-neutral-100 transition-colors shadow-2xs"
            >
              Klubga qo‘shilish
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  posts: any[]
}>()

const carouselRef = ref<HTMLElement | null>(null)

function scrollPrev() {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: -360, behavior: 'smooth' })
  }
}

function scrollNext() {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: 360, behavior: 'smooth' })
  }
}
</script>
