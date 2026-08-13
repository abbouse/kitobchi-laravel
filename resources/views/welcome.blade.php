@extends('layouts.marketplace')

@section('title', 'Kitobchi — Online kitoblar marketpleysi')

@push('meta')
<meta name="description" content="Kitobchi — original kitoblar, darsliklar va badiiy adabiyotlarni onlayn xarid qiling. O'zbekiston bo'ylab tezkor yetkazib berish.">
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
                                            <div class="text-2xl md:text-4xl font-black mb-2">📚 Kitobchi Marketpleysi</div>
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
                                <iconify-icon aria-hidden="true" icon="lucide:arrow-left" class="shrink-0" style="font-size:20px;"></iconify-icon>
                            </button>
                            <button onclick="slideBanner(1)" aria-label="Next" class="font-medium inline-flex items-center transition-colors text-sm gap-1.5 text-inverted bg-inverted hover:bg-inverted/90 active:bg-inverted/90 outline-inverted/25 focus-visible:outline-3 p-1.5 absolute rounded-full end-4 sm:-end-12 top-1/2 -translate-y-1/2 end-6! z-20! bg-gray-600/50! text-white! border-gray-600/50! cursor-pointer" type="button">
                                <iconify-icon aria-hidden="true" icon="lucide:arrow-right" class="shrink-0" style="font-size:20px;"></iconify-icon>
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
                                                <span class="text-3xl md:text-5xl transform transition-transform duration-500 group-hover/item:scale-110">📚</span>
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
                <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-4">
                    <h2 class="text-lg md:text-2xl font-bold flex gap-2 items-center text-primary-950 dark:text-white">
                        🆕 Yangi kelgan kitoblar
                    </h2>
                    <a class="text-sm font-semibold text-primary" href="{{ route('web.catalog') }}">
                        Barchasi
                        <iconify-icon aria-hidden="true" icon="lucide:chevron-right"></iconify-icon>
                    </a>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
                    @foreach($newBooks as $book)
                        @include('partials.home-book-card', ['book' => $book])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ====== SECTION 2: TAVSIYA ETAMIZ (TOP SOTUVLAR) ====== -->
    @if($recommendedBooks->isNotEmpty())
        <section class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
            <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
                <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-4">
                    <h2 class="text-lg md:text-2xl font-bold flex gap-2 items-center text-primary-950 dark:text-white">
                        🔥 Tavsiya etamiz & Top sotuvlar
                    </h2>
                    <a class="text-sm font-semibold text-primary" href="{{ route('web.catalog') }}">
                        Barchasi
                        <iconify-icon aria-hidden="true" icon="lucide:chevron-right"></iconify-icon>
                    </a>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
                    @foreach($recommendedBooks as $book)
                        @include('partials.home-book-card', ['book' => $book])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ====== SECTION 3+: JANRLAR BO'YICHA KITOBLAR ====== -->
    @if($categorySections->isNotEmpty())
        @foreach($categorySections as $section)
            <section class="py-4 md:py-6 lg:py-10 max-lg:rounded-2xl max-lg:mb-2">
                <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
                    <div class="flex justify-between items-center w-full px-1 max-md:mt-5 mb-4">
                        <h2 class="text-lg md:text-2xl font-bold flex gap-2 items-center text-primary-950 dark:text-white">
                            📚 {{ $section->category->name_uz }}
                        </h2>
                        <a class="text-sm font-semibold text-primary" href="{{ route('web.catalog', ['category' => $section->category->id]) }}">
                            Barchasi
                            <iconify-icon aria-hidden="true" icon="lucide:chevron-right"></iconify-icon>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5 gap-4">
                        @foreach($section->books as $book)
                            @include('partials.home-book-card', ['book' => $book])
                        @endforeach
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
                <iconify-icon icon="lucide:x" class="w-5 h-5"></iconify-icon>
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
