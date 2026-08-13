@extends('layouts.marketplace')

@section('title', "Sevimlilar | Kitobchi")

@section('content')
<div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-6 rounded-t-2xl grow">
    
    <div class="flex items-center gap-2 mb-5">
        <a href="{{ route('web.catalog') }}" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25">
            <i class="icon-up-arrow text-xl -rotate-135"></i>
        </a>
        <nav aria-label="breadcrumb" class="relative min-w-0">
            <ol class="flex items-center gap-2">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                    <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">Asosiy</a>
                </li>
                <li class="flex text-gray text-xs">/</li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm font-semibold">
                    Sevimlilar
                </li>
            </ol>
        </nav>
    </div>

    <h1 class="text-4xl text-primary font-bold dark:text-white mb-6">
        Sevimlilar
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
                    Sevimlilar ro'yxati bo'sh
                </h2>
                <p class="text-neutral-500 mb-6">
                    Ushbu bo’limda hozircha ma’lumot yo’q, ammo tez orada qo’shiladi
                </p>
                <div>
                    <a href="{{ route('web.catalog') }}" class="font-medium items-center transition-colors py-1.5 gap-1.5 text-inverted bg-primary hover:bg-primary/75 h-12 justify-center sm:min-w-40 rounded-2xl text-base max-md:w-full inline-flex px-6" style="color:#fff;">
                        Katalogga o‘tish
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
                    @include('partials.home-book-card', ['book' => $product, 'isStationery' => $isStationery])
                    
                    <div class="absolute top-3 right-3 z-20">
                        <button onclick="event.preventDefault(); removeFavoriteCard(this, {{ $product->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/80 backdrop-blur-sm shadow-sm hover:bg-white text-error-500 transition-colors">
                            <iconify-icon icon="heroicons-solid:heart" class="text-lg"></iconify-icon>
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
