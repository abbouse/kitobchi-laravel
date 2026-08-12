@extends('layouts.landing')

@section('title', 'Kitoblar va Kanselyariya Katalogi | Kitobchi')

@push('meta')
    @include('partials.seo-social', [
        'title' => 'Kitoblar va Kanselyariya Katalogi | Kitobchi',
        'description' => 'Kitobchi platformasidagi barcha kitoblar, yangi nashrlar, bestsellerlar va kanselyariya mahsulotlari katalogi.',
        'canonical' => route('web.catalog'),
    ])
@endpush

@section('content')
<div class="kc-catalog-page">
    <div class="page-padding">
        <div class="container">
            <div class="section-header">
                <div class="eyebrow-pill">
                    <div class="eyebrow-pill-inner"><div>Katalog</div></div>
                    <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                </div>
                <h1 class="section-heading">Kitoblar va Mahsulotlar Katalogi</h1>
                <p class="subheading">Sevimli kitoblaringizni qidiring, ko'ring va Kitobchi ilovasida eng qulay narxda xarid qiling.</p>
            </div>

            <!-- Search Bar -->
            <form action="{{ route('web.catalog') }}" method="GET" class="kc-catalog-search-form u-mb-xl">
                <div class="kc-search-input-wrap">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Kitob nomi, muallif, ISBN yoki artikul bo'yicha qidirish..." class="kc-catalog-search-input">
                    <button type="submit" class="cta w-inline-block">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong>Qidirish</strong></div>
                        </div>
                    </button>
                </div>
            </form>

            <!-- Product Grid -->
            @if($books->count() > 0)
                <div class="kc-similar-grid kc-catalog-grid">
                    @foreach($books as $book)
                        <a href="{{ route('web.books.show', ['id' => $book->id, 'slug' => \Illuminate\Support\Str::slug($book->name)]) }}" class="kc-similar-card text-decoration-none">
                            <div class="kc-similar-cover">
                                @if($book->first_image)
                                    <img src="{{ asset('storage/' . $book->first_image) }}" alt="{{ $book->name }}" loading="lazy">
                                @else
                                    <div class="ph">&#128218;</div>
                                @endif
                            </div>
                            <div class="kc-similar-meta">
                                <div class="title">{{ $book->name }}</div>
                                <div class="author">{{ $book->author }}</div>
                                <div class="price">{{ number_format($book->discountPrice ?: $book->price) }} UZS</div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="u-mt-xl d-flex justify-content-center">
                    {{ $books->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <h3>Mahsulotlar topilmadi</h3>
                    <p class="text-muted">Qidiruv so'rovini o'zgartirib ko'ring yoki bosh sahifaga qayting.</p>
                    <a href="{{ route('web.catalog') }}" class="btn btn-outline-primary mt-2">Barcha kitoblar</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
