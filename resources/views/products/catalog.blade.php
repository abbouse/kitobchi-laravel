@extends('layouts.marketplace')

@section('title', 'Kitoblar va Mahsulotlar Katalogi | Kitobchi Marketpleysi')

@section('content')
<div class="container">

    <!-- Page Title Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 u-mb-m">
        <div>
            <h1 class="h3 fw-black text-dark mb-0">Kitoblar va Mahsulotlar Katalogi</h1>
            <small class="text-muted">Barcha original adabiyotlar va o'quv qurollari</small>
        </div>
        <div class="text-muted small">
            Jami {{ $books->total() }} ta mahsulot topildi
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Filters Column -->
        <div class="col-lg-3">
            <div class="p-3 bg-white rounded-4 border shadow-sm sticky-top" style="top: 90px; z-index: 10;">
                <h6 class="fw-extrabold text-dark u-mb-s">Kategoriyalar</h6>
                <div class="list-group list-group-flush mb-4" style="font-size: 13.5px;">
                    <a href="{{ route('web.catalog') }}" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded-2 {{ !request('category') ? 'active fw-bold' : '' }}">
                        Barchasi
                    </a>
                    @if(isset($bookCategories) && $bookCategories->isNotEmpty())
                        @foreach($bookCategories as $cat)
                            <a href="{{ route('web.catalog', ['category' => $cat->id, 'search' => request('search')]) }}" class="list-group-item list-group-item-action border-0 px-2 py-2 rounded-2 {{ request('category') == $cat->id ? 'active fw-bold' : '' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    @endif
                </div>

                <div class="border-top pt-3">
                    <a href="{{ route('web.catalog') }}" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                        Filtrlarni tozash
                    </a>
                </div>
            </div>
        </div>

        <!-- Catalog Product Grid Column -->
        <div class="col-lg-9">
            @if($books->count() > 0)
                <div class="kc-mk-grid">
                    @foreach($books as $book)
                        @php
                            $slug = \Illuminate\Support\Str::slug($book->name);
                            $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                            $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                            $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                            $price = $isDiscounted ? $book->discountPrice : $book->price;
                        @endphp
                        <div class="kc-product-card">
                            <a href="{{ $url }}" class="text-decoration-none color-inherit">
                                <div class="kc-card-cover-wrap">
                                    <img src="{{ $img }}" alt="{{ $book->name }}" class="kc-card-cover-img" loading="lazy">
                                    @if($isDiscounted)
                                        <span class="kc-discount-badge">-{{ round((($book->price - $price) / $book->price) * 100) }}%</span>
                                    @endif
                                </div>
                                <h3 class="kc-card-title">{{ $book->name }}</h3>
                                <div class="kc-card-author">{{ $book->author ?: 'Kitobchi' }}</div>
                            </a>
                            <div class="kc-card-bottom">
                                <div>
                                    <div class="kc-card-price-main">{{ number_format($price) }} <small>UZS</small></div>
                                    @if($isDiscounted)
                                        <div class="kc-card-price-old">{{ number_format($book->price) }} UZS</div>
                                    @endif
                                </div>
                                <button type="button" class="kc-add-cart-btn" onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')" title="Savatga qo'shish">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="u-mt-xl d-flex justify-content-center">
                    {{ $books->withQueryString()->links() }}
                </div>
            @else
                <div class="p-5 bg-white rounded-4 border text-center">
                    <div style="font-size: 48px;">📚</div>
                    <h3 class="fw-bold text-dark mt-2">Mahsulotlar topilmadi</h3>
                    <p class="text-muted" style="max-width: 400px; margin: 0 auto 16px;">
                        Kiritilgan so'rov bo'yicha mahsulotlar topilmadi. Qidiruvni o'zgartirib ko'ring.
                    </p>
                    <a href="{{ route('web.catalog') }}" class="btn btn-primary rounded-pill px-4">
                        Barcha katalogga qaytish
                    </a>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
