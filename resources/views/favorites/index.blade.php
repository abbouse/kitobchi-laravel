@extends('layouts.marketplace')

@section('title', "Sevimlilar | Kitobchi")

@push('meta')
<meta name="robots" content="noindex, follow">
@endpush

@section('content')
<div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-6 rounded-t-2xl grow">
    
    {{-- MUHIM (piyolamarket'ga moslashtirish): piyolamarket.uz'ning
         /favorites sahifasida breadcrumb yoki "orqaga" tugmasi yo'q — faqat
         sodda "Sevimlilar" sarlavhasi bor. Avval bu yerda katalog
         sahifasidagi kabi breadcrumb+back-button+son (parenthesis ichida)
         ko'rsatilardi — bu piyoladan farq qilardi, endi olib tashlandi. --}}
    <h1 class="text-4xl text-primary font-bold dark:text-white mb-6">
        {{ __('marketplace.favorites_title') }}
    </h1>

    @if($items->isEmpty())
        <!-- ====== EMPTY STATE ====== -->
        <div class="pt-10 pb-20">
            <div class="py-10 text-center">
                <div class="w-full">
                    <img alt="Empty favorites" class="w-full max-w-[250px] mx-auto mb-4" src="{{ asset('images/empty-favorites.svg') }}">
                </div>
                <h2 class="text-xl font-bold dark:text-white mb-2">
                    {{ __('marketplace.favorites_empty_title') }}
                </h2>
                <p class="text-neutral-500 mb-6">
                    {{ __('marketplace.favorites_empty_desc') }} <svg viewBox="0 0 24 24" fill="#ef4444" style="width:1em;height:1em;display:inline;vertical-align:-2px;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                </p>
                <div>
                    <a href="{{ route('web.catalog') }}" class="font-medium items-center transition-colors py-1.5 gap-1.5 text-inverted bg-primary hover:bg-primary/75 h-12 justify-center sm:min-w-40 rounded-2xl text-base max-md:w-full inline-flex px-6" style="color:#fff;">
                        {{ __('marketplace.go_to_catalog') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- ====== FAVORITES GRID ====== -->
        <div id="kcFavGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5 xl:gap-6">
            @foreach($items as $fav)
                @php
                    $product = $fav->product;
                    $isStationery = $fav->type === 'stationery';
                    $favSlug = \Illuminate\Support\Str::slug($product->name);
                    $favUrl = $isStationery
                        ? route('web.stationery.show', ['id' => $product->id, 'slug' => $favSlug])
                        : route('web.books.show', ['id' => $product->id, 'slug' => $favSlug]);
                    $favImg = $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
                    $favRawPrice = (float) $product->price;
                    $favDiscRaw = $isStationery ? (float) ($product->discount_price ?? 0) : (float) ($product->discountPrice ?? 0);
                    $favIsDisc = $favDiscRaw > 0 && $favDiscRaw < $favRawPrice;
                    $favPrice = $favIsDisc ? $favDiscRaw : $favRawPrice;
                @endphp
                
                <div class="relative group block h-full">
                    @include('partials.home-book-card', ['book' => $product, 'isStationery' => $isStationery, 'showFavButton' => false])

                    {{-- Boshqa yerdagi (katalog/bosh sahifa) o'z-o'zidan yashirin
                         yurak tugmasi bilan bir xil "glass-card-bg" uslubi — faqat
                         bu yerda bosilganda karta darhol gridd'an olib tashlanadi
                         (sevimlilar sahifasiga xos xatti-harakat). --}}
                    <div class="absolute top-1.5 right-1.5 md:top-2 md:right-2 z-20">
                        <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm glass-card-bg p-0!">
                            <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
                            <button aria-label="{{ __('marketplace.favorites') }}" onclick="event.preventDefault(); removeFavoriteCard(this, {{ $product->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');" class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95">
                                <svg viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="1.6" class="relative z-10" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
    function removeFavoriteCard(btn, productId, productType) {
        const card = btn.closest('.relative.group.block');
        if (card) {
            card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.92)';
            card.style.pointerEvents = 'none';
        }

        fetch("{{ route('web.favorites.toggle') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product_id: productId, product_type: productType }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (typeof bumpFavBadge === 'function') bumpFavBadge(-1);
                setTimeout(() => {
                    if (card) card.remove();
                    const grid = document.getElementById('kcFavGrid');
                    if (grid && grid.children.length === 0) {
                        location.reload();
                    }
                }, 250);
            } else if (card) {
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.pointerEvents = 'auto';
            }
        })
        .catch(() => {
            if (card) {
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.pointerEvents = 'auto';
            }
        });
    }
</script>
@endpush
@endsection
