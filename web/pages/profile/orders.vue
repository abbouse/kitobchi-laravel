<template>
  <main class="max-md:grow h-full md:min-h-dvh bg-[#f1f1f1] lg:bg-white">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
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
            <h1 class="text-xl sm:text-xl text-primary font-bold text-center m-0">Buyurtmalarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="py-6 min-h-dvh">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">

        <div class="pb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button @click="$router.back()" type="button" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25 border-none bg-transparent cursor-pointer">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="relative min-w-0">
              <ol class="flex items-center gap-2 p-0 m-0 list-none">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                  <NuxtLink to="/" class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-medium transition-colors text-[#8F8FA1] text-sm no-underline hover:text-neutral-700">
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
          <button @click="authStore.openAuthModal()" class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors border-none cursor-pointer">
            Kirish
          </button>
        </div>

        <!-- MUHIM: piyolamarket.uz'ning /profile/orders sahifasi bilan
             jonli solishtirildi (2026-08-21). Piyolada BU YERDA "Faol/
             Tugallangan" TAB'LARI UMUMAN YO'Q — hammasi bitta ro'yxatda,
             hech qanday bg-secondary-50/rounded-3xl "karta" o'rash ham
             yo'q (sarlavha va buyurtma kartalari to'g'ridan-to'g'ri sahifa
             foniga chiqadi). Har bir buyurtma o'zi alohida oq (bg-white)
             rounded-2xl karta: {raqam / vaqt / summa} qatori + status
             belgisi (rounded-full pill, TO'LIQ RANGLI fon + oq matn —
             piyolada rgb(11,3,66) yoki holatga qarab boshqa rang, INLINE
             style orqali, Tailwind klassi orqali emas), so'ng chiziq, so'ng
             mahsulot rasmi+nomi+soni va "Buyurtma tafsilotlari" tugmasi.
             Shu struktura pastda takrorlandi. -->
        <div v-else class="flex flex-col lg:flex-row gap-5">
          <ProfileSidebar active="orders" />

          <div class="w-full">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-primary text-xl font-semibold m-0">Buyurtmalaringiz</h2>
            </div>

            <!-- Loading -->
            <div v-if="pending" class="flex flex-col gap-3">
              <div v-for="n in 3" :key="n" class="bg-white rounded-2xl p-4 animate-pulse">
                <div class="h-4 bg-neutral-100 rounded w-1/3 mb-3"></div>
                <div class="h-4 bg-neutral-100 rounded w-1/2"></div>
              </div>
            </div>

            <!-- Error -->
            <div v-else-if="loadError" class="flex flex-col items-center py-12 text-center bg-white rounded-2xl p-6">
              <p class="text-[#8F8FA1] mb-4">Buyurtmalarni yuklashda xatolik yuz berdi</p>
              <button @click="fetchOrders(1)" type="button" class="text-primary font-medium border-none bg-transparent cursor-pointer">Qayta urinish</button>
            </div>

            <!-- Empty -->
            <div v-else-if="orders.length === 0" class="flex flex-col items-center py-16 text-center bg-white rounded-2xl p-6">
              <div class="w-20 h-20 bg-[#F6F6F9] rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-.75 11.25h9a2.25 2.25 0 002.25-2.25l-.75-9a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25l-.75 9a2.25 2.25 0 002.25 2.25z"/></svg>
              </div>
              <p class="font-medium m-0">Buyurtmalar yo'q</p>
            </div>

            <!-- Orders list -->
            <div v-else class="flex flex-col gap-3">
              <div
                v-for="order in orders"
                :key="order.id"
                class="rounded-2xl p-4 bg-white"
              >
                <div class="flex items-center justify-between w-full gap-3 flex-wrap-reverse">
                  <div class="flex items-center gap-8 flex-wrap">
                    <div>
                      <span class="text-[#8F8FA1] text-xs uppercase font-normal">Buyurtma raqami:</span>
                      <p class="text-sm font-medium m-0">№{{ order.id }}</p>
                    </div>
                    <div>
                      <span class="text-[#8F8FA1] text-xs uppercase font-normal">Buyurtma vaqti:</span>
                      <p class="text-sm font-medium m-0">{{ formatOrderDate(order) }}</p>
                    </div>
                    <div>
                      <span class="text-[#8F8FA1] text-xs uppercase font-normal">Buyurtma summasi:</span>
                      <p class="text-sm font-medium m-0">{{ formatPrice(orderTotal(order)) }} so'm</p>
                    </div>
                  </div>
                  <span
                    class="font-medium inline-flex items-center text-sm py-1 gap-1.5 rounded-full px-4"
                    :style="statusBadgeStyle(order)"
                  >{{ statusLabel(order) }}</span>
                </div>

                <div class="flex items-center w-full flex-row my-4">
                  <div class="border-neutral-100 w-full border-solid border-t"></div>
                </div>

                <div class="flex items-center justify-between w-full gap-4">
                  <div class="flex-1 flex gap-3 min-w-0">
                    <div class="flex items-center gap-3 shrink-0">
                      <!-- MUHIM: rasm sifatida faqat type !== 'gift' bo'lgan
                           (ya'ni sovg'a EMAS, xaridorning haqiqiy sotib olgan)
                           birinchi mahsulot ko'rsatiladi. Agar shunday
                           mahsulotlardan yana bo'lsa (+1 va undan ko'p),
                           rasm burchagiga "+N" belgisi chiqadi. */-->
                      <div class="overflow-hidden relative rounded-lg bg-neutral-100 shrink-0" style="width: 70px; height: 93px;">
                        <img v-if="orderThumb(order)" :src="orderThumb(order)" class="w-full h-full object-cover">
                        <span
                          v-if="orderExtraNonGiftCount(order) > 0"
                          class="absolute bottom-1 right-1 h-5 px-1 rounded-full bg-primary text-white text-[11px] font-semibold flex items-center justify-center leading-none shadow-sm" style="min-width: 20px;"
                        >+{{ orderExtraNonGiftCount(order) }}</span>
                      </div>
                    </div>
                    <div class="min-w-0">
                      <h3 class="line-clamp-2 text-sm font-medium m-0">{{ orderFirstItemName(order) }}</h3>
                      <p class="text-[#8F8FA1] text-xs mt-2 m-0">Soni: {{ orderTotalQty(order) }} dona</p>
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="openDetails(order)"
                    class="font-medium items-center transition-colors gap-1.5 text-primary bg-primary/10 hover:bg-primary/15 h-12 flex justify-center rounded-2xl text-base px-6 border-none cursor-pointer shrink-0"
                  >
                    Buyurtma tafsilotlari
                  </button>
                </div>
              </div>

              <!-- Load more -->
              <div v-if="meta && meta.current_page < meta.last_page" class="flex justify-center mt-2">
                <button
                  @click="loadMore"
                  :disabled="loadingMore"
                  type="button"
                  class="font-medium items-center transition-colors py-1.5 text-sm gap-1.5 text-primary bg-primary/10 hover:bg-primary/15 h-10 flex justify-center rounded-xl px-6 border-none cursor-pointer disabled:opacity-60"
                >
                  {{ loadingMore ? 'Yuklanmoqda...' : 'Ko\'proq ko\'rsatish' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Buyurtma tafsilotlari modali -->
  <div
    v-if="isDetailsOpen"
    class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    @click.self="closeDetails"
  >
    <div class="modal-sm-600 relative bg-white rounded-3xl overflow-hidden w-full flex flex-col">
      <div class="flex items-center justify-between p-6 pb-4 shrink-0">
        <h3 class="text-xl font-bold m-0">Buyurtma tafsilotlari</h3>
        <button @click="closeDetails" type="button" class="w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer shrink-0">
          <svg class="w-5 h-5 text-[#8F8FA1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="px-6 pb-6 overflow-y-auto">
        <!-- Loading -->
        <div v-if="detailsLoading" class="flex flex-col gap-3 py-4">
          <div class="h-4 bg-neutral-100 rounded w-1/3 animate-pulse"></div>
          <div class="h-16 bg-neutral-100 rounded animate-pulse"></div>
          <div class="h-16 bg-neutral-100 rounded animate-pulse"></div>
        </div>

        <!-- Error -->
        <div v-else-if="detailsError" class="text-center py-8">
          <p class="text-[#8F8FA1] mb-3">Tafsilotlarni yuklashda xatolik yuz berdi</p>
          <button @click="fetchDetails" type="button" class="text-primary font-medium border-none bg-transparent cursor-pointer">Qayta urinish</button>
        </div>

        <!-- Content -->
        <div v-else-if="selectedOrder" class="flex flex-col gap-5">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-6 flex-wrap">
              <div>
                <span class="text-[#8F8FA1] text-xs uppercase font-normal">Buyurtma raqami:</span>
                <p class="text-sm font-medium m-0">№{{ selectedOrder.id }}</p>
              </div>
              <div>
                <span class="text-[#8F8FA1] text-xs uppercase font-normal">Sana:</span>
                <p class="text-sm font-medium m-0">{{ formatOrderDate(selectedOrder) }}</p>
              </div>
            </div>
            <span
              class="font-medium inline-flex items-center text-sm py-1 gap-1.5 rounded-full px-4"
              :style="statusBadgeStyle(selectedOrder)"
            >{{ statusLabel(selectedOrder) }}</span>
          </div>

          <div v-if="selectedOrderAddress" class="rounded-2xl bg-[#F6F6F9] p-4">
            <span class="text-[#8F8FA1] text-xs uppercase font-normal">Yetkazib berish manzili</span>
            <p class="text-sm font-medium m-0 mt-1">{{ selectedOrderAddress }}</p>
          </div>

          <div>
            <p class="text-sm font-semibold m-0 mb-2">Mahsulotlar</p>
            <div class="flex flex-col gap-3">
              <div v-for="(item, idx) in (selectedOrder.items || [])" :key="idx" class="flex items-center gap-3">
                <div class="overflow-hidden relative rounded-lg bg-neutral-100 shrink-0" style="width: 56px; height: 74px;">
                  <img v-if="item.cover" :src="resolveImageUrl(item.cover)" class="w-full h-full object-cover">
                  <span v-if="item.type === 'gift'" class="absolute top-1 left-1 text-[10px] font-semibold bg-primary text-white rounded-full px-1.5 py-0.5 leading-none">Sovg'a</span>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium m-0 line-clamp-2">{{ item.name }}</p>
                  <p class="text-[#8F8FA1] text-xs m-0 mt-1">{{ item.count_item }} dona</p>
                </div>
                <p class="text-sm font-semibold m-0 shrink-0">{{ formatPrice((item.item_price || 0) * (item.count_item || 0)) }} so'm</p>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-neutral-100 pt-4">
            <p class="text-base font-bold m-0">Jami</p>
            <p class="text-base font-bold m-0 text-primary">{{ formatPrice(orderTotal(selectedOrder)) }} so'm</p>
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

// Backend `items` massividagi har bir element PurchaseController'da
// `name`, `cover` (rasm), `count_item` (soni), `type` ('book' | 'stationery'
// | 'gift') kalitlari bilan saqlanadi (savat item'lari emas!) —
// app/Http/Controllers/Api/PurchaseController.php (~2061-qator) orqali
// tasdiqlangan. Karta rasmi/nomi uchun faqat `type !== 'gift'` bo'lgan
// (ya'ni xaridor haqiqatan sotib olgan, sovg'a EMAS) item'lar hisobga
// olinadi — foydalanuvchi talabiga ko'ra.
function orderNonGiftItems(order: any): any[] {
  if (!Array.isArray(order.items)) return []
  return order.items.filter((item: any) => item?.type !== 'gift')
}

// Ko'rsatiladigan asosiy item: sovg'a bo'lmaganlarning birinchisi. Agar
// buyurtmada sovg'adan boshqa hech narsa bo'lmasa (juda kamdan-kam holat),
// bo'sh qolib ketmasligi uchun birinchi item'ga (garchi u sovg'a bo'lsa ham)
// qaytiladi.
function orderDisplayItem(order: any): any {
  const nonGift = orderNonGiftItems(order)
  if (nonGift.length > 0) return nonGift[0]
  return Array.isArray(order.items) && order.items.length > 0 ? order.items[0] : null
}

function orderThumb(order: any): string | undefined {
  return resolveImageUrl(orderDisplayItem(order)?.cover) || undefined
}

function orderFirstItemName(order: any): string {
  return orderDisplayItem(order)?.name || ''
}

// Rasm chetidagi "+N" belgisi uchun: sovg'a bo'lmagan item'lardan
// birinchisidan TASHQARI yana nechtasi bor.
function orderExtraNonGiftCount(order: any): number {
  return Math.max(0, orderNonGiftItems(order).length - 1)
}

function orderTotalQty(order: any): number {
  if (!Array.isArray(order.items)) return 0
  return order.items.reduce((sum: number, item: any) => sum + (Number(item?.count_item) || 0), 0)
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

// MUHIM: piyolamarket.uz'da status belgisi Tailwind rang-klassi bilan
// EMAS, balki har bir buyurtmaga inline `style="background-color:...;
// color:#fff"` orqali chiziladi (jonli DOM'dan tasdiqlangan: yakunlangan
// buyurtma uchun rgb(255, 3, 91), to'liq rangli, oq matnli pill —
// bg-amber-100/text-amber-700 kabi och (light-tint) fonlar EMAS). Aniq
// rang faqat "yakunlangan/bekor qilingan" holat uchun jonli tasdiqlandi;
// qolgan holatlar (kutilmoqda/yetkazilmoqda/yetkazildi) uchun piyolada
// namuna topilmadi — shu sababli mantiqan yaqin, izchil to'liq rang
// tanlandi. Piyolada boshqa status'li buyurtma paydo bo'lsa, shu jadval
// aniqlashtirilishi kerak.
const STATUS_COLORS: Record<string, string> = {
  pending: '#F59E0B',
  packing: '#F59E0B',
  in_delivery: '#2563EB',
  delivered: '#16A34A',
  customer_received: '#16A34A',
  cancelled: '#FF035B',
  returned: '#FF035B',
}

function statusCode(order: any) {
  return order.status_code || order.status || 'pending'
}

function statusLabel(order: any) {
  const code = statusCode(order)
  return STATUS_LABELS[code] || code
}

function statusBadgeStyle(order: any) {
  const code = statusCode(order)
  const bg = STATUS_COLORS[code] || '#0B0342'
  return { backgroundColor: bg, color: '#fff' }
}

// ── Buyurtma tafsilotlari modali ─────────────────────────────
// GET /v1/kitobchi/purchase/details/{order_id} — PurchaseController::
// purchaseDetails() — { status: 'success', data: [$order] } qaytaradi.
// $order — Sold modelining o'zi (formatted_created_at va boshqa meta
// maydonlar qo'shilgan holda), shu jumladan xom `address` (JSON-cast
// massiv, [{ fullName, fullAddress, lat, lon, country_code, phoneNumber }])
// va `items` maydonlari ham bor.
const isDetailsOpen = ref(false)
const detailsLoading = ref(false)
const detailsError = ref(false)
const selectedOrder = ref<any>(null)
const selectedOrderId = ref<number | null>(null)

async function openDetails(order: any) {
  selectedOrderId.value = order.id
  selectedOrder.value = null
  isDetailsOpen.value = true
  await fetchDetails()
}

function closeDetails() {
  isDetailsOpen.value = false
  selectedOrder.value = null
  detailsError.value = false
}

async function fetchDetails() {
  if (!selectedOrderId.value) return
  detailsLoading.value = true
  detailsError.value = false
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/details/${selectedOrderId.value}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    const found = Array.isArray(res?.data) ? res.data[0] : null
    if (found) {
      selectedOrder.value = found
    } else {
      detailsError.value = true
    }
  } catch (e) {
    detailsError.value = true
  } finally {
    detailsLoading.value = false
  }
}

const selectedOrderAddress = computed(() => {
  const order = selectedOrder.value
  if (!order) return ''
  const raw = order.address
  const entry = Array.isArray(raw) ? raw[0] : raw
  return entry?.fullAddress || ''
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
