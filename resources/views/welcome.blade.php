@extends('layouts.marketplace')

@section('title', 'Kitobchi — Online kitoblar marketpleysi')

@push('meta')
    @include('partials.seo-social', [
        'title' => 'Kitobchi — Online kitoblar va kanselyariya marketpleysi',
        'description' => "Kitobchi — barcha original kitoblar, badiiy adabiyotlar, darsliklar va kanselyariya mahsulotlari marketpleysi. O'zbekiston bo'ylab tezkor yetkazib berish.",
        'canonical' => url('/'),
        'ogType' => 'website',
    ])
@endpush

@section('content')
<div class="bg-white">
    <h1 class="sr-only">
        Kitobchi — Online kitoblar marketpleysi
    </h1>

    @php
        // 1. Banners Query & Filters (to_shop is SKIPPED)
        $banners = Cache::remember('web_banners_v7', 300, function() {
            $items = collect();

            try {
                $news = \App\Models\MarketNews::where('status', true)->get();
                foreach ($news as $item) {
                    $normAction = $item->normalizedAction();
                    // SKIP to_shop action as instructed by user
                    if ($normAction === \App\Models\MarketNews::ACTION_TO_SHOP || $item->action === 'to_shop') {
                        continue;
                    }

                    $img = $item->imgUrl;
                    if ($img && !str_starts_with($img, 'http')) {
                        $img = asset('storage/' . $img);
                    }

                    $actionUrl = route('web.catalog');
                    $type = 'bottomsheet';

                    if ($normAction === \App\Models\MarketNews::ACTION_TO_PRODUCT && $item->action_id) {
                        $type = 'product';
                        $actionUrl = route('web.books.show', $item->action_id);
                    } elseif ($normAction === \App\Models\MarketNews::ACTION_TO_COLLECTION && $item->action_id) {
                        $actionUrl = url("/share/collection/{$item->action_id}");
                    }

                    $items->push((object)[
                        'id'          => 'news_' . $item->id,
                        'title'       => $item->localized('title') ?? 'Aksiya',
                        'description' => $item->localized('description') ?? '',
                        'image'       => $img,
                        'type'        => $type,
                        'url'         => $actionUrl,
                    ]);
                }
            } catch(\Throwable $e) {}

            try {
                $ads = \App\Models\SellerAd::where('moderation', 'approved')
                    ->whereIn('type', ['top_banner', 'center_banner'])
                    ->get();
                foreach ($ads as $ad) {
                    if ($ad->action === 'to_shop') {
                        continue;
                    }
                    $img = $ad->banner_img;
                    if ($img && !str_starts_with($img, 'http')) {
                        $img = asset('storage/' . $img);
                    }
                    $type = $ad->product_id ? 'product' : 'bottomsheet';
                    $url = $ad->product_id ? route('web.books.show', $ad->product_id) : route('web.catalog');

                    $items->push((object)[
                        'id'          => 'ad_' . $ad->id,
                        'title'       => $ad->seller?->shop_name ?? 'Aksiya',
                        'description' => $ad->description ?? '',
                        'image'       => $img,
                        'type'        => $type,
                        'url'         => $url,
                    ]);
                }
            } catch(\Throwable $e) {}

            return $items;
        });

        // 2. New Books (Yangi kitoblar)
        // MUHIM: bu yerda avval faqat mahsulotning o'z status/is_approved/
        // is_hidden maydonlari tekshirilardi — sotuvchining o'zi faolmi
        // (bloklangan/yashiringan do'kon) hech qachon so'ralmasdi. Natijada
        // bloklangan do'konning kitobi ham bosh sahifada ko'rinaverardi.
        // Endi \App\Support\ProductVisibilityScope orqali saytning boshqa
        // hamma joyida ishlatiladigan XUDDI SHU qoida qo'llaniladi.
        try {
            $newBooks = Cache::remember('web_home_new_books_v4', 300, function() {
                return \App\Support\ProductVisibilityScope::applyBooks(\App\Models\Books::query())
                    ->orderByDesc('created_at')
                    ->take(10)
                    ->get();
            });
        } catch(\Throwable $e) { $newBooks = collect(); }

        // 3. Recommended Books (Tavsiya etamiz)
        try {
            $recommendedBooks = Cache::remember('web_home_rec_books_v4', 300, function() {
                return \App\Support\ProductVisibilityScope::applyBooks(\App\Models\Books::query())
                    ->orderByDesc('totalSales')
                    ->take(10)
                    ->get();
            });
        } catch(\Throwable $e) { $recommendedBooks = collect(); }

        // 4. Genre / Category Sections (Janrlar bo'yicha kitoblar)
        try {
            $categorySections = Cache::remember('web_home_cat_sections_v4', 600, function() {
                $categories = \App\Models\BookCategories::where('is_active', true)
                    ->orderBy('name_uz')
                    ->take(6)
                    ->get();

                $sections = collect();
                foreach ($categories as $cat) {
                    $books = \App\Support\ProductVisibilityScope::applyBooks(\App\Models\Books::query())
                        ->where('category_id', $cat->id)
                        ->orderByDesc('totalSales')
                        ->take(5)
                        ->get();

                    if ($books->isNotEmpty()) {
                        $sections->push((object)[
                            'category' => $cat,
                            'books'    => $books,
                        ]);
                    }
                }
                return $sections;
            });
        } catch(\Throwable $e) { $categorySections = collect(); }

        // 5. Joriy foydalanuvchining sevimli kitob ID'lari — yurak
        // ikonkasini to'ldirilgan/bo'sh holatda ko'rsatish uchun.
        try {
            $favoritedBookIds = auth()->check()
                ? \App\Models\FavouriteProducts::where('user_id', auth()->id())
                    ->where('product_type', 'book')
                    ->pluck('product_id')
                    ->all()
                : [];
        } catch (\Throwable $e) { $favoritedBookIds = []; }
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto max-md:px-0 max-md:p-0!">
        <!-- ====== HERO BANNER SLIDER ====== -->
        <section class="md:py-6">
            <div class="relative group">
                <div aria-roledescription="carousel" class="relative focus:outline-none" tabindex="0">
                    <div class="overflow-hidden px-0!" id="kcBannerViewport">
                        <div class="flex items-start flex-row -ms-4 transition-transform duration-700" id="kcBannerTrack" style="transform: translate3d(0px, 0px, 0px);">
                            @if($banners->isNotEmpty())
                                @foreach($banners as $banner)
                                    <div aria-roledescription="slide" class="kc-banner-slide min-w-0 shrink-0 ps-4 basis-[90%] md:basis-full justify-center" role="group">
                                        @if($banner->type === 'product')
                                            <a class="relative w-full aspect-520/141 h-hull rounded-2xl lg:rounded-[30px] overflow-hidden block group/item" href="{{ $banner->url }}" rel="noopener noreferrer">
                                                <img alt="{{ $banner->title }}" class="w-full h-full transform transition-transform duration-700 group-hover/item:scale-105 object-cover" fetchpriority="high" loading="eager" src="{{ $banner->image }}"/>
                                                <div class="absolute inset-0 bg-primary/0 group-hover/item:bg-primary/10 transition-colors duration-300 pointer-events-none"></div>
                                            </a>
                                        @else
                                            <div onclick="openBannerBottomSheet('{{ addslashes($banner->title) }}', '{{ addslashes($banner->description) }}', '{{ $banner->image }}', '{{ $banner->url }}')" class="relative w-full aspect-520/141 h-hull rounded-2xl lg:rounded-[30px] overflow-hidden block group/item cursor-pointer">
                                                <img alt="{{ $banner->title }}" class="w-full h-full transform transition-transform duration-700 group-hover/item:scale-105 object-cover" fetchpriority="high" loading="eager" src="{{ $banner->image }}"/>
                                                <div class="absolute inset-0 bg-primary/0 group-hover/item:bg-primary/10 transition-colors duration-300 pointer-events-none"></div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div aria-roledescription="slide" class="min-w-0 shrink-0 ps-4 basis-[90%] md:basis-full justify-center" role="group">
                                    <div class="relative w-full aspect-520/141 h-hull rounded-2xl lg:rounded-[30px] overflow-hidden block group/item bg-gradient-to-br from-primary-500 to-indigo-500 flex items-center justify-center">
                                        <div class="text-center text-white p-8">
                                            <div class="text-2xl md:text-4xl font-black mb-2 flex items-center justify-center gap-3">
                                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:1em;height:1em;"><path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z"/></svg>
                                                Kitobchi Marketpleysi
                                            </div>
                                            <div class="text-lg opacity-90">Muborak va original kitoblar eng hamyonbop narxlarda</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($banners->count() > 1)
                        <div class="hidden md:block">
                            <button onclick="slideBanner(-1)" aria-label="Prev" class="font-medium inline-flex items-center transition-colors text-sm gap-1.5 ring ring-inset ring-primary/50 text-primary hover:bg-primary/10 active:bg-primary/10 outline-primary/25 focus-visible:outline-3 focus-visible:ring-primary p-1.5 absolute rounded-full start-4 sm:-start-12 top-1/2 -translate-y-1/2 start-6! z-20! bg-gray-600/50! text-white! border-gray-600/50! cursor-pointer" type="button">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            </button>
                            <button onclick="slideBanner(1)" aria-label="Next" class="font-medium inline-flex items-center transition-colors text-sm gap-1.5 text-inverted bg-inverted hover:bg-inverted/90 active:bg-inverted/90 outline-inverted/25 focus-visible:outline-3 p-1.5 absolute rounded-full end-4 sm:-end-12 top-1/2 -translate-y-1/2 end-6! z-20! bg-gray-600/50! text-white! border-gray-600/50! cursor-pointer" type="button">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <!-- ====== CATEGORIES CAROUSEL ====== -->
    <section class="py-6 md:py-10">
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
            <h2 class="font-bold text-[24px] md:text-[36px] text-primary leading-[100%] capitalize mb-4">
                Kataloglar
            </h2>
            <div class="">
                <div class="relative group">
                    <div aria-roledescription="carousel" class="relative focus:outline-none" tabindex="0">
                        <div class="overflow-x-auto no-scrollbar">
                            <div class="flex items-start flex-row -ms-4 gap-[20px] pb-4">
                                @php
                                    try {
                                        $webCategories = Cache::remember('web_top_categories_home_v5', 600, function() {
                                            return \App\Models\BookCategories::where('is_active', true)->orderBy('name_uz')->take(14)->get();
                                        });
                                    } catch(\Throwable $e) { $webCategories = collect(); }
                                @endphp
                                
                                <div class="min-w-0 shrink-0 ps-4 basis-1/4 md:basis-1/6 lg:basis-1/8">
                                    <a class="group/item flex flex-col items-center gap-2" href="{{ route('web.catalog') }}">
                                        <div class="min-w-[90px] min-h-[90px] w-full h-full max-w-[192px] max-h-[192px] rounded-full overflow-hidden border border-transparent group-hover/item:border-primary-500 transition-colors duration-300 bg-gradient-to-br from-primary-500 to-indigo-500 flex items-center justify-center">
                                            <div class="relative w-full aspect-square flex items-center justify-center">
                                                <svg viewBox="0 0 24 24" fill="currentColor" class="text-3xl md:text-5xl text-white transform transition-transform duration-500 group-hover/item:scale-110" style="width:1em;height:1em;"><path d="M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H4.5zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v.75a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z"/></svg>
                                            </div>
                                        </div>
                                        <span class="font-medium md:font-semibold group-hover/item:font-bold group-hover/item:underline text-sm md:text-base leading-6 text-center text-neutral-900 dark:text-white group-hover/item:text-primary-500 transition-all duration-300">
                                            Barchasi
                                        </span>
                                    </a>
                                </div>

                                @foreach($webCategories as $cat)
                                    <div class="min-w-0 shrink-0 ps-4 basis-1/4 md:basis-1/6 lg:basis-1/8">
                                        <a class="group/item flex flex-col items-center gap-2" href="{{ route('web.catalog', ['category' => $cat->id]) }}">
                                            <div class="min-w-[90px] min-h-[90px] w-full h-full max-w-[192px] max-h-[192px] rounded-full overflow-hidden border border-transparent group-hover/item:border-primary-500 transition-colors duration-300 bg-gray-50">
                                                <div class="relative w-full aspect-square flex items-center justify-center">
                                                    @if($cat->image)
                                                        <img alt="{{ $cat->name_uz }}" class="w-full h-full object-cover transform transition-transform duration-500 group-hover/item:scale-110" loading="lazy" src="{{ asset('storage/' . $cat->image) }}"/>
                                                    @else
                                                        <span class="text-3xl font-black text-primary-500 opacity-50 uppercase">{{ mb_substr($cat->name_uz ?? 'K', 0, 1) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="font-medium md:font-semibold group-hover/item:font-bold group-hover/item:underline text-sm md:text-base leading-6 text-center text-neutral-900 dark:text-white group-hover/item:text-primary-500 transition-all duration-300">
                                                {{ $cat->name_uz }}
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== SECTION 1: YANGI KITOBLAR ====== -->
    @if($newBooks->isNotEmpty())
        <section class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
            <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
                <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-3 md:mb-5 lg:mb-8">
                    <h2 class="font-bold text-xl md:text-4xl leading-[100%] text-primary m-0 capitalize flex items-center gap-2">
                        Yangi kelgan kitoblar
                    </h2>
                    <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('web.catalog', ['sort' => 'new']) }}">
                        Barchasi
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-4 md:mb-6">
                    @foreach($newBooks as $book)
                        @include('partials.home-book-card', ['book' => $book])
                    @endforeach
                </div>
                <div class="flex justify-center">
                    <a href="{{ route('web.catalog', ['sort' => 'new']) }}" class="font-medium items-center py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/75 h-12 flex justify-center sm:min-w-40 rounded-2xl text-base px-6 max-md:w-full no-underline transition-colors">
                        Barchasini ko‘rish
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- ====== SECTION 2: TAVSIYA ETAMIZ (TOP SOTUVLAR) ====== -->
    @if($recommendedBooks->isNotEmpty())
        <section class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
            <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
                <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-3 md:mb-5 lg:mb-8">
                    <h2 class="font-bold text-xl md:text-4xl leading-[100%] text-primary m-0 capitalize flex items-center gap-2">
                        Tavsiya etamiz
                    </h2>
                    <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('web.catalog', ['sort' => 'popular']) }}">
                        Barchasi
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-4 md:mb-6">
                    @foreach($recommendedBooks as $book)
                        @include('partials.home-book-card', ['book' => $book])
                    @endforeach
                </div>
                <div class="flex justify-center">
                    <a href="{{ route('web.catalog', ['sort' => 'popular']) }}" class="font-medium items-center py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/75 h-12 flex justify-center sm:min-w-40 rounded-2xl text-base px-6 max-md:w-full no-underline transition-colors">
                        Barchasini ko‘rish
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- ====== SECTION 3+: JANRLAR BO'YICHA KITOBLAR ====== -->
    @if($categorySections->isNotEmpty())
        @foreach($categorySections as $section)
            <section class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
                <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
                    <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-3 md:mb-5 lg:mb-8">
                        <h2 class="font-bold text-xl md:text-4xl leading-[100%] text-primary m-0 capitalize flex items-center gap-2">
                            {{ $section->category->name_uz }}
                        </h2>
                        <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('web.catalog', ['category' => $section->category->id]) }}">
                            Barchasi
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 lg:gap-5 mb-4 md:mb-6">
                        @foreach($section->books as $book)
                            @include('partials.home-book-card', ['book' => $book])
                        @endforeach
                    </div>
                    <div class="flex justify-center">
                        <a href="{{ route('web.catalog', ['category' => $section->category->id]) }}" class="font-medium items-center py-1.5 gap-1.5 text-white bg-primary hover:bg-primary/75 h-12 flex justify-center sm:min-w-40 rounded-2xl text-base px-6 max-md:w-full no-underline transition-colors">
                            Barchasini ko‘rish
                        </a>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
