<template>
  <div class="min-h-dvh bg-secondary-300 md:bg-gray-50 grow" v-if="product">
    <!-- ========================================================================= -->
    <!--  1. MOBIL KO'RINISH (Piyola Market 1:1)                                    -->
    <!-- ========================================================================= -->
    <div class="md:hidden">
      <!-- Rasm Karuseli Kartasi (rounded-b-2xl bg-white p-4 sm:p-6) -->
      <div class="bg-white p-4 sm:p-6 rounded-b-2xl">
        <div class="relative">
          <!-- 3:4 Nisbatdagi Galereya Karuseli -->
          <div role="region" aria-roledescription="carousel" data-orientation="horizontal" tabindex="0" data-slot="root" class="relative focus:outline-none w-full aspect-[3/4] rounded-xl overflow-hidden bg-white">
            <img
              v-if="selectedVariantImage"
              :src="selectedVariantImage"
              :alt="product.name"
              class="w-full h-full object-cover"
              loading="eager"
              draggable="false"
            />
            <div
              v-else
              data-slot="viewport"
              class="overflow-hidden w-full h-full"
            >
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
                  data-slot="item"
                  class="min-w-full w-full shrink-0 basis-full flex h-full snap-center items-center justify-center bg-white"
                >
                  <img
                    :src="img"
                    :alt="product.name"
                    class="w-full h-full object-cover"
                    :loading="idx === 0 ? 'eager' : 'lazy'"
                    draggable="false"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Pastki o'ngdagi Nuqtalar (Dots Pill Indicator) -->
          <div v-if="!selectedVariantImage && galleryImages.length > 1" class="absolute w-full bottom-0 right-0 p-2 z-20 flex justify-between items-end pointer-events-none">
            <div class="flex items-center gap-1 rounded-full py-1 px-2 bg-white pointer-events-auto shadow-xs">
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

          <!-- Yuqori chap: Orqaga qaytish oynasimon tugmasi (Glass Button) -->
          <button
            type="button"
            @click.stop="$router.back()"
            aria-label="arrow left"
            class="absolute top-4 left-4 z-30 border-none bg-transparent p-0 cursor-pointer"
          >
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm flex items-center justify-center bg-white w-10 h-10 shadow-sm text-neutral-900">
              <svg class="w-5 h-5 text-neutral-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </div>
          </button>

          <!-- Yuqori o'ng: Sevimli va Ulashish oynasimon kapsulasi (Glass Pill) -->
          <div class="absolute top-4 right-4 z-30 flex items-center">
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-full hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm flex items-center gap-2 bg-white py-1.5 px-2.5 shadow-sm">
              <button
                type="button"
                @click.stop="favStore.toggleFavorite(product, 'stationery')"
                name="Favorite button"
                aria-label="Favorite button"
                class="w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer p-0"
              >
                <svg class="w-5 h-5 transition-colors duration-200" :class="isFav ? 'text-red-500 fill-red-500' : 'text-neutral-800'" viewBox="0 0 24 24" :fill="isFav ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.312-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
              </button>
              <button
                type="button"
                @click.stop="shareProduct"
                name="share button"
                aria-label="share button"
                class="w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer p-0 text-neutral-800"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186a2.25 2.25 0 0 0-3.935-2.186m0-12.814a2.25 2.25 0 1 0 3.933-2.185a2.25 2.25 0 0 0-3.933 2.185"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Rang variantlari (mobil) -->
        <div v-if="product.variants && product.variants.length > 0" class="flex items-center gap-2 mt-3 flex-wrap">
          <button
            v-for="v in product.variants"
            :key="v.id"
            type="button"
            @click="selectVariant(v)"
            :class="[
              'w-10 h-10 rounded-full overflow-hidden border-2 transition-all cursor-pointer bg-secondary-50 shrink-0 p-0',
              selectedVariantId === v.id ? 'border-primary-500' : 'border-transparent hover:border-neutral-200'
            ]"
            :aria-label="v.color_name || 'variant'"
          >
            <img v-if="v.image_thumb_url || v.image_url" :src="v.image_thumb_url || v.image_url" class="w-full h-full object-cover" />
          </button>
        </div>
      </div>

      <!-- Kulrang Fonli Bo'lim Kartalari (bg-secondary-300 space-y-2 pb-44 pt-2) -->
      <div class="relative z-20 bg-secondary-300 space-y-2 pb-44 pt-2">
        <!-- 1-Karta: Sarlavha, Do'kon nomi va Material -->
        <div class="relative z-20">
          <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto bg-white py-4 rounded-2xl">
            <h1 class="text-lg font-medium text-neutral-900 leading-snug m-0">
              <span class="font-medium inline-flex items-center text-xs rounded-md px-1.5 py-0.5 bg-secondary-200 gap-1 text-primary mr-1.5 align-middle">
                <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                {{ product.seller?.shop_name || 'Kitobchi' }}
              </span>
              {{ product.name }}
            </h1>
            <div v-if="product.material" class="text-xs text-neutral-500 mt-1.5">
              Material: <span class="font-semibold text-neutral-800">{{ product.material }}</span>
            </div>
          </div>
        </div>

        <!-- 2-Karta: Muddatli to'lov / Naqd to'lov Tablari -->
        <div class="relative z-20">
          <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto bg-white py-4 rounded-2xl">
            <div role="tablist" class="relative inline-flex bg-secondary-200 rounded-xl p-1 w-full mb-3">
              <button
                type="button"
                role="tab"
                :aria-selected="paymentTab === 'installment'"
                @click="paymentTab = 'installment'"
                :class="[
                  'text-sm px-3 py-1.5 flex-1 font-medium rounded-lg transition-all duration-200 border-none cursor-pointer',
                  paymentTab === 'installment' ? 'bg-white text-gray-900 shadow-xs' : 'bg-transparent text-gray-500'
                ]"
              >
                Muddatli to‘lov
              </button>
              <button
                type="button"
                role="tab"
                :aria-selected="paymentTab === 'cash'"
                @click="paymentTab = 'cash'"
                :class="[
                  'text-sm px-3 py-1.5 flex-1 font-medium rounded-lg transition-all duration-200 border-none cursor-pointer',
                  paymentTab === 'cash' ? 'bg-white text-gray-900 shadow-xs' : 'bg-transparent text-gray-500'
                ]"
              >
                Naqd to‘lov
              </button>
            </div>

            <!-- Muddatli to'lov ma'lumoti -->
            <div v-if="paymentTab === 'installment'" class="space-y-3">
              <div>
                <p class="text-xs text-gray-400 font-normal m-0 mb-1">Muddatli to'lov</p>
                <div class="inline-flex bg-secondary-200 rounded-xl p-1 gap-1">
                  <button
                    v-for="m in installmentMonths"
                    :key="m"
                    type="button"
                    @click="selectedMonths = m"
                    :class="[
                      'text-xs font-medium rounded-lg px-3 py-1.5 transition-colors border-none cursor-pointer',
                      selectedMonths === m ? 'bg-white text-gray-900 shadow-xs' : 'bg-transparent text-gray-500'
                    ]"
                  >
                    {{ m }} oy
                  </button>
                </div>
              </div>
              <div class="flex items-center justify-between pt-1">
                <div>
                  <p class="text-xs text-gray-400 font-normal m-0">Muddatli to'lovga sotib olish</p>
                  <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-lg font-bold text-neutral-900">{{ formatPrice(monthlyForSelected) }}</span>
                    <span class="text-xs text-gray-500">so‘m/oyiga</span>
                  </div>
                </div>
                <div v-if="discountPercent > 0" class="text-right">
                  <span class="text-xs line-through text-gray-400">{{ formatPrice(product.price) }} so‘m</span>
                </div>
              </div>
            </div>

            <!-- Naqd to'lov ma'lumoti -->
            <div v-else class="flex items-center justify-between pt-1">
              <div>
                <p class="text-xs text-gray-400 font-normal m-0">Narxi</p>
                <div class="text-lg font-bold text-neutral-900 mt-0.5">{{ formatPrice(currentPrice) }} so‘m</div>
              </div>
              <div v-if="discountPercent > 0" class="text-right">
                <span class="text-xs line-through text-gray-400 block">{{ formatPrice(product.price) }} so‘m</span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-[#ED3131] text-white">-{{ discountPercent }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3-Karta: Sharhlar bloki -->
        <div class="relative z-20">
          <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto bg-white py-4 rounded-2xl flex flex-col gap-2 items-center text-center">
            <svg class="w-8 h-8 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
              <path d="M3.505 2.365A41.369 41.369 0 0 1 9 2c1.863 0 3.697.124 5.495.365c1.247.167 2.18 1.108 2.435 2.268a4.45 4.45 0 0 0-.577-.069a43.141 43.141 0 0 0-4.706 0C9.229 4.696 7.5 6.727 7.5 8.998v2.24c0 1.413.67 2.735 1.76 3.562l-2.98 2.98A.75.75 0 0 1 5 17.25v-3.443c-.501-.048-1-.106-1.495-.172C2.033 13.438 1 12.162 1 10.72V5.28c0-1.441 1.033-2.717 2.505-2.914Z"/>
              <path d="M14 6c-.762 0-1.52.02-2.271.062C10.157 6.148 9 7.472 9 8.998v2.24c0 1.519 1.147 2.839 2.71 2.935c.214.013.428.024.642.034c.2.009.385.09.518.224l2.35 2.35a.75.75 0 0 0 1.28-.531v-2.07c1.453-.195 2.5-1.463 2.5-2.915V8.998c0-1.526-1.157-2.85-2.729-2.936A41.645 41.645 0 0 0 14 6Z"/>
            </svg>
            <h2 class="font-semibold text-xs text-neutral-800 m-0">Hozircha sharhlar yo‘q</h2>
          </div>
        </div>

        <!-- 4-Karta: Mahsulot haqida (Description) -->
        <div v-if="product.description" class="relative z-20">
          <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto bg-white py-4 rounded-2xl">
            <h3 class="text-base font-semibold mb-2 text-neutral-900 m-0">Mahsulot haqida</h3>
            <div :class="['text-xs text-neutral-600 leading-relaxed mb-2', !isDescExpanded ? 'line-clamp-3' : '']" v-html="product.description"></div>
            <button
              type="button"
              @click="isDescExpanded = !isDescExpanded"
              class="inline-flex items-center gap-1 text-xs font-semibold text-primary border-none bg-transparent cursor-pointer p-0"
            >
              {{ isDescExpanded ? 'Yopish' : 'Batafsil o‘qish' }}
              <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="isDescExpanded ? '-rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
          </div>
        </div>

        <!-- 5-Karta: Xususiyatlar va tavsif (Specs) -->
        <div v-if="hasSpecs" class="relative z-20">
          <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto bg-white py-4 rounded-2xl">
            <h3 class="text-base font-semibold mb-3 text-neutral-900 m-0">Xususiyatlar va tavsif</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <div v-if="product.material" class="p-2.5 rounded-xl bg-secondary-50 flex justify-between items-center">
                <span class="text-neutral-500">Material:</span>
                <span class="font-semibold text-neutral-800">{{ product.material }}</span>
              </div>
              <div v-if="product.barcode" class="p-2.5 rounded-xl bg-secondary-50 flex justify-between items-center">
                <span class="text-neutral-500">Barkod:</span>
                <span class="font-semibold text-neutral-800">{{ product.barcode }}</span>
              </div>
              <div v-if="product.category" class="p-2.5 rounded-xl bg-secondary-50 flex justify-between items-center">
                <span class="text-neutral-500">Kategoriya:</span>
                <span class="font-semibold text-neutral-800">{{ product.category }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pastki suzuvchi panel uchun qo'shimcha bo'sh joy -->
        <div class="h-6"></div>
      </div>

      <!-- Mobil Pastki Suzuvchi Xarid Paneli (Sticky Buy Bar - Piyola 1:1) -->
      <div
        class="w-full bg-white shadow-2xl rounded-t-2xl fixed bottom-0 left-0 z-60"
        style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 1.25rem))"
      >
        <div class="px-4 py-3 flex items-center gap-3">
          <button
            type="button"
            @click="handleAddToCart"
            class="flex-1 h-12 rounded-2xl bg-secondary-200 hover:bg-secondary-300 text-primary font-bold text-sm border-none cursor-pointer flex items-center justify-center gap-2 transition-all active:scale-95"
            aria-label="Savatga qo'shish"
          >
            <svg v-if="!isAdded" class="w-5 h-5 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path d="M3 1a1 1 0 0 0 0 2h1.22l.305 1.222l.01.042l1.358 5.43l-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 0 0 0-2H6.414l1-1H14a1 1 0 0 0 .894-.553l3-6A1 1 0 0 0 17 3H6.28l-.31-1.243A1 1 0 0 0 5 1zm13 15.5a1.5 1.5 0 1 1-3 0a1.5 1.5 0 0 1 3 0M6.5 18a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3"/>
            </svg>
            <svg v-else class="w-5 h-5 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
            </svg>
            <span class="truncate">{{ isAdded ? 'Savatga qo‘shildi' : 'Savatga qo‘shish' }}</span>
          </button>
          <button
            type="button"
            @click="handleBuyNow"
            class="ios-order-btn flex-1 h-12 rounded-2xl text-base font-semibold text-white border-none cursor-pointer flex items-center justify-center active:scale-95 transition-transform"
          >
            Sotib olish
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!--  2. DESKTOP (PC) KO'RINISH (Piyola Market 1:1)                             -->
    <!-- ========================================================================= -->
    <div class="max-md:hidden py-5">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <!-- Breadcrumb & Back Arrow -->
        <div class="mb-6">
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="$router.back()"
              class="rounded-md font-medium inline-flex items-center p-2 text-primary hover:bg-primary/10 transition-colors border-none bg-transparent cursor-pointer"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <nav aria-label="breadcrumb" class="relative min-w-0">
              <ol class="flex items-center gap-2 text-sm text-[#8F8FA1]">
                <li><NuxtLink to="/" class="hover:text-default transition-colors">Asosiy</NuxtLink></li>
                <li class="text-gray-400 text-xs"> / </li>
                <li><NuxtLink to="/catalog?type=stationery" class="hover:text-default transition-colors">Katalog</NuxtLink></li>
                <template v-if="product.category || product.category_id">
                  <li class="text-gray-400 text-xs"> / </li>
                  <li>
                    <NuxtLink :to="`/catalog?category=${product.category_id || product.category?.id}&type=stationery`" class="hover:text-default transition-colors">
                      {{ product.category?.name_uz || product.category?.name || product.category || 'Kanselyariya' }}
                    </NuxtLink>
                  </li>
                </template>
                <li class="text-gray-400 text-xs"> / </li>
                <li><span class="font-semibold text-neutral-900 truncate max-w-[320px] inline-block align-bottom">{{ product.name }}</span></li>
              </ol>
            </nav>
          </div>
        </div>

        <!-- Asosiy Gridi: Chapda Galereya, O'ngda Ma'lumotlar va Buyurtma -->
        <div class="lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-5 items-start">
          <!-- Chap ustun: Galereya (Thumbnails + Asosiy rasm) -->
          <div class="col-span-1 xl:col-span-2 h-full">
            <div class="flex flex-col-reverse md:flex-row gap-3 h-full">
              <!-- Vertikal Thumbnails ro'yxati (3:4 nisbatda, 75x100px) -->
              <div
                v-if="galleryImages.length > 1"
                class="flex md:flex-col gap-3 overflow-x-auto md:overflow-y-auto md:overflow-x-hidden w-full md:w-auto md:h-0 md:min-h-full scrollbar-hide py-1 shrink-0"
              >
                <button
                  v-for="(img, idx) in galleryImages"
                  :key="'desk-thumb-' + idx"
                  type="button"
                  @click="selectThumbnail(idx)"
                  :aria-label="`gallery-image-selector-${idx}`"
                  :class="[
                    'relative shrink-0 w-[75px] h-[100px] rounded-xl overflow-hidden border-2 transition-all duration-300 cursor-pointer bg-secondary-50 aspect-[3/4] p-0',
                    activeIndex === idx && !selectedVariantImage ? 'border-primary-500' : 'border-transparent hover:border-neutral-200'
                  ]"
                >
                  <img :src="img" :alt="`${product.name} ${idx + 1}`" class="w-full h-full object-cover aspect-[3/4]" />
                </button>
              </div>

              <!-- Asosiy Rasm Karuseli (3:4 nisbatda, max-h-[580px]) -->
              <div class="flex-1 relative rounded-2xl group min-h-0">
                <div class="relative focus:outline-none aspect-[3/4] max-h-[580px] w-full rounded-2xl overflow-hidden bg-secondary-50">
                  <div class="overflow-hidden w-full h-full rounded-2xl">
                    <img
                      v-if="selectedVariantImage"
                      :src="selectedVariantImage"
                      :alt="product.name"
                      class="object-cover rounded-2xl w-full h-full aspect-[3/4]"
                      loading="eager"
                    />
                    <div
                      v-else
                      ref="desktopTrackEl"
                      class="flex flex-row w-full h-full rounded-2xl items-stretch overflow-x-auto no-scrollbar snap-x snap-mandatory"
                      @scroll="onDesktopTrackScroll"
                    >
                      <div
                        v-for="(img, idx) in galleryImages"
                        :key="'desk-main-' + idx"
                        class="min-w-0 shrink-0 basis-full flex h-full snap-center items-center justify-center bg-secondary-50"
                      >
                        <img
                          :src="img"
                          :alt="product.name"
                          class="object-cover rounded-2xl w-full h-full aspect-[3/4]"
                          :loading="idx === 0 ? 'eager' : 'lazy'"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Oldingi / Keyingi strelkalar -->
                  <template v-if="galleryImages.length > 1 && !selectedVariantImage">
                    <button
                      type="button"
                      @click="prevImage"
                      aria-label="Oldingi rasm"
                      class="p-2 absolute rounded-full start-4 top-1/2 -translate-y-1/2 bg-white/80 backdrop-blur-md shadow-md hover:bg-white transition-all cursor-pointer border-none z-10 flex items-center justify-center text-neutral-800"
                    >
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button
                      type="button"
                      @click="nextImage"
                      aria-label="Keyingi rasm"
                      class="p-2 absolute rounded-full end-4 top-1/2 -translate-y-1/2 bg-white/80 backdrop-blur-md shadow-md hover:bg-white transition-all cursor-pointer border-none z-10 flex items-center justify-center text-neutral-800"
                    >
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    </button>
                  </template>

                  <!-- Sevimli oynasimon tugmasi (yuqori o'ng) -->
                  <div class="absolute top-3 right-3 flex gap-2 z-20">
                    <div class="rounded-full backdrop-blur-md p-1 bg-black/5 hover:shadow-sm transition-shadow">
                      <button
                        type="button"
                        @click="favStore.toggleFavorite(product, 'stationery')"
                        name="Favorite button"
                        aria-label="Favorite button"
                        class="w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 border-none bg-transparent cursor-pointer p-0"
                      >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" :fill="isFav ? '#ef4444' : 'none'" :stroke="isFav ? '#ef4444' : '#1e293b'" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Rang variantlari (desktop) -->
                <div v-if="product.variants && product.variants.length > 0" class="flex items-center gap-2 mt-4 flex-wrap">
                  <button
                    v-for="v in product.variants"
                    :key="v.id"
                    type="button"
                    @click="selectVariant(v)"
                    :class="[
                      'w-10 h-10 rounded-full overflow-hidden border-2 transition-all cursor-pointer bg-secondary-50 shrink-0 p-0',
                      selectedVariantId === v.id ? 'border-primary-500' : 'border-transparent hover:border-neutral-200'
                    ]"
                    :aria-label="v.color_name || 'variant'"
                    :title="v.color_name || ''"
                  >
                    <img v-if="v.image_thumb_url || v.image_url" :src="v.image_thumb_url || v.image_url" class="w-full h-full object-cover" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- O'ng ustun: Mahsulot ma'lumotlari, Xususiyatlar va To'lov -->
          <div class="col-span-1 w-full space-y-6">
            <div>
              <!-- Do'kon Nomi Belgisi -->
              <div class="w-full flex gap-2 mb-2">
                <span class="font-medium inline-flex items-center text-sm px-2.5 py-1 bg-transparent border border-blue-500 gap-1 rounded-2xl text-primary">
                  <svg class="w-4 h-4 text-primary" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304a3 3 0 0 0-3.75-3.751a3 3 0 0 0-5.305 0a3 3 0 0 0-3.751 3.75a3 3 0 0 0 0 5.305a3 3 0 0 0 3.75 3.751a3 3 0 0 0 5.305 0a3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79l-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                  {{ product.seller?.shop_name || 'Kitobchi' }}
                </span>
              </div>

              <!-- Sarlavha, Material va Narx -->
              <div class="flex flex-col gap-4">
                <div>
                  <h1 class="text-2xl font-medium leading-tight text-neutral-900 m-0">{{ product.name }}</h1>
                  <div v-if="product.material" class="text-sm font-medium text-neutral-500 mt-1">
                    Material: <span class="text-primary font-semibold">{{ product.material }}</span>
                  </div>
                  <div class="flex flex-col mt-3">
                    <p class="text-sm text-gray-400 font-normal m-0">Narxi</p>
                    <div class="flex items-center gap-1">
                      <div class="flex items-end gap-3">
                        <span class="text-xl font-bold text-neutral-900">{{ formatPrice(currentPrice) }} so‘m</span>
                        <span v-if="discountPercent > 0" class="text-sm line-through text-gray-400">{{ formatPrice(product.price) }} so‘m</span>
                        <span v-if="discountPercent > 0" class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#ED3131] text-white">-{{ discountPercent }}%</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Xususiyatlar va tavsif tugmasi (Accordion) -->
                <div v-if="hasSpecs || product.description">
                  <div
                    @click="specsOpen = !specsOpen"
                    class="w-full bg-secondary-100 cursor-pointer rounded-2xl p-4 md:px-6 flex items-center justify-between transition-colors duration-300 hover:bg-secondary-200"
                  >
                    <div class="flex items-center gap-3">
                      <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                      <span class="font-medium text-primary">Xususiyatlar va tavsif</span>
                    </div>
                    <svg class="w-5 h-5 text-primary transition-transform duration-300" :class="specsOpen ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                  </div>

                  <!-- Xususiyatlar ro'yxati -->
                  <div v-show="specsOpen" class="mt-3 p-6 rounded-3xl bg-secondary-50 space-y-4">
                    <div v-if="hasSpecs" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                      <div v-if="product.material" class="p-3 rounded-2xl bg-white">
                        <span class="text-gray-400 block text-xs">Material:</span>
                        <span class="font-semibold text-neutral-800">{{ product.material }}</span>
                      </div>
                      <div v-if="product.barcode" class="p-3 rounded-2xl bg-white">
                        <span class="text-gray-400 block text-xs">Barkod:</span>
                        <span class="font-semibold text-neutral-800">{{ product.barcode }}</span>
                      </div>
                      <div v-if="product.category" class="p-3 rounded-2xl bg-white">
                        <span class="text-gray-400 block text-xs">Kategoriya:</span>
                        <span class="font-semibold text-neutral-800">{{ product.category }}</span>
                      </div>
                    </div>
                    <div v-if="product.description" class="kb-prose text-sm text-neutral-600" v-html="product.description"></div>
                  </div>
                </div>

                <!-- To'lov va Xarid Kartasi -->
                <div class="p-6 rounded-3xl bg-secondary-100 space-y-5">
                  <div role="tablist" class="relative inline-flex bg-secondary-200 rounded-xl p-1 w-full">
                    <button
                      type="button"
                      role="tab"
                      :aria-selected="paymentTab === 'installment'"
                      @click="paymentTab = 'installment'"
                      :class="[
                        'text-sm px-4 py-2 flex-1 font-medium rounded-lg transition-all duration-200 border-none cursor-pointer',
                        paymentTab === 'installment' ? 'bg-white text-gray-900 shadow-sm' : 'bg-transparent text-gray-400'
                      ]"
                    >
                      Muddatli to‘lov
                    </button>
                    <button
                      type="button"
                      role="tab"
                      :aria-selected="paymentTab === 'cash'"
                      @click="paymentTab = 'cash'"
                      :class="[
                        'text-sm px-4 py-2 flex-1 font-medium rounded-lg transition-all duration-200 border-none cursor-pointer',
                        paymentTab === 'cash' ? 'bg-white text-gray-900 shadow-sm' : 'bg-transparent text-gray-400'
                      ]"
                    >
                      Naqd to‘lov
                    </button>
                  </div>

                  <!-- Muddatli to'lov tanlash -->
                  <div v-if="paymentTab === 'installment'" class="flex justify-between gap-4 w-full">
                    <div>
                      <p class="text-sm text-gray-500 font-normal m-0 mb-1">Muddatli to'lov</p>
                      <div class="inline-flex bg-secondary-200 rounded-xl p-1 gap-1">
                        <button
                          v-for="m in installmentMonths"
                          :key="m"
                          type="button"
                          @click="selectedMonths = m"
                          :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border-none cursor-pointer',
                            selectedMonths === m ? 'bg-white text-gray-900 shadow-xs' : 'bg-transparent text-gray-400'
                          ]"
                        >
                          {{ m }} oy
                        </button>
                      </div>
                    </div>
                    <div class="flex flex-col items-end">
                      <p class="text-sm text-gray-500 font-normal m-0">Muddatli to'lovga sotib olish</p>
                      <div class="flex items-end gap-2 mt-2">
                        <span class="text-xl font-bold text-neutral-900">{{ formatPrice(monthlyForSelected) }}</span>
                        <span class="text-sm text-gray-500">so‘m/oyiga</span>
                      </div>
                    </div>
                  </div>

                  <!-- Naqd to'lov narxi -->
                  <div v-else class="flex justify-between items-center w-full">
                    <div>
                      <p class="text-sm text-gray-500 font-normal m-0">Narxi</p>
                      <div class="text-xl font-bold text-neutral-900 mt-1">{{ formatPrice(currentPrice) }} so‘m</div>
                    </div>
                    <div v-if="discountPercent > 0">
                      <span class="text-sm line-through text-gray-400 block">{{ formatPrice(product.price) }} so‘m</span>
                      <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#ED3131] text-white">-{{ discountPercent }}%</span>
                    </div>
                  </div>

                  <!-- Buyurtma berish & Savat tugmalari -->
                  <div class="flex items-center gap-3 pt-2">
                    <div class="flex-1">
                      <button
                        type="button"
                        @click="handleBuyNow"
                        class="ios-order-btn w-full h-12 rounded-2xl text-base font-semibold text-white border-none cursor-pointer flex items-center justify-center"
                      >
                        Buyurtma berish
                      </button>
                    </div>
                    <button
                      type="button"
                      @click="handleAddToCart"
                      class="h-12 px-3.5 rounded-2xl bg-primary/10 hover:bg-primary/20 text-primary border-none cursor-pointer flex items-center justify-center transition-colors shrink-0"
                      aria-label="Savatga qo'shish"
                    >
                      <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M3 1a1 1 0 0 0 0 2h1.22l.305 1.222l.01.042l1.358 5.43l-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 0 0 0-2H6.414l1-1H14a1 1 0 0 0 .894-.553l3-6A1 1 0 0 0 17 3H6.28l-.31-1.243A1 1 0 0 0 5 1zm13 15.5a1.5 1.5 0 1 1-3 0a1.5 1.5 0 0 1 3 0M6.5 18a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- O'xshash mahsulotlar (Desktop) -->
        <section v-if="similarProducts.length > 0" class="mt-12 pb-8">
          <div class="flex justify-between items-center w-full px-1 mb-5">
            <h2 class="font-bold text-xl md:text-3xl leading-[100%] text-primary m-0 capitalize">
              O‘xshash mahsulotlar
            </h2>
            <NuxtLink
              v-if="product?.category_id"
              :to="`/catalog?category=${product.category_id}&type=stationery`"
              class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
            >
              Barchasi
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </NuxtLink>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <ProductCard
              v-for="sp in similarProducts"
              :key="'similar-' + sp.id"
              :product="sp"
              type="stationery"
            />
          </div>
        </section>
      </div>
    </div>
  </div>

  <!-- ====== FULL SHIMMER SKELETON (While loading) ====== -->
  <div v-else class="py-4 md:py-6 min-h-dvh bg-white grow">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb shimmer -->
      <div class="h-8 w-48 rounded-lg shimmer mb-6 max-md:hidden"></div>

      <div class="lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-8">
        <!-- Gallery Shimmer -->
        <div class="col-span-1 xl:col-span-2 max-w-[520px] mx-auto lg:mx-0 w-full">
          <div class="w-full aspect-3/4 max-h-[460px] rounded-3xl shimmer"></div>
          <div class="flex gap-2 mt-4">
            <div v-for="n in 4" :key="n" class="w-16 h-16 rounded-xl shimmer"></div>
          </div>
        </div>

        <!-- Specs & Price Shimmer -->
        <div class="space-y-4">
          <div class="h-8 w-3/4 rounded-lg shimmer"></div>
          <div class="h-5 w-1/2 rounded-md shimmer"></div>
          <div class="h-10 w-44 rounded-xl shimmer mt-4"></div>
          <div class="h-24 w-full rounded-2xl shimmer mt-4"></div>
          <div class="h-14 w-full rounded-2xl shimmer mt-6"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { useFavoritesStore } from '~/stores/favorites'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const cartStore = useCartStore()
