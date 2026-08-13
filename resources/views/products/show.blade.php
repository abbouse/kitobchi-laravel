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
        <div id="kcProductMain" style="display:grid;grid-template-columns:1fr;gap:1.5rem;">

            <!-- ====== LEFT: IMAGE GALLERY ====== -->
            <div>
                <!-- Main image with carousel dots (PiyolaMarket style) -->
                <div style="position:relative;border-radius:1.5rem;overflow:hidden;background:#fff;" id="kcImgWrap">
                    <!-- Carousel -->
                    <div id="kcImgTrack" style="display:flex;transition:transform 0.5s cubic-bezier(0.16,1,0.3,1);">
                        @foreach($allImages as $imgIdx => $imgPath)
                            <div style="min-width:100%;flex-shrink:0;">
                                <div style="position:relative;width:100%;aspect-ratio:3/4;overflow:hidden;border-radius:1.5rem;">
                                    <img src="{{ $imgPath ? asset('storage/' . $imgPath) : $imgUrl }}"
                                         alt="{{ $product->name }}"
                                         id="{{ $imgIdx === 0 ? 'kcMainImg' : 'kcImg_' . $imgIdx }}"
                                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.7s;display:block;"
                                         loading="{{ $imgIdx === 0 ? 'eager' : 'lazy' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Discount badge (bottom-left) -->
                    @if($isDiscounted)
                        <div style="position:absolute;bottom:0.75rem;left:0.75rem;z-index:20;display:inline-flex;flex-direction:column;gap:4px;">
                            <span style="display:inline-flex;align-items:center;font-size:0.875rem;font-weight:500;border-radius:0.375rem;color:#fff;padding:0.25rem 0.5rem;background:#ED3131;">
                                -{{ $discPct }}%
                            </span>
                        </div>
                    @endif

                    <!-- Favorite button (top-right) -->
                    <div style="position:absolute;top:0.75rem;right:0.75rem;z-index:20;">
                        <div style="position:relative;overflow:hidden;transition:box-shadow 0.3s;border-radius:1rem;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);background:rgba(255,255,255,0.5);">
                            <div style="position:absolute;inset:0;pointer-events:none;padding:1px;background:linear-gradient(165deg,rgba(255,255,255,0.7),rgba(255,255,255,0.3) 25%,transparent,rgba(255,255,255,0.7) 75%,rgba(255,255,255,0.3));-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;border-radius:1rem;"></div>
                            <button aria-label="Sevimlilar"
                                    onclick="toggleFavBtn(this)"
                                    style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:none;border:none;cursor:pointer;transition:all 0.3s;position:relative;z-index:10;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" style="position:relative;z-index:10;" id="kcFavIcon">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Carousel dots (if multiple images) -->
                    @if(count($allImages) > 1)
                        <div style="position:absolute;bottom:0.75rem;left:50%;transform:translateX(-50%);display:flex;gap:4px;z-index:20;" aria-label="Slaydni tanlang" role="tablist">
                            @foreach($allImages as $dIdx => $dImg)
                                <button onclick="kcGotoSlide({{ $dIdx }})"
                                        id="kcDot_{{ $dIdx }}"
                                        aria-label="Slayd {{ $dIdx + 1 }}"
                                        style="height:6px;border-radius:9999px;background:{{ $dIdx === 0 ? '#010101' : '#d1d5db' }};width:{{ $dIdx === 0 ? '16px' : '6px' }};border:none;cursor:pointer;transition:all 0.3s;padding:0;"
                                        role="tab"></button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Thumbnail strip -->
                @if(count($allImages) > 1)
                    <div style="display:flex;gap:0.5rem;margin-top:0.75rem;overflow-x:auto;-ms-overflow-style:none;scrollbar-width:none;padding-bottom:0.25rem;">
                        @foreach($allImages as $tIdx => $tImg)
                            <button onclick="kcGotoSlide({{ $tIdx }})"
                                    style="width:64px;height:80px;border:2px solid {{ $tIdx === 0 ? 'var(--color-tima-500)' : '#e5e7eb' }};border-radius:0.625rem;overflow:hidden;background:#f3f4f6;cursor:pointer;flex-shrink:0;padding:0;transition:border-color 0.2s;"
                                    id="kcThumb_{{ $tIdx }}">
                                <img src="{{ $tImg ? asset('storage/' . $tImg) : $imgUrl }}" alt="{{ $loop->iteration }}"
                                     style="width:100%;height:100%;object-fit:cover;">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- ====== RIGHT: PRODUCT INFO ====== -->
            <div style="display:flex;flex-direction:column;gap:1rem;">

                <!-- Stock & Category Badges -->
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:500;padding:0.25rem 0.625rem;border-radius:9999px;background:#f3f4f6;color:#374151;">
                        {{ $productType === 'book' ? 'Kitob' : 'Kanselyariya' }}
                    </span>
                    @if($categoryName)
                        <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}"
                           style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:500;padding:0.25rem 0.625rem;border-radius:9999px;background:var(--color-tima-100);color:var(--color-tima-600);text-decoration:none;">
                            {{ $categoryName }}
                        </a>
                    @endif
                    @if($inStock)
                        <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:500;padding:0.25rem 0.625rem;border-radius:9999px;background:#dcfce7;color:#16a34a;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            Sotuvda mavjud
                        </span>
                    @else
                        <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:500;padding:0.25rem 0.625rem;border-radius:9999px;background:#fef9c3;color:#a16207;">
                            Vaqtinchalik tugagan
                        </span>
                    @endif
                </div>

                <!-- Product Title -->
                <h1 style="font-size:clamp(1.25rem,4vw,1.75rem);font-weight:800;color:#111827;line-height:1.3;margin:0;">
                    {{ $product->name }}
                </h1>

                <!-- Author (for books) -->
                @if($productType === 'book' && $product->author)
                    <div style="font-size:0.9375rem;color:#6b7280;">
                        Muallif: <a href="{{ route('web.catalog', ['search' => $product->author]) }}" style="font-weight:700;color:var(--color-tima-500);text-decoration:none;">{{ $product->author }}</a>
                    </div>
                @endif

                <!-- Rating -->
                @if($product->ugc_reviews_count)
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <div style="display:flex;gap:2px;">
                            @for($i = 1; $i <= 5; $i++)
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="{{ $i <= round($product->ugc_aggregate_score) ? '#f59e0b' : '#e5e7eb' }}" stroke="none">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            @endfor
                        </div>
                        <span style="font-size:0.875rem;font-weight:600;color:#111827;">{{ number_format($product->ugc_aggregate_score, 1) }}</span>
                        <span style="font-size:0.8125rem;color:#9ca3af;">({{ $product->ugc_reviews_count }} ta baho)</span>
                    </div>
                @endif

                <!-- Tags -->
                @if($validTags->isNotEmpty())
                    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                        @foreach($validTags->take(6) as $tag)
                            <a href="{{ route('web.catalog', ['search' => $tag->name]) }}"
                               style="display:inline-block;padding:0.25rem 0.625rem;background:#f3f4f6;border-radius:9999px;font-size:0.75rem;color:#6b7280;text-decoration:none;transition:all 0.2s;"
                               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- ====== PRICE BLOCK ====== -->
                <div style="background:#f9fafb;border-radius:1.25rem;padding:1.25rem;border:1px solid #f3f4f6;">
                    <div style="display:flex;align-items:baseline;gap:0.75rem;flex-wrap:wrap;">
                        <div style="font-size:1.75rem;font-weight:900;color:var(--color-tima-500);line-height:1;">
                            {{ number_format($currentPrice) }} <span style="font-size:1rem;font-weight:600;">so'm</span>
                        </div>
                        @if($isDiscounted)
                            <div style="font-size:1.125rem;color:#9ca3af;text-decoration:line-through;font-weight:500;">
                                {{ number_format($origPrice) }} so'm
                            </div>
                            <span style="display:inline-flex;align-items:center;font-size:0.8125rem;font-weight:700;padding:0.25rem 0.5rem;border-radius:9999px;background:#fee2e2;color:#ef4444;">
                                -{{ $discPct }}% chegirma
                            </span>
                        @endif
                    </div>
                </div>

                <!-- ====== BUY ACTIONS ====== -->
                <!-- Savatchaga qo'shish -->
                <button type="button"
                        onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')"
                        style="display:flex;align-items:center;justify-content:center;gap:0.75rem;width:100%;height:3.5rem;background:var(--color-tima-500);color:#fff;border:none;border-radius:1rem;font-size:1.0625rem;font-weight:700;cursor:pointer;transition:all 0.2s;font-family:inherit;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    Savatga qo'shish
                </button>

                <!-- Bir klikda sotib olish -->
                <a href="{{ route('web.checkout') }}"
                   onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')"
                   style="display:flex;align-items:center;justify-content:center;gap:0.75rem;width:100%;height:3.5rem;background:#111827;color:#fff;border-radius:1rem;font-size:1.0625rem;font-weight:700;text-decoration:none;transition:all 0.2s;"
                   onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M13 2 4 14h7l-1 8 9-12h-7z"/>
                    </svg>
                    Bir klikda sotib olish
                </a>

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
    @media(min-width: 768px) {
        #kcProductMain {
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
        }
    }
    @media(min-width: 1024px) {
        #kcProductMain {
            grid-template-columns: 5fr 6fr !important;
            gap: 3rem !important;
        }
    }
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
