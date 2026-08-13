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
            // Joriy filtrlarni saqlagan holda link/param quramiz — kategoriya
            // yoki turni almashtirganda saralash/narx oralig'i yo'qolmasin.
            $baseParams = array_filter([
                'search' => $search ?: null,
                'category' => request('category'),
                'sort' => $sort !== 'popular' ? $sort : null,
                'price_min' => $priceMin ?? null,
                'price_max' => $priceMax ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $sortLabels = ['popular' => 'Mashhur', 'new' => 'Yangi', 'price_asc' => 'Arzon narx', 'price_desc' => 'Qimmat narx'];
        @endphp

        <!-- ====== FILTER ROW (top) ====== -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;padding:1rem 0 0.75rem;">

            <!-- Type Switcher Pill -->
            <div style="display:inline-flex;align-items:center;background:#f3f4f6;border-radius:9999px;padding:0.25rem;gap:0.25rem;">
                <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'book'])) }}"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:9999px;font-size:0.875rem;font-weight:{{ $type === 'book' ? '600' : '500' }};text-decoration:none;transition:all 0.2s;
                          background:{{ $type === 'book' ? 'var(--color-tima-500)' : 'transparent' }};
                          color:{{ $type === 'book' ? '#fff' : '#6b7280' }};">
                    Kitoblar
                </a>
                <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'stationery'])) }}"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:9999px;font-size:0.875rem;font-weight:{{ $type === 'stationery' ? '600' : '500' }};text-decoration:none;transition:all 0.2s;
                          background:{{ $type === 'stationery' ? 'var(--color-tima-500)' : 'transparent' }};
                          color:{{ $type === 'stationery' ? '#fff' : '#6b7280' }};">
                    Kanselyariya
                </a>
            </div>

            <!-- Right: results count + sort + filter toggle -->
            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                @if($search)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;background:#f3f4f6;border-radius:9999px;font-size:0.8125rem;color:#374151;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        {{ $search }}
                        <a href="{{ route('web.catalog', ['type' => $type]) }}" style="color:#6b7280;text-decoration:none;font-size:1.1em;">×</a>
                    </div>
                @endif
                <span style="font-size:0.8125rem;color:#9ca3af;">{{ $products->total() }} ta mahsulot</span>

                <!-- Sort dropdown -->
                <div class="relative kc-lang-wrap">
                    <button type="button" onclick="toggleLangMenu(event, 'kcSortMenu')" aria-haspopup="menu" aria-expanded="false"
                            style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 0.875rem;background:#f3f4f6;border-radius:9999px;border:none;font-size:0.8125rem;font-weight:600;color:#374151;cursor:pointer;font-family:inherit;">
                        <iconify-icon icon="lucide:arrow-up-down" style="font-size:14px;"></iconify-icon>
                        {{ $sortLabels[$sort] ?? 'Saralash' }}
                        <iconify-icon icon="lucide:chevron-down" style="font-size:14px;"></iconify-icon>
                    </button>
                    <div id="kcSortMenu" class="kc-lang-menu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:160px;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;overflow:hidden;padding:0.375rem;">
                        @foreach($sortLabels as $sortKey => $sortLabel)
                            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'sort' => $sortKey !== 'popular' ? $sortKey : null])) }}"
                               style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.625rem 0.75rem;border-radius:0.625rem;text-decoration:none;font-size:0.875rem;{{ $sort === $sortKey ? 'background:var(--color-tima-50);font-weight:700;color:var(--color-tima-600);' : 'font-weight:500;color:#111827;' }}">
                                {{ $sortLabel }}
                                @if($sort === $sortKey)
                                    <iconify-icon icon="lucide:check" style="font-size:14px;"></iconify-icon>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                </div>
            </div>
        </div>

        <!-- ====== MAIN AREA: Sidebar + Grid ====== -->
        <div class="flex flex-col lg:flex-row gap-8" style="align-items:flex-start;">

            <!-- ====== SIDEBAR FILTERS ====== -->
            <aside class="w-full lg:w-[280px] shrink-0 hidden lg:block">
                <form method="GET" action="{{ route('web.catalog') }}" class="bg-white rounded-2xl border border-secondary-200 p-5 shadow-sm sticky top-24 flex flex-col gap-6" id="kcFilterForm">
                    <input type="hidden" name="type" value="{{ $type }}">
                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    @if($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif

                    <!-- Categories Filter (Radio style) -->
                    <div>
                        <h3 class="text-lg font-bold text-primary mb-4">Kategoriyalar</h3>
                        <div class="flex flex-col gap-3">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative flex items-center justify-center w-5 h-5 rounded-full border border-secondary-300 group-hover:border-primary transition-colors bg-white">
                                    <input type="radio" name="category" value="" class="peer sr-only" onchange="this.form.submit()" {{ !request('category') ? 'checked' : '' }}>
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                                <span class="text-[15px] font-medium transition-colors {{ !request('category') ? 'text-primary' : 'text-neutral-600 group-hover:text-primary' }}">Barchasi</span>
                            </label>

                            @php $activeCategories = $isStationery ? ($stationeryCategories ?? collect()) : ($bookCategories ?? collect()); @endphp
                            @foreach($activeCategories as $cat)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded-full border border-secondary-300 group-hover:border-primary transition-colors bg-white">
                                        <input type="radio" name="category" value="{{ $cat->id }}" class="peer sr-only" onchange="this.form.submit()" {{ request('category') == $cat->id ? 'checked' : '' }}>
                                        <div class="w-2.5 h-2.5 rounded-full bg-primary opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                    </div>
                                    <span class="text-[15px] font-medium transition-colors {{ request('category') == $cat->id ? 'text-primary' : 'text-neutral-600 group-hover:text-primary' }}">{{ $cat->name_uz ?? $cat->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="border-secondary-200">

                    <!-- Price Filter -->
                    <div>
                        <h3 class="text-lg font-bold text-primary mb-4">Narx</h3>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-1 relative">
                                <input type="number" name="price_min" min="0" placeholder="Dan" value="{{ $priceMin }}" 
                                       class="w-full h-11 bg-secondary-100 border-none rounded-xl px-4 text-sm font-medium text-primary placeholder-neutral-400 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                            </div>
                            <span class="text-neutral-400 font-medium">-</span>
                            <div class="flex-1 relative">
                                <input type="number" name="price_max" min="0" placeholder="Gacha" value="{{ $priceMax }}" 
                                       class="w-full h-11 bg-secondary-100 border-none rounded-xl px-4 text-sm font-medium text-primary placeholder-neutral-400 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                            </div>
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-medium transition-colors flex items-center justify-center">
                            Ko'rsatish
                        </button>
                    </div>

                    @if(request('category') || $priceMin || $priceMax || $sort !== 'popular' || $search)
                        <div class="pt-2">
                            <a href="{{ route('web.catalog', ['type' => $type]) }}"
                               class="flex items-center justify-center h-10 w-full bg-error-50 hover:bg-error-100 text-error-500 rounded-xl text-[15px] font-medium transition-colors gap-2">
                                <iconify-icon icon="lucide:x" class="text-lg"></iconify-icon>
                                Filtrlarni tozalash
                            </a>
                        </div>
                    @endif
                </form>
            </aside>

            <!-- ====== PRODUCT GRID ====== -->
            <main class="flex-1">

                <!-- Mobile category filter pills (horizontal scroll) -->
                <div style="overflow-x:auto;-ms-overflow-style:none;scrollbar-width:none;margin-bottom:1rem;">
                    <div style="display:flex;gap:0.5rem;padding-bottom:0.25rem;width:max-content;">
                        <a href="{{ route('web.catalog', array_merge(array_diff_key($baseParams, ['category' => 1]), ['type' => $type])) }}"
                           style="display:inline-flex;align-items:center;padding:0.375rem 0.875rem;border-radius:9999px;font-size:0.8125rem;font-weight:500;text-decoration:none;white-space:nowrap;transition:all 0.2s;flex-shrink:0;
                                  background:{{ !request('category') ? 'var(--color-tima-500)' : '#f3f4f6' }};
                                  color:{{ !request('category') ? '#fff' : '#374151' }};">
                            Barchasi
                        </a>
                        @foreach($activeCategories as $cat)
                            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'category' => $cat->id])) }}"
                               style="display:inline-flex;align-items:center;padding:0.375rem 0.875rem;border-radius:9999px;font-size:0.8125rem;font-weight:500;text-decoration:none;white-space:nowrap;transition:all 0.2s;flex-shrink:0;
                                      background:{{ request('category') == $cat->id ? 'var(--color-tima-500)' : '#f3f4f6' }};
                                      color:{{ request('category') == $cat->id ? '#fff' : '#374151' }};">
                                {{ $cat->name_uz ?? $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

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
</style>

@endsection
