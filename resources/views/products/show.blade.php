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
                                <button aria-label="Sevimlilar" onclick="toggleFavBtn(this)" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/50 backdrop-blur-md border border-white/50 hover:bg-white transition-all">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" id="kcFavIcon">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
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

                    <!-- ====== PRICE BLOCK ====== -->
                    <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-100">
                        <div class="flex items-baseline gap-3 flex-wrap">
                            <div class="text-3xl font-black text-primary-500 leading-none">
                                {{ number_format($currentPrice) }} <span class="text-base font-semibold">so'm</span>
                            </div>
                            @if($isDiscounted)
                                <div class="text-lg text-neutral-400 line-through font-medium">
                                    {{ number_format($origPrice) }} so'm
                                </div>
                                <span class="inline-flex items-center text-xs font-bold px-2 py-1 rounded-full bg-red-100 text-red-500">
                                    -{{ $discPct }}% chegirma
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- ====== BUY ACTIONS ====== -->
                    <div class="flex flex-col gap-3 mt-2">
                        <!-- Savatchaga qo'shish -->
                        <button type="button"
                                onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')"
                                class="flex items-center justify-center gap-3 w-full h-14 bg-primary-500 text-white border-none rounded-2xl text-lg font-bold cursor-pointer hover:opacity-90 transition-opacity font-inherit">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                            Savatga qo'shish
                        </button>

                        <!-- Bir klikda sotib olish -->
                        <a href="{{ route('web.checkout') }}"
                           onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')"
                           class="flex items-center justify-center gap-3 w-full h-14 bg-neutral-900 text-white rounded-2xl text-lg font-bold no-underline hover:opacity-90 transition-opacity">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                <path d="M13 2 4 14h7l-1 8 9-12h-7z"/>
                            </svg>
                            Bir klikda sotib olish
                        </a>
                    </div>

                <!-- Delivery info -->
                <div style="border:1px solid #f3f4f6;border-radius:1rem;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;background:#f0fdf4;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#16a34a;">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path d="M10 17h4V5H2v12h3"/><path d="M14 8h4l4 4v5h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.875rem;font-weight:600;color:#111827;">Tezkor yetkazib berish</div>
                            <div style="font-size:0.8125rem;color:#6b7280;">Toshkent bo'ylab 1-2 soatda</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;background:#f3f4f6;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#374151;">
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.875rem;font-weight:600;color:#111827;">Qulay to'lov</div>
                            <div style="font-size:0.8125rem;color:#6b7280;">Naqd yoki karta orqali</div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                @if(!empty($product->description))
                    <div style="border-top:1px solid #f3f4f6;padding-top:1.25rem;">
                        <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 0.75rem;">Mahsulot haqida</h3>
                        <div style="font-size:0.9rem;color:#4b5563;line-height:1.7;white-space:pre-line;">
                            {!! strip_tags($product->description) !!}
                        </div>
                    </div>
                @endif

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
                        @endphp
                        <a href="{{ $simUrl }}" class="kc-product-card">
                            <div class="kc-product-card-img">
                                <img src="{{ $simImg }}" alt="{{ $sim->name }}" loading="lazy">
                                @if($simIsDisc)
                                    <span class="kc-discount-badge">-{{ $simDiscPct }}%</span>
                                @endif
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

    function toggleFavBtn(btn) {
        const svg = document.getElementById('kcFavIcon');
        if (svg.getAttribute('fill') === 'none') {
            svg.setAttribute('fill', '#ef4444');
            svg.setAttribute('stroke', '#ef4444');
            btn.style.transform = 'scale(1.2)';
            setTimeout(() => btn.style.transform = 'scale(1)', 200);
        } else {
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', '#374151');
        }
    }
</script>
@endpush
@endsection