</div>

<!-- ====== BANNER BOTTOMSHEET MODAL (PiyolaMarket Mobile BottomSheet) ====== -->
<div id="kcBannerBottomSheet" class="kc-modal-overlay hidden fixed inset-0 z-50 bg-black/50 transition-opacity" onclick="if(event.target===this) closeBannerBottomSheet()">
    <div class="fixed bottom-0 left-0 right-0 max-w-lg mx-auto bg-white rounded-t-3xl p-6 shadow-xl transform transition-transform translate-y-full" id="kcBottomSheetCard">
        
        <div class="flex items-center justify-between mb-4 relative">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto absolute left-1/2 -translate-x-1/2 -top-2"></div>
            <h3 id="kcBottomSheetTitle" class="text-xl font-bold text-gray-900 mt-2">Aksiya</h3>
            <button onclick="closeBannerBottomSheet()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 mt-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="kcBottomSheetImageWrap" class="mb-4 rounded-2xl overflow-hidden aspect-video bg-gray-100 hidden">
            <img id="kcBottomSheetImage" src="" alt="" class="w-full h-full object-cover">
        </div>

        <p id="kcBottomSheetDesc" class="text-gray-600 text-sm leading-relaxed mb-6"></p>

        <a id="kcBottomSheetBtn" href="{{ route('web.catalog') }}"
           class="flex items-center justify-center w-full h-12 bg-primary-500 text-white rounded-full font-bold text-base hover:bg-primary-600 transition-colors">
            Katalogga o'tish
        </a>
    </div>
