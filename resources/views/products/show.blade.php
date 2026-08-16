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
<div style="min-height:100dvh;padding:1.5rem 0;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        <!-- ====== BREADCRUMBS (PiyolaMarket style) ====== -->
        <div style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <a href="{{ url('/') }}" onclick="history.length > 1 ? (event.preventDefault(), history.back()) : null"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem 0.375rem 0.5rem;border-radius:0.375rem;font-size:0.875rem;font-weight:500;color:var(--color-tima-500);background:none;border:none;cursor:pointer;text-decoration:none;transition:color 0.2s;">
                    <i class="icon-up-arrow" style="font-size:20px;display:inline-block;transform:rotate(-135deg);"></i>
                </a>
                <nav aria-label="Breadcrumb">
                    <ol style="display:flex;align-items:center;gap:0.5rem;list-style:none;padding:0;margin:0;">
                        <li>
                            <a href="{{ url('/') }}" style="font-size:0.875rem;color:#8F8FA1;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#8F8FA1'">Asosiy</a>
                        </li>
                        <li aria-hidden="true" style="color:#8F8FA1;font-size:0.75rem;">/</li>
                        <li>
                            <a href="{{ route('web.catalog') }}" style="font-size:0.875rem;color:#8F8FA1;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#8F8FA1'">Katalog</a>
                        </li>
                        @if($categoryName)
                            <li aria-hidden="true" style="color:#8F8FA1;font-size:0.75rem;">/</li>
                            <li>
                                <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" style="font-size:0.875rem;color:#8F8FA1;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#8F8FA1'">{{ $categoryName }}</a>
                            </li>
                        @endif
                        <li aria-hidden="true" style="color:#8F8FA1;font-size:0.75rem;">/</li>
                        <li>
                            <span style="font-size:0.875rem;color:#111827;font-weight:600;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;max-width:200px;">
                                {{ \Illuminate\Support\Str::limit($product->name, 30) }}
                            </span>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- ====== MAIN PRODUCT AREA ====== -->
        <div class="lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-5" id="kcProductMain">
            <!-- ====== LEFT: IMAGE GALLERY ====== -->
            <div class="col-span-1 xl:col-span-2 h-full">
                <div class="flex flex-col-reverse md:flex-row gap-3 h-full">
                    <!-- Thumbnail strip -->
                    @if(count($allImages) > 1)
                    <div class="flex md:flex-col gap-3 overflow-x-auto md:overflow-y-auto md:overflow-x-hidden w-full md:w-auto md:h-0 md:min-h-full scrollbar-hide py-1 shrink-0">
                        @foreach($allImages as $tIdx => $tImg)
                        <button onclick="kcGotoSlide({{ $tIdx }})" aria-label="gallery-image-selector-{{ $tIdx }}" class="border-transparent hover:border-neutral-200 relative shrink-0 w-[75px] h-[100px] rounded-xl overflow-hidden border-2 transition-all duration-300" id="kcThumb_{{ $tIdx }}">
                            <img src="{{ $tImg ? asset('storage/' . $tImg) : $imgUrl }}" class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif

                    <!-- Main image -->
                    <div class="flex-1 relative rounded-2xl group min-h-0">
                        <div class="relative focus:outline-none h-full" id="kcImgWrap">
                            <div class="overflow-hidden h-full">
                                <div id="kcImgTrack" class="flex flex-row -ms-4 rounded-2xl items-stretch h-full" style="transition:transform 0.5s cubic-bezier(0.16,1,0.3,1);">
                                    @foreach($allImages as $imgIdx => $imgPath)
                                    <div class="min-w-0 shrink-0 ps-4 basis-full">
                                        <img src="{{ $imgPath ? asset('storage/' . $imgPath) : $imgUrl }}" class="object-cover rounded-2xl w-full h-full aspect-[3/4]" id="{{ $imgIdx === 0 ? 'kcMainImg' : 'kcImg_' . $imgIdx }}">
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <!-- Discount badge (bottom-left) -->
                            @if($isDiscounted)
                                <div class="absolute bottom-3 left-3 z-20 flex flex-col gap-1">
                                    <span class="inline-flex items-center text-sm font-medium rounded-md text-white px-2 py-1 bg-[#ED3131]">
                                        -{{ $discPct }}%
                                    </span>
                                </div>
                            @endif

                            <!-- Favorite button (top-right) -->
                            <div class="absolute top-3 right-3 z-20">
                                <button aria-label="Sevimlilar" data-fav="{{ $isFavorited ? '1' : '0' }}"
                                        onclick="toggleFavorite(this, {{ $product->id }}, '{{ $productType }}');"
                                        class="w-10 h-10 flex items-center justify-center rounded-full bg-white/50 backdrop-blur-md border border-white/50 hover:bg-white transition-all">
                                    <svg class="kc-heart-icon" viewBox="0 0 24 24" style="width:22px;height:22px;"
                                         fill="{{ $isFavorited ? '#ef4444' : 'none' }}" stroke="{{ $isFavorited ? '#ef4444' : '#374151' }}" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== RIGHT: PRODUCT INFO ====== -->
            <div class="h-max sticky top-24">
                <div class="flex flex-col gap-4">

                    <!-- Stock & Category Badges -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-700">
                            {{ $productType === 'book' ? 'Kitob' : 'Kanselyariya' }}
                        </span>
                        @if($categoryName)
                            <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-primary-100 text-primary-600 no-underline">
                                {{ $categoryName }}
                            </a>
                        @endif
                        @if($inStock)
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                Sotuvda mavjud
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">
                                Vaqtinchalik tugagan
                            </span>
                        @endif
                    </div>

                    <!-- Product Title -->
                    <h1 class="text-[clamp(1.25rem,4vw,1.75rem)] font-extrabold text-neutral-900 leading-tight m-0">
                        {{ $product->name }}
                    </h1>

                    <!-- Author (for books) -->
                    @if($productType === 'book' && $product->author)
                        <div class="text-sm text-neutral-500">
                            Muallif: <a href="{{ route('web.catalog', ['search' => $product->author]) }}" class="font-bold text-primary-500 no-underline hover:underline">{{ $product->author }}</a>
                        </div>
                    @endif

                    <!-- Rating -->
                    @if($product->ugc_reviews_count)
                        <div class="flex items-center gap-2">
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $i <= round($product->ugc_aggregate_score) ? '#f59e0b' : '#e5e7eb' }}" stroke="none">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-sm font-semibold text-neutral-900">{{ number_format($product->ugc_aggregate_score, 1) }}</span>
                            <span class="text-xs text-neutral-400">({{ $product->ugc_reviews_count }} ta baho)</span>
                        </div>
                    @endif

                    <!-- ====== "BU MENGA MOSMI?" (Reading Intelligence) ======
                         Mobil ilova (item.dart)dagi "Bu menga mosmi?" kartasi
                         bilan bir xil orkestrator (ReadingIntelligenceService)
                         ishlatiladi — reyting ostida, XUDDI ILOVADAGIDEK
                         joylashadi. Faqat kitoblar uchun va faqat AI mahsulotni
                         allaqachon tahlil qilgan bo'lsa ko'rinadi (aks holda
                         $readingIntelligence null — hech narsa chiqmaydi). Bosilganda
                         pastdan chiquvchi "sheet" (mobil bottom-sheet'ga o'xshash
                         modal) ochiladi — batafsil tahlil shu yerda. -->
                    @if($productType === 'book' && !empty($readingIntelligence))
                        @php
                            $riIconPaths = [
                                'sparkles' => '<path d="M12 2l1.8 4.9L19 8.5l-5.2 1.6L12 15l-1.8-4.9L5 8.5l5.2-1.6L12 2z"/><path d="M19 15l.9 2.4L22 18l-2.1.6L19 21l-.9-2.4L16 18l2.1-.6L19 15z"/>',
                                'heart' => '<path d="M12 21s-6.5-4.35-9.3-8.1C.6 9.9 1.7 6 5.1 5c2-.6 3.9.2 4.9 1.9C11 5.2 12.9 4.4 14.9 5c3.4 1 4.5 4.9 2.4 7.9C18.5 16.65 12 21 12 21z"/>',
                                'compass' => '<circle cx="12" cy="12" r="10"/><path d="M16 8l-2 6-6 2 2-6 6-2z"/>',
                                'trending-up' => '<path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/>',
                                'message-circle' => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>',
                                'tag' => '<path d="M20.59 13.41L11 3.83 3.83 11l9.58 9.59a2 2 0 002.83 0l4.35-4.35a2 2 0 000-2.83z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
                                'info-circle' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
                                'book' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
                            ];
                            $riIcon = $riIconPaths[$readingIntelligence['teaser']['icon'] ?? ''] ?? $riIconPaths['sparkles'];
                        @endphp
                        <button type="button" onclick="kcOpenMoslikModal()" style="display:flex;align-items:center;gap:0.75rem;width:100%;text-align:left;padding:0.875rem 1rem;background:linear-gradient(135deg,rgba(124,58,237,0.07),rgba(16,185,129,0.07));border:1px solid rgba(124,58,237,0.15);border-radius:1rem;cursor:pointer;">
                            <div style="width:2.5rem;height:2.5rem;border-radius:0.75rem;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $riIcon !!}</svg>
                            </div>
                            <div style="min-width:0;flex:1;">
                                <div style="font-weight:700;font-size:0.9375rem;color:#111827;">{{ $readingIntelligence['teaser']['title'] }}</div>
                                <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.125rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $readingIntelligence['teaser']['subtitle'] }}</div>
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @endif

                    <!-- Tags -->
                    @if($validTags->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($validTags->take(6) as $tag)
                                <a href="{{ route('web.catalog', ['search' => $tag->name]) }}" class="inline-block px-2.5 py-1 bg-neutral-100 rounded-full text-xs text-neutral-500 no-underline hover:bg-neutral-200 transition-colors">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- ====== SOTUVCHI (Seller/Shop) ======
                         Mahsulot qaysi do'kondan sotilayotganini ko'rsatadi —
                         avval bu ma'lumot mahsulot sahifasida umuman
                         chiqmasdi. Do'kon nomiga bosilsa katalogga shu
                         do'konning barcha mahsulotlari bilan filtrlangan
                         holda o'tadi (alohida "do'kon sahifasi" web'da hali
                         yo'q, shuning uchun mavjud Do'konlar filtridan
                         foydalaniladi). -->
                    @if($product->seller)
                        @php $sellerObj = $product->seller; @endphp
                        <a href="{{ route('web.catalog', ['type' => $productType, 'seller_ids' => [$sellerObj->id]]) }}"
                           style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem 1rem;background:#fff;border:1px solid #f1f5f9;border-radius:1rem;text-decoration:none;transition:border-color 0.2s;"
                           onmouseover="this.style.borderColor='var(--color-tima-300,#a3d9c9)'" onmouseout="this.style.borderColor='#f1f5f9'">
                            @if(!empty($sellerObj->photo))
                                <img src="{{ str_starts_with($sellerObj->photo, 'http') ? $sellerObj->photo : asset('storage/' . ltrim($sellerObj->photo, '/')) }}"
                                     alt="{{ $sellerObj->shop_name }}" style="width:2.75rem;height:2.75rem;border-radius:9999px;object-fit:cover;flex-shrink:0;">
                            @else
                                <div style="width:2.75rem;height:2.75rem;border-radius:9999px;background:var(--color-tima-100,#e8eaef);color:var(--color-tima-600);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.125rem;flex-shrink:0;">
                                    {{ mb_strtoupper(mb_substr($sellerObj->shop_name ?: '?', 0, 1)) }}
                                </div>
                            @endif
                            <div style="min-width:0;flex:1;">
                                <div style="display:flex;align-items:center;gap:0.375rem;">
                                    <span style="font-weight:700;font-size:0.9375rem;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sellerObj->shop_name }}</span>
                                    @if($sellerObj->isVerified)
                                        <svg viewBox="0 0 24 24" fill="#3b82f6" style="width:15px;height:15px;flex-shrink:0;" aria-label="Tasdiqlangan do'kon"><path d="M12 2l2.4 1.2 2.7-.3 1.2 2.4 2.4 1.2-.3 2.7L22 12l-1.2 2.4.3 2.7-2.4 1.2-1.2 2.4-2.7-.3L12 22l-2.4-1.2-2.7.3-1.2-2.4-2.4-1.2.3-2.7L2 12l1.2-2.4-.3-2.7 2.4-1.2 1.2-2.4 2.7.3z"/><path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @endif
                                    @if($sellerObj->isPremiumShop)
                                        <span style="font-size:0.625rem;font-weight:700;color:#a16207;background:#fef9c3;padding:0.0625rem 0.375rem;border-radius:9999px;">PREMIUM</span>
                                    @endif
                                </div>
                                <div style="font-size:0.8125rem;color:#9ca3af;margin-top:0.125rem;">
                                    @if($sellerObj->rating > 0)
                                        <span style="color:#f59e0b;font-weight:600;">★ {{ number_format($sellerObj->rating, 1) }}</span>
                                        @if($sellerObj->rating_reviews_count > 0)
                                            <span>({{ $sellerObj->rating_reviews_count }})</span>
                                        @endif
                                        <span> · </span>
                                    @endif
                                    {{ __('marketplace.seller_shop') }}
                                </div>
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @endif

                    <!-- ====== PRICE AND PAYMENT TABS BLOCK ====== -->
                    <div class="p-6 rounded-3xl bg-secondary-100 space-y-5 mt-4">
                        <!-- Tabs -->
                        <div class="relative inline-flex bg-secondary-300 rounded-xl p-1 flex">
                            <button id="tab-installment" class="text-sm px-4 py-2 text-gray-900 bg-white shadow-sm flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap" onclick="togglePaymentTab('installment')">
                                Muddatli to‘lov
                            </button>
                            <button id="tab-cash" class="text-sm px-4 py-2 text-gray-400 hover:text-gray-500 flex-1 relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap" onclick="togglePaymentTab('cash')">
                                Naqd to'lov
                            </button>
                        </div>

                        <!-- Installment View -->
                        <div id="view-installment" class="flex max-md:flex-col md:justify-between gap-4 w-full transition-all">
                            <div>
                                <p class="text-sm text-gray font-normal text-neutral-500">Muddatli to'lov</p>
                                <div id="kcPlanPills" class="inline-flex mt-1 md:mt-2">
                                    <!-- JS to'ldiradi: kcLoadSplitPreview() -->
                                    <div class="relative inline-flex bg-secondary-300 rounded-xl p-1">
                                        <button type="button" class="relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-400 hover:text-gray-500">6 oy</button>
                                        <button type="button" class="relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-900 bg-white shadow-sm">12 oy</button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col md:items-end">
                                <p class="text-sm text-gray font-normal text-neutral-500">Muddatli to'lovga sotib olish</p>
                                <div class="flex items-end justify-between md:justify-end gap-3 mt-1 md:mt-4 w-full">
                                    <div class="flex items-end gap-1">
                                        <span id="kcMonthlyPrice" class="text-xl font-bold text-neutral-900">{{ number_format(ceil($currentPrice * 1.44 / 12)) }}</span>
                                        <span class="text-neutral-500 text-sm">so'm/oyiga</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cash View -->
                        <div id="view-cash" class="flex flex-col transition-all" style="display:none;">
                            <p class="text-sm text-gray font-normal text-neutral-500">Narxi</p>
                            <div class="flex items-center gap-1 mt-1">
                                <div class="flex items-end gap-3">
                                    <span class="text-2xl font-bold text-neutral-900">{{ number_format($currentPrice) }} so'm</span>
                                    @if($isDiscounted)
                                        <span class="text-base text-neutral-400 line-through">{{ number_format($origPrice) }} so'm</span>
                                        <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded text-[#ED3131] bg-[#ED3131]/10">
                                            -{{ $discPct }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- ====== BUY ACTIONS ======
                             Mobil'da bu blok pastga mahkamlanadi (.kc-buybar-mobile —
                             piyolamarket'dagi sticky "Buyurtma berish" panelining
                             o'zi), desktop'da hech narsa o'zgarmaydi. -->
                        <div class="flex items-center gap-2 sm:gap-3 kc-buybar-mobile">
                            <div class="flex-1">
                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}', '{{ $canonicalUrl }}', true)" class="font-medium inline-flex items-center justify-center transition-colors py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/90 active:bg-primary/90 h-12 rounded-2xl text-base px-6 w-full cursor-pointer">
                                    {{ __('marketplace.place_order') }}
                                </button>
                            </div>
                            <div>
                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}', '{{ $canonicalUrl }}', false)" title="{{ __('marketplace.add_to_cart') }}" class="font-medium inline-flex items-center justify-center transition-colors text-base gap-2 text-primary bg-primary/10 hover:bg-primary/15 active:bg-primary/15 h-12 w-14 rounded-2xl cursor-pointer">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:24px;height:24px;"><path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                <!-- ====== XUSUSIYATLAR VA TAVSIF (piyolamarket.uz uslubida) ====== -->
                @if(!empty($specs) || $hasVariants)
                    <div>
                        <div onclick="kcToggleSpecs()" class="w-full bg-secondary-300 cursor-pointer rounded-2xl p-4 md:px-6 flex items-center justify-between transition-colors duration-300 hover:bg-secondary-400 mt-2">
                            <div class="flex items-center gap-3">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-6 h-6 text-primary"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                                <span class="font-medium text-primary text-base">Xususiyatlar va tavsif</span>
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="kcSpecsChevron" class="w-5 h-5 text-primary transition-transform duration-300"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </div>

                        <div id="kcSpecsBody" class="mt-4 p-6 rounded-3xl bg-secondary-100 hidden">
                            @if(!empty($specs))
                                <dl class="space-y-3">
                                    @foreach($specs as $spec)
                                        <div class="flex justify-between gap-4 py-2 border-b border-secondary-200 border-dashed">
                                            <dt class="text-sm text-neutral-500">{{ $spec['label'] }}</dt>
                                            <dd class="text-sm font-semibold text-neutral-900 text-right">{{ $spec['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif

                            @if($hasVariants)
                                <div class="{{ !empty($specs) ? 'mt-6' : '' }}">
                                    <div class="text-sm text-neutral-500 mb-3">Ranglar / turlari</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($product->variants as $variant)
                                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-secondary-200 text-sm font-medium text-neutral-700">
                                                @if(!empty($variant->image_path))
                                                    <img src="{{ asset('storage/' . $variant->image_path) }}" alt="{{ $variant->color_name }}" class="w-5 h-5 rounded-full object-cover">
                                                @endif
                                                {{ $variant->color_name }}
                                                @if(($variant->stock ?? 0) <= 0)
                                                    <span class="text-neutral-400 font-normal">(tugagan)</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
        </div>

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
                    <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:800;color:#111827;margin:0;">
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

        <!-- ====== AI TAVSIYA ======
             $aiRecommendations — mobil ilova (item.dart)dagi "AI tavsiya"
             bilan bir xil mantiq: mahsulotning AI embedding'i (vectorData)
             boshqalarnikiga kosinus o'xshashligi bo'yicha solishtirilib,
             kategoriya/tag/muallif/matn signallari bilan kuchaytiriladi
             (ProductCatalogController::aiRecommendedProducts()). Oddiy
             "O'xshash mahsulotlar" ro'yxatidan farqlash uchun ataylab
             boshqacha — gradient fon + robot ikonkasi bilan — chizilgan. -->
        @if(($aiRecommendations ?? collect())->isNotEmpty())
            <section style="margin-top:2.5rem;padding:1.5rem;border-radius:1.5rem;background:linear-gradient(135deg,rgba(124,58,237,0.06),rgba(16,185,129,0.06));border:1px solid rgba(124,58,237,0.12);">
                <div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:0.375rem;">
                    <div style="width:2rem;height:2rem;border-radius:0.625rem;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>
                    </div>
                    <h2 style="font-size:clamp(1.125rem,3vw,1.5rem);font-weight:800;color:#111827;margin:0;">
                        {{ __('marketplace.ai_recommendations_title') }}
                    </h2>
                </div>
                <p style="font-size:0.8125rem;color:#6b7280;margin:0 0 1.25rem;">{{ __('marketplace.ai_recommendations_desc') }}</p>
                <div id="kcAiGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;">
                    @foreach($aiRecommendations as $sim)
                        @php
                            $aiIsStationery = $productType === 'stationery';
                            $aiSlug = \Illuminate\Support\Str::slug($sim->name);
                            $aiUrl = $aiIsStationery
                                ? route('web.stationery.show', ['id' => $sim->id, 'slug' => $aiSlug])
                                : route('web.books.show', ['id' => $sim->id, 'slug' => $aiSlug]);
                            $aiImg = $sim->first_image ? asset('storage/' . $sim->first_image) : asset('images/logo/logo_blue.png');
                            $aiRawPrice = (float) $sim->price;
                            $aiDiscRaw = $aiIsStationery ? (float) $sim->discount_price : (float) $sim->discountPrice;
                            $aiIsDisc = $aiDiscRaw > 0 && $aiDiscRaw < $aiRawPrice;
                            $aiPrice = $aiIsDisc ? $aiDiscRaw : $aiRawPrice;
                        @endphp
                        <a href="{{ $aiUrl }}" class="kc-product-card" style="background:#fff;">
                            <div class="kc-product-card-img">
                                <img src="{{ $aiImg }}" alt="{{ $sim->name }}" loading="lazy">
                            </div>
                            <div class="kc-product-card-body">
                                <div class="kc-product-card-title">{{ $sim->name }}</div>
                                <div class="kc-product-card-price">{{ number_format($aiPrice) }} {{ __('marketplace.currency') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- ====== O'XSHASH MAHSULOTLAR ====== -->
        @if($similarProducts->isNotEmpty())
            <section style="margin-top:2.5rem;">
                <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:800;color:#111827;margin:0 0 1rem;">
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
        #kcSimilarGrid, #kcAiGrid { grid-template-columns: repeat(3, 1fr) !important; gap: 0.75rem !important; }
    }
    @media(min-width: 1024px) {
        #kcSimilarGrid, #kcAiGrid { grid-template-columns: repeat(4, 1fr) !important; gap: 1rem !important; }
    }
    @media(min-width: 1280px) {
        #kcSimilarGrid, #kcAiGrid { grid-template-columns: repeat(5, 1fr) !important; gap: 1.25rem !important; }
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
</script>
@endpush
@endsection
