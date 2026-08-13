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
        @endphp

        <!-- ====== FILTER ROW (top) ====== -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;padding:1rem 0 0.75rem;">

            <!-- Type Switcher Pill -->
            <div style="display:inline-flex;align-items:center;background:#f3f4f6;border-radius:9999px;padding:0.25rem;gap:0.25rem;">
                <a href="{{ route('web.catalog', array_filter(['type' => 'book', 'search' => $search])) }}"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:9999px;font-size:0.875rem;font-weight:{{ $type === 'book' ? '600' : '500' }};text-decoration:none;transition:all 0.2s;
                          background:{{ $type === 'book' ? 'var(--color-tima-500)' : 'transparent' }};
                          color:{{ $type === 'book' ? '#fff' : '#6b7280' }};">
                    Kitoblar
                </a>
                <a href="{{ route('web.catalog', array_filter(['type' => 'stationery', 'search' => $search])) }}"
                   style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:9999px;font-size:0.875rem;font-weight:{{ $type === 'stationery' ? '600' : '500' }};text-decoration:none;transition:all 0.2s;
                          background:{{ $type === 'stationery' ? 'var(--color-tima-500)' : 'transparent' }};
                          color:{{ $type === 'stationery' ? '#fff' : '#6b7280' }};">
                    Kanselyariya
                </a>
            </div>

            <!-- Right: results count + sort -->
            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                @if($search)
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.875rem;background:#f3f4f6;border-radius:9999px;font-size:0.8125rem;color:#374151;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        {{ $search }}
                        <a href="{{ route('web.catalog', ['type' => $type]) }}" style="color:#6b7280;text-decoration:none;font-size:1.1em;">×</a>
                    </div>
                @endif
                <span style="font-size:0.8125rem;color:#9ca3af;">{{ $products->total() }} ta mahsulot</span>
            </div>
        </div>

        <!-- ====== MAIN AREA: Sidebar + Grid ====== -->
        <div class="flex flex-col lg:flex-row gap-8" style="align-items:flex-start;">

            <!-- ====== SIDEBAR FILTERS ====== -->
            <aside class="w-full lg:w-64 shrink-0 hidden lg:block" style="position:sticky;top:80px;background:#fff;border-radius:1rem;padding:1.25rem;border:1px solid #f3f4f6;">

                <h3 style="font-size:0.9375rem;font-weight:700;color:#111827;margin:0 0 0.875rem;">Kategoriyalar</h3>

                <div style="display:flex;flex-direction:column;gap:0.125rem;">
                    <a href="{{ route('web.catalog', ['type' => $type]) }}" class="text-neutral-600 leading-6 py-3 hover:text-primary-500 hover:underline font-medium transition-all duration-200"
                       style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.625rem;border-radius:0.5rem;font-size:0.875rem;text-decoration:none;
                              background:{{ !request('category') ? 'var(--color-tima-50)' : 'transparent' }};
                              color:{{ !request('category') ? 'var(--color-tima-600)' : '#374151' }};
                              font-weight:{{ !request('category') ? '600' : '400' }};">
                        Barchasi
                        @if(!request('category'))
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        @endif
                    </a>

                    @php $activeCategories = $isStationery ? ($stationeryCategories ?? collect()) : ($bookCategories ?? collect()); @endphp

                    @foreach($activeCategories as $cat)
                        <a href="{{ route('web.catalog', ['type' => $type, 'category' => $cat->id, 'search' => request('search')]) }}" class="text-neutral-600 leading-6 py-3 hover:text-primary-500 hover:underline font-medium transition-all duration-200"
                           style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.625rem;border-radius:0.5rem;font-size:0.875rem;text-decoration:none;
                                  background:{{ request('category') == $cat->id ? 'var(--color-tima-50)' : 'transparent' }};
                                  color:{{ request('category') == $cat->id ? 'var(--color-tima-600)' : '#374151' }};
                                  font-weight:{{ request('category') == $cat->id ? '600' : '400' }};">
                            {{ $cat->name_uz ?? $cat->name }}
                            @if(request('category') == $cat->id)
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if(request('category'))
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f3f4f6;">
                        <a href="{{ route('web.catalog', ['type' => $type]) }}"
                           style="display:block;text-align:center;padding:0.5rem;background:#f3f4f6;border-radius:0.5rem;font-size:0.8125rem;color:#6b7280;text-decoration:none;transition:all 0.2s;"
                           onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            Filtrlarni tozalash
                        </a>
                    </div>
                @endif
            </aside>

            <!-- ====== PRODUCT GRID ====== -->
            <main class="flex-1">

                <!-- Mobile category filter pills (horizontal scroll) -->
                <div style="overflow-x:auto;-ms-overflow-style:none;scrollbar-width:none;margin-bottom:1rem;">
                    <div style="display:flex;gap:0.5rem;padding-bottom:0.25rem;width:max-content;">
                        <a href="{{ route('web.catalog', ['type' => $type]) }}"
                           style="display:inline-flex;align-items:center;padding:0.375rem 0.875rem;border-radius:9999px;font-size:0.8125rem;font-weight:500;text-decoration:none;white-space:nowrap;transition:all 0.2s;flex-shrink:0;
                                  background:{{ !request('category') ? 'var(--color-tima-500)' : '#f3f4f6' }};
                                  color:{{ !request('category') ? '#fff' : '#374151' }};">
                            Barchasi
                        </a>
                        @foreach($activeCategories as $cat)
                            <a href="{{ route('web.catalog', ['type' => $type, 'category' => $cat->id, 'search' => request('search')]) }}"
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
                                                    onclick="event.preventDefault();toggleFav(this);"
                                                    name="Sevimlilar"
                                                    style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:none;border:none;cursor:pointer;transition:all 0.3s;position:relative;z-index:10;">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" style="position:relative;z-index:10;">
                                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                                </svg>
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

@push('scripts')
<script>
    function toggleFav(btn) {
        const svg = btn.querySelector('svg');
        if (svg.getAttribute('fill') === 'none') {
            svg.setAttribute('fill', '#ef4444');
            svg.setAttribute('stroke', '#ef4444');
        } else {
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', '#374151');
        }
    }
</script>
@endpush
@endsection
