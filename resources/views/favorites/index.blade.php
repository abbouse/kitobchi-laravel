@extends('layouts.marketplace')

@section('title', "Sevimlilar | Kitobchi")

@section('content')
<div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-6 rounded-t-2xl grow">
    
    <div class="flex items-center gap-2 mb-5">
        <a href="{{ route('web.catalog') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300" title="{{ __('marketplace.back_to_catalog') }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </a>
        <nav aria-label="breadcrumb" class="relative min-w-0">
            <ol class="flex items-center gap-2">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                    <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_home') }}</a>
                </li>
                <li class="flex text-gray text-xs">/</li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm font-semibold">
                    {{ __('marketplace.favorites_title') }}
                </li>
            </ol>
        </nav>
    </div>

    <h1 class="text-4xl text-primary font-bold dark:text-white mb-6">
        {{ __('marketplace.favorites_title') }}
        @if($items->isNotEmpty())
            <span class="text-xl font-medium text-neutral-400">({{ $items->count() }})</span>
        @endif
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

                    <div class="absolute top-3 right-3 z-20">
                        <button onclick="event.preventDefault(); removeFavoriteCard(this, {{ $product->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/80 backdrop-blur-sm shadow-sm hover:bg-white text-error-500 transition-colors">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="text-lg" style="width:1em;height:1em;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                        </button>
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
