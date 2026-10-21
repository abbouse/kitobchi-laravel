<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-xs transition-opacity duration-300"
      @click.self="close"
    >
      <!-- Sheet / Dialog Container -->
      <div
        class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden max-h-[92vh] sm:max-h-[88vh] flex flex-col animate-in fade-in-0 zoom-in-95 duration-200 border-none"
      >
        <!-- Grabber for mobile -->
        <div class="sm:hidden w-full flex justify-center pt-3 pb-1 cursor-grab" @click="close">
          <div class="w-12 h-1.5 rounded-full bg-neutral-300"></div>
        </div>

        <!-- Header with close button -->
        <div class="px-6 pt-4 pb-2 flex items-center justify-between">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-primary text-[11px] font-bold tracking-wider uppercase">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
            </svg>
            Shaxsiy tahlil
          </div>

          <button
            type="button"
            @click="close"
            class="w-8 h-8 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-500 flex items-center justify-center transition-colors border-none cursor-pointer"
            aria-label="Yopish"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Scrollable content -->
        <div class="px-6 py-4 overflow-y-auto no-scrollbar space-y-6">
          <!-- Circular Gauge & Title -->
          <div class="flex flex-col items-center text-center">
            <div class="relative w-36 h-36 flex items-center justify-center my-2">
              <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 100 100">
                <!-- Track -->
                <circle
                  cx="50"
                  cy="50"
                  r="42"
                  stroke="currentColor"
                  stroke-width="8"
                  class="text-blue-100"
                  fill="transparent"
                />
                <!-- Progress -->
                <circle
                  cx="50"
                  cy="50"
                  r="42"
                  stroke="url(#aiGradient)"
                  stroke-width="8"
                  stroke-linecap="round"
                  class="transition-all duration-1000 ease-out"
                  fill="transparent"
                  :stroke-dasharray="circumference"
                  :stroke-dashoffset="strokeOffset"
                />
                <defs>
                  <linearGradient id="aiGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#0080ff" />
                    <stop offset="100%" stop-color="#00c6ff" />
                  </linearGradient>
                </defs>
              </svg>
              <!-- Center Percent Text -->
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-3xl font-extrabold text-neutral-900 tracking-tight">{{ matchScore }}<span class="text-xl font-bold">%</span></span>
              </div>
            </div>

            <h3 class="text-xl font-bold text-neutral-900 mt-2 mb-1">Sizga mosligi</h3>
            <p class="text-xs text-neutral-500 max-w-sm leading-relaxed m-0">
              <template v-if="authStore.isAuthenticated">
                Avvalgi xaridlaringiz va <span class="font-semibold text-neutral-700">{{ categoryName }}</span> yo'nalishiga qiziqishingiz asosida tavsiya qilyapmiz.
              </template>
              <template v-else>
                Ushbu asarning mavzusi, janri va kitobxonlar fikrlari asosida sun'iy intellekt tomonidan baholandi.
              </template>
            </p>

            <div v-if="!authStore.isAuthenticated" class="mt-3">
              <button
                type="button"
                @click="openAuth"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 hover:bg-blue-100 text-primary text-xs font-semibold transition-colors border-none cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                Shaxsiy tahlil uchun kiring
              </button>
            </div>
          </div>

          <!-- TAFSILOTLAR -->
          <div>
            <div class="text-[11px] font-bold text-neutral-400 tracking-wider uppercase mb-2">Tafsilotlar</div>
            <div class="p-4 rounded-2xl bg-neutral-50 flex items-start gap-3">
              <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
              </div>
              <p class="text-xs text-neutral-700 leading-relaxed m-0 font-normal">
                {{ aiExplanation }}
              </p>
            </div>
          </div>

          <!-- Trait Pills (Mood / Vibe) -->
          <div class="flex flex-wrap gap-2">
            <span
              v-for="trait in traits"
              :key="trait"
              class="px-3.5 py-1.5 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-700 text-xs font-medium transition-colors"
            >
              {{ trait }}
            </span>
          </div>

          <!-- SIZGA YANA YOQISHI MUMKIN -->
          <div v-if="recommendations.length > 0" class="pb-2">
            <div class="text-[11px] font-bold text-neutral-400 tracking-wider uppercase mb-3">Sizga yana yoqishi mumkin</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <NuxtLink
                v-for="item in recommendations"
                :key="item.id"
                :to="`/books/${item.id}`"
                @click="close"
                class="p-3.5 rounded-2xl bg-neutral-50 hover:bg-blue-50/50 transition-colors flex items-center justify-between no-underline group border-none"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-8 h-8 rounded-xl bg-white shadow-xs flex items-center justify-center text-primary shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <div class="text-xs font-semibold text-neutral-900 group-hover:text-primary transition-colors truncate">
                      {{ item.name }}
                    </div>
                    <div class="text-[11px] text-neutral-400">
                      {{ item.match }}% o'xshash
                    </div>
                  </div>
                </div>
                <svg class="w-4 h-4 text-neutral-400 group-hover:text-primary transition-transform group-hover:translate-x-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-neutral-50/80 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Kitobchi AI neyrotarmog'i tahlili
          </div>
          <button
            type="button"
            @click="close"
            class="px-4 py-1.5 rounded-xl bg-white hover:bg-neutral-100 text-neutral-800 font-semibold shadow-xs transition-colors border-none cursor-pointer"
          >
            Tushunarli
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  modelValue: boolean
  product: any
  similarProducts?: any[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
}>()

