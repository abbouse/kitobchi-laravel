@extends('layouts.landing')

@php
    $titleName = $product->name . ($productType === 'book' && $product->author ? ' — ' . $product->author : '');
    $seoTitle = $titleName . ' | Kitobchi';
    $rawDesc = strip_tags($product->description ?? $product->name);
    $seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $rawDesc)), 158, '…');
    $imgUrl = $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
    $isDiscounted = ($productType === 'book' ? $product->discountPrice : $product->discount_price) > 0 && ($productType === 'book' ? $product->discountPrice < $product->price : $product->discount_price < $product->price);
    $currentPrice = $isDiscounted ? ($productType === 'book' ? $product->discountPrice : $product->discount_price) : $product->price;
    $origPrice = $product->price;
    $inStock = $productType === 'book' ? ($product->count > 0) : ($product->stock > 0);
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data=' . urlencode($canonicalUrl);
    $shareUrl = urlencode($canonicalUrl);
    $shareText = urlencode($seoTitle);
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
    @if(isset($schemas) && is_array($schemas))
        @foreach($schemas as $sch)
            <script type="application/ld+json">{!! json_encode($sch, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endforeach
    @endif
@endpush

@section('content')
<div class="kc-product-page">
    <div class="page-padding">
        <div class="container">
            <!-- Breadcrumbs & Share bar -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 u-mb-m">
                <nav class="kc-breadcrumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Bosh sahifa</a>
                    <span class="sep">/</span>
                    <a href="{{ route('web.catalog') }}">Katalog</a>
                    <span class="sep">/</span>
                    @if($product->category)
                        <span>{{ $product->category->name ?? $product->category->title_uz }}</span>
                        <span class="sep">/</span>
                    @endif
                    <span class="current">{{ \Illuminate\Support\Str::limit($product->name, 32) }}</span>
                </nav>

                <!-- Social Share Toolbar -->
                <div class="kc-share-toolbar">
                    <span class="label me-1">Ulashish:</span>
                    <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" class="kc-share-icon tg" title="Telegramda ulashish">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.67-.52.36-1 .54-1.43.53-.47-.01-1.37-.27-2.04-.49-.82-.27-1.47-.42-1.42-.88.03-.24.37-.49 1.02-.75 3.99-1.74 6.66-2.89 8.01-3.46 3.82-1.6 4.61-1.88 5.13-1.89.11 0 .37.03.54.17.14.12.18.28.2.45-.01.07.01.22 0 .38z"/></svg>
                    </a>
                    <a href="https://api.whatsapp.com/send?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" class="kc-share-icon wa" title="WhatsAppda ulashish">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zm5.82 14.07c-.25.7-1.43 1.35-1.99 1.42-.51.07-1.16.1-3.67-.92-3.21-1.31-5.28-4.57-5.44-4.78-.16-.21-1.3-1.73-1.3-3.3 0-1.57.82-2.34 1.11-2.66.29-.32.64-.4.86-.4.21 0 .43 0 .61.01.2.01.46-.07.72.55.26.63.89 2.16.97 2.32.08.16.13.35.03.56-.1.21-.16.34-.31.52-.16.18-.33.4-.47.53-.15.15-.31.32-.13.63.18.31.8 1.32 1.72 2.14 1.18 1.05 2.18 1.38 2.49 1.53.31.16.49.13.67-.08.18-.21.78-.91.99-1.22.21-.31.42-.26.71-.16.29.1.84.87 2.16 1.52.32.16.53.24.61.37.08.13.08.76-.17 1.46z"/></svg>
                    </a>
                    <button type="button" class="kc-share-icon copy" onclick="navigator.clipboard.writeText('{{ $canonicalUrl }}'); alert('Link nusxalandi!')" title="Havolani nusxalash">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Main Product Hero Card -->
            <div class="kc-product-hero-card">
                <div class="kc-product-grid">
                    <!-- Left: Gallery -->
                    <div class="kc-product-media">
                        <div class="kc-product-cover-wrap">
                            @if($product->first_image)
                                <img src="{{ asset('storage/' . $product->first_image) }}" alt="{{ $product->name }}" class="kc-product-cover-img" id="mainProductImg">
                            @else
                                <div class="kc-product-cover-ph">&#128218;</div>
                            @endif
                        </div>
                        @if(is_array($product->images) && count($product->images) > 1)
                            <div class="kc-product-thumbs">
                                @foreach($product->images as $idx => $img)
                                    <button type="button" class="kc-thumb-btn {{ $idx === 0 ? 'active' : '' }}" onclick="document.getElementById('mainProductImg').src='{{ asset('storage/' . $img) }}'">
                                        <img src="{{ asset('storage/' . $img) }}" alt="">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Right: Info & App Conversion -->
                    <div class="kc-product-info">
                        <div class="d-flex align-items-center gap-2 u-mb-m flex-wrap">
                            <div class="eyebrow-pill">
                                <div class="eyebrow-pill-inner">
                                    <div><strong>{{ $productType === 'book' ? 'Kitob' : 'Kanselyariya' }}</strong></div>
                                </div>
                                <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                            </div>
                            @if($product->category)
                                <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="kc-stock-badge" style="background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; text-decoration: none;">
                                    📁 {{ $product->category->name ?? $product->category->title_uz }}
                                </a>
                            @endif
                            @if($inStock)
                                <span class="kc-stock-badge in-stock">&#10004; Sotuvda mavjud</span>
                            @else
                                <span class="kc-stock-badge out-stock">&#9888; Vaqtinchalik tugagan</span>
                            @endif
                        </div>

                        <h1 class="kc-product-title">{{ $product->name }}</h1>

                        @if($productType === 'book' && $product->author)
                            <div class="kc-product-author u-mb-s">
                                Muallif: <a href="{{ route('web.catalog', ['search' => $product->author]) }}" style="color: #4f46e5; font-weight: 700; text-decoration: underline;">{{ $product->author }}</a>
                            </div>
                        @endif

                        <!-- Product Tags -->
                        @if(isset($product->tags) && count($product->tags) > 0)
                            <div class="d-flex align-items-center gap-1 u-mb-m flex-wrap">
                                @foreach($product->tags as $tag)
                                    <a href="{{ route('web.catalog', ['search' => $tag->name]) }}" class="badge rounded-pill" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; font-size: 12px; padding: 4px 10px;">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Ratings / UGC -->
                        @if($product->ugc_reviews_count)
                            <div class="kc-product-rating">
                                <span class="stars">&#11088; {{ number_format($product->ugc_aggregate_score, 1) }}</span>
                                <span class="count">({{ $product->ugc_reviews_count }} ta baho)</span>
                            </div>
                        @endif

                        <!-- Pricing & Stock -->
                        <div class="kc-product-price-block">
                            <div class="kc-product-price-main">
                                {{ number_format($currentPrice) }} <small>UZS</small>
                            </div>
                            @if($isDiscounted)
                                <div class="kc-product-price-orig">
                                    {{ number_format($origPrice) }} UZS
                                </div>
                                <span class="kc-product-badge-discount">
                                    -{{ round((($origPrice - $currentPrice) / $origPrice) * 100) }}% CHEGIRMA
                                </span>
                            @endif
                        </div>

                        <!-- App Redirection & Conversion Box -->
                        <div class="kc-app-conversion-box">
                            <div class="kc-conversion-left">
                                <div class="kc-conversion-title">
                                    {{ $inStock ? 'Kitobchi ilovasida xarid qiling' : 'Ilovada eslatishni yoqing' }}
                                </div>
                                <p class="kc-conversion-sub">
                                    {{ $inStock ? 'Barcha eksklyuziv chegirmalar, keshbek va tezkor yetkazib berish bilan mobil ilovamizda buyurtma bering!' : 'Ushbu mahsulot omborda vaqtinchalik tugagan. Kitobchi ilovasini oching va sotuvga chiqganda darhol xabar oling!' }}
                                </p>

                                <div class="kc-conversion-actions">
                                    <a href="{{ $appScheme }}" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-label="Ilovada ko'rish">
                                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                                        <div class="cta-inner">
                                            <div><strong>{{ $inStock ? 'Kitobchi ilovasida ochish' : 'Ilovada ko\'rish va eslatish' }}</strong></div>
                                        </div>
                                    </a>
                                </div>

                                <div class="kc-conversion-stores">
                                    <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" rel="noopener" class="kc-store-badge">
                                        <img src="{{ asset('vendor/popcorn/images/feature-pin.png') }}" width="20" height="20" alt="">
                                        <span>App Store</span>
                                    </a>
                                    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="kc-store-badge">
                                        <img src="{{ asset('vendor/popcorn/images/feature-event.png') }}" width="20" height="20" alt="">
                                        <span>Google Play</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Service Guarantees / Trust Badges -->
                        <div class="kc-trust-badges-bar u-mt-m">
                            <div class="trust-badge">
                                <span class="ico">&#9889;</span>
                                <span>1-3 kunda yetkazish</span>
                            </div>
                            <div class="trust-badge">
                                <span class="ico">&#128737;</span>
                                <span>Original va kafolatlangan</span>
                            </div>
                            <div class="trust-badge">
                                <span class="ico">&#128179;</span>
                                <span>Xavfsiz to'lovlar</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Product Details & Specs -->
            <div class="kc-product-details-grid u-mt-xl">
                <!-- Description -->
                <div class="kc-product-desc-card">
                    <h2 class="heading-m u-mb-m">Mahsulot haqida</h2>
                    <div class="kc-product-description">
                        {!! nl2br(e($product->description ?? 'Bu mahsulot haqida ma\'lumot kiritilmagan.')) !!}
                    </div>
                </div>

                <!-- Specifications Table -->
                <div class="kc-product-specs-card">
                    <h2 class="heading-m u-mb-m">Xususiyatlari</h2>
                    <dl class="kc-specs-list">
                        <div class="spec-row">
                            <dt>Ombor holati</dt>
                            <dd>{{ $inStock ? 'Sotuvda mavjud' : 'Vaqtinchalik tugagan' }}</dd>
                        </div>
                        @if($product->artikul)
                            <div class="spec-row">
                                <dt>Artikul</dt>
                                <dd>{{ $product->artikul }}</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->isbn)
                            <div class="spec-row">
                                <dt>ISBN</dt>
                                <dd>{{ $product->isbn }}</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->publisher)
                            <div class="spec-row">
                                <dt>Nashriyot</dt>
                                <dd>{{ $product->publisher->name }}</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->year)
                            <div class="spec-row">
                                <dt>Chop etilgan yili</dt>
                                <dd>{{ $product->year }}</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->pages)
                            <div class="spec-row">
                                <dt>Sahifalar soni</dt>
                                <dd>{{ $product->pages }} bet</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->coverType)
                            <div class="spec-row">
                                <dt>Muqova turi</dt>
                                <dd>{{ $product->coverType }}</dd>
                            </div>
                        @endif
                        @if($productType === 'book' && $product->lang)
                            <div class="spec-row">
                                <dt>Tili</dt>
                                <dd>{{ $product->lang }}</dd>
                            </div>
                        @endif
                        @if($product->category)
                            <div class="spec-row">
                                <dt>Kategoriya</dt>
                                <dd><a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" style="color: #4f46e5; text-decoration: underline;">{{ $product->category->name ?? $product->category->title_uz }}</a></dd>
                            </div>
                        @endif
                        @if(isset($product->tags) && count($product->tags) > 0)
                            <div class="spec-row">
                                <dt>Teglar</dt>
                                <dd>
                                    @foreach($product->tags as $tag)
                                        <a href="{{ route('web.catalog', ['search' => $tag->name]) }}" style="color: #64748b; text-decoration: none; margin-right: 4px;">#{{ $tag->name }}</a>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- UGC Customer Reviews & Ratings Section -->
            @if(isset($ugcReviews) && $ugcReviews->count() > 0)
                <div class="u-mt-xl">
                    <div class="section-header">
                        <h2 class="section-heading">Xaridorlar fikrlari va sharhlar</h2>
                    </div>
                    <div class="features-grid cc-reviews">
                        @foreach($ugcReviews as $review)
                            @php
                                $score = (int) round($review->ai_post_score ?? 5);
                                $score = max(1, min(5, $score));
                                $reviewerName = trim(($review->user->name ?? '') . ' ' . ($review->user->lastname ?? '')) ?: 'Kitobxon';
                            @endphp
                            <div class="review-card">
                                <div class="stars" aria-hidden="true">
                                    @for($s = 1; $s <= 5; $s++)
                                        <img src="{{ asset('vendor/popcorn/images/review-star.webp') }}" width="14" height="14" alt="" class="star-icon{{ $s > $score ? ' kc-star-dim' : '' }}" loading="lazy">
                                    @endfor
                                </div>
                                <h3 class="review-title">&ldquo;{{ \Illuminate\Support\Str::limit($review->text, 140) }}&rdquo;</h3>
                                <div class="u-mt-auto fw-bold">{{ $reviewerName }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Similar Products Ticker / Showcase -->
            @if(isset($similarProducts) && $similarProducts->count() > 0)
                <div class="u-mt-xl">
                    <div class="section-header">
                        <h2 class="section-heading">O'xshash kitoblar</h2>
                    </div>
                    <div class="kc-similar-grid">
                        @foreach($similarProducts as $sim)
                            <a href="{{ route('web.books.show', ['id' => $sim->id, 'slug' => \Illuminate\Support\Str::slug($sim->name)]) }}" class="kc-similar-card text-decoration-none">
                                <div class="kc-similar-cover">
                                    @if($sim->first_image)
                                        <img src="{{ asset('storage/' . $sim->first_image) }}" alt="{{ $sim->name }}" loading="lazy">
                                    @else
                                        <div class="ph">&#128218;</div>
                                    @endif
                                </div>
                                <div class="kc-similar-meta">
                                    <div class="title">{{ $sim->name }}</div>
                                    <div class="author">{{ $sim->author }}</div>
                                    <div class="price">{{ number_format($sim->discountPrice ?: $sim->price) }} UZS</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
