<template>
  <div class="min-h-dvh bg-[#f0f2f5] grow" v-if="product">
    <!-- ========================================================================= -->
    <!--  1. MOBIL KO'RINISH (Odnoklassniki / VK & Kitobchi uslubi - Screenshot 2)   -->
    <!-- ========================================================================= -->
    <div class="md:hidden">
      <!-- 1. ASOSIY MAHSULOT BLOKI (Galereya, Narx, Nasiya, Rating, AI) -->
      <div class="bg-white">
        <!-- 3:4 Galereya Karuseli (Edge-to-edge) -->
        <div class="relative w-full aspect-[3/4] bg-neutral-50/60 overflow-hidden flex items-center justify-center">
          <div role="region" aria-roledescription="carousel" tabindex="0" class="w-full h-full focus:outline-none">
            <div
              ref="mobileTrackEl"
              class="flex flex-row w-full h-full overflow-x-auto no-scrollbar snap-x snap-mandatory touch-pan-x"
              @scroll="onMobileTrackScroll"
            >
              <div
                v-for="(img, idx) in galleryImages"
                :key="'mob-img-' + idx"
                role="group"
                aria-roledescription="slide"
                class="min-w-full w-full shrink-0 basis-full flex h-full snap-center items-center justify-center bg-transparent p-3"
              >
                <img
                  :src="img"
                  :alt="product.name"
                  class="w-full h-full object-contain select-none"
                  :loading="idx === 0 ? 'eager' : 'lazy'"
                  draggable="false"
                />
              </div>
            </div>
          </div>

          <!-- Video Play Badge -->
          <button
            v-if="hasVideo"
            type="button"
            @click="isVideoOpen = true"
            class="absolute bottom-3 left-3 z-20 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-black/70 hover:bg-black/85 text-white backdrop-blur-xs text-xs font-semibold shadow-md transition-transform active:scale-95 border-none cursor-pointer"
          >
            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <span>Video sharh</span>
          </button>

          <!-- Dots Pill Indicator -->
          <div v-if="galleryImages.length > 1" class="absolute bottom-3 right-3 z-20 flex justify-end items-end pointer-events-none">
            <div class="flex items-center gap-1 rounded-full py-1 px-2 bg-white/90 shadow-2xs pointer-events-auto">
              <button
                v-for="(img, idx) in galleryImages"
                :key="'mob-dot-' + idx"
                type="button"
                @click.stop="goToMobileSlide(idx)"
                :aria-label="`carousel-dot-${idx}`"
                :class="[
                  'transition-all duration-300 border-none p-0 cursor-pointer',
                  idx === activeIndex ? 'w-5 h-1.5 bg-neutral-900 rounded-full' : 'w-1.5 h-1.5 bg-neutral-300 rounded-full'
                ]"
              ></button>
            </div>
          </div>

          <!-- Orqaga qaytish tugmasi -->
          <button
            type="button"
            @click.stop="$router.back()"
            aria-label="Orqaga"
            class="absolute top-3 left-3 z-30 border-none bg-transparent p-0 cursor-pointer"
          >
            <div class="rounded-full flex items-center justify-center bg-white/95 w-10 h-10 text-neutral-900 shadow-2xs hover:bg-neutral-100 transition-colors">
              <svg class="w-5 h-5 text-neutral-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </div>
          </button>

          <!-- Sevimli va Ulashish kapsulasi -->
          <div class="absolute top-3 right-3 z-30 flex items-center gap-2">
            <div class="rounded-full flex items-center gap-1 bg-white/95 py-1 px-1.5 shadow-2xs">
              <button
                type="button"
                @click.stop="favStore.toggleFavorite(product, 'book')"
                name="Favorite button"
                aria-label="Favorite button"
                class="w-8 h-8 flex items-center justify-center rounded-full transition-all duration-200 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer p-0"
              >
                <svg class="w-5 h-5 transition-colors duration-200" :class="isFav ? 'text-red-500 fill-red-500' : 'text-neutral-800'" viewBox="0 0 24 24" :fill="isFav ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.687-4.5-1.935 0-3.598 1.126-4.313 2.733-.715-1.607-2.377-2.733-4.312-2.733C5.098 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
              </button>
              <button
                type="button"
                @click.stop="shareProduct"
                name="share button"
                aria-label="share button"
                class="w-8 h-8 flex items-center justify-center rounded-full transition-all duration-200 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer p-0 text-neutral-800"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186a2.25 2.25 0 0 0-3.935-2.186m0-12.814a2.25 2.25 0 1 0 3.933-2.185a2.25 2.25 0 0 0-3.933 2.185"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Asosiy Ma'lumotlar Qismi -->
        <div class="px-4 pt-3.5 pb-4 space-y-3">
          <!-- Narx va Chegirma -->
          <div class="flex items-baseline gap-2.5">
            <span class="text-2xl font-black text-neutral-950 tracking-tight">{{ formatPrice(currentPrice) }} so'm</span>
            <span v-if="discountPercent > 0" class="text-sm line-through text-neutral-400 font-medium">{{ formatPrice(originalPrice) }} so'm</span>
            <span v-if="discountPercent > 0" class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-red-500 text-white">-{{ discountPercent }}%</span>
          </div>

          <!-- Nomi va Muallif -->
          <div class="space-y-1">
            <h1 class="text-lg font-bold text-neutral-900 leading-snug m-0">
              {{ product.name }}
            </h1>
            <div v-if="product.author" class="text-xs text-neutral-500">
              Muallif: <span class="font-semibold text-neutral-800">{{ product.author }}</span>
            </div>
          </div>

          <!-- Teglar -->
          <div v-if="productTags.length > 0" class="flex flex-wrap items-center gap-1.5 pt-0.5">
            <span
              v-for="(tag, tidx) in productTags"
              :key="tag"
              class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border-none"
              :class="getTagColorClass(tidx)"
            >
              # {{ tag }}
            </span>
          </div>

          <!-- DO'KONLAR VA TAKLIFLAR BLOKI (Katalog asosida bir nechta do'konlar) -->
          <div v-if="rawOffers.length > 1" class="pt-1 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-neutral-900">Do'konlar ({{ rawOffers.length }} ta taklif)</span>
              <button
                v-if="rawOffers.length > 3"
                type="button"
                @click="isAllOffersOpen = true"
                class="text-xs font-semibold text-primary border-none bg-transparent cursor-pointer p-0 hover:underline"
              >
                Barchasi ({{ rawOffers.length }}) ›
              </button>
            </div>

            <!-- Top 3 Do'konlar ro'yxati -->
            <div class="space-y-1.5">
              <button
                v-for="offer in displayedOffers"
                :key="'mob-offer-' + offer.id"
                type="button"
                @click="selectOffer(offer.id)"
                class="w-full text-left p-2.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-2.5"
                :class="offer.id === activeOffer?.id ? 'border-primary bg-blue-50/50 ring-1 ring-primary/30' : 'border-neutral-200/80 bg-neutral-50/50 hover:bg-neutral-100/60'"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                    :class="offer.id === activeOffer?.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'"
                  >
                    <span v-if="offer.id === activeOffer?.id" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                  </div>
                  <div class="min-w-0">
                    <div class="text-xs font-bold text-neutral-900 truncate flex items-center gap-1">
                      <span>{{ offer.seller?.shop_name || 'Kitobchi do\'koni' }}</span>
                      <svg v-if="offer.seller?.isVerified" class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div v-if="offer.seller?.rating" class="text-[10px] text-neutral-500">
                      ★ {{ Number(offer.seller.rating).toFixed(1) }}
                    </div>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <div class="text-xs font-black text-neutral-900">
                    {{ formatPrice(offer.effective_price || offer.discountPrice || offer.price) }} so'm
                  </div>
                  <div v-if="!offer.in_stock || offer.stock <= 0" class="text-[10px] text-rose-500 font-semibold">
                    Qolmagan
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Yagona do'kon bo'lsa (Sotuvda mavjud yozuvsiz) -->
          <div v-else-if="activeSeller?.shop_name" class="pt-0.5">
            <span class="inline-flex items-center text-xs font-medium rounded-lg px-2.5 py-1 bg-blue-50 text-primary gap-1">
              <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
              {{ activeSeller.shop_name }}
            </span>
          </div>

          <!-- Haqiqiy Split (faqat backend split tizimi ruxsat bergan va rejalar mavjud bo'lganda) -->
          <div
            v-if="isSplitAvailable && selectedSplitPlan"
            @click="isSplitModalOpen = true"
            class="bg-neutral-50 hover:bg-neutral-100/70 rounded-2xl p-3 border border-neutral-100 flex items-center justify-between gap-3 cursor-pointer transition-colors"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-8 h-8 rounded-xl bg-blue-100/70 text-primary flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 6v6l4 2" />
                </svg>
              </div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-neutral-900 truncate">
                  {{ selectedSplitPlan.installments_count }} × {{ formatPrice(selectedSplitPlan.first_payment) }} so'm
                </div>
                <div class="text-[11px] text-neutral-400">
                  {{ selectedSplitPlan.monthly_interest_percent === 0 ? "0% foizsiz bo'lib to'lash" : `${selectedSplitPlan.months} oyga bo'lib to'lash` }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              <span class="text-xs font-semibold text-primary">Rejalar ›</span>
            </div>
          </div>

          <!-- Reyting va AI Tahlili Qisqacha (Bitta qatorda aqlli joylashuv) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
            <!-- Reyting -->
            <div class="bg-neutral-50 rounded-2xl p-2.5 border border-neutral-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-sm font-extrabold text-neutral-900">{{ productRating || '5.0' }}</span>
                <div class="flex items-center text-amber-400">
                  <svg v-for="s in 5" :key="s" class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </div>
              <span class="text-[11px] text-neutral-500 font-medium">{{ reviewsCount }} ta sharh</span>
            </div>

            <!-- AI Tahlili: Auth bo'lganlarga moslik ko'rsatiladi, Guestlarga example data bilan modal -->
            <div
              @click="isAiMatchOpen = true"
              class="bg-white hover:bg-neutral-50 rounded-2xl p-2.5 border border-neutral-100 flex items-center justify-between cursor-pointer transition-colors group"
            >
              <div class="flex items-center gap-2 min-w-0">
                <div class="relative w-7 h-7 flex items-center justify-center shrink-0">
                  <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 36 36">
                    <path class="text-blue-100" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="text-primary" stroke-dasharray="100, 100" :stroke-dashoffset="100 - aiMatchPercent" stroke-width="4" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                  </svg>
                  <span class="absolute text-[8px] font-black text-primary">{{ aiMatchPercent }}%</span>
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-bold text-neutral-900 group-hover:text-primary truncate">
                    {{ aiMatchPercent }}% mos — siz uchun
                  </div>
                  <div class="text-[10px] text-neutral-500 truncate">
                    {{ aiSummarySubtitle }}
                  </div>
                </div>
              </div>
              <svg class="w-3.5 h-3.5 text-neutral-400 group-hover:text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Oraliq bo'shliq (Edge-to-edge ajratuvchi chiziq) -->
      <div class="h-2 bg-[#f0f2f5] w-full"></div>

      <!-- 2. TAVSIF VA XUSUSIYATLAR BLOKI (Bitta yaxlit bo'lim, ortiqcha kapsulalarsiz) -->
      <div class="bg-white px-4 py-4 space-y-4">
        <!-- Mahsulot tavsifi -->
        <div v-if="product.description" class="space-y-2">
          <h2 class="text-sm font-bold text-neutral-900 m-0">Mahsulot haqida</h2>
          <div :class="['text-xs text-neutral-600 leading-relaxed m-0', !isDescExpanded ? 'line-clamp-3' : '']" v-html="product.description"></div>
          <button
            type="button"
            @click="isDescExpanded = !isDescExpanded"
            class="inline-flex items-center gap-1 text-xs font-semibold text-primary border-none bg-transparent cursor-pointer p-0"
          >
            {{ isDescExpanded ? 'Yopish' : 'Batafsil o‘qish' }}
            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="isDescExpanded ? '-rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
          </button>
        </div>

        <!-- Xususiyatlar jadvali -->
        <div v-if="hasSpecs" class="space-y-2 pt-2 border-t border-neutral-100">
          <h3 class="text-sm font-bold text-neutral-900 m-0">Xususiyatlar va parametrlar</h3>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div v-if="product.publisher" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">Nashriyot</span>
              <span class="font-semibold text-neutral-800 truncate">{{ product.publisher.name || product.publisher }}</span>
            </div>
            <div v-if="product.pages" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">Sahifalar soni</span>
              <span class="font-semibold text-neutral-800">{{ product.pages }} bet</span>
            </div>
            <div v-if="product.coverType" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">Muqova</span>
              <span class="font-semibold text-neutral-800">{{ coverTypeLabel(String(product.coverType)) }}</span>
            </div>
            <div v-if="product.lang" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">Til</span>
              <span class="font-semibold text-neutral-800">{{ bookLangLabel(String(product.lang)) }}</span>
            </div>
            <div v-if="product.year" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">Nashr yili</span>
              <span class="font-semibold text-neutral-800">{{ product.year }}</span>
            </div>
            <div v-if="product.isbn" class="p-2.5 rounded-xl bg-neutral-50 flex flex-col justify-center">
              <span class="text-[11px] text-neutral-400">ISBN</span>
              <span class="font-semibold text-neutral-800 truncate">{{ product.isbn }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Oraliq bo'shliq -->
      <div class="h-2 bg-[#f0f2f5] w-full"></div>

      <!-- 3. SHARHLAR VA KITOBXONLAR FIKRI (Yaxlit bo'lim) -->
      <div class="bg-white px-4 py-4 pb-36 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-neutral-900 m-0">Kitobxonlar sharhlari</h3>
          <button
            v-if="reviews.length > 0"
            type="button"
            @click="openReviewsModal"
            class="text-xs font-semibold text-primary border-none bg-transparent cursor-pointer p-0"
          >
            Barchasi ({{ reviewsCount }})
          </button>
        </div>

        <div v-if="reviews.length === 0" class="py-6 text-center text-neutral-400">
          <svg class="w-8 h-8 mx-auto mb-2 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
          </svg>
          <p class="text-xs font-medium text-neutral-600 m-0">Ushbu kitobga hali sharh yozilmagan</p>
          <p class="text-[11px] text-neutral-400 m-0 mt-0.5">Birinchi bo'lib fikr qoldiring</p>
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="r in displayReviews"
            :key="r.id"
            class="p-3 rounded-xl bg-neutral-50/80 space-y-1.5"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center">
                  {{ (r.user_name || 'K').charAt(0).toUpperCase() }}
                </div>
                <span class="text-xs font-semibold text-neutral-900">{{ r.user_name || 'Kitobxon' }}</span>
              </div>
              <div class="flex items-center text-amber-400">
                <svg v-for="s in (r.rating || 5)" :key="s" class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
            <p class="text-xs text-neutral-600 line-clamp-2 m-0">{{ r.comment }}</p>
          </div>
        </div>
      </div>

      <!-- Mobil Pastki Suzuvchi Xarid Paneli (Full-width, edge-to-edge) -->
      <div
        class="w-full bg-white fixed bottom-0 left-0 z-60 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] border-t border-neutral-100"
        style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 1.25rem))"
      >
        <div class="px-4 py-3 flex items-center gap-3">
          <!-- Stepper: Trash or Minus / Count / Plus -->
          <div class="flex items-center bg-neutral-100 rounded-2xl p-1 gap-1 h-12">
            <button
              type="button"
              @click="decrementQuantity"
              class="w-9 h-10 rounded-xl bg-white shadow-xs flex items-center justify-center text-neutral-700 hover:text-red-500 transition-colors border-none cursor-pointer"
              aria-label="Kamaytirish"
            >
              <svg v-if="orderCount === 1" class="w-4 h-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
              </svg>
              <svg v-else class="w-4 h-4 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
              </svg>
            </button>
            <span class="w-8 text-center font-bold text-sm text-neutral-900">{{ orderCount }}</span>
            <button
              type="button"
              @click="incrementQuantity"
              class="w-9 h-10 rounded-xl bg-white shadow-xs flex items-center justify-center text-neutral-700 hover:text-primary transition-colors border-none cursor-pointer"
              aria-label="Ko'paytirish"
            >
              <svg class="w-4 h-4 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
              </svg>
            </button>
          </div>

          <!-- Asosiy Savatcha Tugmasi -->
          <button
            type="button"
            @click="handleAddToCart"
            class="flex-1 h-12 rounded-2xl font-bold text-base flex items-center justify-center gap-2 shadow-md transition-all border-none cursor-pointer"
            :class="[
              !isInStock
                ? 'bg-neutral-200 text-neutral-500 opacity-90 cursor-not-allowed shadow-none'
                : 'bg-brand-gradient text-white hover:opacity-95 active:scale-95'
            ]"
          >
            <span v-if="!isInStock">Sotuvda qolmagan</span>
            <template v-else>
              <span>{{ isAdded ? 'Savatga o‘tish' : 'Savatcha' }}</span>
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
              </svg>
            </template>
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!--  2. DESKTOP (PC) KO'RINISH (Odnoklassniki / VK & Kitobchi uslubi)          -->
    <!-- ========================================================================= -->
    <div class="max-md:hidden min-h-dvh">
      <div class="py-6 px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-5">
          <ol class="flex items-center gap-2 text-xs text-neutral-500 font-medium">
            <li><NuxtLink to="/" class="hover:text-primary transition-colors">Asosiy</NuxtLink></li>
            <li class="text-neutral-300">/</li>
            <template v-if="product.category || product.category_id">
              <li>
                <NuxtLink :to="`/category/book-${product.category_id || product.category?.id}`" class="hover:text-primary transition-colors">
                  {{ productCategoryTitle }}
                </NuxtLink>
              </li>
              <li class="text-neutral-300">/</li>
            </template>
            <li><span class="text-neutral-900 font-semibold truncate inline-block max-w-sm align-bottom">{{ product.name }}</span></li>
          </ol>
        </nav>

        <!-- Asosiy 2 Ustunli Grid (Galereya chapda, Ma'lumot va Side o'ngda) -->
        <div class="grid grid-cols-12 gap-8 items-start">
          <!-- CHAP USTUN: BIR HIL HAJMDAGI DESKTOP GALEREYA (7 ustun) -->
          <div class="col-span-12 lg:col-span-7 flex flex-col gap-4">
            <div class="bg-white p-6 rounded-3xl shadow-2xs flex flex-col md:flex-row gap-5 items-stretch">
              <!-- Chap vertikal thumbnails (75x100px bir xil hajm) -->
              <div
                v-if="galleryImages.length > 1 || hasVideo"
                class="flex md:flex-col gap-3 overflow-x-auto md:overflow-y-auto no-scrollbar w-full md:w-20 shrink-0 select-none"
                style="max-height: 490px;"
              >
                <!-- Agar video bo'lsa, maxsus video thumbnail -->
                <button
                  v-if="hasVideo"
                  type="button"
                  @click="isVideoOpen = true"
                  title="Video sharhni ko'rish"
                  class="relative shrink-0 rounded-2xl overflow-hidden bg-neutral-900 text-white w-18 h-24 flex flex-col items-center justify-center gap-1 border-none cursor-pointer transition-transform hover:scale-105"
                >
                  <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  </div>
                  <span class="text-[10px] font-bold">Video</span>
                </button>

                <!-- Rasm thumbnails -->
                <button
                  v-for="(img, idx) in galleryImages"
                  :key="'desk-thumb-' + idx"
                  type="button"
                  @click="goToSlide(idx)"
                  :aria-label="`Rasm ${idx + 1}`"
                  :class="[
                    'relative shrink-0 rounded-2xl overflow-hidden p-1.5 flex items-center justify-center w-18 h-24 transition-all duration-200 cursor-pointer border-none bg-neutral-50/80',
                    activeIndex === idx ? 'ring-2 ring-primary ring-offset-2' : 'hover:bg-neutral-100 opacity-80 hover:opacity-100'
                  ]"
                >
                  <img :src="img" :alt="`${product.name} ${idx + 1}`" class="w-full h-full object-contain pointer-events-none" />
                </button>
              </div>

              <!-- Asosiy Bir Xil Hajmdagi Rasm Maydoni (Uniform 3:4 aspect, jumping yo'q) -->
              <div class="relative flex-1 rounded-2xl bg-neutral-50/60 flex items-center justify-center overflow-hidden aspect-[3/4] max-h-[490px] group select-none">
                <img
                  :src="activeImage"
                  :alt="product.name"
                  class="w-full h-full object-contain p-4 transition-opacity duration-300"
                  loading="eager"
                  draggable="false"
                />

                <!-- Video ochish tugmasi (Cover ustida suzuvchi) -->
                <button
                  v-if="hasVideo"
                  type="button"
                  @click="isVideoOpen = true"
                  class="absolute top-4 left-4 z-20 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/70 hover:bg-black/85 text-white backdrop-blur-xs text-xs font-semibold shadow-md transition-all hover:scale-105 border-none cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  <span>Videoni ko'rish</span>
                </button>

                <!-- Sevimlilar tugmasi (yuqori o'ng) -->
                <button
                  type="button"
                  @click="favStore.toggleFavorite(product, 'book')"
                  name="Favorite button"
                  aria-label="Sevimlilar"
                  class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-neutral-700 flex items-center justify-center shadow-2xs hover:scale-105 active:scale-95 transition-all border-none cursor-pointer"
                >
                  <svg class="w-5 h-5 transition-colors" viewBox="0 0 24 24" :fill="isFav ? '#ef4444' : 'none'" :stroke="isFav ? '#ef4444' : 'currentColor'" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                  </svg>
                </button>

                <!-- Oldingi / Keyingi strelkalar (Bemalol o'tkazish) -->
                <template v-if="galleryImages.length > 1">
                  <button
                    type="button"
                    @click="prevImage"
                    aria-label="Oldingi rasm"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 hover:bg-white text-neutral-800 flex items-center justify-center shadow-md transition-all opacity-0 group-hover:opacity-100 cursor-pointer border-none"
                  >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                  </button>
                  <button
                    type="button"
                    @click="nextImage"
                    aria-label="Keyingi rasm"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 hover:bg-white text-neutral-800 flex items-center justify-center shadow-md transition-all opacity-0 group-hover:opacity-100 cursor-pointer border-none"
                  >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                  </button>
                </template>

                <!-- Rasm hisoblagichi (1 / 4) -->
                <div v-if="galleryImages.length > 1" class="absolute bottom-3 right-3 px-2.5 py-1 rounded-full bg-black/60 text-white text-[11px] font-semibold backdrop-blur-xs pointer-events-none">
                  {{ activeIndex + 1 }} / {{ galleryImages.length }}
                </div>
              </div>
            </div>

            <!-- Mahsulot Tavsifi (VK Uslubidagi Karta) -->
            <div class="bg-white p-6 rounded-3xl shadow-2xs space-y-3">
              <h2 class="text-lg font-bold text-neutral-900 m-0">Mahsulot haqida</h2>
              <div
                class="kb-prose text-sm text-neutral-700 leading-relaxed transition-all"
                :class="isDescExpanded ? '' : 'line-clamp-4'"
                v-html="product.description || 'Mahsulot haqida ma\'lumot tez orada joylanadi.'"
              ></div>
              <div class="flex justify-start pt-1">
                <button
                  type="button"
                  @click="isDescExpanded = !isDescExpanded"
                  class="text-xs font-semibold text-primary hover:underline border-none bg-transparent cursor-pointer p-0"
                >
                  {{ isDescExpanded ? 'Kamroq ko‘rsatish' : 'Batafsil ko‘rib chiqing' }}
                </button>
              </div>
            </div>

            <!-- Xususiyatlar va Tavsiflar Jadvali -->
            <div v-if="hasSpecs" class="bg-white p-6 rounded-3xl shadow-2xs space-y-3">
              <h2 class="text-lg font-bold text-neutral-900 m-0">Xususiyatlar</h2>
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div v-if="product.publisher" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">Nashriyot:</span>
                  <span class="font-semibold text-neutral-800">{{ product.publisher.name || product.publisher }}</span>
                </div>
                <div v-if="product.pages" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">Sahifalar soni:</span>
                  <span class="font-semibold text-neutral-800">{{ product.pages }} bet</span>
                </div>
                <div v-if="product.coverType" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">Muqova:</span>
                  <span class="font-semibold text-neutral-800">{{ coverTypeLabel(String(product.coverType)) }}</span>
                </div>
                <div v-if="product.lang" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">Til:</span>
                  <span class="font-semibold text-neutral-800">{{ bookLangLabel(String(product.lang)) }}</span>
                </div>
                <div v-if="product.year" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">Yil:</span>
                  <span class="font-semibold text-neutral-800">{{ product.year }}</span>
                </div>
                <div v-if="product.isbn" class="p-3 rounded-2xl bg-neutral-50/80">
                  <span class="text-neutral-400 block text-xs">ISBN:</span>
                  <span class="font-semibold text-neutral-800">{{ product.isbn }}</span>
                </div>
              </div>
            </div>

            <!-- Sharhlar Bo'limi (VK & BookClub Uslubi) -->
            <div class="bg-white p-6 rounded-3xl shadow-2xs space-y-5">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-lg font-bold text-neutral-900 m-0">Kitobxonlar sharhlari</h2>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm font-bold text-neutral-900">{{ productRating || '5.0' }}</span>
                    <div class="flex items-center text-amber-400">
                      <svg v-for="s in 5" :key="s" class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    </div>
                    <span class="text-xs text-neutral-400">({{ reviewsCount }} ta sharh)</span>
                  </div>
                </div>

                <NuxtLink
                  to="/club"
                  class="px-4 py-2 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-xs font-semibold text-neutral-800 transition-colors no-underline"
                >
                  Klubda muhokama qilish
                </NuxtLink>
              </div>

              <!-- Reviews List or Empty State -->
              <div v-if="reviews.length === 0" class="py-8 text-center text-neutral-400 rounded-2xl bg-neutral-50/60">
                <p class="text-sm font-semibold text-neutral-700 m-0">Hozircha sharhlar yo'q</p>
                <p class="text-xs text-neutral-400 mt-1 max-w-xs mx-auto m-0">Ushbu asarni o'qigan bo'lsangiz, birinchi bo'lib fikr qoldiring</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="rev in reviews.slice(0, 4)"
                  :key="rev.id"
                  class="p-4 rounded-2xl bg-neutral-50/70 space-y-2"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center">
                        {{ (rev.user_name || 'K').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="text-xs font-bold text-neutral-900 leading-tight">{{ rev.user_name || 'Kitobxon' }}</div>
                        <div class="text-[10px] text-neutral-400">{{ rev.created_at || 'Yaqinda' }}</div>
                      </div>
                    </div>
                    <div class="flex items-center text-amber-400">
                      <svg v-for="s in (rev.rating || 5)" :key="s" class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    </div>
                  </div>
                  <p class="text-xs text-neutral-700 leading-relaxed m-0">{{ rev.comment }}</p>
                </div>

                <div v-if="reviews.length > 4" class="text-center pt-2">
                  <button
                    type="button"
                    @click="openReviewsModal"
                    class="px-5 py-2 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-xs font-semibold text-neutral-800 transition-colors border-none cursor-pointer"
                  >
                    Barcha sharhlarni ko'rish ({{ reviewsCount }})
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- O'NG USTUN: SIDEBAR / XARID PANELI (5 ustun - VK / Odnoklassniki uslubi) -->
          <div class="col-span-12 lg:col-span-5 sticky top-20 space-y-4">
            <!-- 1. Asosiy Xarid Kartasi -->
            <div class="bg-white p-6 rounded-3xl shadow-2xs space-y-4">
              <!-- Sarlavha (Book.Uz va Sotuvda mavjud row olib tashlandi) -->
              <div>
                <h1 class="text-xl font-bold text-neutral-900 leading-snug m-0">
                  {{ product.name }}
                </h1>
                <div v-if="product.author" class="text-xs text-neutral-500 mt-1">
                  Muallif: <span class="font-semibold text-neutral-800">{{ product.author }}</span>
                </div>
              </div>

              <!-- Hashtaglar -->
              <div v-if="productTags.length > 0" class="flex flex-wrap items-center gap-1.5">
                <span
                  v-for="(tag, tidx) in productTags"
                  :key="tag"
                  class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border-none"
                  :class="getTagColorClass(tidx)"
                >
                  # {{ tag }}
                </span>
              </div>

              <!-- DO'KONLAR VA TAKLIFLAR BLOKI (Katalog asosida ko'p do'konlar) -->
              <div v-if="rawOffers.length > 1" class="space-y-2 pt-1 border-t border-neutral-100">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-neutral-900">Do'konlar ({{ rawOffers.length }} ta taklif)</span>
                  <button
                    v-if="rawOffers.length > 3"
                    type="button"
                    @click="isAllOffersOpen = true"
                    class="text-xs font-semibold text-primary border-none bg-transparent cursor-pointer p-0 hover:underline"
                  >
                    Barchasi ({{ rawOffers.length }}) ›
                  </button>
                </div>

                <!-- Top 3 Do'konlar ro'yxati -->
                <div class="space-y-1.5">
                  <button
                    v-for="offer in displayedOffers"
                    :key="'pc-offer-' + offer.id"
                    type="button"
                    @click="selectOffer(offer.id)"
                    class="w-full text-left p-2.5 rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-2.5"
                    :class="offer.id === activeOffer?.id ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-neutral-200/80 bg-neutral-50/50 hover:bg-neutral-100/60'"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div
                        class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                        :class="offer.id === activeOffer?.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'"
                      >
                        <span v-if="offer.id === activeOffer?.id" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                      </div>
                      <div class="min-w-0">
                        <div class="text-xs font-bold text-neutral-900 truncate flex items-center gap-1">
                          <span>{{ offer.seller?.shop_name || 'Kitobchi do\'koni' }}</span>
                          <svg v-if="offer.seller?.isVerified" class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                          </svg>
                        </div>
                        <div v-if="offer.seller?.rating" class="text-[10px] text-neutral-500">
                          ★ {{ Number(offer.seller.rating).toFixed(1) }} reyting
                        </div>
                      </div>
                    </div>
                    <div class="text-right shrink-0">
                      <div class="text-xs font-black text-neutral-900">
                        {{ formatPrice(offer.effective_price || offer.discountPrice || offer.price) }} so'm
                      </div>
                      <div v-if="!offer.in_stock || offer.stock <= 0" class="text-[10px] text-rose-500 font-semibold">
                        Qolmagan
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Yagona do'kon bo'lsa (Sotuvda mavjud yozuvsiz) -->
              <div v-else-if="activeSeller?.shop_name" class="pt-0.5">
                <span class="inline-flex items-center text-xs font-medium rounded-lg px-2.5 py-1 bg-blue-50 text-primary gap-1">
                  <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                  {{ activeSeller.shop_name }}
                </span>
              </div>

              <!-- Narx va chegirma -->
              <div class="p-4 rounded-2xl bg-neutral-50/80 space-y-1">
                <div class="text-[11px] text-neutral-400 uppercase font-semibold">Narxi</div>
                <div class="flex items-baseline gap-2.5">
                  <span class="text-2xl font-black text-neutral-950">{{ formatPrice(currentPrice) }} so'm</span>
                  <span v-if="discountPercent > 0" class="text-sm line-through text-neutral-400 font-medium">{{ formatPrice(originalPrice) }} so'm</span>
                  <span v-if="discountPercent > 0" class="text-xs font-bold px-2 py-0.5 rounded-md bg-red-500 text-white">-{{ discountPercent }}%</span>
                </div>
              </div>

              <!-- Haqiqiy Split (faqat mavjud bo'lsa) -->
              <div
                v-if="isSplitAvailable && selectedSplitPlan"
                @click="isSplitModalOpen = true"
                class="p-3.5 rounded-2xl bg-neutral-50/60 hover:bg-neutral-100/70 border border-neutral-100 space-y-1 cursor-pointer transition-colors"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <span class="text-[11px] text-neutral-400 font-medium block">
                      {{ selectedSplitPlan.monthly_interest_percent === 0 ? "0% foizsiz bo'lib to'lash" : "Bo'lib to'lash" }}
                    </span>
                    <span class="text-base font-bold text-neutral-900">
                      {{ selectedSplitPlan.installments_count }} × {{ formatPrice(selectedSplitPlan.first_payment) }} so'm
                    </span>
                  </div>
                  <span class="text-xs font-bold text-primary">Rejalarni ko'rish ›</span>
                </div>
              </div>

              <!-- Soni tanlash va Brend Sotib Olish Tugmalari -->
              <div class="space-y-2.5 pt-1">
                <div class="flex items-center gap-3">
                  <!-- Stepper -->
                  <div class="flex items-center bg-neutral-100 rounded-2xl p-1 gap-1 h-12 shrink-0">
                    <button
                      type="button"
                      @click="decrementQuantity"
                      class="w-10 h-10 rounded-xl bg-white shadow-xs flex items-center justify-center text-neutral-700 hover:text-red-500 transition-colors border-none cursor-pointer"
                      aria-label="Kamaytirish"
                    >
                      <svg v-if="orderCount === 1" class="w-4 h-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                      </svg>
                      <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>
                    <span class="w-8 text-center font-bold text-sm text-neutral-900">{{ orderCount }}</span>
                    <button
                      type="button"
                      @click="incrementQuantity"
                      class="w-10 h-10 rounded-xl bg-white shadow-xs flex items-center justify-center text-neutral-700 hover:text-primary transition-colors border-none cursor-pointer"
                      aria-label="Ko'paytirish"
                    >
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                  </div>

                  <!-- Sotib Olish (Brend Gradient Tugmasi) -->
                  <button
                    type="button"
                    @click="handleBuyNow"
                    class="flex-1 h-12 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-md transition-all border-none cursor-pointer"
                    :class="[
                      !isInStock
                        ? 'bg-neutral-200 text-neutral-500 opacity-90 cursor-not-allowed shadow-none'
                        : 'bg-brand-gradient text-white hover:opacity-95 active:scale-[0.98]'
                    ]"
                  >
                    <span v-if="!isInStock">Sotuvda qolmagan</span>
                    <template v-else>
                      <span>Hoziroq sotib olish</span>
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </template>
                  </button>
                </div>

                <!-- Savatga qo'shish / Savatga o'tish tugmasi -->
                <button
                  type="button"
                  @click="handleAddToCart"
                  class="w-full h-11 rounded-2xl font-semibold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer border-none"
                  :class="[
                    !isInStock
                      ? 'bg-neutral-100 text-neutral-400 cursor-not-allowed'
                      : (isAdded ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-neutral-100 hover:bg-neutral-200 text-neutral-800')
                  ]"
                >
                  <template v-if="!isInStock">
                    <span>Tanlangan do'konda qolmagan</span>
                  </template>
                  <template v-else>
                    <svg v-if="!isAdded" class="w-4 h-4 fill-current text-neutral-600" viewBox="0 0 20 20">
                      <path d="M3 1a1 1 0 0 0 0 2h1.22l.305 1.222l.01.042l1.358 5.43l-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 0 0 0-2H6.414l1-1H14a1 1 0 0 0 .894-.553l3-6A1 1 0 0 0 17 3H6.28l-.31-1.243A1 1 0 0 0 5 1zm13 15.5a1.5 1.5 0 1 1-3 0a1.5 1.5 0 0 1 3 0M6.5 18a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3"/>
                    </svg>
                    <svg v-else class="w-4 h-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ isAdded ? 'Savatda mavjud (Ko\'rish)' : 'Savatga qo‘shish' }}</span>
                  </template>
                </button>
              </div>
            </div>

            <!-- 2. AI Tahlil Kartasi (Desktop) — Auth va Guest uchun bir xil, modal ochadi -->
            <div
              @click="isAiMatchOpen = true"
              class="bg-white hover:bg-blue-50/30 p-4 rounded-3xl shadow-2xs flex items-center justify-between cursor-pointer transition-colors border-none group"
            >
              <div class="flex items-center gap-3">
                <div class="relative w-11 h-11 flex items-center justify-center shrink-0">
                  <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 36 36">
                    <path
                      class="text-blue-100"
                      stroke-width="3.5"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-primary transition-all duration-700"
                      stroke-dasharray="100, 100"
                      :stroke-dashoffset="100 - aiMatchPercent"
                      stroke-width="3.5"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <span class="absolute text-[11px] font-black text-primary">{{ aiMatchPercent }}%</span>
                </div>
                <div>
                  <div class="text-xs font-bold text-neutral-900 group-hover:text-primary transition-colors flex items-center gap-1.5">
                    <span>{{ aiMatchPercent }}% mos — siz uchun</span>
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                  </div>
                  <div class="text-[11px] text-neutral-500">
                    {{ aiDesktopSubtitle }}
                  </div>
                </div>
              </div>
              <svg class="w-4 h-4 text-neutral-400 group-hover:text-primary transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
              </svg>
            </div>

            <!-- 3. Ishonch Bloklari (14 kun almashtirish olib tashlandi) -->
            <div class="bg-white p-5 rounded-3xl shadow-2xs space-y-3 text-xs">
              <div class="flex items-center gap-3 text-neutral-700">
                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                </div>
                <div>
                  <div class="font-bold text-neutral-900">100% asl nusxa kafolati</div>
                  <div class="text-[11px] text-neutral-400">Faqat rasmiy nashriyotlardan</div>
                </div>
              </div>

              <div class="flex items-center gap-3 text-neutral-700">
                <div class="w-8 h-8 rounded-full bg-blue-50 text-primary flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V4.5a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v10.5"/></svg>
                </div>
                <div>
                  <div class="font-bold text-neutral-900">Tezkor yetkazib berish</div>
                  <div class="text-[11px] text-neutral-400">Toshkentda 24 soat, viloyatlarga 2-3 kun</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- O'xshash Mahsulotlar (Piyola & Kitobchi 1:1) -->
        <section v-if="similarProducts.length > 0" class="mt-12 pb-12">
          <div class="flex justify-between items-center w-full px-1 mb-5">
            <h2 class="font-extrabold text-2xl text-neutral-900 m-0">
              O‘xshash kitoblar
            </h2>
            <NuxtLink
              v-if="product?.category_id"
              :to="`/category/book-${product.category_id}`"
              class="text-xs font-bold text-primary hover:underline flex items-center gap-1 shrink-0"
            >
              Barchasini ko'rish
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </NuxtLink>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <ProductCard
              v-for="sp in similarProducts"
              :key="'similar-' + sp.id"
              :product="sp"
              type="book"
            />
          </div>
        </section>
      </div>
    </div>

    <!-- ====== 3. MODALLAR ====== -->
    <!-- AI Tahlil Modali / Bottom Sheet (Screenshot 3) -->
    <AiMatchModal
      v-model="isAiMatchOpen"
      :product="product"
      :similar-products="similarProducts"
    />

    <!-- Mahsulot Videosi Modali (HD / SD Player) -->
    <ProductVideoModal
      v-model="isVideoOpen"
      :product="product"
    />

    <!-- Sharhlar Modali / Drawer -->
    <Teleport to="body">
      <div
        v-if="isReviewsOpen"
        class="fixed inset-0 z-[110] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="isReviewsOpen = false"
      >
        <div class="max-h-[85vh] bg-white rounded-3xl w-full max-w-lg p-6 shadow-2xl flex flex-col border-none">
          <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-bold text-neutral-900 m-0">Mijozlar sharhlari</h3>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-50 text-primary font-semibold">{{ reviewsCount }}</span>
            </div>
            <button
              type="button"
              @click="isReviewsOpen = false"
              class="p-1.5 rounded-full hover:bg-neutral-100 text-neutral-400 border-none bg-transparent cursor-pointer"
              aria-label="Yopish"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="overflow-y-auto py-4 flex-1 space-y-3 no-scrollbar">
            <div v-if="reviews.length === 0" class="py-12 text-center text-neutral-400">
              <p class="text-sm font-semibold text-neutral-700 m-0">Hozircha sharhlar mavjud emas</p>
              <p class="text-xs text-neutral-400 max-w-xs mx-auto mt-1 m-0">Ushbu kitob haqida birinchi bo'lib fikringizni bildiring.</p>
            </div>
            <div v-else v-for="rev in reviews" :key="rev.id" class="p-4 rounded-2xl bg-neutral-50/80 space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center">
                    {{ (rev.user_name || 'K').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <span class="text-sm font-semibold text-neutral-900 block">{{ rev.user_name || 'Kitobxon' }}</span>
                    <span class="text-[11px] text-neutral-400">{{ rev.created_at || 'Yaqinda' }}</span>
                  </div>
                </div>
                <div class="flex items-center gap-0.5 text-amber-400">
                  <svg v-for="s in 5" :key="s" class="w-4 h-4" :class="s <= rev.rating ? 'fill-amber-400' : 'fill-neutral-200 text-neutral-200'" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </div>
              <p class="text-xs text-neutral-700 m-0 leading-relaxed">{{ rev.comment }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Barcha do'konlar takliflari modali -->
    <Teleport to="body">
      <div
        v-if="isAllOffersOpen"
        class="fixed inset-0 z-[120] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="isAllOffersOpen = false"
      >
        <div class="max-h-[85vh] bg-white rounded-3xl w-full max-w-lg p-6 shadow-2xl flex flex-col border-none">
          <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-bold text-neutral-900 m-0">Barcha do'konlar</h3>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-50 text-primary font-semibold">{{ rawOffers.length }} ta taklif</span>
            </div>
            <button
              type="button"
              @click="isAllOffersOpen = false"
              class="p-1.5 rounded-full hover:bg-neutral-100 text-neutral-400 border-none bg-transparent cursor-pointer"
              aria-label="Yopish"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="overflow-y-auto py-4 flex-1 space-y-2 no-scrollbar">
            <div
              v-for="offer in rawOffers"
              :key="'all-offer-' + offer.id"
              @click="selectOffer(offer.id)"
              class="p-3.5 rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-3"
              :class="offer.id === activeOffer?.id ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-neutral-200/80 bg-neutral-50/50 hover:bg-neutral-100/60'"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                  :class="offer.id === activeOffer?.id ? 'border-primary bg-primary' : 'border-neutral-300 bg-white'"
                >
                  <span v-if="offer.id === activeOffer?.id" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-neutral-900 truncate flex items-center gap-1.5">
                    <span>{{ offer.seller?.shop_name || 'Kitobchi do\'koni' }}</span>
                    <svg v-if="offer.seller?.isVerified" class="w-4 h-4 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <div class="flex items-center gap-2 mt-0.5 text-xs text-neutral-500">
                    <span v-if="offer.seller?.rating">★ {{ Number(offer.seller.rating).toFixed(1) }}</span>
                    <span v-if="offer.artikul" class="text-neutral-400">Art: {{ offer.artikul }}</span>
                  </div>
                </div>
              </div>
              <div class="text-right shrink-0">
                <div class="text-sm font-black text-neutral-900">
                  {{ formatPrice(offer.effective_price || offer.discountPrice || offer.price) }} so'm
                </div>
                <div v-if="!offer.in_stock || offer.stock <= 0" class="text-xs text-rose-500 font-semibold">
                  Qolmagan
                </div>
                <div v-else class="text-[11px] text-emerald-600 font-medium">
                  Sotuvda bor
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Bo'lib to'lash (Split) Modali -->
    <Teleport to="body">
      <div
        v-if="isSplitModalOpen"
        class="fixed inset-0 z-[120] bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="isSplitModalOpen = false"
      >
        <div class="max-h-[85vh] bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl flex flex-col border-none">
          <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-100 text-primary flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 6v6l4 2" />
                </svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-neutral-900 m-0">Bo'lib to'lash (Split)</h3>
                <p class="text-xs text-neutral-400 m-0">Qulay va foizsiz to'lov rejalari</p>
              </div>
            </div>
            <button
              type="button"
              @click="isSplitModalOpen = false"
              class="p-1.5 rounded-full hover:bg-neutral-100 text-neutral-400 border-none bg-transparent cursor-pointer"
              aria-label="Yopish"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="overflow-y-auto py-4 flex-1 space-y-3 no-scrollbar">
            <div
              v-for="plan in splitPlans"
              :key="'plan-' + plan.id"
              class="p-4 rounded-2xl border border-neutral-200 bg-neutral-50/50 space-y-2"
            >
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-xs font-bold text-primary px-2 py-0.5 rounded-md bg-blue-50">
                    {{ plan.monthly_interest_percent === 0 ? "0% Foizsiz" : `${plan.months} oy` }}
                  </span>
                  <span class="text-xs text-neutral-500 ml-2">{{ plan.installments_count }} ta to'lov</span>
                </div>
                <div class="text-sm font-extrabold text-neutral-900">
                  {{ formatPrice(plan.first_payment) }} so'm / to'lov
                </div>
              </div>

              <div class="text-xs text-neutral-600 flex justify-between pt-1 border-t border-neutral-100">
                <span>Umumiy to'lov:</span>
                <span class="font-bold text-neutral-900">{{ formatPrice(plan.total) }} so'm</span>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-blue-50/60 border border-blue-100/60 text-xs text-neutral-600 space-y-1">
              <div class="font-bold text-primary">To'lov qanday ishlaydi?</div>
              <p class="m-0 leading-relaxed text-[11px] text-neutral-500">
                Birinchi to'lov buyurtma vaqtida amalga oshiriladi, qolgan to'lovlar esa belgilangan grafik asosida avtomatik yechib olinadi.
              </p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>

  <!-- ====== FULL SHIMMER SKELETON (While loading) ====== -->
  <ProductUnavailable v-else-if="notFound" :code="unavailableCode" :product-id="rawId" />
  <div v-else class="py-6 min-h-dvh bg-[#f0f2f5] grow">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <div class="h-6 w-48 rounded-lg shimmer mb-6 max-md:hidden"></div>
      <div class="grid grid-cols-12 gap-8">
        <div class="col-span-12 lg:col-span-7 bg-white p-6 rounded-3xl shadow-2xs">
          <div class="w-full aspect-[3/4] max-h-[460px] rounded-2xl shimmer"></div>
        </div>
        <div class="col-span-12 lg:col-span-5 bg-white p-6 rounded-3xl shadow-2xs space-y-4">
          <div class="h-8 w-3/4 rounded-xl shimmer"></div>
          <div class="h-5 w-1/2 rounded-lg shimmer"></div>
          <div class="h-16 w-full rounded-2xl shimmer mt-4"></div>
          <div class="h-28 w-full rounded-2xl shimmer mt-4"></div>
          <div class="h-12 w-full rounded-2xl shimmer mt-6"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useFavoritesStore } from '~/stores/favorites'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const cartStore = useCartStore()
const favStore = useFavoritesStore()
const authStore = useAuthStore()
const snackbar = useSnackbar()

const isReviewsOpen = ref(false)
const isAiMatchOpen = ref(false)
const isVideoOpen = ref(false)
const isAllOffersOpen = ref(false)
const isSplitModalOpen = ref(false)
const orderCount = ref(1)

function incrementQuantity() {
  orderCount.value++
}

function decrementQuantity() {
  if (orderCount.value > 1) {
    orderCount.value--
  } else if (isAdded.value) {
    if (product.value) {
      cartStore.removeItem(product.value.id)
    }
  }
}

// Xususiyatlar va tillar
const { coverTypeLabel } = useBookFilterLabels()
const BOOK_LANG_LABELS: Record<string, string> = {
  uz: "O'zbek", oz: "O'zbek", ru: 'Rus', en: 'Ingliz', kk: 'Qozoq', kz: 'Qozoq',
  tr: 'Turk', ar: 'Arab', ja: 'Yapon', de: 'Nemis', fr: 'Fransuz', ko: 'Koreys', zh: 'Xitoy',
}
function bookLangLabel(value: string): string {
  const key = value.trim().toLowerCase()
  return BOOK_LANG_LABELS[key] ?? value
}

watch([isReviewsOpen, isAllOffersOpen, isSplitModalOpen], ([revOpen, offersOpen, splitOpen]) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = (revOpen || offersOpen || splitOpen) ? 'hidden' : ''
  }
})

// Extract numeric ID from param like "123-slug-nomi"
const rawId = computed(() => {
  const param = String(route.params.id || '')
  return param.split('-')[0]
})

// Product Data Fetching
const { data: productData, error: productError, status: productStatus, pending: productPending } = await useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/share/product/${rawId.value}`,
  {
    query: { type: 'book' },
    lazy: false
  }
)

const product = computed(() => {
  return productData.value?.data || productData.value?.product || null
})

const notFound = computed(() => {
  if (product.value) return false
  if (Boolean(productError.value)) return true
  if (productData.value?.status === 'error') return true
  if (productData.value && !productData.value?.data && !productData.value?.product) return true
  if (!productPending.value && productStatus.value !== 'pending') return true
  return false
})

const unavailableCode = computed<string | null>(() => {
  const err = productError.value as any
  const errData = err?.data || err?.response?._data
  return errData?.code || productData.value?.code || (notFound.value ? 'KB-B20' : null)
})

if (import.meta.server && notFound.value) {
  const event = useRequestEvent()
  if (event) {
    setResponseStatus(event, 404)
  }
}

const productRating = computed(() => {
  if (product.value?.ugc_aggregate_score && Number(product.value.ugc_aggregate_score) > 0) {
    return Number(product.value.ugc_aggregate_score).toFixed(1)
  }
  if (product.value?.rating && Number(product.value.rating) > 0) {
    return Number(product.value.rating).toFixed(1)
  }
  return undefined
})

const reviewsCount = computed(() => {
  return product.value?.ugc_reviews_count ?? (product.value?.reviews_count || reviews.value.length || 0)
})

const productCategoryTitle = computed(() => {
  const p = product.value
  if (!p) return 'Kitoblar'
  if (typeof p.category === 'string') return p.category
  return p.category?.name_uz || p.category?.name || 'Kitoblar'
})

// Hashtaglar (Screenshot 2: # Jinoyat, # Psixologik triller)
const productTags = computed(() => {
  const p = product.value
  if (!p) return []
  if (Array.isArray(p.tags) && p.tags.length > 0) {
    return p.tags
      .map((t: any) => (typeof t === 'string' ? t : (t.uz || t.name_uz || t.name || '')))
      .filter(Boolean)
  }
  const fallback = []
  if (productCategoryTitle.value && productCategoryTitle.value !== 'Kitoblar') {
    fallback.push(productCategoryTitle.value)
  }
  if (p.coverType) {
    fallback.push(coverTypeLabel(String(p.coverType)))
  }
  return fallback
})

function getTagColorClass(idx: number) {
  const colors = [
    'bg-rose-50 text-rose-700',
    'bg-emerald-50 text-emerald-700',
    'bg-blue-50 text-blue-700',
    'bg-amber-50 text-amber-700',
    'bg-purple-50 text-purple-700'
  ]
  return colors[idx % colors.length]
}

// AI Score calculation (Deterministic & matches Screenshot 2: 69%)
const aiMatchPercent = computed(() => {
  if (!product.value?.id) return 69
  const seed = Number(product.value.id) + (authStore.user?.id ? Number(authStore.user.id) * 7 : 42)
  return 65 + (seed % 30)
})

const aiSummarySubtitle = computed(() => {
  return authStore.isAuthenticated ? "Nega ekanini ko'ring" : "AI Shaxsiy tahlil"
})

const aiDesktopSubtitle = computed(() => {
  return authStore.isAuthenticated ? "Sun'iy intellekt tahlilini ko'rish" : "AI Shaxsiy tahlil — batafsil"
})

// Video support
const hasVideo = computed(() => {
  const p = product.value
  if (!p) return false
  return Boolean(p.has_video || p.video || p.video_url)
})


// UGC Reviews
const ugcList = ref<any[]>([])
const isReviewsLoading = ref(false)

const reviews = computed(() => {
  if (ugcList.value.length > 0) {
    return ugcList.value
  }
  const preview = product.value?.ugc_reviews_preview
  if (Array.isArray(preview) && preview.length > 0) {
    return preview.map((p: any) => ({
      id: p.id,
      user_name: [p.user?.name, p.user?.lastname].filter(Boolean).join(' ') || p.user?.name || 'Kitobxon',
      avatar: p.user?.avatar,
      rating: p.ai_post_score ? Math.min(5, Math.max(1, Math.round(p.ai_post_score))) : 5,
      comment: p.text || '',
      created_at: p.created_at ? new Date(p.created_at).toLocaleDateString('uz-UZ') : 'Yaqinda'
    }))
  }
  if (Array.isArray(product.value?.reviews) && product.value.reviews.length > 0) {
    return product.value.reviews
  }
  return []
})

const displayReviews = computed(() => {
  return reviews.value.slice(0, 3)
})

async function openReviewsModal() {
  isReviewsOpen.value = true
  if (ugcList.value.length === 0 && rawId.value) {
    try {
      isReviewsLoading.value = true
      const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/product_comments/${rawId.value}/book`)
      if (res?.status === 'success' && Array.isArray(res?.data)) {
        ugcList.value = res.data.map((p: any) => ({
          id: p.id,
          user_name: [p.user?.name, p.user?.lastname].filter(Boolean).join(' ') || p.user?.name || 'Kitobxon',
          avatar: p.user?.avatar,
          rating: p.ai_post_score ? Math.min(5, Math.max(1, Math.round(p.ai_post_score))) : 5,
          comment: p.text || '',
          created_at: p.created_at ? new Date(p.created_at).toLocaleDateString('uz-UZ') : 'Yaqinda'
        }))
      }
    } catch (e) {
      // preview displayed
    } finally {
      isReviewsLoading.value = false
    }
  }
}

const isFav = computed(() => product.value ? favStore.isFavorited(product.value.id, 'book') : false)

const hasSpecs = computed(() => {
  const p = product.value
  if (!p) return false
  return !!(p.publisher || p.pages || p.coverType || p.lang || p.year || p.isbn)
})

function resolveImg(src: string) {
  if (!src) return ''
  return src.startsWith('http') || src.startsWith('data:') ? src : `/storage/${src}`
}

const galleryImages = computed(() => {
  const p = product.value
  if (!p) return []
  const lists = [p.medium_images, p.image_urls, p.thumb_images, p.images]
  for (const list of lists) {
    if (Array.isArray(list) && list.length > 0) {
      const resolved = list.map(resolveImg).filter(Boolean)
      if (resolved.length > 0) return resolved
    }
  }
  if (p.first_image) {
    return [resolveImg(p.first_image)]
  }
  return ['/images/logo/logo_blue.png']
})

// O'xshash mahsulotlar
const { data: similarRes } = useLazyFetch<any>(() => `${config.public.apiBase}/v1/kitobchi/search/`, {
  query: computed(() => ({
    type: 'book',
    category_id: product.value?.category_id || undefined,
    sort: 'popular',
    page: 1
  })),
  immediate: Boolean(product.value?.category_id),
  watch: [() => product.value?.category_id]
})

const similarProducts = computed(() => {
  const list = similarRes.value?.data || []
  return list.filter((p: any) => p.id !== product.value?.id).slice(0, 10)
})

const activeIndex = ref(0)
const mobileTrackEl = ref<HTMLElement | null>(null)
let scrollRaf = 0

watchEffect(() => {
  if (activeIndex.value >= galleryImages.value.length) {
    activeIndex.value = 0
  }
})

const activeImage = computed(() => galleryImages.value[activeIndex.value] || galleryImages.value[0] || '/images/logo/logo_blue.png')

function onMobileTrackScroll() {
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  scrollRaf = requestAnimationFrame(() => {
    const el = mobileTrackEl.value
    if (!el || el.clientWidth === 0) return
    activeIndex.value = Math.round(el.scrollLeft / el.clientWidth)
  })
}

function goToSlide(idx: number) {
  activeIndex.value = idx
  const mEl = mobileTrackEl.value
  if (mEl && mEl.children[idx]) {
    const targetChild = mEl.children[idx] as HTMLElement
    mEl.scrollTo({ left: targetChild.offsetLeft, behavior: 'smooth' })
  }
}

function goToMobileSlide(idx: number) {
  goToSlide(idx)
}

function prevImage() {
  const len = galleryImages.value.length
  if (len < 2) return
  goToSlide((activeIndex.value - 1 + len) % len)
}

function nextImage() {
  const len = galleryImages.value.length
  if (len < 2) return
  goToSlide((activeIndex.value + 1) % len)
}

const isDescExpanded = ref(false)

// Multi-shop catalog offers
const rawOffers = computed<any[]>(() => {
  const p = product.value
  if (!p) return []
  if (Array.isArray(p.offers) && p.offers.length > 0) {
    return p.offers.map((off: any) => {
      const pPrice = Number(off.price || 0)
      const pDisc = Number(off.discountPrice || 0)
      const effective = off.effective_price || (pDisc > 0 && pDisc < pPrice ? pDisc : pPrice)
      const stockCount = off.count ?? off.stock ?? 1
      const inStock = off.in_stock !== false && stockCount > 0
      return {
        ...off,
        price: pPrice,
        discountPrice: pDisc,
        effective_price: effective,
        stock: stockCount,
        in_stock: inStock,
        seller: off.seller || {
          seller_id: off.seller_id || p.seller_id,
          shop_name: off.shop_name || p.seller?.shop_name || 'Kitobchi do\'koni',
          rating: off.seller?.rating || p.seller?.rating || 5,
          isVerified: off.seller?.isVerified ?? true
        }
      }
    })
  }

  // Fallback single offer from product
  const pPrice = Number(p.price || 0)
  const pDisc = Number(p.discountPrice || 0)
  const effective = (pDisc > 0 && pDisc < pPrice) ? pDisc : pPrice
  const stockCount = p.count ?? p.stock ?? 1
  return [{
    id: p.id,
    edition_id: p.edition_id,
    artikul: p.artikul,
    price: pPrice,
    discountPrice: pDisc,
    effective_price: effective,
    stock: stockCount,
    in_stock: p.in_stock !== false && stockCount > 0,
    seller: p.seller || {
      seller_id: p.seller_id,
      shop_name: p.seller_name || 'Kitobchi do\'koni',
      rating: p.rating || 5,
      isVerified: true
    }
  }]
})

// Auto-select best offer: prefer in-stock, then lowest price
const bestOffer = computed(() => {
  const list = [...rawOffers.value]
  if (list.length === 0) return null

  list.sort((a, b) => {
    const aStock = a.in_stock ? 1 : 0
    const bStock = b.in_stock ? 1 : 0
    if (aStock !== bStock) return bStock - aStock

    const priceA = Number(a.effective_price || a.price || 0)
    const priceB = Number(b.effective_price || b.price || 0)
    return priceA - priceB
  })

  return list[0]
})

const selectedOfferId = ref<number | string | null>(null)

watchEffect(() => {
  if (bestOffer.value && !selectedOfferId.value) {
    selectedOfferId.value = bestOffer.value.id
  }
})

const activeOffer = computed(() => {
  if (!rawOffers.value.length) return null
  if (selectedOfferId.value) {
    const found = rawOffers.value.find(o => o.id === selectedOfferId.value)
    if (found) return found
  }
  return bestOffer.value || rawOffers.value[0]
})

const activeSeller = computed(() => {
  return activeOffer.value?.seller || product.value?.seller || null
})

const displayedOffers = computed(() => {
  return rawOffers.value.slice(0, 3)
})

function selectOffer(id: number | string) {
  selectedOfferId.value = id
  isAllOffersOpen.value = false
}

const isInStock = computed(() => {
  if (activeOffer.value) {
    return Boolean(activeOffer.value.in_stock && (activeOffer.value.stock ?? 1) > 0)
  }
  if (!product.value) return false
  return product.value.in_stock !== false && (product.value.count ?? product.value.stock ?? 1) > 0
})

const hasOtherInStockOffer = computed(() => {
  return rawOffers.value.some(o => o.id !== activeOffer.value?.id && o.in_stock && (o.stock ?? 1) > 0)
})

const originalPrice = computed(() => {
  if (activeOffer.value) {
    return Number(activeOffer.value.price || 0)
  }
  return Number(product.value?.price || 0)
})

const currentPrice = computed(() => {
  if (activeOffer.value) {
    return Number(activeOffer.value.effective_price || activeOffer.value.price || 0)
  }
  if (!product.value) return 0
  const isDisc = product.value.discountPrice > 0 && product.value.discountPrice < product.value.price
  return isDisc ? product.value.discountPrice : product.value.price
})

const discountPercent = computed(() => {
  const base = originalPrice.value
  const curr = currentPrice.value
  if (base > 0 && curr < base) {
    return Math.round(((base - curr) / base) * 100)
  }
  return 0
})

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

// Real Split calculation from backend API
const { data: splitRes } = useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/split-preview`,
  {
    query: computed(() => ({
      amount: currentPrice.value,
      product_type: 'book',
      product_id: product.value?.id || rawId.value
    })),
    watch: [() => currentPrice.value, () => product.value?.id],
    lazy: true
  }
)

const isSplitAvailable = computed(() => {
  return Boolean(splitRes.value?.data?.enabled && splitPlans.value.length > 0)
})

const splitPlans = computed(() => {
  return Array.isArray(splitRes.value?.data?.plans) ? splitRes.value.data.plans : []
})

const selectedSplitPlan = computed(() => {
  return splitPlans.value[0] || null
})

const isAdded = computed(() => {
  if (!product.value) return false
  const targetId = activeOffer.value?.id || product.value.id
  return cartStore.items.some(i => (i.productId === targetId || i.productId === product.value.id) && i.type === 'book')
})

function handleOutOfStockClick() {
  if (hasOtherInStockOffer.value) {
    snackbar.showWarning("Ushbu do'konda kitob qolmagan. Boshqa do'kon taklifini tanlang.")
    if (rawOffers.value.length > 3) {
      isAllOffersOpen.value = true
    }
  } else {
    snackbar.showWarning("Ushbu kitob hozirda sotuvda mavjud emas.")
  }
}

function handleAddToCart() {
  if (!isInStock.value) {
    handleOutOfStockClick()
    return
  }
  if (isAdded.value) {
    router.push('/cart')
    return
  }
  if (product.value) {
    const targetProduct = activeOffer.value ? {
      ...product.value,
      id: activeOffer.value.id,
      price: originalPrice.value,
      discountPrice: currentPrice.value < originalPrice.value ? currentPrice.value : 0,
      seller: activeOffer.value.seller || product.value.seller
    } : product.value

    cartStore.addItem(targetProduct, 'book', orderCount.value)
    snackbar.showSuccess("Kitob savatga qo'shildi")
  }
}

function handleBuyNow() {
  if (!isInStock.value) {
    handleOutOfStockClick()
    return
  }
  if (product.value) {
    const targetProduct = activeOffer.value ? {
      ...product.value,
      id: activeOffer.value.id,
      price: originalPrice.value,
      discountPrice: currentPrice.value < originalPrice.value ? currentPrice.value : 0,
      seller: activeOffer.value.seller || product.value.seller
    } : product.value

    cartStore.addItem(targetProduct, 'book', orderCount.value)
    router.push('/cart')
  }
}

async function shareProduct() {
  if (import.meta.client) {
    const url = window.location.href
    if (navigator.share) {
      try {
        await navigator.share({
          title: product.value?.name || 'Kitobchi',
          text: `${product.value?.name} — Kitobchi marketpleysida`,
          url
        })
      } catch (e) {
        // User cancelled share
      }
    } else if (navigator.clipboard) {
      await navigator.clipboard.writeText(url)
      alert('Havola nusxalandi!')
    }
  }
}

// SEO & Schema.org JSON-LD Rich Snippet
const { setProductSeo } = useAppSeo()
watchEffect(() => {
  if (!product.value) return
  setProductSeo({
    name: product.value.name,
    description: product.value.description,
    image: activeImage.value,
    price: product.value.price,
    discountPrice: product.value.discountPrice,
    currency: 'UZS',
    inStock: true,
    type: 'book',
    author: product.value.author,
    publisher: product.value.publisher?.name || product.value.publisher,
    isbn: product.value.isbn,
    rating: productRating.value,
    reviewsCount: reviewsCount.value,
    reviews: reviews.value.slice(0, 5),
    categoryName: productCategoryTitle.value,
    urlPath: (() => {
      const pName = product.value?.name || ''
      const clean = pName.toLowerCase()
        .replace(/[^a-z0-9а-яёўқғҳ\s-]/gi, '')
        .trim()
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '')
      return clean ? `/books/${rawId.value}-${clean}` : `/books/${route.params.id}`
    })(),
  })
})
</script>