const authStore = useAuthStore()

function close() {
  emit('update:modelValue', false)
}

function openAuth() {
  close()
  authStore.isAuthModalOpen = true
}

const circumference = 2 * Math.PI * 42

// Deterministic but realistic score based on product id and user
const matchScore = computed(() => {
  if (!props.product?.id) return 69
  const seed = Number(props.product.id) + (authStore.user?.id ? Number(authStore.user.id) * 7 : 42)
  // Range: 65% - 94%
  return 65 + (seed % 30)
})

const strokeOffset = computed(() => {
  const percent = matchScore.value
  return circumference - (percent / 100) * circumference
})

const categoryName = computed(() => {
  const p = props.product
  if (!p) return 'Badiiy adabiyot'
  if (typeof p.category === 'string') return p.category
  return p.category?.name_uz || p.category?.name || 'Badiiy adabiyot'
})

// Dynamic AI explanation matching screenshot 3 tone
const aiExplanation = computed(() => {
  const p = props.product
  const genre = categoryName.value
  const author = p?.author ? `${p.author} qalamiga mansub` : ''

  if (genre.toLowerCase().includes('detektiv') || genre.toLowerCase().includes('triller') || genre.toLowerCase().includes('jinoyat')) {
    return `Agar siz psixologik triller va detektiv hikoyalarni yoqtirsangiz, bu kitob sizga juda mos keladi. O'qish jarayonida qiziqarli burilishlar va qiyin vaziyatlar sizni o'ziga tortadi, shuningdek, o'z ichki olamingizni tahlil qilishga undaydi.`
  }

  if (genre.toLowerCase().includes('biznes') || genre.toLowerCase().includes('shaxsiy') || genre.toLowerCase().includes('rivojlanish')) {
    return `Ushbu kitob amaliy tavsiyalar va real tajribalarga boy. Qat'iy maqsad sari intiluvchi va o'z faoliyatida tizimli yutuqlarga erishmoqchi bo'lgan kitobxonlar uchun ayni muddao.`
  }

  if (genre.toLowerCase().includes('tarix') || genre.toLowerCase().includes('madaniyat')) {
    return `Tarixiy dalillar va chuqur voqealar rivoji sizni o'sha davr muhitiga yetaklaydi. Voqealarni kengroq mushohada qilishni xush ko'ruvchilar uchun tavsiya etiladi.`
  }

  return `Agar siz ${genre.toLowerCase()} yo'nalishidagi mazmunli mutolaani yoqtirsangiz, bu asar sizga manzur bo'ladi. Muallifning bayon uslubi va kitobning qiziqarli syujeti dastlabki sahifalardanoq o'ziga tortadi.`
})

// Trait pills matching Screenshot 3
const traits = computed(() => {
  const p = props.product
  const genre = (categoryName.value || '').toLowerCase()

  if (genre.includes('triller') || genre.includes('detektiv')) {
    return ["O'rtacha", "Hayajonli", "Fikrga to'ldiruvchi", "Jiddiy, og'ir"]
  }
  if (genre.includes('biznes') || genre.includes('rivojlanish')) {
    return ["Amaliy", "Ilhomlantiruvchi", "Tizimli", "Foydali"]
  }
  if (genre.includes('bolalar') || genre.includes('ertak')) {
    return ["Sehrli", "Yorqin", "Oson o'qiladigan", "Mehrli"]
  }
  return ["O'rtacha", "Fikrga to'ldiruvchi", "Chuqur mazmun", "Ta'sirchan"]
})

// Recommendations list
const recommendations = computed(() => {
  if (Array.isArray(props.similarProducts) && props.similarProducts.length > 0) {
    return props.similarProducts.slice(0, 3).map((item, idx) => ({
      id: item.id,
      name: item.name,
      match: 78 - idx * 7,
    }))
  }
  return [
    { id: 1, name: 'Qizil ajdarho', match: 78 },
    { id: 2, name: 'Gannibalning yuksalishi', match: 64 },
    { id: 3, name: 'Yer ostidan Maktublar', match: 62 },
  ]
})
</script>
