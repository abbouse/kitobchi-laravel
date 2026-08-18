<template>
  <div
    v-if="searchStore.isOpen"
    class="fixed inset-0 z-60 bg-white flex flex-col"
  >
    <!-- Top bar: input + Bekor qilish (piyola 1:1) -->
    <div class="shrink-0 border-b border-gray-100 py-4">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto flex items-center gap-3">
        <div class="relative overflow-hidden flex-1 rounded-[20px] h-12 bg-secondary-300 flex-center gap-3 px-4">
          <i class="icon-search text-xl text-gray-500 shrink-0"></i>
          <input
            ref="inputEl"
            v-model="query"
            type="text"
            placeholder="Kitobchi’da izlash"
            class="flex-1 bg-transparent border-none outline-none text-sm text-neutral-900 h-full w-full min-w-0"
            @keyup.enter="goToFullResults"
          />
          <button
            v-if="query"
            type="button"
            @click="query = ''"
            class="shrink-0 text-gray-400 hover:text-gray-500 border-none bg-transparent cursor-pointer p-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <button
          type="button"
          @click="close"
          class="shrink-0 text-sm font-semibold text-neutral-700 hover:opacity-70 border-none bg-transparent cursor-pointer whitespace-nowrap"
        >
          Bekor qilish
        </button>
      </div>
    </div>

    <!-- Results -->
    <div class="flex-1 overflow-y-auto py-5">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <!-- Empty state (piyola 1:1: "Biror nima yozing") -->
        <div v-if="!query.trim()" class="text-center py-20">
          <p class="text-sm text-neutral-400 m-0">Biror nima yozing</p>
        </div>

        <!-- Loading shimmer skeleton -->
        <div v-else-if="pending" class="space-y-3">
          <div class="h-3.5 shimmer rounded w-28 mb-4"></div>
          <div class="h-16 shimmer rounded-2xl w-full"></div>
          <div class="h-16 shimmer rounded-2xl w-full"></div>
          <div class="h-16 shimmer rounded-2xl w-full"></div>
        </div>

        <template v-else>
          <!-- Takliflar (suggestions) -->
          <div v-if="suggestions.length" class="mb-6">
            <h3 class="text-xs font-bold text-neutral-400 uppercase mb-3 m-0">Takliflar</h3>
            <div class="flex flex-wrap gap-2 mt-3">
              <button
                v-for="(s, i) in suggestions"
                :key="i"
                type="button"
                @click="query = s.text"
                class="px-3 py-2 rounded-full bg-secondary-200 text-sm text-neutral-700 border-none cursor-pointer hover:bg-secondary-400 transition-colors"
              >
                {{ s.text }}
              </button>
            </div>
          </div>

          <!-- Mahsulotlar (products) -->
          <div v-if="products.length">
            <h3 class="text-xs font-bold text-neutral-400 uppercase mb-3 m-0">Mahsulotlar</h3>
            <div class="space-y-1 mt-3">
              <NuxtLink
                v-for="p in products"
                :key="`${p.type}-${p.id}`"
                :to="productLink(p)"
                @click="close"
                class="flex items-center gap-3 py-2.5 px-2 rounded-xl hover:bg-gray-50 transition-colors"
              >
                <div class="w-11 h-14 rounded-lg overflow-hidden bg-secondary-100 shrink-0 flex items-center justify-center">
                  <img :src="resolveProductImage(p)" :alt="p.name" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-neutral-900 line-clamp-2">{{ p.name }}</div>
                  <div class="text-xs text-primary font-bold mt-1">{{ formatPrice(p.price) }} so‘m</div>
                </div>
              </NuxtLink>
            </div>

            <button
              type="button"
              @click="goToFullResults"
              class="w-full mt-4 py-3 rounded-2xl bg-secondary-200 text-primary text-sm font-semibold border-none cursor-pointer hover:bg-secondary-400 transition-colors"
            >
              Barcha natijalarni ko‘rish
            </button>
          </div>

          <!-- No results -->
          <div v-else-if="!suggestions.length" class="text-center py-20">
            <p class="text-sm text-neutral-400 m-0">Hech narsa topilmadi</p>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useSearchStore } from '~/stores/search'

const searchStore = useSearchStore()
const config = useRuntimeConfig()
const router = useRouter()

const query = ref('')
const pending = ref(false)
const suggestions = ref<any[]>([])
const products = ref<any[]>([])
const inputEl = ref<HTMLInputElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
let requestSeq = 0

// Overlay ochilganda inputga fokus beramiz (piyola'dagi kabi klaviatura
// darrov chiqadi) va oldingi qidiruv holatini tozalaymiz.
watch(() => searchStore.isOpen, (open) => {
  if (open) {
    query.value = ''
    suggestions.value = []
    products.value = []
    pending.value = false
    nextTick(() => inputEl.value?.focus())
  }
})

watch(query, (val) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  const trimmed = val.trim()
  if (!trimmed) {
    suggestions.value = []
    products.value = []
    pending.value = false
    requestSeq++
    return
  }
  pending.value = true
  debounceTimer = setTimeout(() => runSearch(trimmed), 300)
})

// Har ikkalasini PARALLEL so'raymiz (ketma-ket useFetch waterfall
// yaratmaslik uchun — bu loyihada ilgari home/kategoriya sahifalarida
// xuddi shu turdagi xato topilib tuzatilgan edi).
async function runSearch(q: string) {
  const seq = ++requestSeq
  try {
    const [suggRes, searchRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/search/suggestions`, { query: { q } }).catch(() => null),
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/search/`, { query: { q, type: 'all', sort: 'relevance', page: 1 } }).catch(() => null)
    ])
    // Eskirgan (stale) javob — foydalanuvchi shu orada yana yozgan bo'lsa,
    // eski natija yangisini bosib qo'ymasligi kerak.
    if (seq !== requestSeq) return
    suggestions.value = (suggRes?.data || []).slice(0, 8)
    products.value = (searchRes?.data || []).slice(0, 6)
  } finally {
    if (seq === requestSeq) pending.value = false
  }
}

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

// ProductCard.vue / cart/index.vue bilan bir xil slug mantig'i.
function productLink(p: any) {
  const slug = (p.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return p.type === 'stationery' ? `/stationery/${p.id}-${slug}` : `/books/${p.id}-${slug}`
}

function goToFullResults() {
  const trimmed = query.value.trim()
  if (!trimmed) return
  close()
  router.push(`/catalog?search=${encodeURIComponent(trimmed)}`)
}

function close() {
  searchStore.close()
}
</script>
