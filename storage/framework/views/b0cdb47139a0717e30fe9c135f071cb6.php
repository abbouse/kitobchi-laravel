<?php $__env->startSection('title', 'Kitobchi — Online kitoblar va kanselyariya marketpleysi'); ?>

<?php $__env->startPush('meta'); ?>
<meta name="description" content="Kitobchi — original kitoblar, darsliklar va kanselyariya mahsulotlarini onlayn xarid qiling. O'zbekiston bo'ylab tezkor yetkazib berish.">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white">
    <h1 class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">
        Kitobchi — Online kitoblar va kanselyariya marketpleysi
    </h1>

    <!-- ====== HERO BANNER SLIDER ====== -->
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
        <section style="padding:1.5rem 0;">
            <div id="kcBannerSlider" style="position:relative;overflow:hidden;border-radius:1rem;aspect-ratio:520/141;background:#e2e8f0;">
                <!-- Slides -->
                <div id="kcBannerTrack" style="display:flex;transition:transform 0.7s cubic-bezier(0.16,1,0.3,1);">
                    <?php
                        try {
                            $banners = Cache::remember('web_banners_v2', 300, function() {
                                return \App\Models\Banner::where('is_active', true)->orderBy('order')->take(6)->get();
                            });
                        } catch(\Throwable $e) { $banners = collect(); }
                    ?>

                    <?php if($banners->isNotEmpty()): ?>
                        <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="min-width:100%;flex-shrink:0;position:relative;" class="kc-banner-slide">
                                <div style="position:absolute;inset:0;background:var(--color-tima-500)/10;pointer-events:none;transition:background 0.3s;"></div>
                                <?php if($banner->url): ?>
                                    <a href="<?php echo e($banner->url); ?>" rel="noopener noreferrer" style="display:block;width:100%;height:100%;">
                                <?php else: ?>
                                    <div style="display:block;width:100%;height:100%;">
                                <?php endif; ?>
                                        <img src="<?php echo e(asset('storage/' . $banner->image)); ?>"
                                             alt="<?php echo e($banner->title); ?>"
                                             style="width:100%;height:100%;object-fit:cover;transition:transform 0.7s;display:block;"
                                             loading="eager" fetchpriority="high"
                                             onerror="this.style.display='none'">
                                <?php if($banner->url): ?>
                                    </a>
                                <?php else: ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <!-- Fallback gradient banner -->
                        <div style="min-width:100%;flex-shrink:0;background:linear-gradient(135deg, var(--color-tima-500) 0%, #6366f1 100%);display:flex;align-items:center;justify-content:center;min-height:180px;">
                            <div style="text-align:center;color:#fff;padding:2rem;">
                                <div style="font-size:2.5rem;font-weight:900;margin-bottom:0.5rem;">📚 Kitobchi</div>
                                <div style="font-size:1.125rem;opacity:0.9;">Online kitoblar va kanselyariya do'koni</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Slider Controls -->
                <?php if($banners->count() > 1): ?>
                    <button id="kcBannerPrev" onclick="slideBanner(-1)"
                            aria-label="Oldingi"
                            style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);z-index:20;width:2.5rem;height:2.5rem;border-radius:9999px;background:rgba(96,96,96,0.5);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;transition:all 0.2s;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button id="kcBannerNext" onclick="slideBanner(1)"
                            aria-label="Keyingi"
                            style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);z-index:20;width:2.5rem;height:2.5rem;border-radius:9999px;background:rgba(30,30,30,0.7);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;transition:all 0.2s;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- ====== CATEGORIES CAROUSEL ====== -->
    <section style="padding:1.5rem 0 2.5rem;">
        <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
            <h2 style="font-weight:700;font-size:clamp(1.25rem,4vw,2.25rem);color:var(--color-tima-500);line-height:1;margin:0 0 1rem;text-transform:capitalize;">Kataloglar</h2>
            <div style="position:relative;">
                <div style="overflow-x:auto;overflow-y:hidden;-ms-overflow-style:none;scrollbar-width:none;" id="kcCatScroll">
                    <div style="display:flex;gap:0.75rem;padding-bottom:0.5rem;width:max-content;">

                        <?php
                            try {
                                $webCategories = Cache::remember('web_top_categories_home', 600, function() {
                                    return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(12)->get();
                                });
                            } catch(\Throwable $e) { $webCategories = collect(); }
                        ?>

                        <!-- All categories item -->
                        <a href="<?php echo e(route('web.catalog')); ?>"
                           style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;text-decoration:none;flex-shrink:0;width:90px;">
                            <div style="width:90px;height:90px;border-radius:9999px;overflow:hidden;border:2px solid transparent;transition:border-color 0.3s;background:linear-gradient(135deg,var(--color-tima-500),#6366f1);display:flex;align-items:center;justify-content:center;"
                                 onmouseover="this.style.borderColor='var(--color-tima-500)'" onmouseout="this.style.borderColor='transparent'">
                                <span style="font-size:2rem;">📚</span>
                            </div>
                            <span style="font-size:0.8125rem;font-weight:500;color:#111827;text-align:center;line-height:1.3;transition:all 0.3s;max-width:90px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">Barchasi</span>
                        </a>

                        <?php $__currentLoopData = $webCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('web.catalog', ['category' => $cat->id])); ?>"
                               style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;text-decoration:none;flex-shrink:0;width:90px;">
                                <div style="width:90px;height:90px;border-radius:9999px;overflow:hidden;border:2px solid transparent;transition:border-color 0.3s;position:relative;"
                                     onmouseover="this.style.borderColor='var(--color-tima-500)'" onmouseout="this.style.borderColor='transparent'">
                                    <?php if($cat->image): ?>
                                        <img src="<?php echo e(asset('storage/' . $cat->image)); ?>"
                                             alt="<?php echo e($cat->name); ?>"
                                             style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                                             loading="lazy"
                                             onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-tima-100),var(--color-tima-200));display:flex;align-items:center;justify-content:center;font-size:1.75rem;font-weight:900;color:var(--color-tima-600);">
                                            <?php echo e(mb_substr($cat->name, 0, 1)); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size:0.8125rem;font-weight:500;color:#111827;text-align:center;line-height:1.3;transition:all 0.3s;max-width:90px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo e($cat->name); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <!-- Fade edges (desktop) -->
                <div style="position:absolute;inset-y:0;left:0;width:50px;background:linear-gradient(to right,#fff,transparent);pointer-events:none;display:none;" class="kc-fade-left"></div>
                <div style="position:absolute;inset-y:0;right:0;width:50px;background:linear-gradient(to left,#fff,transparent);pointer-events:none;display:none;" class="kc-fade-right"></div>
            </div>
        </div>
    </section>

    <!-- ====== FEATURED PRODUCTS SECTION ====== -->
    <?php
        try {
            $featuredProducts = Cache::remember('web_featured_home_v2', 300, function() {
                return \App\Models\Books::where('status', true)
                    ->orderByDesc('created_at')
                    ->take(20)
                    ->get();
            });
        } catch(\Throwable $e) { $featuredProducts = collect(); }
    ?>

    <?php if($featuredProducts->isNotEmpty()): ?>
        <section style="padding:1rem 0 2.5rem;">
            <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
                <h2 style="font-weight:700;font-size:clamp(1.125rem,3.5vw,2rem);line-height:1;margin:0 0 0.75rem 0;color:#111827;">
                    🔥 Yangi Kitoblar
                </h2>
                <!-- Product Grid — exactly like PiyolaMarket: grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 -->
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;margin-bottom:2.5rem;"
                     id="kcProductGrid1">
                    <?php $__currentLoopData = $featuredProducts->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $slug = \Illuminate\Support\Str::slug($book->name);
                            $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                            $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                            $isDisc = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                            $price = $isDisc ? $book->discountPrice : $book->price;
                            $discPct = $isDisc ? round((($book->price - $price) / $book->price) * 100) : 0;
                        ?>
                        <a href="<?php echo e($url); ?>"
                           style="position:relative;display:flex;flex-direction:column;border-radius:0.75rem;background:#fff;border:1px solid #fff;box-shadow:none;transition:box-shadow 0.2s;overflow:hidden;text-decoration:none;color:inherit;"
                           onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">

                            <!-- Image area with aspect-ratio 232/309 like PiyolaMarket -->
                            <div style="position:relative;width:100%;background:#fff;border-radius:0.75rem;overflow:hidden;" class="kc-card-img-wrap">
                                <img src="<?php echo e($img); ?>"
                                     alt="<?php echo e($book->name); ?>"
                                     style="width:100%;display:block;object-fit:cover;transition:transform 0.7s;aspect-ratio:3/4;"
                                     loading="lazy"
                                     onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">

                                <!-- Discount badge (bottom-left like PiyolaMarket) -->
                                <?php if($isDisc): ?>
                                    <div style="position:absolute;bottom:0.375rem;left:0.375rem;z-index:20;">
                                        <span style="display:inline-flex;align-items:center;font-size:0.75rem;font-weight:500;border-radius:0.375rem;color:#fff;padding:0.125rem 0.25rem;background:#ED3131;">
                                            -<?php echo e($discPct); ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Favorite button (top-right like PiyolaMarket) -->
                                <div style="position:absolute;top:0.375rem;right:0.375rem;z-index:20;">
                                    <button aria-label="Sevimlilar"
                                            onclick="event.preventDefault();this.querySelector('svg').style.fill='#ef4444';this.querySelector('svg').style.stroke='#ef4444';"
                                            style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:rgba(255,255,255,0.7);border:none;cursor:pointer;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);transition:all 0.3s;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Info area: padding:1rem 0.25rem 1rem like PiyolaMarket -->
                            <div style="padding:1rem 0.25rem 1rem;display:flex;flex-direction:column;flex:1;">
                                <!-- Title: text-sm leading-snug line-clamp-2 -->
                                <div style="font-size:0.875rem;line-height:1.375;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;color:#111827;margin-bottom:0.5rem;flex:1;">
                                    <?php echo e($book->name); ?>

                                </div>

                                <!-- Price -->
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;">
                                    <p style="font-size:0.875rem;color:#6b7280;font-weight:600;margin:0.5rem 0 0;line-height:1.25;">
                                        <?php echo e(number_format($price)); ?> so'm
                                    </p>
                                </div>

                                <?php if($isDisc): ?>
                                    <div style="margin-top:auto;">
                                        <span style="display:inline-block;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:500;background:var(--color-tima-100,#ede9fe);color:var(--color-tima-500);border-radius:9999px;margin-top:0.25rem;">
                                            <?php echo e(number_format($book->price)); ?> so'm
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- View All Button -->
                <div style="text-align:center;">
                    <a href="<?php echo e(route('web.catalog')); ?>"
                       style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.875rem 2rem;background:var(--color-tima-500);color:#fff;border-radius:9999px;font-weight:700;font-size:1rem;text-decoration:none;transition:all 0.2s;"
                       onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Barcha kitoblarni ko'rish
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ====== STATIONERY SECTION ====== -->
    <?php
        try {
            $webStationery = Cache::remember('web_stationery_home_v2', 300, function() {
                return \App\Models\Stationery::where('status', true)
                    ->orderByDesc('created_at')
                    ->take(10)
                    ->get();
            });
        } catch(\Throwable $e) { $webStationery = collect(); }
    ?>

    <?php if($webStationery->isNotEmpty()): ?>
        <section style="padding:1rem 0 2.5rem;">
            <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <h2 style="font-weight:700;font-size:clamp(1.125rem,3.5vw,2rem);line-height:1;margin:0;color:#111827;">
                        ✏️ Kanselyariya
                    </h2>
                    <a href="<?php echo e(route('web.catalog', ['type' => 'stationery'])); ?>"
                       style="display:flex;align-items:center;gap:0.25rem;font-size:0.875rem;font-weight:600;color:var(--color-tima-500);text-decoration:none;">
                        Barchasi
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;">
                    <?php $__currentLoopData = $webStationery->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $sSlug = \Illuminate\Support\Str::slug($item->name);
                            $sUrl = route('web.stationery.show', ['id' => $item->id, 'slug' => $sSlug]);
                            $sImg = $item->first_image ? asset('storage/' . $item->first_image) : asset('images/logo/logo_blue.png');
                            $sIsDisc = $item->discount_price > 0 && $item->discount_price < $item->price;
                            $sPrice = $sIsDisc ? $item->discount_price : $item->price;
                            $sDiscPct = $sIsDisc ? round((($item->price - $sPrice) / $item->price) * 100) : 0;
                        ?>
                        <a href="<?php echo e($sUrl); ?>"
                           style="position:relative;display:flex;flex-direction:column;border-radius:0.75rem;background:#fff;border:1px solid #fff;transition:box-shadow 0.2s;overflow:hidden;text-decoration:none;color:inherit;"
                           onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">

                            <div style="position:relative;width:100%;background:#fff;border-radius:0.75rem;overflow:hidden;">
                                <img src="<?php echo e($sImg); ?>"
                                     alt="<?php echo e($item->name); ?>"
                                     style="width:100%;display:block;object-fit:cover;transition:transform 0.7s;aspect-ratio:3/4;"
                                     loading="lazy"
                                     onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                <?php if($sIsDisc): ?>
                                    <div style="position:absolute;bottom:0.375rem;left:0.375rem;z-index:20;">
                                        <span style="display:inline-flex;font-size:0.75rem;font-weight:500;border-radius:0.375rem;color:#fff;padding:0.125rem 0.25rem;background:#ED3131;">-<?php echo e($sDiscPct); ?>%</span>
                                    </div>
                                <?php endif; ?>
                                <div style="position:absolute;top:0.375rem;right:0.375rem;z-index:20;">
                                    <button aria-label="Sevimlilar" onclick="event.preventDefault();"
                                            style="width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;background:rgba(255,255,255,0.7);border:none;cursor:pointer;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div style="padding:1rem 0.25rem 1rem;display:flex;flex-direction:column;flex:1;">
                                <div style="font-size:0.875rem;line-height:1.375;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;color:#111827;margin-bottom:0.5rem;flex:1;">
                                    <?php echo e($item->name); ?>

                                </div>
                                <p style="font-size:0.875rem;color:#6b7280;font-weight:600;margin:0.5rem 0 0;line-height:1.25;">
                                    <?php echo e(number_format($sPrice)); ?> so'm
                                </p>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ====== WHY US SECTION ====== -->
    <section style="padding:2rem 0 3rem;background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
        <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
            <h2 style="font-weight:700;font-size:clamp(1.25rem,4vw,2rem);text-align:center;margin:0 0 2rem;color:#111827;">Nima uchun Kitobchi?</h2>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;">
                <?php $__currentLoopData = [
                    ['🚀', 'Tezkor yetkazib berish', 'Toshkent bo\'ylab 1-2 soatda'],
                    ['📦', 'Original mahsulotlar', 'Sifati kafolatlangan'],
                    ['💳', 'Qulay to\'lov', 'Naqd yoki karta orqali'],
                    ['🔄', 'Qaytarish kafolati', '7 kun ichida qaytarish'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon, $title, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="background:#fff;border-radius:1rem;padding:1.25rem;display:flex;flex-direction:column;align-items:flex-start;gap:0.5rem;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                        <div style="font-size:1.75rem;"><?php echo e($icon); ?></div>
                        <div style="font-weight:700;font-size:0.9375rem;color:#111827;"><?php echo e($title); ?></div>
                        <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;"><?php echo e($desc); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

</div>

<!-- Responsive grid adjustments -->
<style>
    @media(min-width:768px) {
        #kcProductGrid1 { grid-template-columns: repeat(3, 1fr) !important; gap: 0.75rem !important; }
    }
    @media(min-width:1024px) {
        #kcProductGrid1 { grid-template-columns: repeat(4, 1fr) !important; gap: 1rem !important; }
    }
    @media(min-width:1280px) {
        #kcProductGrid1 { grid-template-columns: repeat(5, 1fr) !important; }
    }
    .kc-cat-scroll::-webkit-scrollbar { display: none; }
    @media(min-width:768px) {
        .kc-fade-left, .kc-fade-right { display: block !important; }
    }
</style>

<?php $__env->startPush('scripts'); ?>
<script>
    // Banner Slider
    (function() {
        let current = 0;
        const track = document.getElementById('kcBannerTrack');
        if (!track) return;
        const slides = track.children;
        const total = slides.length;
        if (total <= 1) return;

        window.slideBanner = function(dir) {
            current = (current + dir + total) % total;
            track.style.transform = `translateX(-${current * 100}%)`;
        };

        // Auto-play
        setInterval(() => slideBanner(1), 4500);
    })();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>