@extends('layouts.marketplace')

@section('title', 'Читай-город uslubidagi Kitobchi onlayn kitoblar do\'koni')

@section('content')
<div class="container">

    <!-- Chitai-Gorod Shelf Box 1: Ommabop Kitoblar (Bestsellers Shelf) -->
    <div class="cg-shelf-box">
        <div class="cg-shelf-header">
            <h2 class="cg-shelf-title">🔥 Ommabop kitoblar</h2>
            <a href="{{ route('web.catalog') }}" class="cg-shelf-link">Barchasini ko'rish &rarr;</a>
        </div>

        @if(isset($featuredBooks) && $featuredBooks->isNotEmpty())
            <div class="cg-product-grid">
                @foreach($featuredBooks->take(15) as $book)
                    @php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    @endphp
                    <div class="cg-product-card">
                        <a href="{{ $url }}" class="text-decoration-none color-inherit">
                            <div class="cg-card-image-wrap">
                                <img src="{{ $img }}" alt="{{ $book->name }}" loading="lazy">
                                @if($isDiscounted)
                                    <span class="cg-badge-sale">-{{ round((($book->price - $price) / $book->price) * 100) }}%</span>
                                @endif
                            </div>

                            <div class="cg-card-price-row">
                                <span class="cg-card-price">{{ number_format($price) }} so'm</span>
                                @if($isDiscounted)
                                    <span class="cg-card-old-price">{{ number_format($book->price) }} so'm</span>
                                @endif
                            </div>

                            <h3 class="cg-card-title">{{ $book->name }}</h3>
                            <div class="cg-card-author">{{ $book->author ?: 'Kitobchi' }}</div>
                        </a>

                        <button type="button" class="cg-card-btn" onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span>Savatga</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-5 text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        @endif
    </div>

</div>
@endsection
