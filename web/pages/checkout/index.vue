<template>
  <main class="max-md:grow h-full md:min-h-dvh">
    <!-- Mobile Header (hidden on md) -->
    <div class="md:hidden py-3 rounded-b-2xl mb-2 bg-white sticky top-0 z-40 transition-all duration-300 shadow-sm">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto space-y-2">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button @click="$router.back()" type="button" class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm h-11 w-11 flex items-center justify-center p-0 cursor-pointer border-none bg-secondary-100 text-primary">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Buyurtma</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="min-h-dvh md:py-6 bg-white md:bg-transparent">
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

        <!-- TUZATILDI: cart/index.vue'dagi bilan bir xil muammo — pastdagi
             "To'lov sahifasiga o'tish" tugmasi max-md:sticky/max-md:z-50
             orqali "yopishqoq" bo'lishi kerak edi, lekin bu klasslar
             piyola.css'da kompilyatsiya qilinmagan (jonli tekshirildi),
             shu sabab u aslida oddiy static holatda edi. Endi savatchadagi
             kabi flex-grow + min-height texnikasi qo'llanildi. -->
        <div
          class="flex max-lg:flex-col gap-2 md:gap-3 lg:gap-5"
          :style="isMobile ? { minHeight: 'calc(100dvh - 71px)' } : {}"
        >
          <!-- Main Form Content -->
          <form class="w-full space-y-2 md:space-y-4" @submit.prevent="submitOrder">

            <!-- Section 1: Buyurtmani oluvchi (Recipient) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <h2 class="text-xl font-bold m-0">Buyurtmani oluvchi</h2>
              <div class="mt-4">
                <label for="fullName" class="block font-medium text-neutral-800 text-base mb-1">To'liq ism</label>
                <div class="relative">
                  <input v-model="form.fullName" type="text" id="fullName" placeholder="Ismingizni kiriting" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-white border border-transparent focus:border-primary/20 transition-all">
                </div>
              </div>
              <div class="mt-4">
                <label for="phone" class="block font-medium text-neutral-800 text-base mb-1">Telefon raqam</label>
                <div class="relative">
                  <input v-model="form.phone" type="tel" id="phone" placeholder="+998" class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none md:text-sm text-base rounded-2xl max-md:h-12 p-3 md:p-4 bg-white border border-transparent focus:border-primary/20 transition-all">
                </div>
              </div>
            </div>

            <!-- Section 2: Yetkazib berish manzili (Delivery Address) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold m-0">Yetkazib berish manzili</h2>
              </div>

              <!-- MUHIM: profil (profile/info.vue) bilan bir xil holat mantig'i.
                   Userning saqlangan manzillari (`v1/kitobchi/locations`) yuklanmoqda
                   bo'lsa — skeleton; agar saqlangan manzillar mavjud bo'lsa —
                   gorizontal scroll orqali tanlanadigan kartochkalar + "Manzil
                   qo'shish" kartasi (bosilganda profildagi bilan bir xil modal
                   ochiladi); agar umuman manzil bo'lmasa — to'g'ridan-to'g'ri
                   Viloyat → Tuman → Mahalla/qishloq formasi (UzAddressPicker)
                   ko'rsatiladi, chunki tanlaydigan hech narsa yo'q. -->

              <!-- Yuklanmoqda -->
              <div v-if="addressesLoading" class="flex gap-3 overflow-x-auto pb-1">
                <div v-for="n in 2" :key="n" class="shrink-0 w-64 rounded-2xl bg-white/60 animate-pulse" style="height: 76px;"></div>
              </div>

              <!-- Saqlangan manzillar bor: gorizontal scroll orqali tanlash -->
              <div v-else-if="savedAddresses.length > 0" class="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1 snap-x snap-mandatory">
                <button
                  v-for="loc in savedAddresses"
                  :key="loc.id"
                  type="button"
                  @click="selectedAddressId = loc.id"
                  class="shrink-0 snap-start w-64 text-left rounded-2xl p-4 border-2 transition-all cursor-pointer bg-white"
                  :class="selectedAddressId === loc.id ? 'border-primary' : 'border-transparent hover:border-neutral-200'"
                >
                  <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-[#F6F6F9] flex items-center justify-center shrink-0">
                      <svg class="text-neutral-500" style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm text-neutral-900 font-medium m-0 line-clamp-2">{{ loc.fullAddress }}</p>
                      <span v-if="mainAddressId === loc.id" class="text-primary text-xs font-semibold">Asosiy manzil</span>
                    </div>
                  </div>
                </button>

                <button
                  type="button"
                  @click="openAddAddressModal"
                  class="shrink-0 snap-start w-36 rounded-2xl p-4 border-2 border-dashed border-neutral-300 hover:border-primary/50 transition-all cursor-pointer bg-white/60 flex flex-col items-center justify-center gap-1.5 text-primary"
                >
                  <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                  <span class="text-sm font-medium text-center">Manzil qo'shish</span>
                </button>
              </div>

              <!-- Saqlangan manzil umuman yo'q: to'g'ridan-to'g'ri forma -->
              <template v-else>
                <UzAddressPicker ref="addressPickerRef" @update="onAddrUpdate" />

                <div v-if="addressSummary.fullAddress" class="mt-3 p-3.5 rounded-2xl bg-white text-sm text-neutral-600">
                  {{ addressSummary.fullAddress }}
                </div>
              </template>
            </div>

            <!-- Section 3: Yetkazib berish usuli (Delivery Method) -->
            <!-- MUHIM: narx/muddat manzilga bog'liq bo'lgani uchun faqat
                 manzil serverda "asosiy" sifatida tanlangandan keyin (ya'ni
                 saqlangan manzillardan biri tanlanganda) yuklanadi — pastdagi
                 `watch(selectedAddressId, ...)` orqali. Yangi (hali
                 saqlanmagan) manzil holatida narx faqat "Yuborish" bosilganda
                 hisoblanadi. -->
            <div v-if="deliveryServices.length > 0" class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <h2 class="text-xl font-bold m-0 mb-4">Yetkazib berish usuli</h2>
              <div class="space-y-2">
                <button
                  v-for="svc in deliveryServices"
                  :key="svc.id"
                  type="button"
                  @click="selectedDeliveryServiceId = svc.id"
                  class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-white border-2 transition-all cursor-pointer text-left"
                  :class="selectedDeliveryServiceId === svc.id ? 'border-primary' : 'border-transparent hover:border-neutral-200'"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="rounded-full w-4 h-4 border-2 flex items-center justify-center shrink-0" :class="selectedDeliveryServiceId === svc.id ? 'border-primary' : 'border-neutral-300'">
                      <div v-if="selectedDeliveryServiceId === svc.id" class="w-2 h-2 bg-primary rounded-full"></div>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium m-0 text-neutral-900">{{ svc.name }}</p>
                      <p v-if="svc.muddat" class="text-xs text-neutral-400 m-0">{{ svc.muddat }} kun ichida</p>
                    </div>
                  </div>
                  <span class="text-sm font-semibold shrink-0" :class="svc.calculated_price > 0 ? 'text-neutral-900' : 'text-primary'">
                    {{ svc.calculated_price > 0 ? formatPrice(svc.calculated_price) + " so'm" : 'Bepul' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Section 4: To'lov turi (Payment Method) -->
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50 w-full">
              <h2 class="text-xl font-bold m-0 mb-4">To'lov turi</h2>
              <div class="text-sm">
                <fieldset class="grid grid-cols-2 gap-3 md:gap-5 border-none p-0 m-0">

                  <label v-for="method in paymentMethods" :key="method.id" class="flex items-start flex-row text-sm p-3.5 w-full sm:pr-6 rounded-2xl bg-white border-2 transition-all cursor-pointer" :class="form.paymentMethod === method.id ? 'border-primary' : 'border-transparent'">
                    <input type="radio" :value="method.id" v-model="form.paymentMethod" class="sr-only">
                    <div class="flex items-center h-5 sm:hidden my-auto mr-3">
                      <div class="rounded-full w-4 h-4 border-2 flex items-center justify-center transition-colors" :class="form.paymentMethod === method.id ? 'border-primary' : 'border-neutral-300'">
                        <div v-if="form.paymentMethod === method.id" class="w-2 h-2 bg-primary rounded-full"></div>
                      </div>
                    </div>
                    <div class="w-full">
                      <div class="block font-medium text-neutral-900">
                        <div class="w-full sm:mx-auto max-sm:flex-row-reverse flex flex-col justify-between items-center sm:gap-2">
                          <div class="h-6 sm:h-8 flex items-center justify-center text-primary font-bold text-lg italic my-auto" style="max-width: 110px;">{{ method.name }}</div>
                          <p class="sm:mt-1 leading-6 font-base m-0">{{ method.label }}</p>
                        </div>
                      </div>
                    </div>
                  </label>

                </fieldset>
              </div>

              <!-- Karta tanlash / qo'shish — faqat "Karta" usuli tanlanganda,
                   xuddi Kitobchi ilovamizdagi kabi: saqlangan kartalar ro'yxati
                   + "Yangi karta qo'shish" (tanlangach avtomatik selected). -->
              <div v-if="form.paymentMethod === 'card'" class="mt-4 space-y-2">
                <div v-if="cardsLoading" class="flex flex-col gap-2">
                  <div v-for="n in 2" :key="n" class="rounded-2xl bg-white/60 animate-pulse" style="height: 62px;"></div>
                </div>
                <template v-else>
                  <button
                    v-for="c in cards"
                    :key="c.id"
                    type="button"
                    @click="selectedCardId = c.id"
                    class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-white border-2 transition-all cursor-pointer text-left"
                    :class="selectedCardId === c.id ? 'border-primary' : 'border-transparent hover:border-neutral-200'"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="w-9 h-9 rounded-lg bg-[#F6F6F9] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-sm font-medium m-0 text-neutral-900">{{ c.masked_number || c.card_number }}</p>
                        <p class="text-xs text-neutral-400 m-0">{{ c.card_name || c.vendor || 'Karta' }}</p>
                      </div>
                    </div>
                    <div class="rounded-full w-4 h-4 border-2 flex items-center justify-center shrink-0" :class="selectedCardId === c.id ? 'border-primary' : 'border-neutral-300'">
                      <div v-if="selectedCardId === c.id" class="w-2 h-2 bg-primary rounded-full"></div>
                    </div>
                  </button>

                  <button
                    type="button"
                    @click="openCardModal"
                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 border-dashed border-neutral-300 hover:border-primary/50 transition-all cursor-pointer bg-white/60 text-primary"
                  >
                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <span class="text-sm font-medium">Yangi karta qo'shish</span>
                  </button>
                </template>
              </div>
            </div>
          </form>

          <!-- Right Side: Order Summary -->
          <div
            class="checkout-summary-col w-full shrink-0 h-fit lg:sticky top-24"
            :style="isMobile ? { display: 'flex', flexDirection: 'column', flexGrow: 1 } : {}"
          >
            <div class="p-4 sm:p-6 rounded-3xl bg-secondary-50">

              <div class="space-y-4 mb-4 border-b border-neutral-200/50 pb-4">
                <div class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>{{ cartStore.selectedCount }} ta mahsulot</span><span class="text-neutral-900 font-medium">{{ formatPrice(cartStore.totalAmount) }} so'm</span>
                </div>
                <div v-if="cartStore.totalDiscount > 0" class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>Chegirma</span><span class="text-red-500 font-medium"> -{{ formatPrice(cartStore.totalDiscount) }} so'm</span>
                </div>
                <div class="text-neutral-500 flex justify-between items-center text-sm md:text-base">
                  <span>Yetkazib berish narxi</span>
                  <span class="font-medium" :class="deliveryPrice > 0 ? 'text-neutral-900' : 'text-primary'">
                    {{ selectedDeliveryService ? (deliveryPrice > 0 ? formatPrice(deliveryPrice) + " so'm" : 'Bepul') : 'Hisoblanadi' }}
                  </span>
                </div>
              </div>

              <!-- Total -->
              <div class="flex justify-between items-center font-bold text-xl text-neutral-900">
                <p class="m-0">Jami</p><p class="m-0 text-primary">{{ formatPrice(cartStore.totalAmount - cartStore.totalDiscount + deliveryPrice) }} so'm</p>
              </div>
            </div>

            <!-- Final Submit Button -->
            <div
              class="p-4 sm:p-6 md:px-0 max-md:bg-white max-md:rounded-t-2xl"
              :style="isMobile ? { marginTop: 'auto' } : {}"
            >
              <div v-if="orderError" class="mb-3 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium text-center">
                {{ orderError }}
              </div>
              <button @click="submitOrder" type="button" :disabled="submitting" class="font-bold items-center justify-center transition-colors py-1.5 gap-2 text-white bg-primary hover:bg-primary/90 h-14 flex rounded-2xl text-base px-6 w-full cursor-pointer border-none shadow-sm disabled:opacity-60">
                {{ submitting ? "Yuborilmoqda..." : "To'lov sahifasiga o'tish" }}
                <svg v-if="!submitting" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <!-- MUHIM: bu yerda ham `<UModal>` (o'rnatilmagan `@nuxt/ui`) ishlatilgan
         edi — buyurtma muvaffaqiyatli qabul qilingandan keyingi eng muhim
         lahzada (checkout yakuni) tasdiqlash oynasi overlay sifatida emas,
         sahifa oxirida oddiy blok sifatida chizilib, foydalanuvchini
         chalg'itardi. AuthModal.vue'dagi bilan bir xil fixed-overlay
         naqshiga o'tkazildi. -->
    <div
      v-if="isSuccessOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl">
        <div class="p-8 text-center">
          <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4 text-green-500">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="text-2xl font-bold text-neutral-900 mb-2">Buyurtma qabul qilindi!</h3>
          <p class="text-sm text-neutral-600 mb-6">Tez orada operatorlarimiz siz bilan bog'lanadi.</p>
          <button @click="finishOrder" type="button" class="w-full px-4 py-3 rounded-xl bg-primary text-white hover:bg-primary/90 transition-colors font-bold border-none cursor-pointer">Tushunarli, Asosiyga qaytish</button>
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
        <div class="modal-sm-600 relative bg-white rounded-3xl overflow-hidden p-6 sm:p-8 w-full overflow-y-auto">
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

            <button type="submit" :disabled="!modalAddressSummary.isValid || savingModalAddress" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 md:h-14 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-sm">
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
            <button type="submit" :disabled="cardSaving" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 flex justify-center rounded-2xl text-base px-6 mt-6 border-none cursor-pointer disabled:opacity-75 shadow-sm">
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
            <button type="submit" :disabled="cardSaving" class="w-full font-bold items-center transition-colors gap-1.5 text-white bg-primary hover:bg-primary/90 h-12 flex justify-center rounded-2xl text-base px-6 mt-2 border-none cursor-pointer disabled:opacity-75 shadow-sm">
              {{ cardSaving ? "Tekshirilmoqda..." : "Tasdiqlash" }}
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

const config = useRuntimeConfig()
const cartStore = useCartStore()
const authStore = useAuthStore()
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
    return
  }
  if (form.paymentMethod === 'card' && !selectedCardId.value) {
    orderError.value = "Iltimos, karta tanlang yoki yangi karta qo'shing"
    return
  }

  submitting.value = true
  try {
    const addressOk = await ensureMainAddress()
    if (!addressOk) {
      orderError.value = "Yetkazib berish manzilini tanlang"
      return
    }

    if (deliveryServices.value.length === 0) {
      await fetchCheckoutInfo()
    }
    if (!selectedDeliveryServiceId.value) {
      orderError.value = "Bu manzil uchun yetkazib berish xizmati topilmadi"
      return
    }

    const selectedCartIds = await syncCartToServer()
    if (selectedCartIds.length === 0) {
      orderError.value = "Savatcha bo'sh"
      return
    }

    const orderRes: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/make`, {
      method: 'POST',
      // "X-Client-Platform: web" — boshqaruv (admin) panelida buyurtma
      // veb-saytdan yoki ilovadan tushganini ajratish uchun (backend:
      // PurchaseController::resolveOrderSource()).
      headers: { ...authHeaders.value, 'X-Client-Platform': 'web' },
      body: {
        paymentStatus: form.paymentMethod === 'cash' ? 0 : 1,
        deliveryservice_id: selectedDeliveryServiceId.value,
        selected_cart_ids: selectedCartIds,
      },
    })

    const orderId = orderRes?.order_id
    if (form.paymentMethod === 'card' && orderId) {
      await $fetch(`${config.public.apiBase}/v1/kitobchi/purchase/details/${orderId}/pay-with-card`, {
        method: 'POST',
        headers: authHeaders.value,
        body: { card_id: selectedCardId.value },
      })
    }

    isSuccessOpen.value = true
  } catch (e: any) {
    orderError.value = e?.data?.message || 'Buyurtma yaratishda xatolik yuz berdi'
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