const favStore = useFavoritesStore()

// Extract numeric ID from param like "123-slug-nomi"
const rawId = computed(() => {
  const param = String(route.params.id || '')
  return param.split('-')[0]
})

// Real backend: ShareController::product (GET v1/kitobchi/share/product/{id}?type=stationery)
const { data: productData } = await useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/share/product/${rawId.value}`,
  {
    query: { type: 'stationery' },
    lazy: false
  }
)

const product = computed(() => {
  return productData.value?.data || productData.value?.product || null
})

const isFav = computed(() => product.value ? favStore.isFavorited(product.value.id, 'stationery') : false)

const hasSpecs = computed(() => {
  const p = product.value
  if (!p) return false
  return !!(p.material || p.barcode || p.category)
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

// O'xshash mahsulotlar — piyoladagi kabi, HAQIQIY backend qidiruv
// endpointidan (bir xil category_id, "popular" saralash, joriy mahsulot
// chiqarib tashlanadi).
const { data: similarRes } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/search/`, {
  query: computed(() => ({
    type: 'stationery',
    category_id: product.value?.category_id || undefined,
    sort: 'popular',
    page: 1
  })),
  lazy: true,
  watch: [() => product.value?.category_id]
})

const similarProducts = computed(() => {
  const list = similarRes.value?.data || []
  return list.filter((p: any) => p.id !== product.value?.id).slice(0, 10)
})

