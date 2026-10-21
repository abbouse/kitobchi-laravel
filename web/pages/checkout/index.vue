<template>
  <main class="max-md:grow h-full md:min-h-dvh pb-10">
    <!-- Mobile Header (hidden on md) -->
    <div class="md:hidden py-3 rounded-b-2xl mb-3 bg-white sticky top-0 z-40 transition-all duration-300 shadow-2xs">
      <div class="px-4 w-full max-w-(--ui-container) mx-auto">
        <div class="flex items-center justify-between">
          <button @click="$router.back()" type="button" aria-label="Orqaga" class="w-10 h-10 rounded-full flex items-center justify-center p-0 cursor-pointer border-none bg-neutral-100 hover:bg-neutral-200 text-neutral-700 transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </button>
          <h1 class="text-lg font-bold text-neutral-900 m-0">Buyurtma rasmiylashtirish</h1>
          <div class="w-10"></div>
        </div>
      </div>
    </div>

    <div class="md:py-6">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">

        <!-- Desktop Breadcrumb (hidden on max-md) -->
        <div class="mb-5 max-md:hidden">
          <div class="flex items-center gap-2">
            <button type="button" @click="$router.back()" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer">
              <svg class="shrink-0 size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav class="flex items-center gap-2 text-sm text-neutral-500">
              <NuxtLink to="/" class="hover:text-neutral-900 transition-colors no-underline">Asosiy</NuxtLink>
              <span>/</span>
              <NuxtLink to="/cart" class="hover:text-neutral-900 transition-colors no-underline">Savat</NuxtLink>
              <span>/</span>
              <span class="text-neutral-900 font-medium">Buyurtma</span>
            </nav>
          </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-5 lg:items-start w-full">
          <!-- Main Form Content -->
          <form class="checkout-form-col w-full space-y-4" @submit.prevent="submitOrder">

            <!-- Section 1: Buyurtmani oluvchi (Recipient) -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white shadow-2xs w-full">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">1</div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 m-0">Buyurtmani oluvchi</h2>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="fullName" class="block font-medium text-neutral-700 text-sm mb-1.5">To'liq ism</label>
                  <input
                    v-model="form.fullName"
                    type="text"
                    id="fullName"
                    placeholder="Ismingiz va familiyangiz"
                    class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all"
                  >
                </div>
                <div>
                  <label for="phone" class="block font-medium text-neutral-700 text-sm mb-1.5">Telefon raqam</label>
                  <input
                    v-model="form.phone"
                    @input="onPhoneInput"
                    type="tel"
                    id="phone"
                    placeholder="+998"
                    class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all font-mono"
                  >
                </div>
              </div>
            </div>

            <!-- Section 2: Yetkazib berish manzili (Delivery Address) -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white shadow-2xs w-full">
              <div class="flex items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">2</div>
                  <h2 class="text-lg sm:text-xl font-bold text-neutral-900 m-0">Yetkazib berish manzili</h2>
                </div>
                <button
                  v-if="savedAddresses.length > 0"
                  type="button"
                  @click="openAddAddressModal"
                  class="text-xs sm:text-sm font-semibold text-primary hover:underline bg-transparent border-none cursor-pointer flex items-center gap-1"
                >
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                  Manzil qo'shish
                </button>
              </div>

              <!-- Loading Skeleton -->
              <div v-if="addressesLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div v-for="n in 2" :key="n" class="h-20 rounded-2xl bg-neutral-100 animate-pulse"></div>
              </div>

              <!-- Saved Addresses: Clean VK grid cards -->
              <div v-else-if="savedAddresses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                  v-for="loc in savedAddresses"
                  :key="loc.id"
                  type="button"
                  @click="selectedAddressId = loc.id"
                  class="text-left rounded-2xl p-4 border-2 transition-all cursor-pointer relative"
                  :class="selectedAddressId === loc.id ? 'border-primary bg-[#F4F8FD]' : 'border-transparent bg-neutral-50/80 hover:bg-neutral-100/70'"
                >
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="selectedAddressId === loc.id ? 'bg-primary text-white' : 'bg-neutral-100 text-neutral-500'">
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="text-sm text-neutral-900 font-semibold m-0 line-clamp-2 leading-snug">{{ loc.fullAddress }}</p>
                      <div class="flex items-center gap-2 mt-1.5">
                        <span v-if="mainAddressId === loc.id" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary">Asosiy manzil</span>
                        <span v-if="selectedAddressId === loc.id" class="text-[11px] font-semibold text-emerald-600 flex items-center gap-0.5">
                          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Tanlangan
                        </span>
                      </div>
                    </div>
                  </div>
                </button>
              </div>

              <!-- No saved address: directly show address picker -->
              <template v-else>
                <div class="pt-1">
                  <UzAddressPicker ref="addressPickerRef" @update="onAddrUpdate" />
                </div>
              </template>
            </div>

            <!-- Section 3: Yetkazib berish usuli (Delivery Method) -->
            <div v-if="deliveryServices.length > 0" class="p-5 sm:p-6 rounded-3xl bg-white shadow-2xs w-full">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">3</div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 m-0">Yetkazib berish usuli</h2>
              </div>
              <div class="space-y-2.5">
                <button
                  v-for="svc in deliveryServices"
                  :key="svc.id"
                  type="button"
                  @click="selectedDeliveryServiceId = svc.id"
                  class="w-full flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-left"
                  :class="selectedDeliveryServiceId === svc.id ? 'border-primary bg-[#F4F8FD]' : 'border-transparent bg-neutral-50/80 hover:bg-neutral-100/70'"
                >
                  <div class="flex items-center gap-3.5 min-w-0">
                    <div class="rounded-full w-5 h-5 border-2 flex items-center justify-center shrink-0 transition-all" :class="selectedDeliveryServiceId === svc.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'">
                      <div v-if="selectedDeliveryServiceId === svc.id" class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold m-0 text-neutral-900">{{ svc.name }}</p>
                      <p v-if="svc.muddat" class="text-xs text-neutral-500 m-0 mt-0.5">{{ svc.muddat }} kun ichida yetkaziladi</p>
                    </div>
                  </div>
                  <span class="text-sm font-bold shrink-0 px-2.5 py-1 rounded-xl" :class="svc.calculated_price > 0 ? 'text-neutral-900 bg-neutral-100' : 'text-emerald-700 bg-emerald-50'">
                    {{ svc.calculated_price > 0 ? formatPrice(svc.calculated_price) + " so'm" : 'Bepul' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Section 4: To'lov turi (Payment Method) -->
            <div class="p-5 sm:p-6 rounded-3xl bg-white shadow-2xs w-full">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center shrink-0">4</div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 m-0">To'lov turi</h2>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label
                  v-for="method in paymentMethods"
                  :key="method.id"
                  class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer"
                  :class="form.paymentMethod === method.id ? 'border-primary bg-[#F4F8FD]' : 'border-transparent bg-neutral-50/80 hover:bg-neutral-100/70'"
                >
                  <input type="radio" :value="method.id" v-model="form.paymentMethod" class="sr-only">
                  <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="form.paymentMethod === method.id ? 'bg-primary text-white' : 'bg-neutral-100 text-neutral-600'">
                      <svg v-if="method.id === 'card'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><circle cx="7" cy="15" r="1"/></svg>
                      <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    </div>
                    <div>
                      <p class="text-sm font-bold text-neutral-900 m-0">{{ method.name }}</p>
                      <p class="text-xs text-neutral-500 m-0">{{ method.label }}</p>
                    </div>
                  </div>
                  <div class="rounded-full w-5 h-5 border-2 flex items-center justify-center shrink-0 transition-all" :class="form.paymentMethod === method.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'">
                    <div v-if="form.paymentMethod === method.id" class="w-2 h-2 bg-white rounded-full"></div>
                  </div>
                </label>
              </div>

              <!-- Card Payment Section -->
              <div v-if="form.paymentMethod === 'card'" class="mt-4">
                <!-- AUTHENTICATED: Saved cards list + Add card button -->
                <div v-if="authStore.isAuthenticated" class="space-y-2.5">
                  <div v-if="cardsLoading" class="space-y-2">
                    <div v-for="n in 2" :key="n" class="h-16 rounded-2xl bg-neutral-100 animate-pulse"></div>
                  </div>
                  <template v-else>
                    <button
                      v-for="c in cards"
                      :key="c.id"
                      type="button"
                      @click="selectedCardId = c.id"
                      class="w-full flex items-center justify-between p-3.5 rounded-2xl border-2 transition-all cursor-pointer text-left"
                      :class="selectedCardId === c.id ? 'border-primary bg-[#F4F8FD]' : 'border-transparent bg-neutral-50/80 hover:bg-neutral-100/70'"
                    >
                      <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-neutral-100 flex items-center justify-center shrink-0 text-primary">
                          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        </div>
                        <div class="min-w-0">
                          <p class="text-sm font-semibold m-0 text-neutral-900 font-mono">{{ c.masked_number || c.card_number }}</p>
                          <p class="text-xs text-neutral-500 m-0">{{ c.card_name || c.vendor || 'Bank kartasi' }}</p>
                        </div>
                      </div>
                      <div class="rounded-full w-5 h-5 border-2 flex items-center justify-center shrink-0 transition-all" :class="selectedCardId === c.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'">
                        <div v-if="selectedCardId === c.id" class="w-2 h-2 bg-white rounded-full"></div>
                      </div>
                    </button>

                    <button
                      type="button"
                      @click="openCardModal"
                      class="w-full flex items-center justify-center gap-2.5 p-3.5 rounded-2xl border border-dashed border-neutral-300 hover:border-primary/60 transition-all cursor-pointer bg-neutral-50/50 text-primary font-semibold text-sm hover:bg-primary/5"
                    >
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                      <span>Yangi karta qo'shish</span>
                    </button>
                  </template>
                </div>

                <!-- GUEST / NON-AUTH: Direct inline card inputs (one-time payment) -->
                <div v-else class="p-4 sm:p-5 rounded-2xl bg-neutral-50/80 space-y-3.5">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-neutral-600">Karta ma'lumotlari</span>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full flex items-center gap-1 shrink-0">
                      <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      Birmartalik to'lov (saqlanmaydi)
                    </span>
                  </div>

                  <div>
                    <label class="block font-medium text-neutral-700 text-xs sm:text-sm mb-1.5">Karta raqami</label>
                    <div class="relative">
                      <input
                        v-model="guestCard.number"
                        @input="onGuestCardNumberInput"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-number"
                        maxlength="19"
                        placeholder="8600 0000 0000 0000"
                        class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 font-mono tracking-wider focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 pl-11 bg-white focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all shadow-2xs"
                      >
                      <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block font-medium text-neutral-700 text-xs sm:text-sm mb-1.5">Amal qilish oyi (MM)</label>
                      <input
                        v-model="guestCard.expireMonth"
                        @input="onGuestMonthInput"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-exp-month"
                        maxlength="2"
                        placeholder="01"
                        class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 font-mono text-center focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-white focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all shadow-2xs"
                      >
                    </div>
                    <div>
                      <label class="block font-medium text-neutral-700 text-xs sm:text-sm mb-1.5">Yili (YY)</label>
                      <input
                        v-model="guestCard.expireYear"
                        @input="onGuestYearInput"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-exp-year"
                        maxlength="2"
                        placeholder="28"
                        class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 font-mono text-center focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-white focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all shadow-2xs"
                      >
                    </div>
                  </div>

                  <p class="text-xs text-neutral-500 m-0 pt-1 leading-relaxed">
                    Kartangiz Paylov tizimida saqlanmaydi va hisobingizga bog'lanmaydi. Mablag' faqat ushbu buyurtma uchun bir martalik yechib olinadi.
                  </p>
                </div>
              </div>
            </div>
          </form>

          <!-- Right Side: Sticky Order Summary (VK style card) -->
          <div class="checkout-summary-col shrink-0 h-fit lg:sticky lg:top-24">
            <div class="p-5 sm:p-6 rounded-3xl bg-white shadow-2xs space-y-4">
              <h3 class="text-lg font-bold text-neutral-900 m-0 pb-3 border-b border-neutral-100">Buyurtmangiz</h3>

              <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center text-neutral-600">
                  <span>Mahsulotlar ({{ cartStore.selectedCount }} ta)</span>
                  <span class="text-neutral-900 font-semibold">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
                <div v-if="cartStore.totalDiscount > 0" class="flex justify-between items-center text-neutral-600">
                  <span>Chegirma</span>
                  <span class="text-red-500 font-semibold">-{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
                </div>
                <div class="flex justify-between items-center text-neutral-600">
                  <span>Yetkazib berish</span>
                  <span class="font-semibold" :class="deliveryPrice > 0 ? 'text-neutral-900' : 'text-emerald-600'">
                    {{ selectedDeliveryService ? (deliveryPrice > 0 ? formatPrice(deliveryPrice) + " so'm" : 'Bepul') : 'Hisoblanadi' }}
                  </span>
                </div>
              </div>

              <div class="pt-4 border-t border-neutral-100 flex justify-between items-baseline">
                <span class="text-base font-bold text-neutral-900">Jami to'lov</span>
                <span class="text-2xl font-extrabold text-primary">{{ formatPrice(cartStore.totalAmount - cartStore.totalDiscount + deliveryPrice) }} so'm</span>
              </div>

              <!-- Error message if any -->
              <div v-if="orderError" class="p-3 bg-red-50 text-red-600 rounded-xl text-xs sm:text-sm font-medium text-center">
                {{ orderError }}
              </div>

              <!-- Submit button -->
              <button
                @click="submitOrder"
                type="button"
                :disabled="submitting"
                class="w-full h-14 rounded-2xl bg-brand-gradient text-white font-bold text-base shadow-md hover:brightness-105 active:scale-[0.99] transition-all flex items-center justify-center gap-2 border-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <span v-if="submitting">Yuborilmoqda...</span>
                <template v-else>
                  <span>To'lov sahifasiga o'tish</span>
                  <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                </template>
              </button>

              <!-- Trust & Security Badges -->
              <div class="pt-3 border-t border-neutral-100 space-y-2 text-xs text-neutral-500">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                  <span>100% xavfsiz to'lov va ma'lumotlar kafolati</span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75a1.125 1.125 0 00-1.125-1.125H3.375A1.125 1.125 0 002.25 3.75v10.5c0 .621.504 1.125 1.125 1.125h1.5"/></svg>
                  <span>Butun O'zbekiston bo'ylab tez yetkazib berish</span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div
      v-if="isSuccessOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl">
        <div class="p-8 text-center">
          <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4 text-emerald-600">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="text-2xl font-bold text-neutral-900 mb-2">Buyurtma qabul qilindi!</h3>
          <p class="text-sm text-neutral-600 mb-6">Tez orada operatorlarimiz siz bilan bog'lanadi.</p>
          <button @click="finishOrder" type="button" class="w-full h-12 rounded-2xl bg-brand-gradient text-white font-bold text-base hover:brightness-105 transition-all border-none cursor-pointer shadow-md">Tushunarli, Asosiyga qaytish</button>
        </div>
      </div>
    </div>

    <!-- Manzil Qo'shish Modali -->
    <!-- MUHIM: profile/info.vue'dagi "Manzil qo'shish" modali bilan bir xil
         naqsh (fixed inset-0 backdrop + markazlashtirilgan oq kartochka) —
         foydalanuvchining saqlangan manzillari mavjud bo'lganda, gorizontal
         scroll ro'yxatidagi "Manzil qo'shish" kartasi bosilganda ochiladi. -->
    <Teleport to="body">
      <div
        v-if="isAddAddressModalOpen"
        class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="isAddAddressModalOpen = false"
      >
        <div class="modal-sm-600 max-h-85vh overflow-y-auto relative bg-white rounded-3xl p-6 sm:p-8 w-full shadow-2xl">
          <button @click="isAddAddressModalOpen = false" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
            <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <h3 class="text-2xl font-bold text-center m-0 mb-8 text-neutral-900">Manzil qo'shish</h3>
          <form @submit.prevent="handleSaveAddressModal" class="space-y-4">

            <div v-if="modalAddressError" class="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
              {{ modalAddressError }}
            </div>

            <UzAddressPicker ref="modalAddressPickerRef" @update="onModalAddrUpdate" />

            <div v-if="modalAddressSummary.fullAddress" class="p-3 rounded-xl bg-[#F6F6F9] text-sm text-neutral-600">
              {{ modalAddressSummary.fullAddress }}
            </div>

            <button type="submit" :disabled="!modalAddressSummary.isValid || savingModalAddress" class="w-full font-bold items-center transition-all gap-1.5 text-white bg-brand-gradient hover:brightness-105 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-md">
              {{ savingModalAddress ? "Qo'shilmoqda..." : "Qo'shish" }}
            </button>
          </form>
        </div>
      </div>

      <!-- Karta Qo'shish Modali -->
      <!-- MUHIM: Kitobchi ilovamizdagi bilan bir xil oqim — 1) karta raqami +
           muddati kiritiladi, 2) shu MODALNING O'ZIDA (yangi modal ochilmaydi)
           "kod yuborildi" xabari va SMS kod maydoniga o'tadi, 3) tasdiqlangach
           karta ro'yxatga avtomatik qo'shilib, selected bo'ladi. Backend:
           POST /cards (raqam+muddat) → POST /cards/verify (SMS kod). -->
      <div
        v-if="isCardModalOpen"
        class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="closeCardModal"
      >
        <div class="relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full max-w-md">
          <button @click="closeCardModal" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
            <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <h3 class="text-xl font-bold text-center m-0 mb-6 text-neutral-900">
            {{ cardStep === 'form' ? "Karta qo'shish" : 'SMS kodni kiriting' }}
          </h3>

          <div v-if="cardError" class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
            {{ cardError }}
          </div>

          <form v-if="cardStep === 'form'" @submit.prevent="submitCardForm" class="space-y-4">
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1">Karta raqami</label>
              <input v-model="cardForm.number" type="text" inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="0000 0000 0000 0000" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-base rounded-2xl h-12 p-3 bg-[#F6F6F9] border border-transparent focus:border-primary/20 transition-all">
            </div>
            <div class="flex gap-3">
              <div class="flex-1">
                <label class="block font-medium text-neutral-800 text-sm mb-1">Oy (MM)</label>
                <input v-model="cardForm.expireMonth" type="text" inputmode="numeric" autocomplete="cc-exp-month" maxlength="2" placeholder="MM" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-base rounded-2xl h-12 p-3 bg-[#F6F6F9] border border-transparent focus:border-primary/20 transition-all">
              </div>
              <div class="flex-1">
                <label class="block font-medium text-neutral-800 text-sm mb-1">Yil (YY)</label>
                <input v-model="cardForm.expireYear" type="text" inputmode="numeric" autocomplete="cc-exp-year" maxlength="2" placeholder="YY" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-base rounded-2xl h-12 p-3 bg-[#F6F6F9] border border-transparent focus:border-primary/20 transition-all">
              </div>
            </div>
            <button type="submit" :disabled="cardSaving" class="w-full font-bold items-center transition-all gap-1.5 text-white bg-brand-gradient hover:brightness-105 h-12 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-md">
              {{ cardSaving ? "Saqlanmoqda..." : "Davom etish" }}
            </button>
          </form>

          <form v-else @submit.prevent="submitCardOtp" class="space-y-4">
            <p class="text-sm text-neutral-500 text-center m-0">
              {{ pendingCard?.otpPhone ? `${pendingCard.otpPhone} raqamiga kod yuborildi` : "Telefon raqamingizga kod yuborildi" }}
            </p>
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1">SMS kod</label>
              <input v-model="cardOtpCode" type="text" inputmode="numeric" maxlength="6" placeholder="000000" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-center tracking-[0.5em] text-lg rounded-2xl h-12 p-3 bg-[#F6F6F9] border border-transparent focus:border-primary/20 transition-all">
            </div>
            <button type="submit" :disabled="cardSaving" class="w-full font-bold items-center transition-all gap-1.5 text-white bg-brand-gradient hover:brightness-105 h-12 flex justify-center rounded-2xl text-base px-6 mt-2 border-none cursor-pointer disabled:opacity-75 shadow-md">
              {{ cardSaving ? "Tekshirilmoqda..." : "Tasdiqlash" }}
            </button>
          </form>
        </div>
      </div>

      <!-- Auth Phone OTP Modali (Mehmon foydalanuvchilar uchun) -->
      <div
        v-if="isAuthOtpModalOpen"
        class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="isAuthOtpModalOpen = false"
      >
        <div class="relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full max-w-md shadow-2xl">
          <button @click="isAuthOtpModalOpen = false" type="button" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 rounded-full bg-[#F6F6F9] hover:bg-neutral-200 transition-colors flex items-center justify-center border-none cursor-pointer">
            <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
          </div>
          <h3 class="text-xl font-bold text-center m-0 mb-2 text-neutral-900">
            Raqamni tasdiqlang
          </h3>
          <p class="text-sm text-neutral-500 text-center m-0 mb-6">
            {{ form.phone }} raqamiga yuborilgan 6 xonali SMS kodni kiriting
          </p>

          <div v-if="authOtpError" class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
            {{ authOtpError }}
          </div>

          <form @submit.prevent="submitAuthOtp" class="space-y-4">
            <div>
              <label class="block font-medium text-neutral-800 text-sm mb-1 text-center">SMS kod</label>
              <input
                v-model="authOtpCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                placeholder="000000"
                class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-center tracking-[0.5em] text-xl font-bold rounded-2xl h-12 p-3 bg-[#F6F6F9] border border-transparent focus:border-primary/20 transition-all"
              >
            </div>
            <button
              type="submit"
              :disabled="authOtpVerifying || authOtpCode.length !== 6"
              class="w-full font-bold items-center transition-all gap-1.5 text-white bg-brand-gradient hover:brightness-105 h-12 flex justify-center rounded-2xl text-base px-6 mt-2 border-none cursor-pointer disabled:opacity-60 shadow-md"
            >
              {{ authOtpVerifying ? "Tekshirilmoqda..." : "Tasdiqlash va davom etish" }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </main>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { useSnackbar } from '~/composables/useSnackbar'

const config = useRuntimeConfig()
const cartStore = useCartStore()
const authStore = useAuthStore()
const snackbar = useSnackbar()
const router = useRouter()

const isSuccessOpen = ref(false)

const authHeaders = computed(() => ({ Authorization: `Bearer ${authStore.token}` }))

// cart/index.vue'dagi bilan bir xil — piyoladagi flex-grow + min-height
// "pastga itarish" texnikasi faqat mobil kenglikda kerak.
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

const form = reactive({
  fullName: authStore.user?.name || '',
  phone: authStore.user?.phone_number || '',
  paymentMethod: 'card'
})

function onPhoneInput(e: Event) {
  const target = e.target as HTMLInputElement
  let digits = target.value.replace(/\D/g, '')
  if (digits.startsWith('998')) {
    digits = digits.slice(3)
  }
  digits = digits.slice(0, 9)

  if (digits.length === 0) {
    form.phone = ''
    return
  }

  let formatted = '+998'
  if (digits.length > 0) formatted += ' (' + digits.slice(0, 2)
  if (digits.length >= 2) formatted += ') ' + digits.slice(2, 5)
  if (digits.length >= 5) formatted += ' ' + digits.slice(5, 7)
  if (digits.length >= 7) formatted += ' ' + digits.slice(7, 9)
  form.phone = formatted
}

const guestCard = reactive({
  number: '',
  expireMonth: '',
  expireYear: '',
})

function onGuestCardNumberInput(e: Event) {
  const target = e.target as HTMLInputElement
  const digits = target.value.replace(/\D/g, '').slice(0, 16)
  guestCard.number = digits.replace(/(\d{4})(?=\d)/g, '$1 ')
}

function onGuestMonthInput(e: Event) {
  const target = e.target as HTMLInputElement
  guestCard.expireMonth = target.value.replace(/\D/g, '').slice(0, 2)
}

function onGuestYearInput(e: Event) {
  const target = e.target as HTMLInputElement
  guestCard.expireYear = target.value.replace(/\D/g, '').slice(0, 2)
}

const pendingOrderId = ref<number | null>(null)
const isAuthOtpModalOpen = ref(false)
const authOtpCode = ref('')
const authOtpVerifying = ref(false)
const authOtpError = ref('')

async function submitAuthOtp() {
  const code = authOtpCode.value.replace(/\D/g, '')
  if (code.length !== 6) {
    authOtpError.value = "6 xonali kodni kiriting"
    return
  }
  let rawPhone = form.phone.replace(/\D/g, '')
  if (!rawPhone.startsWith('998') && rawPhone.length === 9) {
    rawPhone = '998' + rawPhone
  }

  authOtpVerifying.value = true
  authOtpError.value = ''
  try {
    await authStore.verifyOtp(rawPhone, code)
    if (form.fullName && authStore.user && !authStore.user.name) {
      await authStore.updateProfile({ name: form.fullName }).catch(() => {})
    }
    isAuthOtpModalOpen.value = false
    await executeOrderSubmission()
  } catch (e: any) {
    authOtpError.value = e?.data?.message || "Tasdiqlash kodi noto'g'ri kiritildi"
  } finally {
    authOtpVerifying.value = false
  }
}

// MUHIM: manzil endi GPS/geolocation yoki qo'lda kiritilgan Viloyat/Tuman
// emas, balki components/UzAddressPicker.vue orqali (Viloyat → Tuman →
// Mahalla/qishloq, MIMAXUZ/uzbekistan-regions-data) tanlanadi.
const addressPickerRef = ref<{ reset: () => void } | null>(null)
const addressSummary = ref({
  regionId: null as number | null,
  regionName: '',
  districtId: null as number | null,
  districtName: '',
  village: '',
  street: '',
  fullAddress: '',
  isValid: false,
})

function onAddrUpdate(summary: typeof addressSummary.value) {
  addressSummary.value = summary
}

// ── Saqlangan manzillar (profile/info.vue bilan bir xil endpoint/mantiq) ──
// MUHIM: avval bu yerda faqat bo'sh (yangi) forma ko'rsatilardi — profildagi
// "Manzil qo'shish" bilan farqi yo'q edi, garchi userning allaqachon
// saqlangan manzillari bo'lsa ham. Endi: agar saqlangan manzillar bo'lsa —
// ular orasidan gorizontal scroll orqali tanlanadi (+ "Manzil qo'shish"
// kartasi profildagi bilan bir xil modalni ochadi); agar umuman manzil
// bo'lmasa — to'g'ridan-to'g'ri UzAddressPicker formasi ko'rsatiladi.
const savedAddresses = ref<any[]>([])
const addressesLoading = ref(true)
const selectedAddressId = ref<number | null>(null)
const mainAddressId = computed(() => (authStore.user as any)?.mainAddressID ?? null)

async function fetchAddresses() {
  addressesLoading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations`, {
      headers: authHeaders.value
    })
    savedAddresses.value = res?.data || []
    if (savedAddresses.value.length > 0) {
      const match = savedAddresses.value.find((a) => a.id === mainAddressId.value)
      selectedAddressId.value = (match || savedAddresses.value[0]).id
    }
  } catch (e) {
    savedAddresses.value = []
  } finally {
    addressesLoading.value = false
  }
}

// Tanlangan manzilni serverdagi "asosiy" (mainAddressID) sifatida belgilaydi
// — buyurtma yaratishda backend FAQAT shu maydonni o'qiydi.
async function selectAddress(id: number) {
  await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/select/${id}`, {
    headers: authHeaders.value
  })
  authStore.updateUser({ mainAddressID: id })
}

// Yetkazib berish narxini/muddatini serverdan olib kelib, eng arzonini
// avtomatik tanlaydi (foydalanuvchi istasa boshqasini tanlashi mumkin).
const deliveryServices = ref<any[]>([])
const selectedDeliveryServiceId = ref<number | null>(null)
const selectedDeliveryService = computed(() =>
  deliveryServices.value.find((s: any) => s.id === selectedDeliveryServiceId.value) || null
)
const deliveryPrice = computed(() => Number(selectedDeliveryService.value?.calculated_price || 0))

async function fetchCheckoutInfo() {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/getCartCheckoutInfo`, {
      headers: authHeaders.value
    })
    const services = ((res?.data?.delivery_services || []) as any[])
      .slice()
      .sort((a, b) => (a.calculated_price || 0) - (b.calculated_price || 0))
    deliveryServices.value = services
    if (services.length > 0 && !services.find((s) => s.id === selectedDeliveryServiceId.value)) {
      selectedDeliveryServiceId.value = services[0].id
    }
  } catch (e) {
    deliveryServices.value = []
  }
}

// Foydalanuvchi boshqa saqlangan manzilni tanlasa — serverdagi asosiy
// manzilni yangilab, yetkazib berish narxini shu manzil uchun qayta hisoblaydi.
watch(selectedAddressId, async (id, old) => {
  if (!id || id === old || savedAddresses.value.length === 0) return
  try {
    if (id !== mainAddressId.value) {
      await selectAddress(id)
    }
    await fetchCheckoutInfo()
  } catch (e) {
    // jim — "Yuborish" bosilganda yana urinib ko'riladi
  }
})

// Manzil qo'shish modali — profile/info.vue'dagi bilan bir xil (o'z alohida
// `modalAddressSummary`/`modalAddressPickerRef` holati bilan, checkout
// formasidagi asosiy `addressSummary`ga aralashib ketmasligi uchun).
const isAddAddressModalOpen = ref(false)
const modalAddressPickerRef = ref<{ reset: () => void } | null>(null)
const modalAddressSummary = ref({
  regionId: null as number | null,
  regionName: '',
  districtId: null as number | null,
  districtName: '',
  village: '',
  street: '',
  fullAddress: '',
  isValid: false,
})
const savingModalAddress = ref(false)
const modalAddressError = ref('')

function openAddAddressModal() {
  modalAddressError.value = ''
  modalAddressSummary.value = {
    regionId: null, regionName: '', districtId: null, districtName: '',
    village: '', street: '', fullAddress: '', isValid: false,
  }
  isAddAddressModalOpen.value = true
  nextTick(() => modalAddressPickerRef.value?.reset())
}

function onModalAddrUpdate(summary: typeof modalAddressSummary.value) {
  modalAddressSummary.value = summary
}

async function handleSaveAddressModal() {
  if (!modalAddressSummary.value.isValid) return
  savingModalAddress.value = true
  modalAddressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: authHeaders.value,
      body: {
        fullAddress: modalAddressSummary.value.fullAddress,
        countryCode: 'UZ',
        regionName: modalAddressSummary.value.regionName,
        districtName: modalAddressSummary.value.districtName,
        cityName: modalAddressSummary.value.village,
      }
    })
    if (res?.location_id) {
      savedAddresses.value.push({ id: res.location_id, fullAddress: modalAddressSummary.value.fullAddress })
      selectedAddressId.value = res.location_id
      authStore.updateUser({ mainAddressID: res.location_id })
    }
    isAddAddressModalOpen.value = false
  } catch (e: any) {
    modalAddressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingModalAddress.value = false
  }
}

// Buyurtma yuborish uchun "manzil to'g'ri tanlangan/kiritilgan"ligini
// bitta joydan tekshirish — holatiga qarab ikki xil manbadan keladi.
const hasValidAddress = computed(() => {
  if (savedAddresses.value.length > 0) return !!selectedAddressId.value
  return addressSummary.value.isValid
})

// Manzil hali serverga saqlanmagan bo'lsa (saqlangan manzillar ro'yxati
// bo'sh) — buyurtma yaratishdan oldin uni saqlab, asosiy manzil qilib
// belgilaydi. Aks holda faqat tanlangan manzilni asosiy qilib belgilaydi.
async function ensureMainAddress(): Promise<boolean> {
  if (savedAddresses.value.length > 0) {
    if (!selectedAddressId.value) return false
    if (selectedAddressId.value !== mainAddressId.value) {
      await selectAddress(selectedAddressId.value)
    }
    return true
  }

  if (!addressSummary.value.isValid) return false

  const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
    method: 'POST',
    headers: authHeaders.value,
    body: {
      fullAddress: addressSummary.value.fullAddress,
      countryCode: 'UZ',
      regionName: addressSummary.value.regionName,
      districtName: addressSummary.value.districtName,
      cityName: addressSummary.value.village,
    }
  })
  if (!res?.location_id) return false

  savedAddresses.value.push({ id: res.location_id, fullAddress: addressSummary.value.fullAddress })
  selectedAddressId.value = res.location_id
  authStore.updateUser({ mainAddressID: res.location_id })
  return true
}

// ── Savatchani serverga sinxronlash ────────────────────────────────────
// MUHIM: web savatchasi (stores/cart.ts) faqat localStorage'da yashaydi —
// backend `purchase/make` esa foydalanuvchining serverdagi `my_carts`
// qatorlaridan ("MyCart") o'qiydi. Shu sabab buyurtma yaratishdan oldin
// tanlangan mahsulotlarni ANIQ shu miqdorda serverga yozib, qaytgan
// `cart_id`larni `selected_cart_ids` sifatida yuboramiz.
async function syncCartToServer(): Promise<number[]> {
  const ids: number[] = []
  for (const item of cartStore.selectedItems) {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/cart_user/plus`, {
      method: 'POST',
      headers: authHeaders.value,
      body: {
        product_id: item.productId,
        product_type: item.type,
        variant_id: null,
        plusType: 'cartScreen',
        new_count_item: item.quantity,
      },
    })
    if (res?.data?.cart_id) {
      ids.push(res.data.cart_id)
    } else {
      throw new Error(res?.message || `"${item.name}" savatga qo'shishda xatolik yuz berdi`)
    }
  }
  return ids
}

// ── Kartalar (Kitobchi ilovamizdagi bilan bir xil Paylov oqimi) ───────────
const cards = ref<any[]>([])
const cardsLoading = ref(false)
const selectedCardId = ref<number | null>(null)

async function fetchCards() {
  cardsLoading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/cards`, {
      headers: authHeaders.value
    })
    cards.value = res?.data || []
    if (!selectedCardId.value) {
      const def = cards.value.find((c: any) => c.is_default) || cards.value[0]
      if (def) selectedCardId.value = def.id
    }
  } catch (e) {
    cards.value = []
  } finally {
    cardsLoading.value = false
  }
}

const isCardModalOpen = ref(false)
const cardStep = ref<'form' | 'otp'>('form')
const cardForm = reactive({ number: '', expireMonth: '', expireYear: '' })
const cardOtpCode = ref('')
const cardSaving = ref(false)
const cardError = ref('')
const pendingCard = ref<{ token: string; otpPhone: string } | null>(null)

function openCardModal() {
  cardStep.value = 'form'
  cardForm.number = ''
  cardForm.expireMonth = ''
  cardForm.expireYear = ''
  cardOtpCode.value = ''
  cardError.value = ''
  pendingCard.value = null
  isCardModalOpen.value = true
}

function closeCardModal() {
  isCardModalOpen.value = false
}

async function submitCardForm() {
  const digits = cardForm.number.replace(/\D/g, '')
  const mm = cardForm.expireMonth.padStart(2, '0')
  const yy = cardForm.expireYear.padStart(2, '0')

  if (digits.length !== 16) {
    cardError.value = "Karta raqami 16 ta raqamdan iborat bo'lishi kerak"
    return
  }
  if (mm.length !== 2 || yy.length !== 2 || Number(mm) < 1 || Number(mm) > 12) {
    cardError.value = "Amal qilish muddatini to'g'ri kiriting"
    return
  }

  cardSaving.value = true
  cardError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/cards`, {
      method: 'POST',
      headers: authHeaders.value,
      body: { number: digits, expire: `${mm}${yy}`, remember_card: true },
    })
    pendingCard.value = {
      token: res?.data?.verification_id || res?.data?.token,
      otpPhone: res?.data?.otp_sent_phone || '',
    }
    cardStep.value = 'otp'
  } catch (e: any) {
    cardError.value = e?.data?.message || "Kartani qo'shishda xatolik yuz berdi"
  } finally {
    cardSaving.value = false
  }
}

async function submitCardOtp() {
  if (!pendingCard.value) return
  const code = cardOtpCode.value.replace(/\D/g, '')
  if (code.length !== 6) {
    cardError.value = "6 xonali kodni kiriting"
    return
  }

  cardSaving.value = true
  cardError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/cards/verify`, {
      method: 'POST',
      headers: authHeaders.value,
      body: { verification_id: pendingCard.value.token, code },
    })
    const newCard = res?.card
    if (newCard) {
      cards.value = [newCard, ...cards.value.filter((c: any) => c.id !== newCard.id)]
      selectedCardId.value = newCard.id
    }
    form.paymentMethod = 'card'
    closeCardModal()

    if (pendingOrderId.value) {
      isSuccessOpen.value = true
      pendingOrderId.value = null
    }
  } catch (e: any) {
    cardError.value = e?.data?.message || 'Kodni tasdiqlashda xatolik yuz berdi'
  } finally {
    cardSaving.value = false
  }
}

const paymentMethods = [
  { id: 'card', name: 'Karta', label: "Bank kartasi orqali" },
  { id: 'cash', name: 'Naqd pul', label: "Qabul qilganda" }
]

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

const submitting = ref(false)
const orderError = ref('')

async function submitOrder() {
  orderError.value = ''

  if (!form.fullName || !form.phone || !hasValidAddress.value) {
    orderError.value = "Iltimos barcha maydonlarni to'ldiring"
    snackbar.showWarning("Iltimos, yetkazib berish manzili va barcha maydonlarni to'ldiring")
    return
  }

  if (form.paymentMethod === 'card') {
    if (authStore.isAuthenticated) {
      if (!selectedCardId.value) {
        orderError.value = "Iltimos, karta tanlang yoki yangi karta qo'shing"
        snackbar.showWarning("Iltimos, to'lov uchun bank kartasini tanlang")
        return
      }
    } else {
      const digits = guestCard.number.replace(/\D/g, '')
      const mm = guestCard.expireMonth.padStart(2, '0')
      const yy = guestCard.expireYear.padStart(2, '0')
      if (digits.length !== 16) {
        orderError.value = "Karta raqami 16 ta raqamdan iborat bo'lishi kerak"
        snackbar.showWarning("Karta raqami 16 ta raqamdan iborat bo'lishi kerak")
        return
      }
      if (mm.length !== 2 || yy.length !== 2 || Number(mm) < 1 || Number(mm) > 12) {
        orderError.value = "Amal qilish muddatini to'g'ri kiriting"
        snackbar.showWarning("Karta amal qilish muddatini to'g'ri kiriting")
        return
      }
    }
  }

  if (!authStore.isAuthenticated) {
    submitting.value = true
    try {
      let rawPhone = form.phone.replace(/\D/g, '')
      if (!rawPhone.startsWith('998') && rawPhone.length === 9) {
        rawPhone = '998' + rawPhone
      }
      if (rawPhone.length < 9) {
        orderError.value = "Telefon raqamini to'liq kiriting"
        snackbar.showWarning("Telefon raqamini to'liq kiriting")
        submitting.value = false
        return
      }
      await authStore.sendOtp(rawPhone)
      authOtpCode.value = ''
      authOtpError.value = ''
      isAuthOtpModalOpen.value = true
    } catch (e: any) {
      orderError.value = e?.data?.message || "SMS kod yuborishda xatolik yuz berdi"
      snackbar.showError(orderError.value)
    } finally {
      submitting.value = false
    }
    return
  }

  await executeOrderSubmission()
}

async function executeOrderSubmission() {
  submitting.value = true
  orderError.value = ''
  try {
    const addressOk = await ensureMainAddress()
    if (!addressOk) {
      orderError.value = "Yetkazib berish manzilini tanlang"
      snackbar.showWarning("Yetkazib berish manzilini tanlang")
      return
    }

    if (deliveryServices.value.length === 0) {
      await fetchCheckoutInfo()
    }
    if (!selectedDeliveryServiceId.value) {
      orderError.value = "Bu manzil uchun yetkazib berish xizmati topilmadi"
      snackbar.showError(orderError.value)
      return
    }

    const selectedCartIds = await syncCartToServer()
    if (selectedCartIds.length === 0) {
      orderError.value = "Savatcha bo'sh"
      snackbar.showWarning("Savatchada mahsulot topilmadi")
      return
    }

    const orderRes: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/make`, {
      method: 'POST',
      headers: { ...authHeaders.value, 'X-Client-Platform': 'web' },
      body: {
        paymentStatus: form.paymentMethod === 'cash' ? 0 : 1,
        deliveryservice_id: selectedDeliveryServiceId.value,
        selected_cart_ids: selectedCartIds,
      },
    })

    const orderId = orderRes?.order_id
    if (!orderId) {
      throw new Error("Buyurtma yaratilmadi")
    }

    if (form.paymentMethod === 'card') {
      if (selectedCardId.value) {
        await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/details/${orderId}/pay-with-card`, {
          method: 'POST',
          headers: authHeaders.value,
          body: { card_id: selectedCardId.value },
        })
        isSuccessOpen.value = true
        snackbar.showSuccess("Buyurtma muvaffaqiyatli to'landi va qabul qilindi!")
      } else {
        // Guest user: one-time charge without binding to paylov (remember_card: false)
        const digits = guestCard.number.replace(/\D/g, '')
        const mm = guestCard.expireMonth.padStart(2, '0')
        const yy = guestCard.expireYear.padStart(2, '0')

        pendingOrderId.value = orderId
        const cardRes: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/cards`, {
          method: 'POST',
          headers: authHeaders.value,
          body: {
            number: digits,
            expire: `${mm}${yy}`,
            remember_card: false,
            order_id: orderId,
          },
        })

        pendingCard.value = {
          token: cardRes?.data?.verification_id || cardRes?.data?.token,
          otpPhone: cardRes?.data?.otp_sent_phone || '',
        }
        cardStep.value = 'otp'
        cardOtpCode.value = ''
        cardError.value = ''
        isCardModalOpen.value = true
      }
    } else {
      isSuccessOpen.value = true
      snackbar.showSuccess("Buyurtmangiz muvaffaqiyatli qabul qilindi!")
    }
  } catch (e: any) {
    orderError.value = e?.data?.message || 'Buyurtma yaratishda xatolik yuz berdi'
    snackbar.showError(orderError.value)
  } finally {
    submitting.value = false
  }
}

function finishOrder() {
  isSuccessOpen.value = false
  cartStore.removeSelected()
  router.push('/')
}

onMounted(() => {
  if (cartStore.selectedCount === 0) {
    router.push('/cart')
  }
  if (authStore.isAuthenticated) {
    fetchAddresses()
    fetchCards()
  } else {
    addressesLoading.value = false
  }
})

useSeoMeta({
  title: 'Buyurtma rasmiylashtirish — Kitobchi'
})
</script>
