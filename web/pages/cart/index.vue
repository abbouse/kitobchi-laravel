<template>
  <div class="flex flex-col min-h-dvh bg-[#F6F6F9] grow">
    <!-- ====== MOBILE STICKY TOP BAR (Piyola Market 1:1) ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-2 bg-white sticky top-0 z-40 transition-all duration-300 shadow-xs">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto space-y-2">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
              aria-label="Orqaga"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Savatcha</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>

        <div v-if="cartStore.items.length > 0">
          <div class="flex items-center justify-between min-h-8 pt-1">
            <div>
              <button
                type="button"
                @click="cartStore.toggleSelectAll()"
                class="flex items-center gap-2 border-none bg-transparent cursor-pointer p-0"
              >
                <div
                  class="flex items-center justify-center w-5 h-5 rounded-sm transition-colors"
                  :class="cartStore.isAllSelected ? 'bg-primary text-white' : 'border-2 border-neutral-300 bg-white'"
                >
                  <svg v-if="cartStore.isAllSelected" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                </div>
                <span class="text-sm font-medium text-neutral-800">Barcha mahsulotlarni tanlash</span>
              </button>
            </div>
            <button
              type="button"
              @click="cartStore.removeSelected()"
              :disabled="cartStore.selectedCount === 0"
              class="p-1.5 text-neutral-400 hover:text-red-500 disabled:opacity-40 transition-colors border-none bg-transparent cursor-pointer"
              aria-label="Tanlanganlarni o'chirish"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21q.512.078 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48 48 0 0 0-3.478-.397m-12 .562q.51-.088 1.022-.165m0 0a48 48 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a52 52 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49 49 0 0 0-7.5 0"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== MAIN CONTENT ====== -->
    <main class="max-md:grow h-full md:min-h-dvh max-md:pb-4 bg-[#F6F6F9]">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-7xl mx-auto py-2 md:py-6">
        <!-- Desktop Breadcrumb & Title Header (Piyola Market 1:1) -->
        <div class="mb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="$router.back()"
              class="rounded-md font-medium inline-flex items-center p-2 text-primary hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer"
              aria-label="Orqaga"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
              <NuxtLink to="/" class="hover:text-neutral-900 transition-colors">Asosiy</NuxtLink>
              <span>/</span>
              <span class="text-neutral-600 font-medium">Savat</span>
            </nav>
          </div>

          <div v-if="cartStore.items.length > 0" class="mt-4">
            <div class="flex items-baseline gap-2 mb-3">
              <h1 class="text-2xl font-bold text-neutral-900 m-0">Savat</h1>
              <span class="text-sm text-neutral-400 font-medium">{{ cartStore.totalCount }} ta mahsulot</span>
            </div>

            <!-- Desktop Select All Bar -->
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="cartStore.toggleSelectAll()"
                class="flex items-center gap-2 text-sm font-semibold text-neutral-800 border-none bg-transparent cursor-pointer p-0"
              >
                <div
                  class="flex items-center justify-center w-5 h-5 rounded-md transition-colors"
                  :class="cartStore.isAllSelected ? 'bg-[#0B0A3F] text-white' : 'border-2 border-neutral-300 bg-white'"
                >
                  <svg v-if="cartStore.isAllSelected" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                </div>
                <span>Barcha mahsulotlarni tanlash</span>
              </button>
              <span class="text-xs text-neutral-400 font-medium ml-2">{{ cartStore.selectedCount }} ta mahsulot tanlandi</span>
            </div>
          </div>
        </div>

        <!-- If cart has items (Piyola Market 1:1 Desktop Grid) -->
        <div v-if="cartStore.items.length > 0" class="flex flex-col lg:flex-row gap-5 min-h-[calc(100dvh-140px)]">
          <!-- Chap ustun (Mahsulotlar ro'yxati) -->
          <div class="flex-1 space-y-4 min-w-0">
            <!-- Items Cards List (Piyola 1:1) -->
            <div
              v-for="item in cartStore.items"
              :key="item.id"
              class="rounded-[20px] p-4 bg-white flex gap-4 transition-colors relative shadow-sm border border-neutral-100/50"
            >
              <div class="pt-1 flex-shrink-0">
                <!-- Checkbox -->
                <button
                  type="button"
                  @click.stop="cartStore.toggleSelect(item.id)"
                  class="w-5 h-5 rounded-md border flex items-center justify-center cursor-pointer transition-colors p-0 shrink-0 mt-1"
                  :class="item.selected ? 'bg-[#0B0A3F] border-[#0B0A3F] text-white' : 'border-neutral-300 bg-white text-transparent'"
                  aria-label="Tanlash"
                >
                  <svg v-if="item.selected" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                </button>

              </div>
              <div class="flex gap-3 flex-1 overflow-hidden">
                <!-- Product Image -->
                <NuxtLink :to="productUrl(item)" class="block w-[100px] h-[133px] rounded-xl overflow-hidden bg-[#F6F6F9] shrink-0 p-1 flex items-center justify-center">
                  <img :src="item.image" :alt="item.name" class="w-full h-full object-contain" />
                </NuxtLink>

                <!-- Title & Actions on Right -->
                <div class="flex flex-col justify-between flex-1 min-w-0">
                  <div class="space-y-2">
                    <div class="flex justify-between items-start gap-4">
                      <NuxtLink :to="productUrl(item)" class="text-sm font-semibold text-neutral-900 line-clamp-2 hover:underline">
                        {{ item.name }}
                      </NuxtLink>

                    <!-- Heart & Trash Quick Actions (Piyola 1:1) -->
                    <div class="flex items-center gap-2 shrink-0">
                      <button
                        type="button"
                        @click.stop="moveToFavorites(item)"
                        class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors border-none bg-transparent cursor-pointer"
                        title="Sevimlilarga qo'shish"
                      >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                      </button>
                      <button
                        type="button"
                        @click.stop="cartStore.removeItem(item.id)"
                        class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors border-none bg-transparent cursor-pointer"
                        title="O'chirish"
                      >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21q.512.078 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48 48 0 0 0-3.478-.397m-12 .562q.51-.088 1.022-.165m0 0a48 48 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a52 52 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49 49 0 0 0-7.5 0"/></svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Bottom Row: Price & Stepper -->
                  <div class="flex items-center justify-between md:justify-end gap-6 pt-2">
                    <span class="text-lg font-bold text-neutral-900">
                      {{ formatPrice(item.price * item.quantity) }} so'm
                    </span>

                    <!-- Stepper Counter -->
                    <div class="inline-flex items-center bg-[#ECECEF] rounded-xl px-2 py-1 gap-2">
                      <button
                        type="button"
                        :disabled="item.quantity <= 1"
                        @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                        class="w-6 h-6 text-neutral-600 disabled:text-neutral-300 hover:text-neutral-900 active:scale-95 transition-all border-none bg-transparent cursor-pointer flex items-center justify-center font-bold"
                        aria-label="Kamaytirish"
                      >
                        –
                      </button>
                      <span class="w-6 text-center text-sm font-semibold text-neutral-900 select-none">{{ item.quantity }}</span>
                      <button
                        type="button"
                        @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                        class="w-6 h-6 text-neutral-600 hover:text-neutral-900 active:scale-95 transition-all border-none bg-transparent cursor-pointer flex items-center justify-center font-bold"
                        aria-label="Oshirish"
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ====== O'NG USTUN: Promokod + Buyurtma xulosasi + Muddatli to'lov + Rasmiylashtirish (Piyola 1:1) ====== -->
          <div class="flex flex-col gap-4 lg:w-[400px] shrink-0 h-fit space-y-2 md:sticky md:top-6">
            <!-- Promokod va Buyurtma xulosasi kartasi -->
            <div class="p-6 bg-white rounded-3xl shadow-sm border border-neutral-100/50 space-y-4">
              <div>
                <input
                  v-model="promoCode"
                  type="text"
                  placeholder="Promokod"
                  class="w-full rounded-2xl bg-[#F6F6F9] px-4 py-3 border-none outline-none font-medium text-neutral-900 text-sm placeholder:text-neutral-400"
                />
              </div>

              <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-neutral-500">{{ cartStore.selectedCount }} ta mahsulot</span>
                  <span class="font-semibold text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-neutral-500">Yetkazib berish narxi</span>
                  <span class="font-semibold text-emerald-600">Bepul</span>
                </div>
                <div class="pt-3 border-t border-neutral-100 flex items-center justify-between">
                  <span class="text-base font-bold text-neutral-900">Jami</span>
                  <span class="text-lg font-bold text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
              </div>
            </div>

            <!-- Muddatli to'lov kartasi -->
            <div class="p-6 bg-white rounded-3xl shadow-xs border border-neutral-100/80 space-y-2">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-neutral-900 m-0">Muddatli to‘lovga rasmiylashtirish</h3>
                <button
                  type="button"
                  @click="isInstallmentActive = !isInstallmentActive"
                  class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none p-0"
                  :class="isInstallmentActive ? 'bg-[#0B0A3F]' : 'bg-neutral-200'"
                  aria-label="Muddatli to'lovni yoqish"
                >
                  <span
                    class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                    :class="isInstallmentActive ? 'translate-x-5' : 'translate-x-0'"
                  ></span>
                </button>
              </div>
              <p class="text-xs text-neutral-400 font-normal m-0 leading-relaxed">
                Muddatli to'lovni yoqish orqali xaridingizni qismlarga bo'ling
              </p>

              <!-- Agar muddatli to'lov yoqilgan bo'lsa: Tanlangan muddat va oylik to'lov -->
              <div
                v-if="isInstallmentActive"
                @click="isDrawerOpen = true"
                class="mt-3 p-3.5 rounded-2xl bg-[#F6F6F9] flex items-center justify-between cursor-pointer border border-neutral-200/60 hover:bg-[#ECECEF] transition-colors"
              >
                <div>
                  <span class="text-xs text-neutral-500 block">Oylik to'lov</span>
                  <span class="text-base font-bold text-neutral-900">
                    {{ formatPrice(monthlyPayment) }} so'm <span class="text-xs text-neutral-400 font-normal">× {{ selectedInstallmentMonths }} oy</span>
                  </span>
                </div>
                <button type="button" class="text-xs font-semibold text-primary flex items-center gap-1 border-none bg-transparent cursor-pointer p-0">
                  O'zgartirish
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
              </div>
            </div>

            <!-- Rasmiylashtirishga o'tish Button (Piyola 1:1) -->
            <div>
              <button
                type="button"
                @click="handleCheckout"
                :disabled="cartStore.selectedCount === 0"
                class="w-full bg-[#0B0A3F] hover:bg-[#150a58] text-white rounded-2xl py-4 px-6 text-base font-bold flex items-center justify-center gap-2 shadow-md transition-all border-none cursor-pointer disabled:opacity-50"
              >
                <span>Rasmiylashtirishga o'tish</span>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-20 bg-white rounded-3xl p-8 shadow-xs">
          <div class="w-24 h-24 rounded-full bg-secondary-100 text-neutral-400 mx-auto flex items-center justify-center mb-4">
            <svg class="w-12 h-12 text-primary" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 2a4 4 0 0 0-4 4v1H5a1 1 0 0 0-.994.89l-1 9A1 1 0 0 0 4 18h12a1 1 0 0 0 .994-1.11l-1-9A1 1 0 0 0 15 7h-1V6a4 4 0 0 0-4-4m2 5V6a2 2 0 1 0-4 0v1zm-6 3a1 1 0 1 1 2 0a1 1 0 0 1-2 0m7-1a1 1 0 1 0 0 2a1 1 0 0 0 0-2" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-neutral-800 mb-2">Savatchangiz bo‘sh</h2>
          <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">
            Bosh sahifa yoki katalogdan o‘zingizga ma’qul kitoblarni tanlab, savatchaga qo‘shing.
          </p>
          <NuxtLink
            to="/catalog"
            class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/90 transition-colors"
          >
            Xaridni boshlash
          </NuxtLink>
        </div>
      </div>
    </main>

    <!-- ====== DRAWER: TO'LOV MUDDATINI TANLASH (Piyola 1:1) ====== -->
    <div
      v-if="isDrawerOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs transition-opacity"
      @click="isDrawerOpen = false"
    ></div>
    <div
      v-if="isDrawerOpen"
      class="fixed z-50 bg-white flex flex-col max-h-[90vh] inset-x-0 bottom-0 rounded-t-3xl shadow-2xl transition-transform"
    >
      <div class="shrink-0 bg-neutral-300 mt-3 w-12 h-1.5 mx-auto rounded-full"></div>
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto pb-6 pt-3">
        <h2 class="text-xl font-semibold text-neutral-900 mb-3 m-0">To‘lov muddatini tanlang</h2>
        
        <div class="flex flex-col gap-3 py-2">
          <!-- 6 oy -->
          <label
            @click="tempMonths = 6"
            class="flex text-sm rounded-2xl border-2 bg-gray-50 flex-row-reverse justify-between items-center p-4 cursor-pointer transition-all"
            :class="tempMonths === 6 ? 'border-primary bg-primary/5' : 'border-transparent'"
          >
            <div class="flex items-center justify-between w-full">
              <span class="font-medium text-neutral-900">6 oy</span>
              <h3 class="font-semibold text-primary m-0">{{ formatPrice(Math.round(cartStore.totalAmount / 6)) }} so'm/oyiga</h3>
            </div>
          </label>

          <!-- 12 oy -->
          <label
            @click="tempMonths = 12"
            class="flex text-sm rounded-2xl border-2 bg-gray-50 flex-row-reverse justify-between items-center p-4 cursor-pointer transition-all"
            :class="tempMonths === 12 ? 'border-primary bg-primary/5' : 'border-transparent'"
          >
            <div class="flex items-center justify-between w-full">
              <span class="font-medium text-neutral-900">12 oy</span>
              <h3 class="font-semibold text-primary m-0">{{ formatPrice(Math.round(cartStore.totalAmount / 12)) }} so'm/oyiga</h3>
            </div>
          </label>
        </div>

        <button
          type="button"
          @click="selectedInstallmentMonths = tempMonths; isDrawerOpen = false"
          class="w-full text-white bg-primary h-12 flex justify-center items-center rounded-2xl text-base px-6 mt-4 font-semibold border-none cursor-pointer hover:bg-primary/90 transition-colors shadow-md"
        >
          Tanlash
        </button>
      </div>
    </div>

    <!-- Rasmiylashtirish oynasi (Piyola cart checkout modaliga yaqin) -->
    <div
      v-if="isOrderConfirmOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="closeOrderConfirm"
    >
      <div class="bg-white rounded-3xl w-full max-w-md p-5 md:p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-xl font-bold text-neutral-900 m-0">Rasmiylashtirish</h2>
          <button
            type="button"
            @click="closeOrderConfirm"
            class="w-9 h-9 rounded-full bg-[#F6F6F9] text-neutral-500 hover:text-neutral-900 flex items-center justify-center border-none cursor-pointer"
            aria-label="Yopish"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="space-y-3">
          <label class="block">
            <span class="block text-xs font-semibold text-neutral-500 mb-1.5">To‘liq ism</span>
            <input
              v-model="checkoutForm.name"
              type="text"
              placeholder="Ism va familiya"
              class="w-full h-12 rounded-2xl bg-[#F6F6F9] px-4 border-none outline-none text-sm font-semibold text-neutral-900 placeholder:text-neutral-400"
            />
          </label>

          <label class="block">
            <span class="block text-xs font-semibold text-neutral-500 mb-1.5">Telefon raqamingiz</span>
            <div class="w-full h-12 rounded-2xl bg-[#F6F6F9] px-4 flex items-center gap-2">
              <span class="text-sm font-bold text-neutral-900">+998</span>
              <input
                v-model="checkoutForm.phone"
                type="tel"
                placeholder="90 123 45 67"
                class="flex-1 h-full bg-transparent border-none outline-none text-sm font-semibold text-neutral-900 placeholder:text-neutral-400"
              />
            </div>
          </label>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="block">
              <span class="block text-xs font-semibold text-neutral-500 mb-1.5">Viloyatni tanlang</span>
              <input
                v-model="checkoutForm.region"
                type="text"
                placeholder="Toshkent"
                class="w-full h-12 rounded-2xl bg-[#F6F6F9] px-4 border-none outline-none text-sm font-semibold text-neutral-900 placeholder:text-neutral-400"
              />
            </label>
            <label class="block">
              <span class="block text-xs font-semibold text-neutral-500 mb-1.5">Tumanni tanlang</span>
              <input
                v-model="checkoutForm.district"
                type="text"
                placeholder="Yunusobod"
                class="w-full h-12 rounded-2xl bg-[#F6F6F9] px-4 border-none outline-none text-sm font-semibold text-neutral-900 placeholder:text-neutral-400"
              />
            </label>
          </div>

          <label class="block">
            <span class="block text-xs font-semibold text-neutral-500 mb-1.5">Manzil</span>
            <textarea
              v-model="checkoutForm.address"
              rows="3"
              placeholder="Ko‘cha, uy, mo‘ljal"
              class="w-full rounded-2xl bg-[#F6F6F9] px-4 py-3 border-none outline-none text-sm font-semibold text-neutral-900 placeholder:text-neutral-400 resize-none"
            ></textarea>
          </label>
        </div>

        <div class="mt-5 rounded-2xl bg-[#F6F6F9] p-4 space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-neutral-500">{{ cartStore.selectedCount }} ta mahsulot</span>
            <span class="font-bold text-neutral-900">{{ formatPrice(cartStore.totalAmount) }} so‘m</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-neutral-500">Yetkazib berish</span>
            <span class="font-bold text-emerald-600">Aniqlanadi</span>
          </div>
        </div>

        <button
          type="button"
          @click="submitCheckout"
          class="w-full mt-4 py-3.5 rounded-2xl bg-[#0B0A3F] text-white font-bold text-base hover:bg-[#150a58] transition-colors border-none cursor-pointer"
        >
          Buyurtma berish
        </button>
      </div>
    </div>

    <!-- Buyurtma qabul qilindi oynasi -->
    <div
      v-if="isOrderSuccessOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isOrderSuccessOpen = false"
    >
      <div class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-400 text-white mx-auto flex items-center justify-center mb-4">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <h2 class="text-xl font-bold text-neutral-900 mb-2">Buyurtmangiz qabul qilindi!</h2>
        <p class="text-sm text-neutral-500 mb-6">
          Operatorimiz tez orada siz bilan bog‘lanib, buyurtmani tasdiqlaydi.
        </p>
        <button
          type="button"
          @click="isOrderSuccessOpen = false"
          class="w-full py-3.5 rounded-2xl bg-primary text-white font-semibold text-base hover:bg-primary/90 transition-colors border-none cursor-pointer"
        >
          Tushunarli
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import type { CartItem } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { useFavoritesStore } from '~/stores/favorites'

const cartStore = useCartStore()
const authStore = useAuthStore()
const favStore = useFavoritesStore()

const activeMenuId = ref<string | number | null>(null)
// Piyola'dagi "Promokod" maydoniga vizual parallellik — yuqoridagi
// shablon izohiga qarang (haqiqiy tekshiruv hali ulanmagan).
const promoCode = ref('')
const isInstallmentActive = ref(false)
const isDrawerOpen = ref(false)
const selectedInstallmentMonths = ref(12)
const tempMonths = ref(12)
const isOrderConfirmOpen = ref(false)
const isOrderSuccessOpen = ref(false)
const checkoutForm = reactive({
  name: authStore.user?.name || '',
  phone: authStore.user?.phone_number ? String(authStore.user.phone_number).replace(/^998/, '') : '',
  region: '',
  district: '',
  address: ''
})

const monthlyPayment = computed(() => {
  if (!cartStore.totalAmount || selectedInstallmentMonths.value <= 0) return 0
  return Math.round(cartStore.totalAmount / selectedInstallmentMonths.value)
})

function toggleItemMenu(id: string | number) {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

// Click outside to close active item menu
onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('click', () => {
      activeMenuId.value = null
    })
  }
})

function moveToFavorites(item: CartItem) {
  favStore.toggleFavorite({
    id: item.productId,
    name: item.name,
    price: item.originalPrice,
    discountPrice: item.price < item.originalPrice ? item.price : undefined,
    image_urls: [item.image]
  }, item.type)
  cartStore.removeItem(item.id)
}

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function productUrl(item: CartItem) {
  const slug = (item.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  return item.type === 'stationery' ? `/stationery/${item.productId}-${slug}` : `/books/${item.productId}-${slug}`
}

function handleCheckout() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }
  isOrderConfirmOpen.value = true
}

function closeOrderConfirm() {
  isOrderConfirmOpen.value = false
}

function submitCheckout() {
  isOrderConfirmOpen.value = false
  isOrderSuccessOpen.value = true
  cartStore.removeSelected()
}

useSeoMeta({
  title: 'Savatcha — Kitobchi',
  description: 'Tanlangan kitoblar va xaridlar savatchasi.'
})
</script>
