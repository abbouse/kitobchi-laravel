@php
    // MUHIM: bu partial avval faqat kitob uchun yozilgan edi (hardcoded
    // web.books.show route va $book->discountPrice). Endi sevimlilar
    // sahifasi ham shu kartani stationery (kanselyariya) uchun ham
    // ishlatadi — shuning uchun $isStationery orqali ikkalasini ham
    // to'g'ri qo'llab-quvvatlaydi (aks holda kanselyariya mahsuloti
    // noto'g'ri — hatto boshqa bir kitobning — sahifasiga olib borardi).
    $isStationery = $isStationery ?? false;

    $slug = \Illuminate\Support\Str::slug($book->name);
    $url = $isStationery
        ? route('web.stationery.show', ['id' => $book->id, 'slug' => $slug])
        : route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
    $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');

    $basePrice = floatval($book->price);
    $discPrice = $isStationery ? floatval($book->discount_price ?? 0) : floatval($book->discountPrice ?? 0);

    $isDisc = $discPrice > 0 && $basePrice > 0 && $discPrice < $basePrice;
    $price = $isDisc ? $discPrice : $basePrice;
    $discPct = $isDisc ? round((($basePrice - $price) / $basePrice) * 100) : 0;

    // Oyiga taxminiy narx — product/show.blade.php'dagi bilan bir xil formula
    // (piyolamarket.uz'ning haqiqiy karta-darajasidagi ko'rsatkichiga mos
    // keladi: masalan 800 000 so'm -> 96 000 so'm/oyiga = narx*1.44/12).
    // Kartalar ro'yxatida har biriga alohida split-preview API so'rovi
    // yubormaymiz (ko'p va sekin bo'lardi) — mahsulot sahifasida esa haqiqiy
    // SplitPlan asosida hisoblangan aniq summa ko'rsatiladi.
    $monthly = ceil($price * 1.44 / 12);

    // Chaqiruvchi sahifa xohlasa aniq isFav/showFavButton uzatishi mumkin
    // (masalan sevimlilar sahifasida bu karta har doim sevimli — va u
    // sahifa o'zining alohida "olib tashlash" tugmasini ko'rsatadi, shu
    // sabab bu yerdagi standart yurak tugmasi ikki marta chiqmasligi
    // uchun yashiriladi).
    $showFavButton = $showFavButton ?? true;
    $isFav = $isFav ?? in_array($book->id, $favoritedBookIds ?? [], true);
@endphp

<a class="group relative flex flex-col rounded-xl bg-white border border-white hover:shadow-md transition-all duration-200 overflow-hidden" href="{{ $url }}">
    <div class="relative w-full rounded-xl bg-white" style="aspect-ratio: 232 / 309;">
        <div class="focus:outline-none h-full relative z-0">
            <div class="overflow-hidden h-full">
                <div class="flex items-start flex-row h-full">
                    <div class="min-w-0 shrink-0 basis-full aspect-[3/4] overflow-hidden">
                        <div class="w-full h-full rounded-xl overflow-hidden bg-gray-50 flex items-center justify-center">
                            <img alt="{{ $book->name }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" src="{{ $img }}"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isDisc)
            <div class="absolute bottom-1.5 left-1.5 md:bottom-2 md:left-2 z-20 inline-flex items-start flex-col gap-1">
                <span class="font-medium inline-flex items-center text-xs gap-1 rounded-md text-inverted px-1 py-0.5 bg-[#ED3131] text-white">
                    -{{ $discPct }}%
                </span>
            </div>
        @endif

        @if($showFavButton)
        <div class="absolute top-1.5 right-1.5 md:top-2 md:right-2 z-20">
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm glass-card-bg p-0!">
                <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
                <button aria-label="Sevimlilar" data-fav="{{ $isFav ? '1' : '0' }}" onclick="event.preventDefault(); toggleFavorite(this, {{ $book->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');" class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 text-gray-500 hover:text-gray-900">
                    {{-- MUHIM: avval <iconify-icon> web-komponenti ishlatilardi — u
                         runtime'da tashqi API'dan SVG olib kelishga tayanadi, va bu
                         yerda hech qachon chizilmasdi (yurak ikonkasi butunlay
                         ko'rinmas edi, har bir mahsulot kartasida). Endi tayyor
                         inline SVG (heroicons heart) — hech qanday tashqi so'rovga
                         muhtoj emas, doim ko'rinadi. toggleFavorite() shu SVG'ning
                         fill/stroke'ini almashtiradi (marketplace.blade.php). --}}
                    <svg class="kc-heart-icon w-5 h-5 relative z-10 transition-colors" viewBox="0 0 24 24"
                         fill="{{ $isFav ? '#ef4444' : 'none' }}" stroke="{{ $isFav ? '#ef4444' : 'currentColor' }}" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif
    </div>

    <div class="px-1 pt-4 pb-4 flex flex-col h-full">
        <div class="group/title grow">
            <div class="text-sm leading-snug line-clamp-2 transition-colors duration-300 group-hover:text-primary-600 text-neutral-900 dark:text-white">
                {{ $book->name }}
            </div>
        </div>
        <div class="flex items-start justify-between gap-2 mt-2">
            <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 font-semibold leading-tight">
                {{ number_format($price, 0, '', ' ') }} so'm
            </p>
        </div>
        <div class="mt-auto">
            <span class="inline-block px-2 py-0.5 text-xs md:text-sm font-medium bg-primary-100 text-primary-500 rounded-full my-1">
                {{ number_format($monthly, 0, '', ' ') }} so'm/oyiga
            </span>
        </div>
    </div>
</a>
