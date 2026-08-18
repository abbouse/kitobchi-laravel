<template>
  <div class="py-3 md:py-6 min-h-dvh bg-secondary-300 md:bg-gray-50 grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Buyurtmalarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-600 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Buyurtmalarim</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Buyurtmalar tarixini ko'rish uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <ProfileSidebar active="orders" />

        <div class="flex-1 min-w-0">
          <h1 class="text-xl font-bold text-neutral-900 mb-4 max-md:hidden">Buyurtmalarim</h1>

          <!-- MUHIM: piyola'dagi "Faol"/"Tugallangan" tab'lariga funksional
               parallellik uchun qo'shildi — oldin bu sahifada UMUMAN tab
               yo'q edi, barcha buyurtmalar (statusidan qat'iy nazar) bitta
               ro'yxatda chiqardi. Backendda status bo'yicha filtrlash
               parametri yo'qligi (jonli tasdiqlangan) sababli, filtrlash
               allaqachon yuklab olingan sahifalar ustida CLIENT tomonda
               amalga oshiriladi. -->
          <div class="inline-flex bg-secondary-100 rounded-2xl p-1 mb-4">
            <button
              v-for="tab in ORDER_TABS"
              :key="tab.value"
              type="button"
              @click="activeTab = tab.value"
              :class="[
                'px-5 py-2 rounded-xl text-sm font-semibold transition-colors border-none cursor-pointer',
                activeTab === tab.value ? 'bg-primary text-white shadow-xs' : 'bg-transparent text-neutral-500 hover:text-neutral-800'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- Loading skeleton -->
          <div v-if="pending" class="space-y-4">
            <div v-for="n in 4" :key="n" class="shimmer h-28 w-full rounded-2xl"></div>
          </div>

          <!-- Error -->
          <div v-else-if="loadError" class="text-center py-16">
            <p class="text-sm text-neutral-500 mb-4">Buyurtmalarni yuklab bo'lmadi. Birozdan so'ng qayta urinib ko'ring.</p>
            <button type="button" @click="fetchOrders(1)" class="px-5 py-2.5 rounded-xl bg-secondary-100 text-sm font-semibold text-neutral-700 border-none cursor-pointer hover:bg-secondary-400 transition-colors">
              Qayta urinish
            </button>
          </div>

          <!-- Empty -->
          <div v-else-if="!filteredOrders.length" class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-.75 11.25h9a2.25 2.25 0 002.25-2.25l-.75-9a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25l-.75 9a2.25 2.25 0 002.25 2.25z"/></svg>
            </div>
            <p class="text-sm text-neutral-500 mb-4">{{ orders.length ? "Bu bo'limda buyurtmalar mavjud emas" : "Hozircha hech qanday buyurtma mavjud emas" }}</p>
            <NuxtLink to="/catalog" class="inline-flex items-center px-6 py-3 rounded-2xl bg-primary text-white font-semibold text-sm hover:bg-primary/90 transition-colors">
              Katalogga o'tish
            </NuxtLink>
          </div>

          <!-- Orders list -->
          <div v-else class="space-y-4">
            <div v-for="order in filteredOrders" :key="order.id" class="bg-white border border-neutral-100 rounded-2xl p-4 sm:p-6 shadow-sm">
              <div class="flex items-center justify-between gap-3 mb-3">
                <div class="text-sm font-bold text-neutral-900">Buyurtma #{{ order.id }}</div>
                <span :class="statusBadgeClass(order)" class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">{{ statusLabel(order) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm text-neutral-500">
                <span>{{ formatOrderDate(order) }}</span>
                <span>{{ orderItemCount(order) }} mahsulot</span>
              </div>
              <div class="mt-3 pt-3 border-t border-neutral-100 flex items-center justify-between">
                <span class="text-sm text-neutral-500">Jami summa</span>
                <span class="text-base font-bold text-primary">{{ formatPrice(orderTotal(order)) }} so'm</span>
              </div>
            </div>

            <button
              v-if="meta && meta.current_page < meta.last_page"
              type="button"
              @click="loadMore"
              :disabled="loadingMore"
              class="w-full py-3 rounded-2xl bg-secondary-100 text-neutral-700 text-sm font-semibold hover:bg-secondary-400 transition-colors border-none cursor-pointer disabled:opacity-75"
            >
              {{ loadingMore ? 'Yuklanmoqda...' : 'Yana yuklash' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()

const orders = ref<any[]>([])
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
const pending = ref(true)
const loadingMore = ref(false)
const loadError = ref(false)

async function fetchOrders(page = 1) {
  if (page === 1) {
    pending.value = true
    loadError.value = false
  }
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/list`, {
      query: { page, per_page: 15 },
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const data = res?.data || []
    orders.value = page === 1 ? data : [...orders.value, ...data]
    meta.value = res?.meta || null
  } catch (e) {
    loadError.value = true
  } finally {
    pending.value = false
    loadingMore.value = false
  }
}

async function loadMore() {
  if (!meta.value || loadingMore.value) return
  loadingMore.value = true
  await fetchOrders(meta.value.current_page + 1)
}

function orderTotal(order: any) {
  return order.amount ?? order.total ?? order.total_price ?? 0
}

function orderItemCount(order: any) {
  if (Array.isArray(order.items)) return order.items.length
  return order.items_count ?? 0
}

function formatPrice(value: number) {
  return new Intl.NumberFormat('ru-RU').format(value || 0)
}

// `formatUzDate` — utils/formatDate.ts (Nuxt avto-import). Backendning
// `formatted_created_at` (Carbon::isoFormat) rus tilidagi oy nomlari bilan
// qaytishi jonli production API orqali tasdiqlangani uchun xom
// `created_at`dan o'zimiz o'zbekcha formatlaymiz.
function formatOrderDate(order: any): string {
  return formatUzDate(order.created_at) || order.formatted_created_at || ''
}

const STATUS_LABELS: Record<string, string> = {
  pending: 'Kutilmoqda',
  packing: "Yig'ilmoqda",
  in_delivery: 'Yetkazilmoqda',
  delivered: 'Yetkazildi',
  customer_received: 'Qabul qilindi',
  cancelled: 'Bekor qilindi',
  returned: 'Qaytarildi',
}

const STATUS_CLASSES: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-700',
  packing: 'bg-amber-100 text-amber-700',
  in_delivery: 'bg-secondary-100 text-blue',
  delivered: 'bg-secondary-100 text-emerald-600',
  customer_received: 'bg-secondary-100 text-emerald-600',
  cancelled: 'bg-red-100 text-red-700',
  returned: 'bg-red-100 text-red-700',
}

function statusCode(order: any) {
  return order.status_code || order.status || 'pending'
}

function statusLabel(order: any) {
  const code = statusCode(order)
  return STATUS_LABELS[code] || code
}

function statusBadgeClass(order: any) {
  const code = statusCode(order)
  return STATUS_CLASSES[code] || 'bg-secondary-100 text-neutral-600'
}

// "Faol" (hali yakunlanmagan) vs "Tugallangan" (yakuniy holat) — piyola'dagi
// Buyurtmalarim tab'lariga mos.
const ACTIVE_STATUSES = ['pending', 'packing', 'in_delivery']
const COMPLETED_STATUSES = ['delivered', 'customer_received', 'cancelled', 'returned']

const ORDER_TABS = [
  { value: 'active' as const, label: 'Faol' },
  { value: 'completed' as const, label: 'Tugallangan' },
]

const activeTab = ref<'active' | 'completed'>('active')

const filteredOrders = computed(() => {
  return orders.value.filter((order) => {
    const code = statusCode(order)
    return activeTab.value === 'active'
      ? ACTIVE_STATUSES.includes(code)
      : COMPLETED_STATUSES.includes(code)
  })
})

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchOrders(1)
  } else {
    pending.value = false
  }
})

useSeoMeta({ title: 'Buyurtmalarim — Kitobchi' })
</script>
