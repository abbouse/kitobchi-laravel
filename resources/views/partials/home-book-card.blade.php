@php
    $slug = \Illuminate\Support\Str::slug($book->name);
    $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
    $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
    
    $basePrice = floatval($book->price);
    $discPrice = floatval($book->discountPrice);
    
    $isDisc = $discPrice > 0 && $basePrice > 0 && $discPrice < $basePrice;
    $price = $isDisc ? $discPrice : $basePrice;
    $discPct = $isDisc ? round((($basePrice - $price) / $basePrice) * 100) : 0;
    
    // Kitobchi specific monthly installment calculation (e.g., Alif nasiya or just simple 12 month div)
    $monthly = ceil($price / 12);

    $isFav = in_array($book->id, $favoritedBookIds ?? [], true);
@endphp

<a class="group relative flex flex-col rounded-xl bg-white border border-white hover:shadow-md transition-all duration-200 overflow-hidden" href="{{ $url }}">
    <div class="relative w-full rounded-xl bg-white" style="aspect-ratio: 232 / 309;">
        <div class="focus:outline-none h-full relative z-0">
            <div class="overflow-hidden h-full">
                <div class="flex items-start flex-row h-full">
                    <div class="min-w-0 shrink-0 basis-full aspect-[3/4] overflow-hidden">
                        <div class="w-full h-full rounded-xl overflow-hidden bg-gray-50 flex items-center justify-center">
                            <img alt="{{ $book->name }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" loading="lazy" src="{{ $img }}"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isDisc)
            <div class="absolute bottom-1.5 left-1.5 md:bottom-2 md:left-2 z-20 inline-flex items-start flex-col gap-1">
                <span class="font-medium inline-flex items-center text-xs gap-1 rounded-md text-inverted px-1 py-0.5 bg-[#ED3131] text-white">
                    -{{ $discPct }}%
                </span>
            </div>
        @endif

        <div class="absolute top-1.5 right-1.5 md:top-2 md:right-2 z-20">
            <div class="relative overflow-hidden transition-shadow duration-300 rounded-2xl px-5 py-[14px] hover:shadow-sm hover:shadow-black/10 backdrop-blur-sm glass-card-bg p-0!">
                <div class="absolute inset-0 pointer-events-none glass-border rounded-2xl"></div>
                <button aria-label="Sevimlilar" data-fav="{{ $isFav ? '1' : '0' }}" onclick="event.preventDefault(); toggleFavorite(this, {{ $book->id }}, 'book');" class="relative w-8 h-8 flex items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95 text-gray-500 hover:text-gray-900">
                    <iconify-icon aria-hidden="true" icon="{{ $isFav ? 'heroicons-solid:heart' : 'heroicons:heart' }}" class="w-5 h-5 relative z-10 transition-colors" style="{{ $isFav ? 'color:#ef4444;' : '' }}"></iconify-icon>
                </button>
            </div>
        </div>
    </div>

    <div class="px-1 pt-4 pb-4 flex flex-col h-full">
        <div class="group/title grow">
            <div class="text-sm leading-snug line-clamp-2 transition-colors duration-300 group-hover:text-primary-600 text-neutral-900 dark:text-white">
                {{ $book->name }}
            </div>
        </div>
        <div class="flex items-start justify-between gap-2 mt-2">
            <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 font-semibold leading-tight">
                {{ number_format($price, 0, '', ' ') }} so'm
            </p>
        </div>
        <div class="mt-auto">
            <span class="inline-block px-2 py-0.5 text-xs md:text-sm font-medium bg-primary-100 text-primary-500 rounded-full my-1">
                {{ number_format($monthly, 0, '', ' ') }} so'm/oyiga
            </span>
        </div>
    </div>
</a>
