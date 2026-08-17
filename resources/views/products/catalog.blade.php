@extends('layouts.marketplace')

@php
    if ($currentCategory) {
        $dynamicTitle = $currentCategory->name . ' — Kitoblar Katalogi | Kitobchi';
        $dynamicDesc = $currentCategory->name . " bo'limidagi barcha original kitoblar va mahsulotlar. Tezkor yetkazib berish va arzon narxlar Kitobchi marketpleysida.";
        $canonicalCatalogUrl = route('web.catalog', array_filter(['category' => $currentCategory->id, 'type' => $type !== 'book' ? $type : null]));
    } elseif ($search) {
        $dynamicTitle = 'Qidiruv: ' . $search . ' — Kitobchi';
        $dynamicDesc = '"' . $search . '" bo\'yicha qidiruv natijalari Kitobchi marketpleysida.';
        $canonicalCatalogUrl = route('web.catalog');
    } elseif ($type === 'stationery') {
        $dynamicTitle = 'Kanselyariya Mahsulotlari Katalogi | Kitobchi';
        $dynamicDesc = "Kitobchi'da sifatli daftarlar, ruchkalar, qalamlar va barcha kanselyariya mahsulotlarini qulay narxlarda xarid qiling.";
        $canonicalCatalogUrl = route('web.catalog', ['type' => 'stationery']);
    } else {
        $dynamicTitle = 'Kitoblar va Mahsulotlar Katalogi | Kitobchi';
        $dynamicDesc = "Kitobchi'da barcha original kitoblar, darsliklar va badiiy adabiyotlarni toping. Qulay filtrlar, tezkor yetkazib berish.";
        $canonicalCatalogUrl = route('web.catalog');
    }
@endphp

@section('title', $dynamicTitle)

