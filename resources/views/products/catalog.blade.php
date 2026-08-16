@extends('layouts.marketplace')

@section('title', 'Kitoblar va Mahsulotlar Katalogi | Kitobchi')

@push('meta')
<meta name="description" content="Kitobchi'da barcha original kitoblar, darsliklar va kanselyariya mahsulotlarini toping. Qulay filtrlar, tezkor yetkazib berish.">
@endpush

@section('content')
<div class="kc-page-surface">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        @php
            $isStationery = $type === 'stationery';
            $sort = $sort ?? 'popular';
            $baseParams = array_filter([
                'search' => $search ?: null,
                'category' => request('category'),
                'sort' => $sort !== 'popular' ? $sort : null,
                'price_min' => $priceMin ?? null,
                'price_max' => $priceMax ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $sortLabels = ['popular' => 'Ommabop', 'new' => 'Yangi', 'price_asc' => 'Arzon narx', 'price_desc' => 'Qimmat narx'];
        @endphp

        <!-- ====== FILTER ROW (top) ====== -->
        <div class="flex-y-center justify-between flex-wrap gap-3 pt-6 pb-2">
            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl text-primary font-bold">
                {{ $type === 'book' ? 'Kitoblar' : 'Kanselyariya' }}
                @if($search) 
                    <span class="text-neutral-500 font-medium text-lg ml-2">"{{ $search }}" bo'yicha qidiruv</span>
                @endif
            </h1>
            <span style="font-size:0.875rem;color:#9ca3af;font-weight:500;">{{ $products->total() }} ta mahsulot</span>
        </div>

        <!-- ====== HORIZONTAL FILTERS ====== -->
        <div class="flex items-center gap-2 py-4 overflow-x-auto no-scrollbar flex-nowrap" style="-ms-overflow-style:none;scrollbar-width:none;">
            <!-- Type Switcher Pill -->
            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'book'])) }}"
               class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-2 {{ $type === 'book' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10' }}">
                Kitoblar
            </a>
            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'stationery'])) }}"
               class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-2 {{ $type === 'stationery' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10' }}">
                Kanselyariya
            </a>

            <!-- Divider -->
            <div class="w-px h-6 bg-secondary-200 mx-1 shrink-0"></div>

            <!-- Sort Dropdown -->
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcSortMenu')"
                        class="inline-flex items-center gap-1.5 text-primary hover:bg-primary/10 bg-secondary-300 rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">Saralash: {{ $sortLabels[$sort] ?? 'Ommabop' }}</span>
                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="size-5"></iconify-icon>
                </button>
                <div id="kcSortMenu" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;min-width:180px;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:0.375rem;">
                    @foreach($sortLabels as $sortKey => $sortLabel)
                        <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'sort' => $sortKey !== 'popular' ? $sortKey : null])) }}"
                           style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0.75rem;border-radius:0.625rem;text-decoration:none;font-size:0.875rem;{{ $sort === $sortKey ? 'background:var(--color-tima-50);font-weight:700;color:var(--color-tima-600);' : 'font-weight:500;color:#111827;' }}">
                            {{ $sortLabel }}
                            @if($sort === $sortKey)
                                <iconify-icon icon="lucide:check" style="font-size:14px;"></iconify-icon>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Price Popover -->
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcPriceMenu')"
                        class="inline-flex items-center gap-1.5 {{ ($priceMin || $priceMax) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">Narx</span>
                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="size-5"></iconify-icon>
                </button>
                <div id="kcPriceMenu" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;width:300px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                        @if($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                        @foreach($selectedSellers ?? [] as $sid)<input type="hidden" name="seller_ids[]" value="{{ $sid }}">@endforeach
                        @foreach($selectedPublishers ?? [] as $pid)<input type="hidden" name="publisher_ids[]" value="{{ $pid }}">@endforeach

                        <div class="flex items-center gap-3 mb-4">
                            <input type="number" name="price_min" value="{{ $priceMin }}" placeholder="Dan" class="w-full h-11 bg-secondary-100 rounded-xl px-4 text-[15px] font-medium text-primary outline-none focus:ring-2 ring-primary/20 transition-all border-none">
                            <span class="text-neutral-400 font-medium">-</span>
                            <input type="number" name="price_max" value="{{ $priceMax }}" placeholder="Gacha" class="w-full h-11 bg-secondary-100 rounded-xl px-4 text-[15px] font-medium text-primary outline-none focus:ring-2 ring-primary/20 transition-all border-none">
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">
                            Qo'llash
                        </button>
                    </form>
                </div>
            </div>

            <!-- Shops Popover -->
            @if(isset($sellers) && $sellers->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcShopsMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedSellers) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">Do'konlar</span>
                    @if(!empty($selectedSellers)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedSellers) }}</span> @endif
                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="size-5"></iconify-icon>
                </button>
                <div id="kcShopsMenu" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                        @if($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                        @if($priceMin)<input type="hidden" name="price_min" value="{{ $priceMin }}">@endif
                        @if($priceMax)<input type="hidden" name="price_max" value="{{ $priceMax }}">@endif
                        @foreach($selectedPublishers ?? [] as $pid)<input type="hidden" name="publisher_ids[]" value="{{ $pid }}">@endforeach

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($sellers as $seller)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="seller_ids[]" value="{{ $seller->id }}" class="peer sr-only" {{ in_array($seller->id, $selectedSellers ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <iconify-icon icon="lucide:check" class="text-white text-[10px]"></iconify-icon>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $seller->shop_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">Qo'llash</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Publishers Popover -->
            @if($type !== 'stationery' && isset($publishers) && $publishers->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcPublishersMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedPublishers) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">Nashriyotlar</span>
                    @if(!empty($selectedPublishers)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedPublishers) }}</span> @endif
                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="size-5"></iconify-icon>
                </button>
                <div id="kcPublishersMenu" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                        @if($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                        @if($priceMin)<input type="hidden" name="price_min" value="{{ $priceMin }}">@endif
                        @if($priceMax)<input type="hidden" name="price_max" value="{{ $priceMax }}">@endif
                        @foreach($selectedSellers ?? [] as $sid)<input type="hidden" name="seller_ids[]" value="{{ $sid }}">@endforeach

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($publishers as $publisher)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="publisher_ids[]" value="{{ $publisher->id }}" class="peer sr-only" {{ in_array($publisher->id, $selectedPublishers ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <iconify-icon icon="lucide:check" class="text-white text-[10px]"></iconify-icon>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $publisher->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">Qo'llash</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Clear filters -->
            @if(request('category') || $priceMin || $priceMax || $sort !== 'popular' || $search || !empty($selectedSellers) || !empty($selectedPublishers))
                <a href="{{ route('web.catalog', ['type' => $type]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-1.5 bg-error-50 text-error-500 hover:bg-error-100 border-none ml-2">
                    <iconify-icon icon="lucide:x" class="text-lg"></iconify-icon>
                    Tozalash
                </a>
            @endif
        </div>

        <!-- Categories Horizontal -->
        <div class="flex items-center gap-2 pb-5 mb-5 overflow-x-auto no-scrollbar flex-nowrap" style="-ms-overflow-style:none;scrollbar-width:none;border-bottom:1px solid var(--kc-border-light);">
            @php $activeCategories = $isStationery ? ($stationeryCategories ?? collect()) : ($bookCategories ?? collect()); @endphp
            <a href="{{ route('web.catalog', array_merge(array_diff_key($baseParams, ['category' => 1]), ['type' => $type])) }}"
               class="inline-flex items-center px-4 py-2 rounded-full text-[15px] font-medium transition-colors shrink-0 {{ !request('category') ? 'bg-primary text-white' : 'bg-transparent text-primary hover:bg-secondary-100' }}">
                Barchasi
            </a>
            @foreach($activeCategories as $cat)
                <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'category' => $cat->id])) }}"
                   class="inline-flex items-center px-4 py-2 rounded-full text-[15px] font-medium transition-colors shrink-0 {{ request('category') == $cat->id ? 'bg-primary text-white' : 'bg-transparent text-primary hover:bg-secondary-100' }}">
                    {{ $cat->name_uz ?? $cat->name }}
                </a>
            @endforeach
        </div>

        <!-- ====== PRODUCT GRID ====== -->
        <main class="w-full">

                @if($products->count() > 0)
                    <!-- Product Grid — grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 -->
                    <div id="kcCatalogGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;margin-bottom:2rem;">
                        @foreach($products as $item)
                            @php
                                $slug = \Illuminate\Support\Str::slug($item->name);
                                $url = $isStationery
                                    ? route('web.stationery.show', ['id' => $item->id, 'slug' => $slug])
                                    : route('web.books.show', ['id' => $item->id, 'slug' => $slug]);
                                $img = $item->first_image ? asset('storage/' . $item->first_image) : asset('images/logo/logo_blue.png');
                                $rawPrice = (float) $item->price;
                                $discRaw = $isStationery ? (float) $item->discount_price : (float) $item->discountPrice;
                                $isDisc = $discRaw > 0 && $discRaw < $rawPrice;
                                $price = $isDisc ? $discRaw : $rawPrice;
                                $discPct = $isDisc ? round((($rawPrice - $price) / $rawPrice) * 100) : 0;
                                $subtitle = $isStationery ? ($item->material ?: 'Kanselyariya') : ($item->author ?: 'Kitobchi');
                                $isFav = in_array($item->id, $favoritedIds ?? [], true);
                            @endphp

                            <!-- Product card — exactly like PiyolaMarket: group relative flex flex-col rounded-xl bg-white border border-white hover:shadow-md transition-all duration-200 overflow-hidden -->
                            <a href="{{ $url }}"
                               style="position:relative;display:flex;flex-direction:column;border-radius:0.75rem;background:#fff;border:1px solid #fff;transition:box-shadow 0.2s;overflow:hidden;text-decoration:none;color:inherit;"
                               onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">

                                <!-- Image wrapper: relative w-full rounded-xl bg-white, aspect-ratio 232/309 -->
                                <div style="position:relative;width:100%;background:#fff;border-radius:0.75rem;overflow:hidden;">

                                    <!-- Image with carousel wrapper (just single image here) -->
                                    <div style="overflow:hidden;position:relative;">
                                        <img src="{{ $img }}"
                                             alt="{{ $item->name }}"
                                             style="width:100%;display:block;object-fit:cover;aspect-ratio:3/4;transition:transform 0.7s;"
                                             loading="lazy"
                                             onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                    </div>

                                    <!-- Discount badge (bottom-left) -->
                                    @if($isDisc)
                                        <div style="position:absolute;bottom:0.375rem;left:0.375rem;z-index:20;display:inline-flex;align-items:flex-start;flex-direction:column;gap:4px;">
                                            <span style="display:inline-flex;align-items:center;font-size:0.75rem;font-weight:500;border-radius:0.375rem;color:#fff;padding:0.125rem 0.25rem;background:#ED3131;">
                                                -{{ $discPct }}%
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Favorite button (top-right) — glass-card-bg like PiyolaMarket -->
                                    <div style="position:absolute;top:0.375rem;right:0.375rem;z-index:20;">
                                        <div style="position:relative;overflow:hidden;transition:box-shadow 0.3s;border-radius:1rem;padding:0!important;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);background:rgba(255,255,255,0.5);">
                                            <div style="position:absolute;inset:0;pointer-events:none;"></div>
                                            <button aria-label="Sevimlilar"
                                                    data-fav="{{ $isFav ? '1' : '0' }}"
                                                    onclick="event.preventDefault();toggleFavorite(this, {{ $item->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');"
                                                    name="Sevimlilar"
                                                    style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:none;border:none;cursor:pointer;transition:all 0.3s;position:relative;z-index:10;">
                                                <iconify-icon icon="{{ $isFav ? 'heroicons-solid:heart' : 'heroicons:heart' }}" style="font-size:18px;position:relative;z-index:10;color:{{ $isFav ? '#ef4444' : '#374151' }};"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card content: px-1 pt-4 pb-4 flex flex-col h-full -->
                                <div style="padding:1rem 0.25rem 1rem;display:flex;flex-direction:column;flex:1;">
                                    <!-- Title: text-sm leading-snug line-clamp-2 -->
                                    <div style="flex:1;">
                                        <div style="font-size:0.875rem;line-height:1.375;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;color:#111827;transition:color 0.3s;"
                                             onmouseover="this.style.color='var(--color-tima-600)'" onmouseout="this.style.color='#111827'">
                                            {{ $item->name }}
                                        </div>
                                    </div>

                                    <!-- Price: text-sm md:text-base text-muted font-semibold mt-2 -->
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;">
                                        <p style="font-size:0.875rem;color:#6b7280;font-weight:600;margin:0.5rem 0 0;line-height:1.25;">
                                            {{ number_format($price) }} so'm
                                        </p>
                                    </div>

                                    <!-- Installment badge: bg-primary-100 text-primary-500 rounded-full -->
                                    @if($isDisc)
                                        <div style="margin-top:auto;">
                                            <span style="display:inline-block;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:500;background:var(--color-tima-100);color:var(--color-tima-600);border-radius:9999px;margin-top:0.25rem;">
                                                {{ number_format($rawPrice) }} so'm o'rniga
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div style="display:flex;justify-content:center;margin-bottom:2rem;">
                        {{ $products->withQueryString()->links('pagination::simple-bootstrap-4') }}
                    </div>

                @else
                    <!-- Empty state -->
                    <div style="text-align:center;padding:4rem 1rem;background:#fff;border-radius:1rem;">
                        <div class="kc-icon-empty" aria-hidden="true">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 7v14"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                            </svg>
                        </div>
                        <h3 style="font-size:1.25rem;font-weight:700;color:#111827;margin:0 0 0.5rem;">Mahsulotlar topilmadi</h3>
                        <p style="color:#6b7280;font-size:0.9375rem;max-width:360px;margin:0 auto 1.5rem;line-height:1.6;">
                            Kiritilgan so'rov bo'yicha hech narsa topilmadi. Qidiruvni o'zgartiring yoki barcha katalogga qaytish.
                        </p>
                        <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                            Barcha katalog
                        </a>
                    </div>
                @endif
            </main>

    </div>
</div>

<style>
    /* Responsive catalog grid */
    @media(min-width: 640px) {
        #kcCatalogGrid { grid-template-columns: repeat(2, 1fr) !important; gap: 0.75rem !important; }
    }
    @media(min-width: 768px) {
        #kcCatalogGrid { grid-template-columns: repeat(3, 1fr) !important; gap: 0.75rem !important; }
        #kcSidebar { display: block !important; }
    }
    @media(min-width: 1024px) {
        #kcCatalogGrid { grid-template-columns: repeat(3, 1fr) !important; gap: 1rem !important; }
        #kcSidebar { width: 240px !important; }
    }
    @media(min-width: 1280px) {
        #kcCatalogGrid { grid-template-columns: repeat(5, 1fr) !important; gap: 1.25rem !important; }
    }

    /* Pagination */
    .pagination { display: flex; gap: 0.25rem; list-style: none; padding: 0; margin: 0; }
    .pagination .page-item .page-link {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.25rem; height: 2.25rem; border-radius: 9999px;
        font-size: 0.875rem; font-weight: 500; text-decoration: none;
        background: #f3f4f6; color: #374151; border: none; transition: all 0.2s;
    }
    .pagination .page-item.active .page-link {
        background: var(--color-tima-500); color: #fff;
    }
    .pagination .page-item .page-link:hover { background: #e5e7eb; }
    .pagination .page-item.active .page-link:hover { background: var(--color-tima-600); }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>

@endsection
