@extends('layouts.marketplace')

@php
    $titleName = $product->name . ($productType === 'book' && $product->author ? ' — ' . $product->author : '');
    $seoTitle = $titleName . ' | Kitobchi Marketpleysi';
    $rawDesc = strip_tags($product->description ?? $product->name);
    $seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $rawDesc)), 158, '…');
    $imgUrl = $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
    $discountField = $productType === 'book' ? $product->discountPrice : $product->discount_price;
    $isDiscounted = $discountField > 0 && $discountField < $product->price;
    $currentPrice = $isDiscounted ? $discountField : $product->price;
    $origPrice = $product->price;
    $discPct = $isDiscounted ? round((($origPrice - $currentPrice) / $origPrice) * 100) : 0;
    $inStock = $productType === 'book' ? ($product->count > 0) : ($product->stock > 0);
    $validTags = collect($product->tags ?? [])->filter(fn($t) => !empty(trim($t->name ?? '')) && trim($t->name) !== '#');
    $categoryName = $product->category ? ($product->category->name ?? $product->category->title_uz ?? null) : null;
    if ($categoryName && trim($categoryName) === '') { $categoryName = null; }
    $allImages = is_array($product->images) && count($product->images) > 0 ? $product->images : [$product->first_image];

    // ── Xususiyatlar (specs) — piyolamarket.uz'dagi "Xususiyatlar va
    // tavsif" bo'limiga o'xshash, lekin bizning kitob/kanselyariya
    // maydonlarimiz bilan (ilovada ko'rsatiladigan maydonlar). Muallif
    // yuqorida sarlavha ostida alohida ko'rsatilgani uchun bu yerga
    // qo'shilmaydi (piyolamarketda ham brend shunday — characteristics
    // ro'yxatidan tashqarida, alohida ko'rsatiladi).
    $specs = [];
    if ($productType === 'book') {
        if (!empty($product->translator)) $specs[] = ['label' => 'Tarjimon', 'value' => $product->translator];
        if (!empty($product->publisher?->name)) $specs[] = ['label' => 'Nashriyot', 'value' => $product->publisher->name];
        if (!empty($product->isbn)) $specs[] = ['label' => 'ISBN', 'value' => $product->isbn];
        if (!empty($product->pages)) $specs[] = ['label' => 'Sahifalar soni', 'value' => $product->pages . ' bet'];
        if (!empty($product->lang)) $specs[] = ['label' => 'Til', 'value' => $product->lang];
        if (!empty($product->langType)) $specs[] = ['label' => 'Yozuv turi', 'value' => $product->langType];
        if (!empty($product->coverType)) $specs[] = ['label' => 'Muqova turi', 'value' => $product->coverType];
        if (!empty($product->year)) $specs[] = ['label' => 'Nashr yili', 'value' => $product->year];
        if (!empty($product->artikul)) $specs[] = ['label' => 'Artikul', 'value' => $product->artikul];
    } else {
        if (!empty($product->material)) $specs[] = ['label' => 'Material', 'value' => $product->material];
        if (!empty($categoryName)) $specs[] = ['label' => 'Kategoriya', 'value' => $categoryName];
        if (!empty($product->barcode)) $specs[] = ['label' => 'Shtrix-kod', 'value' => $product->barcode];
        if (!empty($product->artikul)) $specs[] = ['label' => 'Artikul', 'value' => $product->artikul];
    }
    $hasVariants = $productType === 'stationery' && $product->relationLoaded('variants') && $product->variants->isNotEmpty();
@endphp

@section('title', $seoTitle)

@push('meta')
    @include('partials.seo-social', [
        'title' => $seoTitle,
        'description' => $seoDesc,
        'canonical' => $canonicalUrl,
        'ogImage' => $imgUrl,
        'ogType' => 'product',
        'productPrice' => $currentPrice,
        'productAvailability' => $inStock ? 'in stock' : 'out of stock',
    ])
@endpush