const activeIndex = ref(0)
const mobileTrackEl = ref<HTMLElement | null>(null)
const desktopTrackEl = ref<HTMLElement | null>(null)
let scrollRaf = 0

watchEffect(() => {
  if (activeIndex.value >= galleryImages.value.length) {
    activeIndex.value = 0
  }
})

// Rang varianti tanlansa, uning o'z rasmi (variant galereyada yo'q — alohida
// surat) asosiy ko'rgazmada ko'rsatiladi va gallery indeksidan USTUN turadi.
// Miniatyuralar yoki oldingi/keyingi tugmalari bosilsa, bu holat tozalanadi.
const selectedVariantImage = ref<string | null>(null)
const selectedVariantId = ref<number | null>(null)

const activeImage = computed(() => selectedVariantImage.value || galleryImages.value[activeIndex.value] || galleryImages.value[0] || '/images/logo/logo_blue.png')

function onMobileTrackScroll() {
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  scrollRaf = requestAnimationFrame(() => {
    const el = mobileTrackEl.value
    if (!el || el.clientWidth === 0) return
    activeIndex.value = Math.round(el.scrollLeft / el.clientWidth)
  })
}

function onDesktopTrackScroll() {
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  scrollRaf = requestAnimationFrame(() => {
    const el = desktopTrackEl.value
    if (!el || el.clientWidth === 0) return
    activeIndex.value = Math.round(el.scrollLeft / el.clientWidth)
  })
}

