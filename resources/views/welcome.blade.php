@extends('layouts.marketplace')

@section('title', 'Kitobchi — Online kitob va kanselyariya marketpleysi')

@section('content')
<div class="container">

    <!-- PiyolaMarket Circular Categories Section ("Kataloglar") -->
    <div class="piyola-cat-section">
        <h2 class="piyola-cat-title">Kataloglar</h2>
        <div class="piyola-cat-row">
            <a href="{{ route('web.catalog') }}" class="piyola-cat-item">
                <div class="piyola-cat-avatar">
                    <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Barchasi">
                </div>
                <div class="piyola-cat-name">Barchasi</div>
            </a>

            @php
                try {
                    $bookCategories = Cache::remember('web_top_categories_piyola_merged', 600, function() {
                        return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(12)->get();
                    });
                } catch (\Throwable $e) {
                    $bookCategories = collect();
                }
            @endphp

            @foreach($bookCategories as $cat)
                <a href="{{ route('web.catalog', ['category' => $cat->id]) }}" class="piyola-cat-item">
                    <div class="piyola-cat-avatar">
                        @if($cat->image)
                            <img src="{{ asset('storage/' . $cat->image) }}" alt="{{ $cat->name }}">
                        @else
                            <div class="fw-black text-primary fs-4">{{ mb_substr($cat->name, 0, 1) }}</div>
                        @endif
                    </div>
                    <div class="piyola-cat-name">{{ $cat->name }}</div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Section 1: Xaridorgir Kitoblar (Bestsellers Grid with UGC Ratings) -->
    <div class="u-mt-l u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <div>
                <h2 class="h3 fw-black text-primary mb-0">🔥 Xaridorgir mahsulotlar</h2>
                <small class="text-muted">Eng ko'p xarid qilingan original adabiyotlar</small>
            </div>
            <a href="{{ route('web.catalog') }}" class="text-primary text-decoration-none fw-bold small">
                Barchasi &rarr;
            </a>
        </div>

        @if(isset($featuredBooks) && $featuredBooks->isNotEmpty())
            <div class="piyola-product-grid">
                @foreach($featuredBooks->take(15) as $book)
                    @php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                        $rating = $book->ugc_aggregate_score > 0 ? number_format($book->ugc_aggregate_score, 1) : '5.0';
                    @endphp
                    <div class="piyola-card">
                        <a href="{{ $url }}" class="text-decoration-none color-inherit">
                            <div class="piyola-card-cover">
                                <img src="{{ $img }}" alt="{{ $book->name }}" loading="lazy">
                                @if($isDiscounted)
                                    <span class="piyola-discount-tag">-{{ round((($book->price - $price) / $book->price) * 100) }}%</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="text-warning small fw-bold">⭐ {{ $rating }}</span>
                                @if($book->ugc_reviews_count)
                                    <span class="text-muted" style="font-size: 11px;">({{ $book->ugc_reviews_count }})</span>
                                @endif
                            </div>
                            <h3 class="piyola-card-title">{{ $book->name }}</h3>
                            <div class="piyola-card-author">{{ $book->author ?: 'Kitobchi' }}</div>
                        </a>
                        <div class="piyola-card-footer">
                            <div>
                                <div class="piyola-card-price">{{ number_format($price) }} so'm</div>
                                @if($isDiscounted)
                                    <div class="piyola-card-old-price">{{ number_format($book->price) }} so'm</div>
                                @endif
                            </div>
                            <button type="button" class="piyola-add-cart-btn" onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')" title="Savatchaga qo'shish">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-5 bg-white rounded-4 border text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        @endif
    </div>

</div>
@endsection