</div>
@endsection

@push('scripts')
<style>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
<script>
    // Banner slider auto-play & touch swiping
    let bannerIdx = 0;
    let bannerInterval = null;

    function slideBanner(dir) {
        const track = document.getElementById('kcBannerTrack');
        if (!track) return;
        const slides = track.querySelectorAll('.kc-banner-slide');
        if (slides.length <= 1) return;

        bannerIdx += dir;
        if (bannerIdx >= slides.length) bannerIdx = 0;
        if (bannerIdx < 0) bannerIdx = slides.length - 1;

        track.style.transform = `translate3d(-${bannerIdx * 100}%, 0, 0)`;
    }

    function startBannerAutoplay() {
        clearInterval(bannerInterval);
        bannerInterval = setInterval(() => slideBanner(1), 5000);
    }

    // Touch support for mobile slider
    (function() {
        const track = document.getElementById('kcBannerTrack');
        const viewport = document.getElementById('kcBannerViewport');
        if (!viewport) return;
        let startX = 0;
        let dist = 0;

        viewport.addEventListener('touchstart', e => {
            startX = e.touches[0].clientX;
            dist = 0;
            clearInterval(bannerInterval);
        }, { passive: true });

        viewport.addEventListener('touchmove', e => {
            dist = e.touches[0].clientX - startX;
        }, { passive: true });

        viewport.addEventListener('touchend', () => {
            if (dist < -50) slideBanner(1);
            else if (dist > 50) slideBanner(-1);
            startBannerAutoplay();
        });
    })();

    // BANNER BOTTOMSHEET MODAL
    function openBannerBottomSheet(title, desc, image, url) {
        const modal = document.getElementById('kcBannerBottomSheet');
        const card = document.getElementById('kcBottomSheetCard');
        const imgWrap = document.getElementById('kcBottomSheetImageWrap');
        const imgEl = document.getElementById('kcBottomSheetImage');
        const titleEl = document.getElementById('kcBottomSheetTitle');
        const descEl = document.getElementById('kcBottomSheetDesc');
        const btnEl = document.getElementById('kcBottomSheetBtn');

        if (!modal) return;

        titleEl.textContent = title || 'Aksiya va yangilik';
        descEl.textContent = desc || '';

        if (image) {
            imgEl.src = image;
            imgWrap.classList.remove('hidden');
        } else {
            imgWrap.classList.add('hidden');
        }

        if (url) {
            btnEl.href = url;
            btnEl.classList.remove('hidden');
            btnEl.classList.add('flex');
        } else {
            btnEl.classList.add('hidden');
            btnEl.classList.remove('flex');
        }

        modal.classList.remove('hidden');
        // Trigger animation
        setTimeout(() => {
            card.classList.remove('translate-y-full');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeBannerBottomSheet() {
        const modal = document.getElementById('kcBannerBottomSheet');
        const card = document.getElementById('kcBottomSheetCard');
        if (!modal) return;
        
        card.classList.add('translate-y-full');
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        startBannerAutoplay();
    });
</script>
@endpush
