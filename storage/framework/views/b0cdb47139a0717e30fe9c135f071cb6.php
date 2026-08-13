<?php $__env->startSection('title', 'Kitobchi — Online kitoblar marketpleysi'); ?>

<?php $__env->startPush('meta'); ?>
<meta name="description" content="Kitobchi — original kitoblar, darsliklar va badiiy adabiyotlarni onlayn xarid qiling. O'zbekiston bo'ylab tezkor yetkazib berish.">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white">
    <h1 class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">
        Kitobchi — Online kitoblar marketpleysi
    </h1>

    <?php
        // 1. Banners Query & Filters (to_shop is SKIPPED)
        $banners = Cache::remember('web_banners_v6', 300, function() {
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
        try {
            $newBooks = Cache::remember('web_home_new_books_v2', 300, function() {
                return \App\Models\Books::where('status', true)
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0)
                    ->orderByDesc('created_at')
                    ->take(10)
                    ->get();
            });
        } catch(\Throwable $e) { $newBooks = collect(); }

        // 3. Recommended Books (Tavsiya etamiz)
        try {
            $recommendedBooks = Cache::remember('web_home_rec_books_v2', 300, function() {
                return \App\Models\Books::where('status', true)
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0)
                    ->orderByDesc('totalSales')
                    ->take(10)
                    ->get();
            });
        } catch(\Throwable $e) { $recommendedBooks = collect(); }

        // 4. Genre / Category Sections (Janrlar bo'yicha kitoblar)
        try {
            $categorySections = Cache::remember('web_home_cat_sections_v2', 600, function() {
                $categories = \App\Models\BookCategories::where('is_active', true)
                    ->orderBy('name_uz')
                    ->take(6)
                    ->get();

                $sections = collect();
                foreach ($categories as $cat) {
                    $books = \App\Models\Books::where('status', true)
                        ->where('is_approved', 1)
                        ->where('is_hidden', 0)
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
    ?>

    <!-- ====== HERO BANNER SLIDER ====== -->
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
        <section style="padding:1.25rem 0 1rem;">
            <div id="kcBannerSlider" style="position:relative;overflow:hidden;border-radius:1.25rem;aspect-ratio:520/141;background:#f1f5f9;">
                <!-- Slides Track -->
                <div id="kcBannerTrack" style="display:flex;transition:transform 0.7s cubic-bezier(0.16,1,0.3,1);height:100%;">
                    <?php if($banners->isNotEmpty()): ?>
                        <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="min-width:100%;flex-shrink:0;position:relative;height:100%;" class="kc-banner-slide">
                                <?php if($banner->type === 'product'): ?>
                                    <a href="<?php echo e($banner->url); ?>" style="display:block;width:100%;height:100%;position:relative;overflow:hidden;" class="group/item">
                                        <img src="<?php echo e($banner->image); ?>"
                                             alt="<?php echo e($banner->title); ?>"
                                             style="width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.7s;"
                                             loading="eager" fetchpriority="high">
                                    </a>
                                <?php else: ?>
                                    <div onclick="openBannerBottomSheet('<?php echo e(addslashes($banner->title)); ?>', '<?php echo e(addslashes($banner->description)); ?>', '<?php echo e($banner->image); ?>', '<?php echo e($banner->url); ?>')"
                                         style="display:block;width:100%;height:100%;position:relative;overflow:hidden;cursor:pointer;" class="group/item">
                                        <img src="<?php echo e($banner->image); ?>"
                                             alt="<?php echo e($banner->title); ?>"
                                             style="width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.7s;"
                                             loading="eager" fetchpriority="high">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <!-- Default Gradient Banner -->
                        <div style="min-width:100%;flex-shrink:0;background:linear-gradient(135deg, var(--color-tima-500) 0%, #6366f1 100%);display:flex;align-items:center;justify-content:center;height:100%;">
                            <div style="text-align:center;color:#fff;padding:2rem;">
                                <div style="font-size:clamp(1.5rem,4vw,2.5rem);font-weight:900;margin-bottom:0.5rem;">📚 Kitobchi Marketpleysi</div>
                                <div style="font-size:1.125rem;opacity:0.9;">Muborak va original kitoblar eng hamyonbop narxlarda</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Slider Controls -->
                <?php if($banners->count() > 1): ?>
                    <button onclick="slideBanner(-1)"
                            aria-label="Oldingi"
                            style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);z-index:20;width:2.25rem;height:2.25rem;border-radius:9999px;background:rgba(0,0,0,0.35);backdrop-filter:blur(4px);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;transition:all 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button onclick="slideBanner(1)"
                            aria-label="Keyingi"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);z-index:20;width:2.25rem;height:2.25rem;border-radius:9999px;background:rgba(0,0,0,0.35);backdrop-filter:blur(4px);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;transition:all 0.2s;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- ====== CATEGORIES CAROUSEL ====== -->
    <section style="padding:1rem 0 2rem;">
        <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
            <h2 style="font-weight:800;font-size:clamp(1.25rem,4vw,2rem);color:var(--color-tima-500);line-height:1;margin:0 0 1rem;text-transform:capitalize;">Kataloglar</h2>
            <div style="position:relative;">
                <div style="overflow-x:auto;overflow-y:hidden;-ms-overflow-style:none;scrollbar-width:none;" id="kcCatScroll">
                    <div style="display:flex;gap:0.875rem;padding-bottom:0.5rem;width:max-content;">
                        <?php
                            try {
                                $webCategories = Cache::remember('web_top_categories_home_v4', 600, function() {
                                    return \App\Models\BookCategories::where('is_active', true)->orderBy('name_uz')->take(14)->get();
                                });
                            } catch(\Throwable $e) { $webCategories = collect(); }
                        ?>

                        <!-- All categories item -->
                        <a href="<?php echo e(route('web.catalog')); ?>"
                           style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;text-decoration:none;flex-shrink:0;width:84px;">
                            <div style="width:80px;height:80px;border-radius:9999px;overflow:hidden;border:2px solid transparent;transition:border-color 0.3s;background:linear-gradient(135deg,var(--color-tima-500),#6366f1);display:flex;align-items:center;justify-content:center;"
                                 onmouseover="this.style.borderColor='var(--color-tima-500)'" onmouseout="this.style.borderColor='transparent'">
                                <span style="font-size:1.75rem;">📚</span>
                            </div>
                            <span style="font-size:0.8125rem;font-weight:600;color:#111827;text-align:center;line-height:1.3;max-width:84px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">Barchasi</span>
                        </a>

                        <?php $__currentLoopData = $webCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('web.catalog', ['category' => $cat->id])); ?>"
                               style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;text-decoration:none;flex-shrink:0;width:84px;">
                                <div style="width:80px;height:80px;border-radius:9999px;overflow:hidden;border:2px solid #e2e8f0;transition:border-color 0.3s;position:relative;background:#f8fafc;"
                                     onmouseover="this.style.borderColor='var(--color-tima-500)'" onmouseout="this.style.borderColor='#e2e8f0'">
                                    <?php if($cat->image): ?>
                                        <img src="<?php echo e(asset('storage/' . $cat->image)); ?>"
                                             alt="<?php echo e($cat->name_uz); ?>"
                                             style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-tima-100),var(--color-tima-200));display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;color:var(--color-tima-600);">
                                            <?php echo e(mb_substr($cat->name_uz ?? 'K', 0, 1)); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size:0.8125rem;font-weight:600;color:#111827;text-align:center;line-height:1.3;max-width:84px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo e($cat->name_uz); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== SECTION 1: YANGI KITOBLAR ====== -->
    <?php if($newBooks->isNotEmpty()): ?>
        <section style="padding:1rem 0 2.5rem;">
            <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h2 style="font-weight:800;font-size:clamp(1.25rem,3.5vw,1.875rem);line-height:1;margin:0;color:#111827;">
                        🆕 Yangi kelgan kitoblar
                    </h2>
                    <a href="<?php echo e(route('web.catalog')); ?>" style="font-size:0.875rem;font-weight:700;color:var(--color-tima-500);text-decoration:none;">
                        Barchasi →
                    </a>
                </div>

                <!-- 2-col on mobile, 5-col on desktop -->
                <div class="kc-home-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;">
                    <?php $__currentLoopData = $newBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('partials.home-book-card', ['book' => $book], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ====== SECTION 2: TAVSIYA ETAMIZ (TOP SOTUVLAR) ====== -->
    <?php if($recommendedBooks->isNotEmpty()): ?>
        <section style="padding:1rem 0 2.5rem;background:#f8fafc;">
            <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h2 style="font-weight:800;font-size:clamp(1.25rem,3.5vw,1.875rem);line-height:1;margin:0;color:#111827;">
                        🔥 Tavsiya etamiz & Top sotuvlar
                    </h2>
                    <a href="<?php echo e(route('web.catalog')); ?>" style="font-size:0.875rem;font-weight:700;color:var(--color-tima-500);text-decoration:none;">
                        Barchasi →
                    </a>
                </div>

                <div class="kc-home-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;">
                    <?php $__currentLoopData = $recommendedBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('partials.home-book-card', ['book' => $book], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ====== SECTION 3+: JANRLAR BO'YICHA KITOBLAR ====== -->
    <?php if($categorySections->isNotEmpty()): ?>
        <?php $__currentLoopData = $categorySections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section style="padding:1.5rem 0 2.5rem;border-top:1px solid #f1f5f9;">
                <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <h2 style="font-weight:800;font-size:clamp(1.125rem,3vw,1.75rem);line-height:1;margin:0;color:#111827;">
                            📚 <?php echo e($section->category->name_uz); ?>

                        </h2>
                        <a href="<?php echo e(route('web.catalog', ['category' => $section->category->id])); ?>" style="font-size:0.875rem;font-weight:700;color:var(--color-tima-500);text-decoration:none;">
                            Barchasi →
                        </a>
                    </div>

                    <div class="kc-home-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;">
                        <?php $__currentLoopData = $section->books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('partials.home-book-card', ['book' => $book], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</div>

<!-- ====== BANNER BOTTOMSHEET MODAL (PiyolaMarket Mobile BottomSheet) ====== -->
<div id="kcBannerBottomSheet" class="kc-modal-overlay" onclick="if(event.target===this) closeBannerBottomSheet()">
    <div style="width:100%;max-width:480px;background:#fff;border-radius:1.5rem 1.5rem 0 0;padding:1.5rem;box-shadow:0 -10px 40px rgba(0,0,0,0.2);position:fixed;bottom:0;max-height:85vh;overflow-y:auto;transition:transform 0.3s ease-out;" id="kcBottomSheetCard">
        
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div style="width:36px;height:4px;background:#cbd5e1;border-radius:9999px;margin:0 auto;"></div>
            <button onclick="closeBannerBottomSheet()" style="position:absolute;top:1.25rem;right:1.25rem;width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border:none;background:#f1f5f9;border-radius:9999px;cursor:pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="kcBottomSheetImageWrap" style="margin-bottom:1rem;border-radius:1rem;overflow:hidden;aspect-ratio:16/9;background:#f1f5f9;display:none;">
            <img id="kcBottomSheetImage" src="" alt="" style="width:100%;height:100%;object-fit:cover;">
        </div>

        <h3 id="kcBottomSheetTitle" style="font-size:1.25rem;font-weight:800;color:#0f172a;margin:0 0 0.5rem;"></h3>
        <p id="kcBottomSheetDesc" style="color:#64748b;font-size:0.9375rem;line-height:1.6;margin:0 0 1.5rem;"></p>

        <a id="kcBottomSheetBtn" href="<?php echo e(route('web.catalog')); ?>"
           style="display:flex;align-items:center;justify-content:center;width:100%;height:3.25rem;background:var(--color-tima-500);color:#fff;border-radius:9999px;font-size:1rem;font-weight:700;text-decoration:none;transition:all 0.2s;">
            Katalogga o'tish →
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
        const slider = document.getElementById('kcBannerSlider');
        if (!slider) return;
        let startX = 0;
        let dist = 0;

        slider.addEventListener('touchstart', e => {
            startX = e.touches[0].clientX;
            dist = 0;
        }, { passive: true });

        slider.addEventListener('touchmove', e => {
            dist = e.touches[0].clientX - startX;
        }, { passive: true });

        slider.addEventListener('touchend', () => {
            if (dist < -50) slideBanner(1);
            else if (dist > 50) slideBanner(-1);
        });
    })();

    // BANNER BOTTOMSHEET MODAL
    function openBannerBottomSheet(title, desc, image, url) {
        const modal = document.getElementById('kcBannerBottomSheet');
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
            imgWrap.style.display = 'block';
        } else {
            imgWrap.style.display = 'none';
        }

        if (url) {
            btnEl.href = url;
            btnEl.style.display = 'flex';
        } else {
            btnEl.style.display = 'none';
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeBannerBottomSheet() {
        const modal = document.getElementById('kcBannerBottomSheet');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Responsive grid
    function updateResponsiveGrids() {
        const grids = document.querySelectorAll('.kc-home-grid');
        const w = window.innerWidth;

        let cols = 2;
        if (w >= 1280) cols = 5;
        else if (w >= 1024) cols = 4;
        else if (w >= 768) cols = 3;

        grids.forEach(g => {
            g.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        startBannerAutoplay();
        updateResponsiveGrids();
    });
    window.addEventListener('resize', updateResponsiveGrids);
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>