@extends('layouts.marketplace')

@section('title', 'Kitobchi — Original kitoblar va atirlar online do\'koni')

@section('content')
<div class="container">

    <!-- Hero Banner -->
    <div class="kc-pm-banner u-mt-m u-mb-l">
        <div class="kc-pm-banner-title">Original kitoblar va kanselyariya —<br>bir joyda</div>
        <div class="kc-pm-banner-sub">O'zbekiston bo'ylab tezkor yetkazib berish. Minglab kitob va o'quv qurollari eng qulay narxlarda.</div>
        <a href="{{ route('web.catalog') }}" class="kc-pm-banner-btn">
            Xarid qilishni boshlash &rarr;
        </a>
    </div>

    <!-- PiyolaMarket Circular Categories Section ("Kataloglar") -->
    <div class="kc-pm-cat-section">
        <h2 class="h3 fw-black text-primary u-mb-m">Kataloglar</h2>
        <div class="kc-pm-cat-row">
            <a href="{{ route('web.catalog') }}" class="kc-pm-cat-card">
                <div class="kc-pm-cat-avatar">
                    <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Barchasi">
                </div>
                <div class="kc-pm-cat-name">Barchasi</div>
            </a>

            @php
                try {
                    // MUHIM: jadvalda `status`/`name` ustunlari yo'q (faqat
                    // is_active va name_uz/ru/en/ja) — noto'g'ri ustun nomi
                    // SQL xatoga sabab bo'lib, try/catch uni yutib yuborardi,
                    // kategoriyalar hech qachon ko'rinmasdi.
                    $bookCategories = Cache::remember('web_top_categories_avatars', 600, function() {
                        return \App\Models\BookCategories::where('is_active', true)->orderBy('name_uz')->take(10)->get();
                    });
                } catch (\Throwable $e) {
                    $bookCategories = collect();
                }
            @endphp

            @foreach($bookCategories as $cat)
                <a href="{{ route('web.catalog', ['category' => $cat->id]) }}" class="kc-pm-cat-card">
                    <div class="kc-pm-cat-avatar">
                        {{-- Jadvalda haqiqiy rasm ustuni yo'q — faqat bitta belgili
                             `icon` bor. Shu sabab emoji/belgi katta ko'rsatiladi,
                             u ham bo'lmasa nom bosh harfiga tushadi. --}}
                        @if($cat->icon)
                            <div class="fs-3">{{ $cat->icon }}</div>
                        @else
                            <div class="fw-black text-primary fs-4">{{ mb_substr($cat->name, 0, 1) }}</div>
                        @endif
                    </div>
                    <div class="kc-pm-cat-name">{{ $cat->name }}</div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Section 1: Xaridorgir Mahsulotlar (Bestsellers Grid) -->
    <div class="u-mt-l u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <h2 class="h3 fw-black text-primary mb-0">Xaridorgir mahsulotlar</h2>
            <a href="{{ route('web.catalog') }}" class="text-primary text-decoration-none fw-bold small">
                Barchasi &rarr;
            </a>
        </div>

        @if(isset($featuredBooks) && $featuredBooks->isNotEmpty())
            <div class="kc-pm-grid">
                @foreach($featuredBooks->take(15) as $book)
                    @php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    @endphp
                    <div class="kc-pm-product-card">
                        <a href="{{ $url }}" class="text-decoration-none color-inherit">
                            <div class="kc-pm-cover-wrap">
                                <img src="{{ $img }}" alt="{{ $book->name }}" class="kc-pm-cover-img" loading="lazy">
                                @if($isDiscounted)
                                    <span class="kc-pm-discount-pill">-{{ round((($book->price - $price) / $book->price) * 100) }}%</span>
                                @endif
                            </div>
                            <h3 class="kc-pm-title">{{ $book->name }}</h3>
                            <div class="kc-pm-author">{{ $book->author ?: 'Kitobchi' }}</div>
                        </a>
                        <div class="kc-pm-card-bottom">
                            <div>
                                <div class="kc-pm-price">{{ number_format($price) }} so'm</div>
                                @if($isDiscounted)
                                    <div class="kc-pm-old-price">{{ number_format($book->price) }} so'm</div>
                                @endif
                            </div>
                            <button type="button" class="kc-pm-add-btn" onclick="addToCart({{ $book->id }}, '{{ addslashes($book->name) }}', {{ $price }}, '{{ $img }}')" title="Savatchaga qo'shish">
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
