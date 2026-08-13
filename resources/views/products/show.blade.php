@extends('layouts.marketplace')

@php
    $titleName = $product->name . ($productType === 'book' && $product->author ? ' — ' . $product->author : '');
    $seoTitle = $titleName . ' | Kitobchi Marketpleysi';
    $rawDesc = strip_tags($product->description ?? $product->name);
    $seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $rawDesc)), 158, '…');
    $imgUrl = $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
    $isDiscounted = ($productType === 'book' ? $product->discountPrice : $product->discount_price) > 0 && ($productType === 'book' ? $product->discountPrice < $product->price : $product->discount_price < $product->price);
    $currentPrice = $isDiscounted ? ($productType === 'book' ? $product->discountPrice : $product->discount_price) : $product->price;
    $origPrice = $product->price;
    $inStock = $productType === 'book' ? ($product->count > 0) : ($product->stock > 0);

    $validTags = collect($product->tags ?? [])->filter(fn($t) => !empty(trim($t->name ?? '')) && trim($t->name) !== '#');
    $categoryName = $product->category ? ($product->category->name ?? $product->category->title_uz ?? null) : null;
    if ($categoryName && trim($categoryName) === '') { $categoryName = null; }
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
<div class="container">
    <!-- Breadcrumbs Navigation -->
    <nav class="u-mb-m" aria-label="Breadcrumb" style="font-size: 13px; color: #64748b;">
        <a href="{{ url('/') }}" class="text-decoration-none text-muted">Bosh sahifa</a>
        <span class="mx-1">/</span>
        <a href="{{ route('web.catalog') }}" class="text-decoration-none text-muted">Katalog</a>
        @if($categoryName)
            <span class="mx-1">/</span>
            <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="text-decoration-none text-muted">{{ $categoryName }}</a>
        @endif
        <span class="mx-1">/</span>
        <span class="text-dark fw-semibold">{{ \Illuminate\Support\Str::limit($product->name, 28) }}</span>
    </nav>

    <!-- Main Product Card Grid -->
    <div class="p-4 bg-white rounded-4 border shadow-sm u-mb-xl">
        <div class="row g-4">
            <!-- Left Column: Image Gallery -->
            <div class="col-md-5">
                <div class="rounded-3 overflow-hidden border bg-light position-relative text-center p-3" style="aspect-ratio: 3/4; display: grid; place-items: center;">
                    <img src="{{ $imgUrl }}" alt="{{ $product->name }}" id="mainProductImg" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    @if($isDiscounted)
                        <span class="badge bg-danger position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill fw-bold">
                            -{{ round((($origPrice - $currentPrice) / $origPrice) * 100) }}% CHEGIRMA
                        </span>
                    @endif
                </div>

                @if(is_array($product->images) && count($product->images) > 1)
                    <div class="d-flex gap-2 u-mt-m overflow-x-auto">
                        @foreach($product->images as $idx => $img)
                            <button type="button" class="btn btn-outline-light border p-1 rounded-2" onclick="document.getElementById('mainProductImg').src='{{ asset('storage/' . $img) }}'" style="width: 60px; height: 75px;">
                                <img src="{{ asset('storage/' . $img) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right Column: Product Specs & Direct Web Buying Actions -->
            <div class="col-md-7 d-flex flex-column">
                
                <!-- Stock & Category Badges -->
                <div class="d-flex align-items-center gap-2 u-mb-s flex-wrap">
                    <span class="badge bg-light text-dark border fw-bold px-3 py-2 rounded-pill" style="font-size: 12px;">
                        {{ $productType === 'book' ? 'Kitob' : 'Kanselyariya' }}
                    </span>
                    @if($categoryName)
                        <a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none fw-bold px-3 py-2 rounded-pill" style="font-size: 12px;">
                            📁 {{ $categoryName }}
                        </a>
                    @endif
                    @if($inStock)
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-3 py-2 rounded-pill" style="font-size: 12px;">
                            ✓ Sotuvda mavjud
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-bold px-3 py-2 rounded-pill" style="font-size: 12px;">
                            ⚠️ Vaqtinchalik tugagan
                        </span>
                    @endif
                </div>

                <h1 class="h3 fw-black text-dark u-mb-xs" style="line-height: 1.3;">
                    {{ $product->name }}
                </h1>

                @if($productType === 'book' && $product->author)
                    <div class="text-muted u-mb-s" style="font-size: 14.5px;">
                        Muallif: <a href="{{ route('web.catalog', ['search' => $product->author]) }}" class="fw-bold text-primary text-decoration-underline">{{ $product->author }}</a>
                    </div>
                @endif

                <!-- Valid Tags -->
                @if($validTags->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1 u-mb-m">
                        @foreach($validTags as $tag)
                            <a href="{{ route('web.catalog', ['search' => $tag->name]) }}" class="badge bg-light text-muted border text-decoration-none rounded-pill px-2 py-1" style="font-size: 11px;">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Rating -->
                @if($product->ugc_reviews_count)
                    <div class="d-flex align-items-center gap-1 u-mb-m">
                        <span class="text-warning fw-bold">⭐ {{ number_format($product->ugc_aggregate_score, 1) }}</span>
                        <span class="text-muted small">({{ $product->ugc_reviews_count }} ta xaridor bahosi)</span>
                    </div>
                @endif

                <!-- Price Block -->
                <div class="p-3 bg-light rounded-3 border u-mb-l">
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="h2 fw-black text-primary mb-0">
                            {{ number_format($currentPrice) }} <small class="fs-6">UZS</small>
                        </div>
                        @if($isDiscounted)
                            <div class="text-muted text-decoration-line-through fs-6">
                                {{ number_format($origPrice) }} UZS
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Web Purchase Buttons -->
                <div class="d-grid gap-2 d-sm-flex u-mb-l">
                    <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')">
                        🛒 Savatga qo'shish
                    </button>
                    <a href="{{ route('web.checkout') }}" class="btn btn-dark btn-lg rounded-pill px-4 fw-bold" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')">
                        ⚡ Bir klikda sotib olish
                    </a>
                </div>

                <!-- App Referral Option -->
                <div class="p-3 bg-indigo-subtle border border-indigo-subtle rounded-3 mt-auto">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <small class="fw-bold text-dark d-block">Kitobchi mobil ilovasi orqali ochasizmi?</small>
                            <small class="text-muted">Keshbek va chegirmalar bilan ilovada ko'rish mumkin.</small>
                        </div>
                        <a href="{{ $appScheme }}" class="btn btn-sm btn-outline-indigo rounded-pill fw-bold">
                            📲 Ilovada ochish
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Specifications & Description Grid -->
    <div class="row g-4 u-mb-xl">
        <div class="col-lg-7">
            <div class="p-4 bg-white rounded-4 border h-100">
                <h3 class="h5 fw-bold text-dark u-mb-m">Mahsulot haqida</h3>
                <div class="text-muted" style="line-height: 1.7; font-size: 14.5px;">
                    {!! nl2br(e($product->description ?? 'Bu mahsulot haqida qo\'shimcha ma\'lumot kiritilmagan.')) !!}
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="p-4 bg-white rounded-4 border h-100">
                <h3 class="h5 fw-bold text-dark u-mb-m">Xususiyatlari</h3>
                <table class="table table-borderless table-sm text-muted" style="font-size: 14px;">
                    <tbody>
                        <tr>
                            <td>Ombor holati:</td>
                            <td class="fw-bold text-dark text-end">{{ $inStock ? 'Sotuvda mavjud' : 'Vaqtinchalik tugagan' }}</td>
                        </tr>
                        @if($product->artikul)
                            <tr>
                                <td>Artikul:</td>
                                <td class="fw-bold text-dark text-end">{{ $product->artikul }}</td>
                            </tr>
                        @endif
                        @if($productType === 'book' && $product->isbn)
                            <tr>
                                <td>ISBN:</td>
                                <td class="fw-bold text-dark text-end">{{ $product->isbn }}</td>
                            </tr>
                        @endif
                        @if($productType === 'book' && $product->publisher && $product->publisher->name)
                            <tr>
                                <td>Nashriyot:</td>
                                <td class="fw-bold text-dark text-end">{{ $product->publisher->name }}</td>
                            </tr>
                        @endif
                        @if($productType === 'book' && $product->year)
                            <tr>
                                <td>Chop etilgan yili:</td>
                                <td class="fw-bold text-dark text-end">{{ $product->year }}</td>
                            </tr>
                        @endif
                        @if($productType === 'book' && $product->pages)
                            <tr>
                                <td>Sahifalar soni:</td>
                                <td class="fw-bold text-dark text-end">{{ $product->pages }} bet</td>
                            </tr>
                        @endif
                        @if($categoryName)
                            <tr>
                                <td>Kategoriya:</td>
                                <td class="fw-bold text-end"><a href="{{ route('web.catalog', ['category' => $product->category_id]) }}" class="text-primary text-decoration-underline">{{ $categoryName }}</a></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- O'xshash mahsulotlar -->
    @if(isset($similarProducts) && $similarProducts->isNotEmpty())
        <div class="u-mb-xl">
            <h3 class="h4 fw-black text-primary u-mb-m">O'xshash mahsulotlar</h3>
            <div class="kc-pm-grid">
                @foreach($similarProducts as $sp)
                    @php
                        $spSlug = \Illuminate\Support\Str::slug($sp->name);
                        $spUrl = $productType === 'book'
                            ? route('web.books.show', ['id' => $sp->id, 'slug' => $spSlug])
                            : route('web.stationery.show', ['id' => $sp->id, 'slug' => $spSlug]);
                        $spImg = $sp->first_image ? asset('storage/' . $sp->first_image) : asset('images/logo/logo_blue.png');
                        $spRawPrice = (float) $sp->price;
                        $spDiscountRaw = $productType === 'book' ? (float) $sp->discountPrice : (float) $sp->discount_price;
                        $spIsDiscounted = $spDiscountRaw > 0 && $spDiscountRaw < $spRawPrice;
                        $spPrice = $spIsDiscounted ? $spDiscountRaw : $spRawPrice;
                        $spSubtitle = $productType === 'book' ? ($sp->author ?: 'Kitobchi') : ($sp->material ?: 'Kanselyariya');
                    @endphp
                    <div class="kc-pm-product-card">
                        <a href="{{ $spUrl }}" class="text-decoration-none color-inherit">
                            <div class="kc-pm-cover-wrap">
                                <img src="{{ $spImg }}" alt="{{ $sp->name }}" class="kc-pm-cover-img" loading="lazy">
                                @if($spIsDiscounted)
                                    <span class="kc-pm-discount-pill">-{{ round((($spRawPrice - $spPrice) / $spRawPrice) * 100) }}%</span>
                                @endif
                            </div>
                            <h3 class="kc-pm-title">{{ $sp->name }}</h3>
                            <div class="kc-pm-author">{{ $spSubtitle }}</div>
                        </a>
                        <div class="kc-pm-card-bottom">
                            <div>
                                <div class="kc-pm-price">{{ number_format($spPrice) }} so'm</div>
                                @if($spIsDiscounted)
                                    <div class="kc-pm-old-price">{{ number_format($spRawPrice) }} so'm</div>
                                @endif
                            </div>
                            <button type="button" class="kc-pm-add-btn" onclick="addToCart({{ $sp->id }}, '{{ addslashes($sp->name) }}', {{ $spPrice }}, '{{ $spImg }}')" title="Savatchaga qo'shish">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
