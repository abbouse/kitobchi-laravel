<template>
  <main class="max-md:pb-[71px] max-md:grow h-full md:min-h-dvh">
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
                  <span class="group relative flex items-center gap-1.5 min-w-0 rounded-md font-semibold text-[#8F8FA1] text-sm text-neutral-900">
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

        <div v-else class="flex flex-col lg:flex-row gap-5 lg:items-start">
          <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold flex items-center gap-2 m-0">Savat <span class="text-neutral-500 text-sm font-medium leading-5">{{ cartStore.items.length }} ta mahsulot</span></h2>
            
            <div class="mt-4 flex gap-4 items-center">
              <div class="relative flex items-start flex-row">
                <div class="flex items-center h-6">
                  <button @click="toggleSelectAll" class="rounded-sm ring ring-inset overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer relative" :class="isAllSelected ? 'ring-primary bg-primary' : 'ring-neutral-300 bg-white'" type="button">
                    <span v-if="isAllSelected" class="flex items-center justify-center size-full text-white">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                  </button>
                </div>
                <div class="w-full ms-2 text-base">
                  <label @click="toggleSelectAll" class="block font-medium text-neutral-900 cursor-pointer text-sm md:text-base m-0">Barcha mahsulotlarni tanlash</label>
                </div>
              </div>
              <span class="text-neutral-500 text-sm font-medium leading-5 max-md:hidden">{{ cartStore.selectedCount }} ta mahsulot tanlandi</span>
            </div>

            <div class="space-y-4 mt-4">
              <div v-for="item in cartStore.items" :key="item.id" class="rounded-[20px] px-[14px] py-[18px] bg-white flex gap-4 border border-transparent hover:border-neutral-200 transition-colors shadow-sm md:shadow-none">
                
                <div>
                  <div class="relative flex items-start flex-row">
                    <div class="flex items-center h-6">
                      <button @click="cartStore.toggleSelect(item.id)" class="rounded-sm ring ring-inset overflow-hidden outline-primary/25 size-5 border-none p-0 cursor-pointer relative" :class="cartStore.isSelected(item.id) ? 'ring-primary bg-primary' : 'ring-neutral-300 bg-white'" type="button">
                        <span v-if="cartStore.isSelected(item.id)" class="flex items-center justify-center size-full text-white">
                          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex gap-3 flex-1 overflow-hidden">
                  <img :src="item.image" class="w-[80px] h-[106px] md:w-[100px] md:h-[133px] rounded-xl object-cover shrink-0 bg-neutral-100">
                  <div class="flex flex-col justify-between flex-1 min-w-0">
                    <div class="space-y-2">
                      <div class="flex justify-between items-start gap-4">
                        <NuxtLink :to="item.type === 'book' ? `/books/${item.id}` : `/stationery/${item.id}`" class="text-sm md:text-sm leading-5 font-normal lg:max-w-[70%] line-clamp-2 text-neutral-900 no-underline hover:text-primary transition-colors">
                          {{ item.name }}
                        </NuxtLink>
                        <div class="flex shrink-0">
                          <button class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer text-neutral-400 hover:text-red-500">
                            <svg class="w-5 h-5 relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                          </button>
                          <button @click="cartStore.removeItem(item.id)" class="rounded-md font-medium inline-flex items-center transition-colors text-sm gap-1.5 text-neutral-400 hover:text-red-500 p-1.5 border-none bg-transparent cursor-pointer">
                            <svg class="shrink-0 size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <div class="flex items-center justify-between md:justify-end gap-6 pt-2">
                      <h2 class="text-base md:text-lg font-bold shrink-0 m-0">{{ formatPrice(item.price) }} so'm</h2>
                      <div class="relative inline-flex items-center bg-[#EAEAEA] rounded-xl overflow-hidden">
                        <input type="text" readonly class="w-full border-0 text-base/5 gap-1.5 text-neutral-900 focus:outline-none text-center px-9 md:text-sm bg-transparent max-w-[120px] h-8 md:h-[34px] font-medium" :value="item.quantity">
                        <div class="absolute flex items-center inset-y-0 end-0 pe-1">
                          <button @click="cartStore.updateQuantity(item.id, item.quantity + 1)" type="button" class="rounded-md font-medium inline-flex items-center transition-colors text-sm text-neutral-600 hover:text-primary p-1.5 border-none bg-transparent cursor-pointer">
                            <svg class="shrink-0 size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                          </button>
                        </div>
                        <div class="absolute flex items-center inset-y-0 start-0 ps-1">
                          <button @click="cartStore.updateQuantity(item.id, item.quantity - 1)" :disabled="item.quantity <= 1" type="button" class="rounded-md font-medium inline-flex items-center transition-colors text-sm text-neutral-600 hover:text-primary disabled:opacity-50 disabled:hover:text-neutral-600 p-1.5 border-none bg-transparent cursor-pointer">
                            <svg class="shrink-0 size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="lg:w-[400px] shrink-0 space-y-4 lg:sticky top-24 mt-4 lg:mt-0">
            <div class="p-4 sm:p-6 rounded-2xl bg-white space-y-2 sm:space-y-3 md:space-y-4">
              <div class="relative inline-flex items-center w-full">
                <input v-model="promoCode" type="text" placeholder="Promokod" class="w-full appearance-none placeholder:text-neutral-400 text-base/5 text-neutral-900 focus:outline-none md:text-sm rounded-2xl p-3 md:p-4 bg-[#F1F2F7] border border-transparent focus:border-primary/20 transition-all">
              </div>
              <div class="space-y-4 pt-2">
                <div class="flex justify-between text-neutral-500 text-sm md:text-base">
                  <span>{{ cartStore.selectedCount }} ta mahsulot</span>
                  <span class="font-medium text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
                <div v-if="cartStore.totalDiscount > 0" class="flex justify-between text-neutral-500 text-sm md:text-base">
                  <span>Chegirma</span>
                  <span class="font-medium text-red-500"> -{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
                </div>
                <div class="flex justify-between text-neutral-500 text-sm md:text-base">
                  <span>Yetkazib berish narxi</span>
                  <span class="font-medium text-neutral-900">Bepul</span>
                </div>
              </div>
              <div class="flex justify-between items-center bg-white pt-2 border-t border-neutral-100">
                <span class="text-xl font-bold text-neutral-900">Jami</span>
                <span class="text-xl font-bold text-neutral-900">{{ formatPrice(cartStore.totalAmount - cartStore.totalDiscount) }} so'm</span>
              </div>
            </div>

            <div class="p-4 sm:p-6 rounded-t-2xl md:rounded-2xl bg-white md:space-y-4 max-md:fixed max-md:bottom-0 max-md:left-0 max-md:right-0 max-md:z-50 max-md:shadow-[0_-4px_10px_rgba(0,0,0,0.05)] md:shadow-none">
              <div class="flex items-center justify-between">
                <h3 class="md:text-xl font-semibold leading-6 md:max-w-[200px] m-0 text-neutral-900 max-md:hidden">Muddatli to‘lovga rasmiylashtirish</h3>
                <button @click="isInstallmentActive = !isInstallmentActive" class="w-14 h-7 rounded-full transition-colors relative border-none cursor-pointer p-0 max-md:hidden" :class="isInstallmentActive ? 'bg-primary' : 'bg-neutral-300'">
                  <span class="absolute top-1 bg-white w-5 h-5 rounded-full transition-all shadow-sm" :class="isInstallmentActive ? 'left-8' : 'left-1'"></span>
                </button>
              </div>
              <div class="text-neutral-500 text-sm max-md:hidden mt-2 mb-4">Muddatli to'lovni yoqish orqali xaridingizni qismlarga bo'ling</div>
              
              <button @click="$router.push('/checkout')" :disabled="cartStore.selectedCount === 0" type="button" class="inline-flex items-center justify-center transition-colors px-2.5 py-1.5 gap-1.5 hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed outline-none w-full bg-primary text-white rounded-2xl h-14 text-base font-bold border-none cursor-pointer shadow-sm">
                Rasmiylashtirishga o'tish <svg class="w-5 h-5 ml-1 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'

const cartStore = useCartStore()
const promoCode = ref('')
const isInstallmentActive = ref(false)

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