function goToSlide(idx: number) {
  activeIndex.value = idx
  const dEl = desktopTrackEl.value
  if (dEl) {
    dEl.scrollTo({ left: idx * dEl.clientWidth, behavior: 'smooth' })
  }
  const mEl = mobileTrackEl.value
  if (mEl) {
    mEl.scrollTo({ left: idx * mEl.clientWidth, behavior: 'smooth' })
  }
}

function goToMobileSlide(idx: number) {
  goToSlide(idx)
}

function selectThumbnail(idx: number) {
  selectedVariantImage.value = null
  selectedVariantId.value = null
  goToSlide(idx)
}

function prevImage() {
  selectedVariantImage.value = null
  selectedVariantId.value = null
  const len = galleryImages.value.length
  if (len < 2) return
  goToSlide((activeIndex.value - 1 + len) % len)
}

function nextImage() {
  selectedVariantImage.value = null
  selectedVariantId.value = null
  const len = galleryImages.value.length
  if (len < 2) return
  goToSlide((activeIndex.value + 1) % len)
}

// Rang variantlari — tanlansa faqat ko'rgazma rasmi almashadi, narx/savat
// mantig'iga ta'sir qilmaydi (variant-level narxlash backendda yo'q).
function selectVariant(v: any) {
  selectedVariantId.value = v.id
  const img = v.image_medium_url || v.image_url || v.image_thumb_url
  selectedVariantImage.value = img ? resolveImg(img) : null
}