@push('styles')
<style>
    /* Piyolamarketdagidek: mahsulot sahifasida "Buyurtma berish" bloki
       mobil ekranda pastga mahkamlanadi (pastki navigatsiya kcIsProductPage
       o'zgaruvchisi yordamida marketplace.blade.php'da yashiriladi).
       Desktop'da (md+) hech narsa o'zgarmaydi — blok o'z joyida, narx
       kartasi ichida qoladi. */
    .kc-buybar-mobile {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 60;
        background: #fff;
        margin: 0;
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
        box-shadow: 0 -8px 24px rgba(15,23,42,0.12);
        border-top: 1px solid #e5e7eb;
        border-radius: 1.25rem 1.25rem 0 0;
    }
    @media (min-width: 768px) {
        .kc-buybar-mobile {
            position: static;
            left: auto;
            right: auto;
            bottom: auto;
            z-index: auto;
            background: transparent;
            margin: 0;
            padding: 0;
            box-shadow: none;
            border-top: none;
            border-radius: 0;
        }
    }

    /* Xaridorlar sharhlari — mobil'da gorizontal skroll, desktop'da grid. */
    .kc-reviews-scroll {
        display: flex;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
    }
    .kc-review-card {
        flex: 0 0 280px;
        scroll-snap-align: start;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1.25rem;
        padding: 1.25rem;
    }
    @media (min-width: 1024px) {
        .kc-reviews-scroll {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            overflow-x: visible;
        }
        .kc-review-card { flex: none; }
    }
</style>
@endpush

