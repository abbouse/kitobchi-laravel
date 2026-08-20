<template>
  <main class="max-md:grow h-full md:min-h-dvh bg-[#f1f1f1] lg:bg-white">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-[0_4px_10px_rgba(0,0,0,0.05)]">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-[--ui-container] mx-auto">
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
            <h1 class="text-xl sm:text-xl text-primary font-bold text-center m-0">Buyurtmalarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>
    
    <div class="py-6 min-h-dvh">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-[--ui-container] mx-auto">
        
        <div class="pb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button @click="$router.back()" type="button" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25 border-none bg-transparent cursor-pointer">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="relative min-w-0">
              <ol class="flex items-center gap-2 p-0 m-0 list-none">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <NuxtLink to="/" class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-medium transition-colors text-[#8F8FA1] text-sm no-underline hover:text-neutral-900">
                    <span class="truncate">Asosiy</span>
                  </NuxtLink>
                </li>
                <li class="flex"><span class="text-neutral-400 text-xs"> / </span></li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <span class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-semibold text-[#8F8FA1] text-sm text-neutral-900">
                    <span class="truncate">Profil</span>
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>

        <div v-if="!authStore.isAuthenticated" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl mt-4 max-w-2xl mx-auto shadow-sm">
          <div class="w-24 h-24 bg-[#F6F6F9] rounded-full flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <h2 class="text-2xl font-bold text-neutral-900 mb-2 m-0">Avtorizatsiya</h2>
          <p class="text-neutral-500 mb-8 max-w-xs text-center m-0">Shaxsiy kabinetga kirish uchun tizimga kiring</p>
          <button @click="$router.push('/login')" class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer">
            Kirish
          </button>
        </div>

        <div v-else class="flex flex-col lg:flex-row gap-5">
          <ProfileSidebar active="orders" />
          
          <div class="w-full">
            <div class="max-md:min-h-dvh flex flex-col max-md:pb-2">
              <div class="p-4 md:p-6 rounded-3xl bg-secondary-50">

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  
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