// Expandable text
const isDescExpanded = ref(false)
const specsOpen = ref(false)

// To'lov usuli (visual-only demo, no real installment/payment integration)
const paymentTab = ref<'installment' | 'cash'>('installment')
const installmentMonths = [3, 6, 12]
const selectedMonths = ref(12)

const currentPrice = computed(() => {
  if (!product.value) return 0
  const discountPrice = product.value.discountPrice ?? product.value.discount_price ?? 0
  const isDisc = discountPrice > 0 && discountPrice < product.value.price
  return isDisc ? discountPrice : product.value.price
})

const discountPercent = computed(() => {
  if (!product.value) return 0
  const base = Number(product.value.price || 0)
  const curr = Number(currentPrice.value || 0)
  if (base > 0 && curr < base) {
    return Math.round(((base - curr) / base) * 100)
  }
  return 0
})

const monthlyForSelected = computed(() => {
  return Math.ceil(Number(currentPrice.value || 0) * 1.44 / selectedMonths.value)
})

function formatPrice(val: number) {
  return (val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

const isAdded = ref(false)
let isAddedTimeout: any = null

function handleAddToCart() {
  if (product.value) {
    cartStore.addItem(product.value, 'stationery', 1)
    isAdded.value = true
    if (isAddedTimeout) clearTimeout(isAddedTimeout)
    isAddedTimeout = setTimeout(() => {
      isAdded.value = false
    }, 2000)
  }
}

function handleBuyNow() {
  if (product.value) {
    cartStore.addItem(product.value, 'stationery', 1)
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

// SEO & Schema.org JSON-LD Rich Snippet for Google
useSeoMeta({
  title: () => `${product.value?.name || 'Mahsulot'} — Kitobchi`,
  description: () => `${product.value?.name || 'Mahsulot'}. Tezkor yetkazib berish va arzon narxlar Kitobchi marketpleysida.`,
  ogTitle: () => `${product.value?.name || 'Mahsulot'} | Kitobchi`,
  ogImage: () => activeImage.value || '/images/logo/logo_blue.png',
  ogType: 'product' as any
})

useHead({
  script: [
    {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org/',
        '@type': 'Product',
        name: product.value?.name,
        image: activeImage.value,
        description: product.value?.description ? product.value.description.replace(/<[^>]*>?/gm, '') : product.value?.name,
        offers: {
          '@type': 'Offer',
          url: `https://kitobchi.com/stationery/${route.params.id}`,
          priceCurrency: 'UZS',
          price: currentPrice.value,
          availability: 'https://schema.org/InStock'
        }
      })
    }
  ]
})
</script>