@section('content')
<div class="py-6 min-h-dvh">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">

        <!-- ====== BREADCRUMBS & TOP ACTIONS (PiyolaMarket style) ====== -->
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ url('/') }}" onclick="history.length > 1 ? (event.preventDefault(), history.back()) : null"
                   class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300 shrink-0" title="Orqaga">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                </a>
                <nav aria-label="breadcrumb" class="relative min-w-0">
                    <ol class="flex items-center gap-2 flex-wrap text-sm text-[#8F8FA1]">
                        <li>
                            <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">Asosiy</a>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li>
                            <a href="{{ route('web.catalog') }}" class="hover:text-neutral-900 transition-colors">Katalog</a>
                        </li>
                        @if($categoryName)
                            <li class="text-gray-300">/</li>
                            <li>
                                <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="hover:text-neutral-900 transition-colors">{{ $categoryName }}</a>
                            </li>
                        @endif
                        <li class="text-gray-300">/</li>
                        <li class="text-neutral-900 font-semibold truncate max-w-[240px]">
                            {{ $product->name }}
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Top right favorite button -->
            <button aria-label="Sevimlilar" data-fav="{{ $isFavorited ? '1' : '0' }}"
                    onclick="toggleFavorite(this, {{ $product->id }}, '{{ $productType }}');"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-secondary-200 shadow-sm hover:bg-neutral-50 transition-all shrink-0 cursor-pointer">
                <svg class="kc-heart-icon" viewBox="0 0 24 24" style="width:20px;height:20px;"
                     fill="{{ $isFavorited ? '#ef4444' : 'none' }}" stroke="{{ $isFavorited ? '#ef4444' : '#374151' }}" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
            </button>
        </div>

        <!-- ====== MAIN PRODUCT AREA (PiyolaMarket 1:1 Layout) ====== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start" id="kcProductMain">
            <!-- ====== LEFT: IMAGE GALLERY ====== -->
            <div class="w-full">
                <div class="flex flex-col-reverse md:flex-row gap-4 items-start">
                    <!-- Thumbnail strip -->
                    @if(count($allImages) > 1)
                    <div class="flex md:flex-col gap-3 overflow-x-auto md:overflow-y-auto scrollbar-hide py-1 shrink-0 w-full md:w-auto">
                        @foreach($allImages as $tIdx => $tImg)
                        <button onclick="kcGotoSlide({{ $tIdx }})" aria-label="gallery-thumb-{{ $tIdx }}" class="{{ $tIdx === 0 ? 'border-primary' : 'border-transparent' }} hover:border-neutral-300 relative shrink-0 w-[75px] h-[100px] rounded-2xl overflow-hidden border-2 transition-all duration-200 cursor-pointer bg-[#F6F7F9]" id="kcThumb_{{ $tIdx }}">
                            <img src="{{ $tImg ? asset('storage/' . $tImg) : $imgUrl }}" class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif

                    <!-- Main image card -->
                    <div class="flex-1 w-full relative rounded-3xl overflow-hidden bg-[#F6F7F9] aspect-[3/4] flex items-center justify-center">
                        <div id="kcImgTrack" class="flex flex-row w-full h-full" style="transition:transform 0.5s cubic-bezier(0.16,1,0.3,1);">
                            @foreach($allImages as $imgIdx => $imgPath)
                            <div class="min-w-full w-full h-full flex items-center justify-center shrink-0">
                                <img src="{{ $imgPath ? asset('storage/' . $imgPath) : $imgUrl }}" class="w-full h-full object-cover" id="{{ $imgIdx === 0 ? 'kcMainImg' : 'kcImg_' . $imgIdx }}">
                            </div>
                            @endforeach
                        </div>

                        <!-- Discount badge (bottom-left) -->
                        @if($isDiscounted)
                            <div class="absolute bottom-4 left-4 z-20">
                                <span class="inline-flex items-center text-xs font-bold rounded-lg text-white px-2.5 py-1 bg-[#ED3131]">
                                    -{{ $discPct }}%
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ====== RIGHT: PRODUCT INFO ====== -->
            <div class="w-full flex flex-col gap-5">
                <!-- Stock & Category Badges -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full bg-neutral-100 text-neutral-700">
                        {{ $productType === 'book' ? 'Kitob' : 'Kanselyariya' }}
                    </span>
                    @if($categoryName)
                        <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full bg-primary/10 text-primary no-underline hover:bg-primary/20 transition-colors">
                            {{ $categoryName }}
                        </a>
                    @endif
                    @if($inStock)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-600">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                            Sotuvda mavjud
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-600">
                            Vaqtinchalik tugagan
                        </span>
                    @endif
                </div>

                <!-- Title -->
                <h1 class="text-2xl lg:text-3xl font-bold text-neutral-900 leading-snug">
                    {{ $product->name }}
                </h1>

                <!-- Author (for books) -->
                @if($productType === 'book' && !empty($product->author) && $product->author !== 'null')
                    <div class="text-sm text-neutral-500 font-medium">
                        Muallif: <a href="{{ route('web.catalog', ['search' => $product->author]) }}" class="font-bold text-primary hover:underline">{{ $product->author }}</a>
                    </div>
                @endif

                <!-- Rating -->
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-amber-500 font-bold">★ 5.00</span>
                    <span class="text-neutral-400">· {{ $product->ugc_reviews_count ?: 1 }} Sharhlar</span>
                </div>

                <!-- Price row -->
                <div>
                    <div class="text-xs text-neutral-400 font-medium mb-1">Narxi</div>
                    <div class="flex items-baseline gap-3">
                        <span class="text-2xl lg:text-3xl font-bold text-neutral-900">{{ number_format($currentPrice) }} so'm</span>
                        @if($isDiscounted)
                            <span class="text-base text-neutral-400 line-through">{{ number_format($origPrice) }} so'm</span>
                            <span class="text-xs font-bold text-[#ED3131] bg-[#ED3131]/10 px-2 py-0.5 rounded-md">-{{ $discPct }}%</span>
                        @endif
                    </div>
                </div>

                <!-- ====== XUSUSIYATLAR VA TAVSIF ACCORDION (Above Payment) ====== -->
                @if(!empty($specs) || $hasVariants)

        <!-- ====== XARIDORLAR SHARHLARI (piyolamarket.uz uslubida) ======
             $ugcReviews — BookClub'dagi shu mahsulotga yozilgan postlar
             (ProductCatalogController'da allaqachon olib kelinardi, lekin
             hech qayerda ko'rsatilmasdi — endi shu yerda chiqariladi). AI
             tomonidan yashirilgan (is_hidden_by_ai) yoki o'chirilgan
             postlar controller darajasida allaqachon filtrlangan. Har bir
             postning o'ziga xos yulduzcha reytingi yo'q (faqat AI sifat
             bahosi bor, u FOYDALANUVCHI reytingi emas) — shu sabab bu yerda
             yulduz o'ylab topilmaydi, faqat matn/rasm/muallif ko'rsatiladi;
             umumiy ★ reyting yuqorida (mahsulot darajasida) allaqachon bor. -->
        @if($ugcReviews->isNotEmpty())
            <section style="margin-top:2.5rem;">
                <div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:1.25rem;">
                    <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:600;color:var(--kc-primary,#0b0342);margin:0;">
                        {{ __('marketplace.reviews_title') }}
                    </h2>
                    <span style="font-size:1rem;font-weight:600;color:#9ca3af;">({{ $ugcReviews->count() }})</span>
                </div>

                <div class="kc-reviews-scroll">
                    @foreach($ugcReviews as $review)
                        @php
                            $ruName = $review->user->name ?? null;
                            $ruPhone = $review->user->phone_number ?? null;
                            $ruLabel = $ruName ?: ($ruPhone ? \Illuminate\Support\Str::mask($ruPhone, '*', 4, 4) : 'Foydalanuvchi');
                            $ruInitial = mb_strtoupper(mb_substr($ruLabel, 0, 1));
                            $ruAvatar = $review->user->avatar ?? null;
                            $ruDate = optional($review->created_at)->translatedFormat('d M Y');
                        @endphp
                        <div class="kc-review-card">
                            <div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:0.75rem;">
                                @if($ruAvatar)
                                    <img src="{{ asset('storage/' . $ruAvatar) }}" alt="{{ $ruLabel }}" style="width:2.5rem;height:2.5rem;border-radius:9999px;object-fit:cover;flex-shrink:0;">
                                @else
                                    <div style="width:2.5rem;height:2.5rem;border-radius:9999px;background:var(--color-tima-100,#e8eaef);color:var(--color-tima-500);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex-shrink:0;">
                                        {{ $ruInitial }}
                                    </div>
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-weight:700;font-size:0.875rem;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ruLabel }}</div>
                                    @if($ruDate)
                                        <div style="font-size:0.75rem;color:#9ca3af;">{{ $ruDate }}</div>
                                    @endif
                                </div>
                            </div>

                            @if(trim((string) $review->text) !== '')
                                <p style="font-size:0.875rem;line-height:1.6;color:#374151;margin:0 0 0.75rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;">
                                    {{ $review->text }}
                                </p>
                            @endif

                            @if($review->images->isNotEmpty())
                                <div style="display:flex;gap:0.375rem;overflow-x:auto;">
                                    @foreach($review->images->take(4) as $img)
                                        <img src="{{ asset('storage/' . $img->image) }}" alt="" loading="lazy" style="width:4.5rem;height:4.5rem;border-radius:0.75rem;object-fit:cover;flex-shrink:0;">
                                    @endforeach
                                </div>
                            @endif

                            {{-- Book club'dagi like/izoh sonlari — ilovadagi kabi
                                 like = "foydali", izoh soni = shu postga yozilgan
                                 javoblar (BookClubComment) miqdori. --}}
                            @if(($review->likes_count ?? 0) > 0 || ($review->comments_count ?? 0) > 0)
                                <div style="display:flex;align-items:center;gap:1rem;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid #f3f4f6;">
                                    @if(($review->likes_count ?? 0) > 0)
                                        <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.75rem;font-weight:600;color:#ef4444;">
                                            <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                                            {{ __('marketplace.reviews_helpful', ['count' => $review->likes_count]) }}
                                        </span>
                                    @endif
                                    @if(($review->comments_count ?? 0) > 0)
                                        <span style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.75rem;font-weight:600;color:#9ca3af;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.97-4.03 9-9 9-1.5 0-2.91-.37-4.15-1.02L3 21l1.05-3.16A8.96 8.96 0 013 12c0-4.97 4.03-9 9-9s9 4.03 9 9z"/></svg>
                                            {{ __('marketplace.reviews_comments_count', ['count' => $review->comments_count]) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- ====== MAHSULOT HAQIDA (piyolamarket.uz uslubida) ======
             Piyolada Sharhlar bo'limidan keyin, alohida "Mahsulot haqida"
             degan bo'lim bor — mahsulotning to'liq tavsifi (uzun bo'lsa,
             pastdan fade + "Batafsil ko'rib chiqing" tugmasi bilan
             yig'ilgan holda). Bizda $product->description ALLAQACHON bor
             edi, lekin faqat SEO meta teglarida ishlatilardi — sahifada
             HECH QAYERDA ko'rsatilmasdi. Endi shu yerda chiqariladi. -->
        @if(!empty(trim(strip_tags((string) $product->description))))
            <section style="margin-top:2.5rem;">
                <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:600;color:var(--kc-primary,#0b0342);margin:0 0 1rem;">
                    {{ __('marketplace.about_product_title') }}
                </h2>
                <div style="position:relative;">
                    <div id="kcProductDescBody" class="kc-product-desc-body" style="background:#fff;border:1px solid #f1f5f9;border-radius:1.25rem;padding:1.5rem;font-size:0.9375rem;line-height:1.75;color:#374151;max-height:9.5rem;overflow:hidden;">
                        {!! $product->description !!}
                    </div>
                    <div id="kcProductDescFade" style="position:absolute;left:0;right:0;bottom:0;height:5rem;background:linear-gradient(to bottom, rgba(255,255,255,0) 0%, #fff 85%);border-radius:0 0 1.25rem 1.25rem;pointer-events:none;"></div>
                    <div style="text-align:center;margin-top:-0.5rem;position:relative;">
                        <button type="button" id="kcProductDescToggle" onclick="kcToggleProductDesc()" style="background:#fff;color:#111827;padding:0.75rem 2rem;border-radius:9999px;font-weight:600;font-size:0.875rem;box-shadow:0 4px 16px rgba(15,23,42,0.14);border:1px solid #f1f5f9;cursor:pointer;">
                            {{ __('marketplace.about_product_expand') }}
                        </button>
                    </div>
                </div>
            </section>
        @endif

        <!-- ====== O'XSHASH MAHSULOTLAR ====== -->
        @if($similarProducts->isNotEmpty())
            <section style="margin-top:2.5rem;">
                <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:600;color:var(--kc-primary,#0b0342);margin:0 0 1rem;">
                    {{ __('marketplace.similar_products_title') }}
                </h2>
                <div id="kcSimilarGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;">
                    @foreach($similarProducts as $sim)
                        @php
                            $simType = $sim->type_label ?? $productType;
                            $simIsStationery = $simType === 'stationery';
                            $simSlug = \Illuminate\Support\Str::slug($sim->name);
                            $simUrl = $simIsStationery
                                ? route('web.stationery.show', ['id' => $sim->id, 'slug' => $simSlug])
                                : route('web.books.show', ['id' => $sim->id, 'slug' => $simSlug]);
                            $simImg = $sim->first_image ? asset('storage/' . $sim->first_image) : asset('images/logo/logo_blue.png');
                            $simRawPrice = (float) $sim->price;
                            $simDiscRaw = $simIsStationery ? (float) $sim->discount_price : (float) $sim->discountPrice;
                            $simIsDisc = $simDiscRaw > 0 && $simDiscRaw < $simRawPrice;
                            $simPrice = $simIsDisc ? $simDiscRaw : $simRawPrice;
                            $simDiscPct = $simIsDisc ? round((($simRawPrice - $simPrice) / $simRawPrice) * 100) : 0;
                            $simIsFav = in_array($sim->id, $favoritedSimilarIds ?? [], true);
                        @endphp
                        <a href="{{ $simUrl }}" class="kc-product-card">
                            <div class="kc-product-card-img">
                                <img src="{{ $simImg }}" alt="{{ $sim->name }}" loading="lazy">
                                @if($simIsDisc)
                                    <span class="kc-discount-badge">-{{ $simDiscPct }}%</span>
                                @endif
                                <div class="kc-fav-btn-wrap">
                                    <button aria-label="Sevimlilar" data-fav="{{ $simIsFav ? '1' : '0' }}"
                                            onclick="event.preventDefault(); toggleFavorite(this, {{ $sim->id }}, '{{ $simIsStationery ? 'stationery' : 'book' }}');"
                                            class="kc-fav-btn">
                                        <svg class="kc-heart-icon" viewBox="0 0 24 24" style="width:16px;height:16px;"
                                             fill="{{ $simIsFav ? '#ef4444' : 'none' }}" stroke="{{ $simIsFav ? '#ef4444' : '#374151' }}" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="kc-product-card-body">
                                <div class="kc-product-card-title">{{ $sim->name }}</div>
                                <div class="kc-product-card-price">{{ number_format($simPrice) }} {{ __('marketplace.currency') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

<!-- ====== "BU MENGA MOSMI?" MODAL (bottom-sheet, mobil ilovadagi kabi) ======
     $readingIntelligence['sheet'] — bo'limlar (match/interest_match/
     discovery/quality/reminder — birinchi mos kelgani, hech qachon bir
     nechtasi birga emas) + traits (qiyinlik/kayfiyat/kimlar uchun) +
     similar (yana yoqishi mumkin) + guest_cta (mehmon uchun kirish taklifi).
     Barchasi allaqachon serverda tarjima qilingan holda keladi
     (ReadingIntelligenceService), shuning uchun bu yerda faqat chiqariladi. -->
@if($productType === 'book' && !empty($readingIntelligence))
    @php $riSheet = $readingIntelligence['sheet'] ?? []; @endphp
    <div id="kcMoslikOverlay" class="kc-modal-overlay" onclick="if(event.target===this) kcCloseMoslikModal()" style="align-items:flex-end;">
        <div class="kc-moslik-panel">
            <div class="kc-moslik-grabber"></div>
            <button onclick="kcCloseMoslikModal()" aria-label="{{ __('marketplace.close') }}" style="position:absolute;top:1rem;right:1rem;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border:none;background:#f3f4f6;border-radius:9999px;cursor:pointer;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2.5"><path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
            </button>

            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;padding-right:2rem;">
                <div style="width:2.75rem;height:2.75rem;border-radius:0.875rem;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $riIcon !!}</svg>
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:800;font-size:1.0625rem;color:#111827;">{{ $readingIntelligence['teaser']['title'] }}</div>
                    <div style="font-size:0.8125rem;color:#6b7280;">{{ $readingIntelligence['teaser']['subtitle'] }}</div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:1rem;">
                @foreach(['match', 'interest_match', 'discovery', 'quality', 'reminder'] as $riKey)
                    @if(!empty($riSheet[$riKey]))
                        @php $riBlock = $riSheet[$riKey]; @endphp
                        <div style="padding:1rem;background:#f8fafc;border-radius:1rem;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:0.375rem;">
                                <span style="font-weight:700;font-size:0.875rem;color:#111827;">{{ $riBlock['label'] }}</span>
                                @if(isset($riBlock['percent']))
                                    <span style="font-weight:800;font-size:1.125rem;color:#7c3aed;flex-shrink:0;">{{ $riBlock['percent'] }}%</span>
                                @endif
                            </div>
                            @if(!empty($riBlock['reason']))
                                <p style="font-size:0.8125rem;color:#4b5563;line-height:1.6;margin:0;">{{ $riBlock['reason'] }}</p>
                            @endif
                            @if(!empty($riBlock['detail']))
                                <p style="font-size:0.8125rem;color:#4b5563;line-height:1.6;margin:0;">{{ $riBlock['detail'] }}</p>
                            @endif
                        </div>
                    @endif
                @endforeach

                @if(!empty($riSheet['traits']))
                    @php $riTraits = $riSheet['traits']; @endphp
                    <div style="padding:1rem;background:#fff;border:1px solid #f1f5f9;border-radius:1rem;">
                        @if(!empty($riTraits['difficulty']))
                            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.625rem;">
                                <span style="font-size:0.6875rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.02em;">{{ __('reading_intelligence.section_difficulty') }}</span>
                                <span style="font-size:0.8125rem;font-weight:600;color:#111827;background:#eef2ff;padding:0.1875rem 0.625rem;border-radius:9999px;">{{ $riTraits['difficulty'] }}</span>
                            </div>
                        @endif
                        @if(!empty($riTraits['mood']))
                            <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:0.625rem;">
                                @foreach($riTraits['mood'] as $riMood)
                                    <span style="font-size:0.75rem;font-weight:600;color:#059669;background:#ecfdf5;padding:0.1875rem 0.625rem;border-radius:9999px;">{{ $riMood }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($riTraits['audience_fit']))
                            <p style="font-size:0.8125rem;color:#4b5563;line-height:1.6;margin:0;">{{ $riTraits['audience_fit'] }}</p>
                        @endif
                    </div>
                @endif

                @if(!empty($riSheet['similar']))
                    <div>
                        <div style="font-size:0.8125rem;font-weight:700;color:#111827;margin-bottom:0.625rem;">{{ $riSheet['similar_label'] ?? __('reading_intelligence.section_similar') }}</div>
                        <div style="display:flex;flex-direction:column;gap:0.5rem;">
                            @foreach($riSheet['similar'] as $riSim)
                                <a href="{{ route('web.books.show', ['id' => $riSim['id'], 'slug' => \Illuminate\Support\Str::slug($riSim['name'])]) }}" style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.625rem 0.75rem;background:#f8fafc;border-radius:0.75rem;text-decoration:none;">
                                    <span style="font-size:0.8125rem;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $riSim['name'] }}</span>
                                    <span style="font-size:0.75rem;font-weight:600;color:#7c3aed;flex-shrink:0;">{{ $riSim['score'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($riSheet['guest_cta']))
                    <div style="padding:1rem;background:#fffbeb;border:1px solid #fde68a;border-radius:1rem;">
                        <div style="font-weight:700;font-size:0.875rem;color:#92400e;margin-bottom:0.25rem;">{{ $riSheet['guest_cta']['label'] }}</div>
                        <p style="font-size:0.8125rem;color:#92400e;line-height:1.6;margin:0 0 0.75rem;">{{ $riSheet['guest_cta']['detail'] }}</p>
                        <button type="button" onclick="kcCloseMoslikModal(); if (typeof openAuthModal === 'function') openAuthModal();" style="font-size:0.8125rem;font-weight:700;color:#fff;background:#f59e0b;border:none;padding:0.5rem 1rem;border-radius:0.625rem;cursor:pointer;">
                            {{ __('marketplace.login') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

<style>
    @media(min-width: 640px) {
        #kcSimilarGrid { grid-template-columns: repeat(3, 1fr) !important; gap: 0.75rem !important; }
    }
    @media(min-width: 1024px) {
        #kcSimilarGrid { grid-template-columns: repeat(4, 1fr) !important; gap: 1rem !important; }
    }
    @media(min-width: 1280px) {
        #kcSimilarGrid { grid-template-columns: repeat(5, 1fr) !important; gap: 1.25rem !important; }
    }

    /* "Bu menga mosmi?" bottom-sheet — mobil'da pastdan chiqadi (ilovadagi
       kabi), desktop'da (sm+) markazlashgan oddiy modal kartaga aylanadi. */
    .kc-moslik-panel {
        position: relative;
        width: 100%;
        max-width: 480px;
        max-height: 85vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 1.5rem 1.5rem 0 0;
        padding: 1.5rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom, 0px));
        box-shadow: 0 -8px 40px rgba(0,0,0,0.18);
    }
    .kc-moslik-grabber {
        width: 2.5rem;
        height: 0.25rem;
        background: #e5e7eb;
        border-radius: 9999px;
        margin: 0 auto 1rem;
    }
    @media (min-width: 640px) {
        #kcMoslikOverlay.active { align-items: center !important; }
        .kc-moslik-panel { border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .kc-moslik-grabber { display: none; }
    }
</style>

@push('scripts')
<script>
    let kcCurrentSlide = 0;
    const kcTotal = {{ count($allImages) }};

    function kcGotoSlide(idx) {
        kcCurrentSlide = idx;
        const track = document.getElementById('kcImgTrack');
        if (track) track.style.transform = `translateX(-${idx * 100}%)`;

        // Update dots
        for (let i = 0; i < kcTotal; i++) {
            const dot = document.getElementById('kcDot_' + i);
            if (dot) {
                dot.style.width = i === idx ? '16px' : '6px';
                dot.style.background = i === idx ? '#010101' : '#d1d5db';
            }
            const thumb = document.getElementById('kcThumb_' + i);
            if (thumb) {
                thumb.style.borderColor = i === idx ? 'var(--color-tima-500)' : '#e5e7eb';
            }
        }
    }

    function kcToggleSpecs() {
        const body = document.getElementById('kcSpecsBody');
        const chev = document.getElementById('kcSpecsChevron');
        if (!body) return;
        const willOpen = body.classList.contains('hidden');
        if(willOpen) {
            body.classList.remove('hidden');
            if (chev) chev.style.transform = 'rotate(180deg)';
        } else {
            body.classList.add('hidden');
            if (chev) chev.style.transform = 'rotate(0deg)';
        }
    }

    function togglePaymentTab(tab) {
        const tInst = document.getElementById('tab-installment');
        const tCash = document.getElementById('tab-cash');
        const vInst = document.getElementById('view-installment');
        const vCash = document.getElementById('view-cash');

        if(tab === 'installment') {
            tInst.className = 'text-sm px-4 py-2 text-gray-900 bg-white shadow-sm flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap';
            tCash.className = 'text-sm px-4 py-2 text-gray-400 hover:text-gray-500 flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap';
            vInst.style.display = 'flex';
            vCash.style.display = 'none';
        } else {
            tCash.className = 'text-sm px-4 py-2 text-gray-900 bg-white shadow-sm flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap';
            tInst.className = 'text-sm px-4 py-2 text-gray-400 hover:text-gray-500 flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap';
            vCash.style.display = 'flex';
            vInst.style.display = 'none';
        }
    }

    // ── Muddatli to'lov: haqiqiy hisob-kitob ──────────────────────────
    // Avval bu yerda doim price*1.44/12 (taxminiy, 12 oy deb qattiq yozilgan)
    // ko'rsatilar, "6 oy"/"12 oy" tugmalari esa hech narsaga ulanmagan edi.
    // Endi backend'ning haqiqiy, autentifikatsiyasiz split-preview API'sidan
    // (SplitScheduleService orqali hisoblangan) haqiqiy oylik summa olinadi,
    // va tugmalar shu haqiqiy rejalar orasida almashtiradi. Agar nasiya shu
    // mahsulot/kategoriya uchun yoqilmagan bo'lsa, "Muddatli to'lov" tabi
    // butunlay yashiriladi (soxta raqam ko'rsatilmaydi) — faqat "Naqd to'lov"
    // qoladi.
    let kcSplitPlans = [];

    function kcLoadSplitPreview() {
        const amount = {{ (int) $currentPrice }};
        const params = new URLSearchParams({
            amount: amount,
            product_type: '{{ $productType }}',
            product_id: '{{ $product->id }}',
        });

        fetch('/api/v1/kitobchi/split-preview?' + params.toString())
            .then(r => r.json())
            .then(res => {
                const data = res.data || {};
                if (!data.enabled || !Array.isArray(data.plans) || data.plans.length === 0) {
                    kcHideInstallmentOption();
                    return;
                }
                kcSplitPlans = data.plans;
                // 12 oylikni afzal ko'ramiz, bo'lmasa birinchi mavjud reja.
                const preferred = kcSplitPlans.find(p => p.months === 12) || kcSplitPlans[0];
                kcRenderPlanPills(preferred.months);
                kcApplyPlan(preferred);
            })
            .catch(() => kcHideInstallmentOption());
    }

    function kcHideInstallmentOption() {
        const tabInst = document.getElementById('tab-installment');
        if (tabInst) tabInst.style.display = 'none';
        togglePaymentTab('cash');
    }

    function kcRenderPlanPills(activeMonths) {
        const wrap = document.getElementById('kcPlanPills');
        if (!wrap) return;
        const pills = kcSplitPlans.map(p => {
            const active = p.months === activeMonths;
            const cls = active
                ? 'relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-900 bg-white shadow-sm'
                : 'relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-400 hover:text-gray-500';
            return `<button type="button" class="${cls}" onclick="kcSelectPlanByMonths(${p.months})">${p.months} oy</button>`;
        }).join('');
        wrap.innerHTML = `<div class="relative inline-flex bg-secondary-300 rounded-xl p-1">${pills}</div>`;
    }

    function kcSelectPlanByMonths(months) {
        const plan = kcSplitPlans.find(p => p.months === months);
        if (!plan) return;
        kcRenderPlanPills(months);
        kcApplyPlan(plan);
    }

    function kcApplyPlan(plan) {
        const el = document.getElementById('kcMonthlyPrice');
        if (el) el.textContent = new Intl.NumberFormat('uz').format(plan.regular_payment);
    }

    document.addEventListener('DOMContentLoaded', kcLoadSplitPreview);

    // "Bu menga mosmi?" bottom-sheet — mavjud kcCartOverlay/kcAuthModalOverlay
    // bilan bir xil ochish/yopish naqshi (.kc-modal-overlay + .active klassi).
    function kcOpenMoslikModal() {
        const modal = document.getElementById('kcMoslikOverlay');
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function kcCloseMoslikModal() {
        const modal = document.getElementById('kcMoslikOverlay');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // "Mahsulot haqida" — piyolamarket'dagi kabi uzun tavsiflar dastlab
    // yig'ilgan (max-height + pastdan oq fade) holda ko'rsatiladi, "Batafsil
    // ko'rib chiqing" bosilganda to'liq ochiladi. Agar tavsif QISQA bo'lib,
    // umuman kesilmasa (scrollHeight <= clientHeight) — fade va tugma
    // ko'rsatilmaydi, chunki "ko'proq ko'rsatish" uchun hech narsa yo'q.
    function kcToggleProductDesc() {
        const body = document.getElementById('kcProductDescBody');
        const fade = document.getElementById('kcProductDescFade');
        const btn = document.getElementById('kcProductDescToggle');
        if (!body) return;
        const isExpanded = body.style.maxHeight === 'none';
        body.style.maxHeight = isExpanded ? '9.5rem' : 'none';
        if (fade) fade.style.display = isExpanded ? 'block' : 'none';
        if (btn) btn.textContent = isExpanded
            ? @json(__('marketplace.about_product_expand'))
            : @json(__('marketplace.about_product_collapse'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const body = document.getElementById('kcProductDescBody');
        const fade = document.getElementById('kcProductDescFade');
        const btn = document.getElementById('kcProductDescToggle');
        if (body && body.scrollHeight <= body.clientHeight + 4) {
            if (fade) fade.style.display = 'none';
            if (btn) btn.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
