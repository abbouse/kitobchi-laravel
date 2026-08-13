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

@section('content')
<div style="min-height:100dvh;padding:1.5rem 0;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        <!-- ====== BREADCRUMBS (PiyolaMarket style) ====== -->
        <div style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <a href="{{ url('/') }}" onclick="history.length > 1 ? (event.preventDefault(), history.back()) : null"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem 0.375rem 0.5rem;border-radius:0.375rem;font-size:0.875rem;font-weight:500;color:var(--color-tima-500);background:none;border:none;cursor:pointer;text-decoration:none;transition:color 0.2s;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="transform:rotate(-135deg);">
                        <path d="M12 5l7 7-7 7M5 12h14"/>
                    </svg>
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
                                    <iconify-icon icon="{{ $isFavorited ? 'heroicons-solid:heart' : 'heroicons:heart' }}" style="font-size:22px;color:{{ $isFavorited ? '#ef4444' : '#374151' }};"></iconify-icon>
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
                                <div class="inline-flex mt-1 md:mt-2">
                                    <div class="relative inline-flex bg-secondary-300 rounded-xl p-1">
                                        <button class="relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-400 hover:text-gray-500">6 oy</button>
                                        <button class="relative z-10 font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm px-3 py-1.5 text-gray-900 bg-white shadow-sm">12 oy</button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col md:items-end">
                                <p class="text-sm text-gray font-normal text-neutral-500">Muddatli to'lovga sotib olish</p>
                                <div class="flex items-end justify-between md:justify-end gap-3 mt-1 md:mt-4 w-full">
                                    <div class="flex items-end gap-1">
                                        <span class="text-xl font-bold text-neutral-900">{{ number_format(ceil($currentPrice * 1.44 / 12)) }}</span>
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

                        <!-- ====== BUY ACTIONS ====== -->
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="flex-1">
                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}', '{{ $canonicalUrl }}'); window.location.href='{{ route('web.checkout') }}'" class="font-medium inline-flex items-center justify-center transition-colors py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/90 active:bg-primary/90 h-12 rounded-2xl text-base px-6 w-full cursor-pointer">
                                    Buyurtma berish
                                </button>
                            </div>
                            <div>
                                <button type="button" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}', '{{ $canonicalUrl }}')" class="font-medium inline-flex items-center justify-center transition-colors text-base gap-2 text-primary bg-primary/10 hover:bg-primary/15 active:bg-primary/15 h-12 w-14 rounded-2xl cursor-pointer">
                                    <iconify-icon icon="heroicons-solid:shopping-cart" style="font-size: 24px;"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>

                <!-- ====== XUSUSIYATLAR VA TAVSIF (piyolamarket.uz uslubida) ====== -->
                @if(!empty($specs) || $hasVariants)
                    <div>
                        <div onclick="kcToggleSpecs()" class="w-full bg-secondary-300 cursor-pointer rounded-2xl p-4 md:px-6 flex items-center justify-between transition-colors duration-300 hover:bg-secondary-400 mt-2">
                            <div class="flex items-center gap-3">
                                <iconify-icon icon="heroicons:information-circle" class="w-6 h-6 text-primary" style="font-size: 24px;"></iconify-icon>
                                <span class="font-medium text-primary text-base">Xususiyatlar va tavsif</span>
                            </div>
                            <iconify-icon icon="heroicons:chevron-down" id="kcSpecsChevron" class="w-5 h-5 text-primary transition-transform duration-300" style="font-size: 20px;"></iconify-icon>
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

        <!-- ====== O'XSHASH MAHSULOTLAR ====== -->
        @if($similarProducts->isNotEmpty())
            <section style="margin-top:2.5rem;">
                <h2 style="font-size:clamp(1.25rem,3vw,1.75rem);font-weight:800;color:#111827;margin:0 0 1rem;">
                    O'xshash mahsulotlar
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
                                        <iconify-icon icon="{{ $simIsFav ? 'heroicons-solid:heart' : 'heroicons:heart' }}" style="font-size:16px;color:{{ $simIsFav ? '#ef4444' : '#374151' }};"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                            <div class="kc-product-card-body">
                                <div class="kc-product-card-title">{{ $sim->name }}</div>
                                <div class="kc-product-card-price">{{ number_format($simPrice) }} so'm</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

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
</script>
@endpush
@endsection
