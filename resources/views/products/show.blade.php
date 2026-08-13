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
    <!-- Breadcrumbs -->
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

    <!-- Chitai-Gorod Style Main Product Box -->
    <div class="cg-shelf-box">
        <div class="row g-4">
            <!-- Left Column: Image -->
            <div class="col-md-5">
                <div class="cg-card-image-wrap text-center p-3" style="aspect-ratio: 13/20; position: relative;">
                    <img src="{{ $imgUrl }}" alt="{{ $product->name }}" id="mainProductImg" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    @if($isDiscounted)
                        <span class="cg-badge-sale" style="font-size: 12px; padding: 4px 10px;">
                            -{{ round((($origPrice - $currentPrice) / $origPrice) * 100) }}% CHEGIRMA
                        </span>
                    @endif
                </div>

                @if(is_array($product->images) && count($product->images) > 1)
                    <div class="d-flex gap-2 u-mt-m overflow-x-auto">
                        @foreach($product->images as $idx => $img)
                            <button type="button" class="btn btn-outline-light border p-1 rounded-3" onclick="document.getElementById('mainProductImg').src='{{ asset('storage/' . $img) }}'" style="width: 60px; height: 80px;">
                                <img src="{{ asset('storage/' . $img) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right Column: Specs & Buying Actions -->
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

                <h1 class="h2 fw-black text-dark u-mb-xs" style="line-height: 1.3;">
                    {{ $product->name }}
                </h1>

                @if($productType === 'book' && $product->author)
                    <div class="text-muted u-mb-s" style="font-size: 15px;">
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
                            {{ number_format($currentPrice) }} <small class="fs-6">so'm</small>
                        </div>
                        @if($isDiscounted)
                            <div class="text-muted text-decoration-line-through fs-6">
                                {{ number_format($origPrice) }} so'm
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Web Purchase Buttons -->
                <div class="d-grid gap-2 d-sm-flex u-mb-l">
                    <button type="button" class="btn btn-primary btn-lg rounded-3 px-4 fw-bold" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')">
                        🛒 Savatga qo'shish
                    </button>
                    <a href="{{ route('web.checkout') }}" class="btn btn-dark btn-lg rounded-3 px-4 fw-bold" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $currentPrice }}, '{{ $imgUrl }}')">
                        ⚡ Bir klikda sotib olish
                    </a>
                </div>

                <!-- Description -->
                @if(!empty($product->description))
                    <div class="border-top pt-4 mt-auto">
                        <h5 class="fw-bold text-dark mb-2">Kitob haqida ma'lumot</h5>
                        <div class="text-muted small" style="line-height: 1.7; white-space: pre-line;">
                            {!! strip_tags($product->description) !!}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
