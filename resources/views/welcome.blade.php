@extends('layouts.marketplace')

@section('title', 'Kitobchi — Online Kitoblar va Kanselyariya Marketpleysi')

@section('content')
<div class="container">

    <!-- Top Compact Category Banner Strip -->
    <div class="p-4 bg-white rounded-3 border u-mb-l d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="h4 fw-extrabold text-dark mb-1">Kitobchi Onlayn Marketpleysi</h1>
            <p class="text-muted small mb-0">Original kitoblar va o'quv qurollarini vebda to'g'ridan-to'g'ri xarid qiling</p>
        </div>
        <a href="{{ route('web.catalog') }}" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold">
            Barcha katalog &rarr;
        </a>
    </div>

    <!-- Section 1: Ommabop Kitoblar Grid (5 columns) -->
    <div class="u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <h2 class="h5 fw-extrabold text-dark mb-0">🔥 Ommabop kitoblar</h2>
            <a href="{{ route('web.catalog') }}" class="text-primary text-decoration-none small fw-bold">Barchasi &rarr;</a>
        </div>

        @if(isset($featuredBooks) && $featuredBooks->isNotEmpty())
            <div class="kc-products-grid">
                @foreach($featuredBooks->take(15) as $book)
                    @php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    @endphp
                    <div class="kc-card">
                        <a href="{{ $url }}" class="text-decoration-none color-inherit">
                            <div class="kc-card-img-wrap">
                                <img src="{{ $img }}" alt="{{ $book->name }}" class="kc-card-img" loading="lazy">
                                @if($isDiscounted)
                                    <span class="kc-badge-sale">-{{ round((($book->price - $price) / $book->price) * 100) }}%</span>
                                @endif
                            </div>
                            <h3 class="kc-card-name">{{ $book->name }}</h3>
                            <div class="kc-card-sub">{{ $book->author ?: 'Kitobchi' }}</div>
                        </a>
                        <div class="kc-card-footer">
                            <div>
                                <div class="kc-price-val">{{ number_format($price) }} so'm</div>
                                @if($isDiscounted)
                                    <div class="kc-price-old">{{ number_format($book->price) }} so'm</div>
                                @endif
                            </div>
                            <button type="button" class="kc-btn-cart-add" onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')" title="Savatga qo'shish">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-5 bg-white rounded-3 border text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        @endif
    </div>

</div>
@endsection
