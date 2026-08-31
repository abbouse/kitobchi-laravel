<template>
  <!-- TUZATILDI: avval bu yerda <main> o'zining max-md:pb-[71px]'ini olib
       yurar edi, lekin AppBottomNav endi savatchada ham ko'rinadigani
       uchun bu joy endi layouts/default.vue'ning umumiy <main>'idan
       keladi (boshqa sahifalar — catalog, category — ham xuddi shunday).
       Ikkalasida ham bo'lsa 71px ikki marta qo'shilib ketardi. Element
       ham boshqa sahifalardagidek <div> qilindi (<main> ichida yana
       <main> — noto'g'ri semantika bo'lardi). -->
  <div class="max-md:grow h-full md:min-h-dvh">
    <ClientOnly>
    <div class="min-h-dvh py-3 md:py-6">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-[--ui-container] mx-auto">
        
        <div class="mb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button @click="$router.back()" type="button" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 active:text-primary/75 outline-primary/25 border-none bg-transparent cursor-pointer">
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
                  <span class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-semibold text-[#8F8FA1] text-sm">
                    <span class="truncate">Savat</span>
                  </span>
                </li>
              </ol>
            </nav>
          </div>
        </div>

        <div v-if="cartStore.items.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl mt-4 max-w-2xl mx-auto shadow-sm">
          <div class="w-24 h-24 bg-[#F6F6F9] rounded-full flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
          </div>
          <h2 class="text-2xl font-bold text-neutral-900 mb-2 m-0">Savatingiz bo'sh</h2>
          <p class="text-neutral-500 mb-8 max-w-xs text-center m-0">Savatga mahsulot qo'shish uchun katalogni ko'ring</p>
          <NuxtLink to="/catalog" class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors no-underline">
            Katalogga o'tish
          </NuxtLink>
        </div>

        <div
          v-else
          class="flex flex-col lg:flex-row gap-5 lg:items-start"
          :style="isMobile ? { minHeight: 'calc(100dvh - 71px)' } : {}"
        >
          <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold flex items-center gap-2 m-0">Savat <span class="text-[#8F8FA1] text-sm font-medium leading-5">{{ cartStore.items.length }} ta mahsulot</span></h2>
            
            <div class="mt-4 flex gap-4 items-center">
              <div class="relative flex items-start flex-row">
                <div class="flex items-center h-6">
                  <button @click="toggleSelectAll" class="rounded-sm ring ring-inset overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer relative" :class="isAllSelected ? 'ring-primary bg-primary' : 'ring-[var(--ui-color-neutral-300)] bg-white'" type="button">
                    <span v-if="isAllSelected" class="flex items-center justify-center size-full text-white">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                  </button>
                </div>
                <div class="w-full ms-2 text-base">
                  <label @click="toggleSelectAll" class="block font-medium text-neutral-700 cursor-pointer text-sm md:text-base m-0">Barcha mahsulotlarni tanlash</label>
                </div>
              </div>
              <span class="text-[#8F8FA1] text-sm font-medium leading-5 max-md:hidden">{{ cartStore.selectedCount }} ta mahsulot tanlandi</span>
            </div>

            <!-- TUZATILDI: piyolaning savat kartochkasi jonli DOM'idan
                 (getComputedStyle + struktura bo'yicha) tasdiqlangan haqiqiy
                 farqlar:
                 (1) karta butunlay TEKIS — soyasi (box-shadow) YO'Q, bizda
                     esa mobileda `shadow-sm` bor edi;
                 (2) checkbox alohida ustun sifatida EMAS, rasmning chap
                     yuqori burchagiga QOPLANGAN holda turadi;
                 (3) narx+dona-hisoblagich rasm yonida SIQILGAN emas —
                     alohida, TO'LIQ KENGLIKDAGI ikkinchi qator sifatida
                     (karta o'zi flex-col, ichida 2 ta qator: rasm+nom, va
                     narx+hisoblagich);
                 (4) yurak(sevimli)+savat ikkita alohida ikonka o'rniga —
                     BITTA "⋮" menyu tugmasi, ichida "Ulashish"/"O'chirish"
                     (piyolada shu menyuni ochib, matnini o'qib tasdiqlandi). -->
            <div class="space-y-4 mt-4">
              <div v-for="item in cartStore.items" :key="item.id" class="rounded-[20px] p-4 bg-white flex flex-col gap-3 border border-transparent hover:border-neutral-200 transition-colors">

                <div class="w-full flex gap-4 items-start">
                  <div class="relative shrink-0">
                    <!-- TUZATILDI (2026-08-31, 2-marta): avvalgi tuzatish
                         to'liq emas ekan — w-[80px]/md:w-[100px]'ning O'ZI
                         HAM arbitrary-bracket klass bo'lib, xuddi
                         h-[106px]/md:h-[133px] kabi kompilyatsiya
                         qilinmagan (0 ta natija — CSS fayllarda tekshirib
                         tasdiqlandi), ya'ni rasm HALI HAM o'lchamsiz, tabiiy
                         hajmida chiqib turgan edi. Endi haqiqatan ishlaydigan
                         maxsus .cart-item-thumb klassiga o'tkazildi (mobil
                         80px, md+ da 100px), balandlik esa aspect-ratio
                         orqali avtomatik hisoblanadi. -->
                    <img :src="item.image" class="cart-item-thumb rounded-xl object-cover bg-neutral-100" style="aspect-ratio: 3 / 4;">
                    <button
                      @click="cartStore.toggleSelect(item.id)"
                      class="absolute left-1.5 top-1.5 rounded-sm ring ring-inset overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer shadow-sm"
                      :class="cartStore.isSelected(item.id) ? 'ring-primary bg-primary' : 'ring-white bg-white'"
                      type="button"
                    >
                      <span v-if="cartStore.isSelected(item.id)" class="flex items-center justify-center size-full text-white">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                      </span>
                    </button>
                  </div>

                  <div class="flex-1 min-w-0 flex justify-between items-start gap-4">
                    <NuxtLink :to="item.type === 'book' ? `/books/${item.slug || item.productId || item.id}` : `/stationery/${item.slug || item.productId || item.id}`" class="flex-1 min-w-0 text-sm leading-5 font-normal line-clamp-2 no-underline hover:text-primary/75 transition-colors">
                      {{ item.name }}
                    </NuxtLink>
                    <div class="relative shrink-0" data-item-menu>
                      <button
                        type="button"
                        @click="toggleItemMenu(item.id)"
                        aria-label="Yana"
                        class="w-8 h-8 flex items-center justify-center rounded-full transition-colors border-none bg-transparent cursor-pointer text-neutral-500 hover:bg-neutral-100"
                      >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.5A1.75 1.75 0 1 0 12 3a1.75 1.75 0 0 0 0 3.5Zm0 7A1.75 1.75 0 1 0 12 10a1.75 1.75 0 0 0 0 3.5Zm0 7A1.75 1.75 0 1 0 12 17a1.75 1.75 0 0 0 0 3.5Z"/></svg>
                      </button>
                      <div
                        v-if="openMenuItemId === item.id"
                        class="absolute right-0 top-full mt-1 z-20 w-40 rounded-2xl bg-white shadow-lg border border-neutral-100 py-1.5"
                      >
                        <button
                          type="button"
                          @click="shareItem(item)"
                          class="w-full text-left px-4 py-2.5 text-sm text-neutral-700 hover:bg-neutral-100 border-none bg-transparent cursor-pointer flex items-center gap-2"
                        >
                          <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                          Ulashish
                        </button>
                        <button
                          type="button"
                          @click="cartStore.removeItem(item.id); openMenuItemId = null"
                          class="w-full text-left px-4 py-2.5 text-sm text-red-500 hover:bg-neutral-100 border-none bg-transparent cursor-pointer flex items-center gap-2"
                        >
                          <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                          O'chirish
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-base sm:text-lg font-bold shrink-0 m-0">{{ formatPrice(item.price) }} so'm</h2>
                  <div class="relative inline-flex items-center bg-[#EAEAEA] rounded-xl overflow-hidden">
                    <input type="text" readonly class="w-full border-0 text-base/5 gap-1.5 text-neutral-900 focus:outline-none text-center px-9 md:text-sm bg-transparent h-8 font-medium" style="max-width: 120px;" :value="item.quantity">
                    <div class="absolute flex items-center inset-y-0 end-0 pe-1">
                      <button @click="cartStore.updateQuantity(item.id, item.quantity + 1)" type="button" class="rounded-md font-medium inline-flex items-center transition-colors text-sm text-primary p-1.5 border-none bg-transparent cursor-pointer">
                        <svg class="shrink-0 size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                      </button>
                    </div>
                        <div class="absolute flex items-center inset-y-0 start-0 ps-1">
                          <button @click="cartStore.updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1" type="button" class="rounded-md font-medium inline-flex items-center transition-colors text-sm text-primary disabled:opacity-50 p-1.5 border-none bg-transparent cursor-pointer">
                            <svg class="shrink-0 size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

          <div
            class="checkout-summary-col shrink-0 space-y-4 lg:sticky top-24 mt-4 lg:mt-0"
            :style="isMobile ? { display: 'flex', flexDirection: 'column', flexGrow: 1 } : {}"
          >
            <div class="p-4 sm:p-6 rounded-2xl bg-white space-y-2 sm:space-y-3 md:space-y-4">
              <div class="relative inline-flex items-center w-full">
                <input v-model="promoCode" type="text" placeholder="Promokod" class="w-full appearance-none placeholder:text-neutral-400 text-base/5 text-neutral-900 focus:outline-none md:text-sm rounded-2xl p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
              </div>
              <div class="space-y-4 pt-2">
                <div class="flex justify-between text-[#8F8FA1] text-sm md:text-base">
                  <span>{{ cartStore.selectedCount }} ta mahsulot</span>
                  <span class="font-medium">{{ formatPrice(cartStore.originalTotalAmount) }} so'm</span>
                </div>
                <div v-if="cartStore.totalDiscount > 0" class="flex justify-between text-[#8F8FA1] text-sm md:text-base">
                  <span>Chegirma</span>
                  <span class="font-medium text-red-500"> -{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
                </div>
                <div class="flex justify-between text-[#8F8FA1] text-sm md:text-base">
                  <span>Yetkazib berish narxi</span>
                  <span class="font-medium">Bepul</span>
                </div>
              </div>
              <div class="flex justify-between items-center bg-white">
                <span class="text-xl font-bold">Jami</span>
                <span class="text-xl font-bold">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
              </div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl bg-white space-y-3 max-md:hidden">
              <div class="flex items-center justify-between gap-4">
                <h3 class="text-base md:text-xl font-semibold leading-6 m-0">Muddatli to‘lovga rasmiylashtirish</h3>
                <button @click="isInstallmentActive = !isInstallmentActive" class="w-14 h-7 rounded-full transition-colors relative border-none cursor-pointer p-0 shrink-0" :class="isInstallmentActive ? 'bg-primary' : 'bg-neutral-300'">
                  <span class="absolute top-1 bg-white w-5 h-5 rounded-full transition-all shadow-sm" :class="isInstallmentActive ? 'left-8' : 'left-1'"></span>
                </button>
              </div>

              <p v-if="!isInstallmentActive" class="text-sm text-[#8F8FA1] leading-relaxed m-0">
                Muddatli to'lovni yoqish orqali xaridingizni qismlarga bo'ling
              </p>

              <div v-else class="space-y-3 pt-3 border-t border-neutral-100">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-[#8F8FA1]">Oylik to'lov</span>
                  <span class="text-primary font-bold text-base">{{ formatPrice(Math.round(cartStore.totalAmount / installmentMonths * 1.15)) }} so'm <span class="text-neutral-400 font-normal text-xs"> × {{ installmentMonths }} oy</span></span>
                </div>
                <div class="flex gap-2">
                  <button @click="installmentMonths = 6" type="button" class="py-2 px-4 rounded-[40px] text-sm font-medium transition-all duration-200 border-none cursor-pointer" :class="installmentMonths === 6 ? 'bg-primary text-white' : 'bg-[#F8F8F8] text-primary hover:bg-neutral-100'">
                    6 oy
                  </button>
                  <button @click="installmentMonths = 12" type="button" class="py-2 px-4 rounded-[40px] text-sm font-medium transition-all duration-200 border-none cursor-pointer" :class="installmentMonths === 12 ? 'bg-primary text-white' : 'bg-[#F8F8F8] text-primary hover:bg-neutral-100'">
                    12 oy
                  </button>
                </div>
              </div>
            </div>

            <!-- TUZATILDI: avvalgi max-md:fixed/max-md:bottom-0/max-md:left-0/
                 max-md:right-0/max-md:z-50 klasslari kitobchining piyola.css
                 to'plamida UMUMAN KOMPILYATSIYA QILINMAGAN edi (jonli saytda
                 fetch qilib tekshirildi) — shuning uchun tugma aslida hech
                 qachon fixed bo'lmagan, oddiy oqimda turgan, faqat qisqa
                 savatda tasodifan pastda ko'rinardi. Piyolaning o'zi ham bu
                 qismni position:fixed bilan EMAS, balki flexbox orqali
                 (yuqoridagi ikkita wrapper — min-height + flex-grow) pastga
                 "itarib" chiqaradi (jonli getComputedStyle bilan tasdiqlandi:
                 piyolada barcha ota-elementlar position:static). mt-auto
                 shu texnikaning davomi — flex-grow bo'lgan sidebar ichida
                 shu kartani pastga suradi. -->
            <div
              class="p-4 rounded-t-2xl md:rounded-2xl bg-white"
              :style="isMobile ? { marginTop: 'auto' } : {}"
            >
              <button @click="$router.push('/checkout')" :disabled="cartStore.selectedCount === 0" type="button" class="inline-flex items-center justify-center transition-colors px-2.5 py-1.5 gap-1.5 hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed outline-none w-full bg-primary text-white rounded-2xl h-14 text-base font-bold border-none cursor-pointer shadow-sm">
                Rasmiylashtirishga o'tish <svg class="w-5 h-5 ml-1 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'

const cartStore = useCartStore()
const promoCode = ref('')
const isInstallmentActive = ref(false)
const installmentMonths = ref(12)

// Piyoladagi flex-grow + min-height "pastga itarish" texnikasi faqat
// mobil kenglikda kerak (desktopda sidebar allaqachon lg:sticky bilan
// ishlaydi) — shuning uchun bu yerda ham boshqa sahifalardagi kabi
// matchMedia orqali mobil holatni JSda aniqlaymiz (arbitrary Tailwind
// klasslari piyola.css'da kompilyatsiya qilinmasligi mumkinligi sababli
// inline :style ishlatilyapti, class emas).
const isMobile = ref(false)
function updateIsMobile() {
  isMobile.value = window.matchMedia('(max-width: 767.98px)').matches
}
onMounted(() => {
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', updateIsMobile)
})

// Piyoladagi kabi har bir savat elementida bitta "⋮" (ko'proq) tugmasi —
// bosilganda "Ulashish" va "O'chirish" variantlari bilan kichik dropdown
// ochiladi (alohida yurak/urn ikonkalar o'rniga). `data-item-menu`
// atributi orqali menyudan tashqariga bosilganda uni yopamiz.
const openMenuItemId = ref<number | string | null>(null)

function toggleItemMenu(id: any) {
  openMenuItemId.value = openMenuItemId.value === id ? null : id
}

async function shareItem(item: any) {
  openMenuItemId.value = null
  if (import.meta.client) {
    const url = `${location.origin}${item.type === 'stationery' ? '/stationery/' : '/books/'}${item.slug || item.productId || item.id}`
    if (navigator.share) {
      try {
        await navigator.share({ title: item.name, url })
      } catch (e) {
        // Foydalanuvchi ulashishni bekor qildi — xato emas, e'tiborsiz qoldiramiz.
      }
    } else if (navigator.clipboard) {
      await navigator.clipboard.writeText(url)
      alert("Havola nusxalandi!")
    }
  }
}

function handleItemMenuDocClick(e: MouseEvent) {
  if (openMenuItemId.value !== null && !(e.target as HTMLElement).closest('[data-item-menu]')) {
    openMenuItemId.value = null
  }
}
onMounted(() => {
  document.addEventListener('click', handleItemMenuDocClick)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleItemMenuDocClick)
})

const isAllSelected = computed(() => {
  return cartStore.items.length > 0 && cartStore.selectedItems.length === cartStore.items.length
})

function toggleSelectAll() {
  cartStore.toggleSelectAll()
}

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

useSeoMeta({
  title: 'Savat — Kitobchi',
  description: "Savatdagi mahsulotlar va buyurtmani rasmiylashtirish."
})
</script>