@push('meta')
    @php
        $catalogBreadcrumbs = [
            ['name' => __('marketplace.breadcrumb_home'), 'url' => url('/')],
            ['name' => __('marketplace.catalog'), 'url' => route('web.catalog')],
        ];
        if ($currentCategory) {
            $catalogBreadcrumbs[] = ['name' => $currentCategory->name, 'url' => $canonicalCatalogUrl];
        }
    @endphp
    @include('partials.seo-social', [
        'title' => $dynamicTitle,
        'description' => $dynamicDesc,
        'canonical' => $canonicalCatalogUrl,
        'robots' => request()->filled('search') ? 'noindex, follow' : 'index, follow',
        'ogType' => 'website',
        'breadcrumbs' => $catalogBreadcrumbs,
    ])
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

            // MUHIM (i18n fix): avval bu yerda "$cat->name_uz ?? $cat->name" ishlatilardi —
            // bu doim name_uz'ni ustun qo'yardi, hatto joriy til boshqa bo'lsa ham (masalan
            // ru/en/ja tanlangan bo'lsa ham kategoriya nomi hech qachon tarjima qilinmasdi).
            // $cat->name accessor'i (BookCategories/StationeryCategory modeli) o'zi allaqachon
            // joriy tilga qarab name_{locale} → name_uz fallback qiladi — shuning uchun endi
            // to'g'ridan-to'g'ri $cat->name ishlatiladi.
            if ($currentCategory) {
                $pageTitle = $currentCategory->name;
            } elseif ($search) {
                $pageTitle = __('marketplace.search_results', ['query' => $search]);
            } elseif ($sort === 'new') {
                $pageTitle = __('marketplace.new_products');
            } elseif ($sort === 'popular') {
                $pageTitle = __('marketplace.popular_products');
            } else {
                $pageTitle = $type === 'book' ? __('marketplace.books') : __('marketplace.stationery');
            }
        @endphp

        <!-- Breadcrumb & Back button (PiyolaMarket style) -->
        <div class="flex items-center gap-2 pt-4 pb-1">
            <a href="{{ url('/') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300" title="{{ __('marketplace.back_to_home') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
            <nav aria-label="breadcrumb" class="relative min-w-0">
                <ol class="flex items-center gap-2">
                    <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                        <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_home') }}</a>
                    </li>
                    <li class="flex text-gray text-xs">/</li>
                    <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                        <a href="{{ route('web.catalog') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_catalog') }}</a>
                    </li>
                    @if($currentCategory)
                        <li class="flex text-gray text-xs">/</li>
                        <li class="flex min-w-0 text-[#8F8FA1] text-sm font-semibold truncate">
                            {{ $currentCategory->name }}
                        </li>
                    @endif
                </ol>
            </nav>
        </div>

        <!-- ====== FILTER ROW (top) ====== -->
        <div class="flex-y-center justify-between flex-wrap gap-3 pt-2 pb-2">
            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl text-primary font-bold">
                {{ $pageTitle }}
            </h1>
            <span style="font-size:0.875rem;color:#9ca3af;font-weight:500;">{{ __('marketplace.products_count', ['count' => $products->total()]) }}</span>
        </div>

        <!-- ====== HORIZONTAL FILTERS ======
             MUHIM (filtr bug fix): quyidagi har bir popover (Saralash/Narx/Do'konlar/
             Nashriyotlar) avval position:absolute + gorizontal overflow-x:auto qatorining
             ICHIDA edi — bu ularni ko'rinmas holda kesib tashlardi (bosilganda hech narsa
             ko'rinmasdi). Endi ular JS orqali (toggleLangMenu/kcPositionDropdown,
             layouts/marketplace.blade.php) position:fixed qilib ochiladi, shu sabab inline
             style'dan position/top/left olib tashlandi — joylashuvni endi to'liq JS boshqaradi. -->
        <div class="flex items-center gap-2 py-4 overflow-x-auto no-scrollbar flex-nowrap" style="-ms-overflow-style:none;scrollbar-width:none;">
            <!-- Type Switcher Pill -->
            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'book'])) }}"
               class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-2 {{ $type === 'book' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10' }}">
                {{ __('marketplace.books') }}
            </a>
            <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => 'stationery'])) }}"
               class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-2 {{ $type === 'stationery' ? 'bg-primary text-white' : 'bg-secondary-300 text-primary hover:bg-primary/10' }}">
                {{ __('marketplace.stationery') }}
            </a>

            <!-- Divider -->
            <div class="w-px h-6 bg-secondary-200 mx-1 shrink-0"></div>

            @php
                $sortLabelsTranslated = [
                    'popular' => __('marketplace.sort_popular'),
                    'new' => __('marketplace.sort_new'),
                    'price_asc' => __('marketplace.sort_price_asc'),
                    'price_desc' => __('marketplace.sort_price_desc'),
                ];
            @endphp

            <!-- Sort Dropdown -->
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcSortMenu')"
                        class="inline-flex items-center gap-1.5 text-primary hover:bg-primary/10 bg-secondary-300 rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.sort') }}: {{ $sortLabelsTranslated[$sort] ?? $sortLabelsTranslated['popular'] }}</span>
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcSortMenu" class="kc-lang-menu" style="display:none;min-width:180px;background:#fff;border-radius:1rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:0.375rem;">
                    @foreach($sortLabelsTranslated as $sortKey => $sortLabel)
                        <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'sort' => $sortKey !== 'popular' ? $sortKey : null])) }}"
                           style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0.75rem;border-radius:0.625rem;text-decoration:none;font-size:0.875rem;{{ $sort === $sortKey ? 'background:var(--color-tima-50);font-weight:700;color:var(--color-tima-600);' : 'font-weight:500;color:#111827;' }}">
                            {{ $sortLabel }}
                            @if($sort === $sortKey)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Price Popover -->
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcPriceMenu')"
                        class="inline-flex items-center gap-1.5 {{ ($priceMin || $priceMax) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.price') }}</span>
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcPriceMenu" class="kc-lang-menu" style="display:none;width:300px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['price']])

                        <div class="flex items-center gap-3 mb-4">
                            <input type="number" name="price_min" value="{{ $priceMin }}" placeholder="{{ __('marketplace.price_from') }}" class="w-full h-11 bg-secondary-100 rounded-xl px-4 text-[15px] font-medium text-primary outline-none focus:ring-2 ring-primary/20 transition-all border-none">
                            <span class="text-neutral-400 font-medium">-</span>
                            <input type="number" name="price_max" value="{{ $priceMax }}" placeholder="{{ __('marketplace.price_to') }}" class="w-full h-11 bg-secondary-100 rounded-xl px-4 text-[15px] font-medium text-primary outline-none focus:ring-2 ring-primary/20 transition-all border-none">
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">
                            {{ __('marketplace.apply') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Shops Popover -->
            @if(isset($sellers) && $sellers->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcShopsMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedSellers) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.shops') }}</span>
                    @if(!empty($selectedSellers)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedSellers) }}</span> @endif
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcShopsMenu" class="kc-lang-menu" style="display:none;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['sellers']])

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($sellers as $seller)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="seller_ids[]" value="{{ $seller->id }}" class="peer sr-only" {{ in_array($seller->id, $selectedSellers ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white" style="width:10px;height:10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $seller->shop_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">{{ __('marketplace.apply') }}</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Publishers Popover -->
            @if($type !== 'stationery' && isset($publishers) && $publishers->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcPublishersMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedPublishers) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.publishers') }}</span>
                    @if(!empty($selectedPublishers)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedPublishers) }}</span> @endif
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcPublishersMenu" class="kc-lang-menu" style="display:none;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['publishers']])

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($publishers as $publisher)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="publisher_ids[]" value="{{ $publisher->id }}" class="peer sr-only" {{ in_array($publisher->id, $selectedPublishers ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white" style="width:10px;height:10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $publisher->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">{{ __('marketplace.apply') }}</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Muallif (Author) Popover — faqat kitoblar uchun, kategoriyaga xos
                 atribut filtri (piyolamarket.uz'dagi kabi). -->
            @if($type !== 'stationery' && isset($authorOptions) && $authorOptions->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcAuthorsMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedAuthors) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.authors') }}</span>
                    @if(!empty($selectedAuthors)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedAuthors) }}</span> @endif
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcAuthorsMenu" class="kc-lang-menu" style="display:none;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['authors']])

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($authorOptions as $authorName)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="authors[]" value="{{ $authorName }}" class="peer sr-only" {{ in_array($authorName, $selectedAuthors ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white" style="width:10px;height:10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $authorName }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">{{ __('marketplace.apply') }}</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Muqova turi (Cover type) Popover — faqat kitoblar uchun. DB'da
                 coverType ozod matn ("Yumshoq"/"Qattiq" va h.k.) — shu sabab
                 checkbox qiymatlari controller darajasida ikkita guruhga
                 (soft/hard) normalize qilingan, xom matn emas. -->
            @if($type !== 'stationery' && ($coverTypeAvailable ?? false))
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcCoverTypeMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedCoverTypes) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.cover_type') }}</span>
                    @if(!empty($selectedCoverTypes)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedCoverTypes) }}</span> @endif
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcCoverTypeMenu" class="kc-lang-menu" style="display:none;width:240px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['cover_types']])

                        <div class="flex flex-col gap-3 mb-4">
                            @foreach(['soft' => __('marketplace.cover_type_soft'), 'hard' => __('marketplace.cover_type_hard')] as $ctVal => $ctLabel)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="cover_types[]" value="{{ $ctVal }}" class="peer sr-only" {{ in_array($ctVal, $selectedCoverTypes ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white" style="width:10px;height:10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $ctLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">{{ __('marketplace.apply') }}</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Material Popover — faqat kanselyariya uchun, kategoriyaga xos
                 atribut filtri. -->
            @if($type === 'stationery' && isset($materialOptions) && $materialOptions->count() > 0)
            <div class="relative kc-lang-wrap shrink-0">
                <button type="button" onclick="toggleLangMenu(event, 'kcMaterialMenu')"
                        class="inline-flex items-center gap-1.5 {{ !empty($selectedMaterials) ? 'bg-primary text-white' : 'text-primary hover:bg-primary/10 bg-secondary-300' }} rounded-2xl px-4 py-2 text-[15px] font-semibold border-none cursor-pointer transition-colors">
                    <span class="truncate">{{ __('marketplace.material') }}</span>
                    @if(!empty($selectedMaterials)) <span class="bg-white text-primary rounded-full px-2 py-0.5 text-xs ml-1 flex-center">{{ count($selectedMaterials) }}</span> @endif
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="kcMaterialMenu" class="kc-lang-menu" style="display:none;width:280px;background:#fff;border-radius:1.25rem;box-shadow:0 20px 40px rgba(0,0,0,0.14);z-index:200;padding:1.25rem;border:1px solid #f3f4f6;">
                    <form method="GET" action="{{ route('web.catalog') }}">
                        @include('partials.catalog-filter-hidden-inputs', ['except' => ['materials']])

                        <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-2 custom-scrollbar mb-4">
                            @foreach($materialOptions as $materialName)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-5 h-5 rounded border border-secondary-300 group-hover:border-primary bg-white transition-colors">
                                        <input type="checkbox" name="materials[]" value="{{ $materialName }}" class="peer sr-only" {{ in_array($materialName, $selectedMaterials ?? []) ? 'checked' : '' }}>
                                        <div class="w-3 h-3 bg-primary rounded-[2px] opacity-0 peer-checked:opacity-100 flex-center transition-opacity">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white" style="width:10px;height:10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                    </div>
                                    <span class="text-[15px] font-medium text-neutral-700 group-hover:text-primary transition-colors">{{ $materialName }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full h-11 bg-primary hover:bg-primary/90 text-white rounded-xl text-[15px] font-semibold transition-colors shadow-md shadow-primary/20">{{ __('marketplace.apply') }}</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Clear filters -->
            @if(request('category') || $priceMin || $priceMax || $sort !== 'popular' || $search || !empty($selectedSellers) || !empty($selectedPublishers) || !empty($selectedAuthors) || !empty($selectedCoverTypes) || !empty($selectedMaterials))
                <a href="{{ route('web.catalog', ['type' => $type]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-2xl text-[15px] font-semibold transition-colors shrink-0 gap-1.5 bg-error-50 text-error-500 hover:bg-error-100 border-none ml-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.125em;height:1.125em;"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
                    {{ __('marketplace.clear_filters') }}
                </a>
            @endif
        </div>

        <!-- Categories Horizontal -->
        <div class="flex items-center gap-2 pb-5 mb-5 overflow-x-auto no-scrollbar flex-nowrap" style="-ms-overflow-style:none;scrollbar-width:none;border-bottom:1px solid var(--kc-border-light);">
            @php $activeCategories = $isStationery ? ($stationeryCategories ?? collect()) : ($bookCategories ?? collect()); @endphp
            <a href="{{ route('web.catalog', array_merge(array_diff_key($baseParams, ['category' => 1]), ['type' => $type])) }}"
               class="inline-flex items-center px-4 py-2 rounded-full text-[15px] font-medium transition-colors shrink-0 {{ !request('category') ? 'bg-primary text-white' : 'bg-transparent text-primary hover:bg-secondary-100' }}">
                {{ __('marketplace.all_categories') }}
            </a>
            @foreach($activeCategories as $cat)
                <a href="{{ route('web.catalog', array_merge($baseParams, ['type' => $type, 'category' => $cat->id])) }}"
                   class="inline-flex items-center px-4 py-2 rounded-full text-[15px] font-medium transition-colors shrink-0 {{ request('category') == $cat->id ? 'bg-primary text-white' : 'bg-transparent text-primary hover:bg-secondary-100' }}">
                    {{ $cat->name }}
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
                                            <button aria-label="{{ __('marketplace.favorites') }}"
                                                    data-fav="{{ $isFav ? '1' : '0' }}"
                                                    onclick="event.preventDefault();toggleFavorite(this, {{ $item->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');"
                                                    name="{{ __('marketplace.favorites') }}"
                                                    style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:none;border:none;cursor:pointer;transition:all 0.3s;position:relative;z-index:10;">
                                                <svg class="kc-heart-icon" viewBox="0 0 24 24" style="width:18px;height:18px;position:relative;z-index:10;"
                                                     fill="{{ $isFav ? '#ef4444' : 'none' }}" stroke="{{ $isFav ? '#ef4444' : '#374151' }}" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
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
                                            {{ number_format($price) }} {{ __('marketplace.currency') }}
                                        </p>
                                    </div>

                                    <!-- Installment badge: bg-primary-100 text-primary-500 rounded-full -->
                                    @if($isDisc)
                                        <div style="margin-top:auto;">
                                            <span style="display:inline-block;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:500;background:var(--color-tima-100);color:var(--color-tima-600);border-radius:9999px;margin-top:0.25rem;">
                                                {{ __('marketplace.instead_of_price', ['price' => number_format($rawPrice) . ' ' . __('marketplace.currency')]) }}
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
                        <h3 style="font-size:1.25rem;font-weight:700;color:#111827;margin:0 0 0.5rem;">{{ __('marketplace.products_not_found') }}</h3>
                        <p style="color:#6b7280;font-size:0.9375rem;max-width:360px;margin:0 auto 1.5rem;line-height:1.6;">
                            {{ __('marketplace.products_not_found_desc') }}
                        </p>
                        <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                            {{ __('marketplace.all_catalog') }}
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
