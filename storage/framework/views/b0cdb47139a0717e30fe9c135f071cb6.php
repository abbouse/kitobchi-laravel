<?php
    $__seoTitle = __('landing.seo.title');
    $__seoDesc = __('landing.seo.description');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
?>

<?php $__env->startSection('title', $__seoTitle); ?>

<?php $__env->startPush('head'); ?>
<style>
/* popcorn-html bilan bir xil: mask-size cover (vendor `.coverage-wrap` ustidan) */
#coverage .coverage-wrap {
    -webkit-mask-image: url('<?php echo e(asset('images/uz_map.png')); ?>');
    mask-image: url('<?php echo e(asset('images/uz_map.png')); ?>');
    -webkit-mask-size: cover;
    mask-size: cover;
    -webkit-mask-position: center;
    mask-position: center;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => url('/'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php
        $base = rtrim((string) (config('app.url') ?: url('/')), '/');
        $logo = url('/images/logo/logo_blue.png');
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $base.'/#website',
                    'url' => $base.'/',
                    'name' => 'Kitobchi',
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                    'publisher' => ['@id' => $base.'/#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('web.catalog') . '?search={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => $base.'/#organization',
                    'name' => 'Kitobchi',
                    'url' => $base.'/',
                    'logo' => ['@type' => 'ImageObject', 'url' => $logo],
                ],
            ],
        ];
    ?>
    <script type="application/ld+json"><?php echo json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $kcScreenshot = function (string $base): ?string {
            $allowed = ['png', 'webp', 'jpg', 'jpeg'];
            $baseLower = strtolower($base);
            $dirs = [
                [public_path('images/screenshots'), 'images/screenshots'],
                [public_path('screenshots'), 'screenshots'],
                [storage_path('app/public/screenshots'), 'storage/screenshots'],
            ];
            foreach ($dirs as [$dir, $urlPrefix]) {
                if (! is_dir($dir)) {
                    continue;
                }
                foreach (scandir($dir) ?: [] as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $full = $dir . DIRECTORY_SEPARATOR . $file;
                    if (! is_file($full)) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (! in_array($ext, $allowed, true)) {
                        continue;
                    }
                    if (strtolower(pathinfo($file, PATHINFO_FILENAME)) !== $baseLower) {
                        continue;
                    }
                    return asset($urlPrefix . '/' . $file);
                }
            }
            return null;
        };
        $shotHero1 = $kcScreenshot('kitobchi_1');
        $shotHero2 = $kcScreenshot('kitobchi_2');
        $shotBiz = $kcScreenshot('kitobchi_b');
    ?>
    <header class="hero">
        <div class="page-padding">
            <div class="container">
                <div class="home-hero-header">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner">
                            <div><strong><?php echo e(__('landing.hero.eyebrow')); ?></strong></div>
                        </div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h1 class="home-hero-heading"><?php echo e(__('landing.hero.heading_line1')); ?><br><?php echo e(__('landing.hero.heading_line2')); ?></h1>
                    <div class="home-hero-subheading">
                        <p class="subheading"><?php echo e(__('landing.hero.sub')); ?></p>
                    </div>
                    <div class="btn-wrapper cc-hero">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-label="<?php echo e(__('landing.hero.download_aria')); ?>">
                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                            <div class="cta-inner">
                                <div><strong><?php echo e(__('landing.hero.download')); ?></strong></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="home-hero-img-wrap">
                    <img src="<?php echo e(asset('vendor/popcorn/images/hero-portal-bg-new.webp')); ?>" alt="" width="780" height="978" class="home-hero-portal" loading="eager" decoding="async" sizes="(max-width: 479px) 96vw, (max-width: 991px) 95vw, 741px">
                    <div class="home-hero-mockup cc-1">
                        <?php if($shotHero1): ?>
                            <img src="<?php echo e($shotHero1); ?>" alt="<?php echo e(__('landing.hero.shot1_alt')); ?>" class="u-auto-img" width="390" height="844" loading="eager" decoding="async">
                        <?php else: ?>
                            <div class="u-auto-img kc-ph" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                    <div class="home-hero-mockup cc-2">
                        <?php if($shotHero2): ?>
                            <img src="<?php echo e($shotHero2); ?>" alt="<?php echo e(__('landing.hero.shot2_alt')); ?>" class="u-auto-img" width="390" height="844" loading="eager" decoding="async">
                        <?php else: ?>
                            <div class="u-auto-img kc-ph" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-highlights-wrap">
                    <div class="hero-highlight-cell">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <img src="<?php echo e(asset('vendor/popcorn/images/feature-pin.png')); ?>" width="32" height="32" alt="" class="icon-img" loading="lazy">
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m"><?php echo e(__('landing.hero.highlight1_title')); ?></h3>
                        <p class="subheading cc-features"><?php echo e(__('landing.hero.highlight1_text')); ?></p>
                    </div>
                    <div class="hero-highlight-cell">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <div class="svg-embed w-embed">
                                    <svg width="22" height="22" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M4 8.88887L6.66667 11.5555L11.8889 5.33331" stroke="#393737" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m"><?php echo e(__('landing.hero.highlight2_title')); ?></h3>
                        <p class="subheading cc-features"><?php echo e(__('landing.hero.highlight2_text')); ?></p>
                    </div>
                    <div class="hero-highlight-cell cc-cancel">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <img src="<?php echo e(asset('vendor/popcorn/images/feature-event.png')); ?>" width="32" height="32" alt="" class="icon-img" loading="lazy">
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m"><?php echo e(__('landing.hero.highlight3_title')); ?></h3>
                        <p class="subheading cc-features"><?php echo e(__('landing.hero.highlight3_text')); ?></p>
                    </div>
                </div>
                <div class="hero-notice"><?php echo e(__('landing.hero.notice')); ?></div>
            </div>
        </div>
    </header>

    <?php
        if (!function_exists('kcStatRound')) {
            function kcStatRound(int $n): int {
                if ($n <= 0) return 0;
                $len = strlen((string) $n);
                $step = (int) max(1, (int) pow(10, $len - 1) / 2);
                return (int)(ceil($n / $step) * $step);
            }
        }
        if (!function_exists('kcFmtStat')) {
            function kcFmtStat(int $n): string {
                if ($n < 1000) return (string) $n;
                if ($n < 1_000_000) {
                    $val = rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.');
                    return $val . 'K';
                }
                $val = rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.');
                return $val . 'M';
            }
        }
        $storesTarget    = kcStatRound($landingPartnerStoresCount * 3);
        $salesTarget     = kcStatRound($landingSalesCount * 600);
        $customersTarget = kcStatRound($landingCustomersCount * 5);
    ?>
    <section class="section" id="stats">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading"><?php echo e(__('landing.stats.heading_line1')); ?><br><?php echo e(__('landing.stats.heading_line2')); ?></h2>
                    <p class="subheading"><?php echo e(__('landing.stats.sub')); ?></p>
                </div>
                <div class="features-grid kc-stats-grid">
                    <div class="features-card cc-green kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num<?php echo e($storesTarget > 0 ? ' js-count' : ''); ?>"
                                 <?php if($storesTarget > 0): ?> data-count-target="<?php echo e($storesTarget); ?>" <?php endif; ?>
                                 aria-label="<?php echo e($storesTarget); ?><?php echo e($storesTarget > 0 ? '+' : ''); ?> <?php echo e(__('landing.stats.partners_aria')); ?>">
                                <span class="kc-stat-num__val"><?php echo e(kcFmtStat($storesTarget)); ?></span><?php if($storesTarget > 0): ?><span class="kc-stat-num__plus" aria-hidden="true">+</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m"><?php echo e(__('landing.stats.partners')); ?></h3>
                            <p class="subheading cc-features"><?php echo e(__('landing.stats.partners_desc')); ?></p>
                        </div>
                    </div>
                    <div class="features-card cc-blue kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num<?php echo e($salesTarget > 0 ? ' js-count' : ''); ?>"
                                 <?php if($salesTarget > 0): ?> data-count-target="<?php echo e($salesTarget); ?>" <?php endif; ?>
                                 aria-label="<?php echo e($salesTarget); ?><?php echo e($salesTarget > 0 ? '+' : ''); ?> <?php echo e(__('landing.stats.sales_aria')); ?>">
                                <span class="kc-stat-num__val"><?php echo e(kcFmtStat($salesTarget)); ?></span><?php if($salesTarget > 0): ?><span class="kc-stat-num__plus" aria-hidden="true">+</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m"><?php echo e(__('landing.stats.sales')); ?></h3>
                            <p class="subheading cc-features"><?php echo e(__('landing.stats.sales_desc')); ?></p>
                        </div>
                    </div>
                    <div class="features-card cc-purple kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num<?php echo e($customersTarget > 0 ? ' js-count' : ''); ?>"
                                 <?php if($customersTarget > 0): ?> data-count-target="<?php echo e($customersTarget); ?>" <?php endif; ?>
                                 aria-label="<?php echo e($customersTarget); ?><?php echo e($customersTarget > 0 ? '+' : ''); ?> <?php echo e(__('landing.stats.customers_aria')); ?>">
                                <span class="kc-stat-num__val"><?php echo e(kcFmtStat($customersTarget)); ?></span><?php if($customersTarget > 0): ?><span class="kc-stat-num__plus" aria-hidden="true">+</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m"><?php echo e(__('landing.stats.customers')); ?></h3>
                            <p class="subheading cc-features"><?php echo e(__('landing.stats.customers_desc')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="coverage">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-coverage">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div><?php echo e(__('landing.coverage.eyebrow')); ?></div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h2 class="section-heading"><?php echo e(__('landing.coverage.heading_line1')); ?><br><?php echo e(__('landing.coverage.heading_line2')); ?></h2>
                    <p class="subheading"><?php echo e(__('landing.coverage.sub')); ?></p>
                </div>
                <div class="coverage">
                    <div class="coverage-wrap">
                        <canvas id="uzMapCanvas" class="coverage-map"></canvas>
                    </div>
                    <div class="coverage-countries-wrap scroll-observe">
                    <?php $__currentLoopData = __('landing.coverage.regions'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="country-tag-wrap cc-<?php echo e(($i % 9) + 1); ?>">
                            <div class="country-tag cc-<?php echo e(($i % 9) + 1); ?>"><div><?php echo e($city); ?></div></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="kc-book-ticker" aria-label="<?php echo e(__('landing.ticker.aria')); ?>">
        <div class="kc-book-ticker__track">
            <?php
                $books = $featuredBooks ?? collect();
                $clrs = ['#393737','#5a5757','#6b6560','#4a4a48'];
            ?>
            <?php if($books->count() > 0): ?>
                <?php $__currentLoopData = [0, 1]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <article class="kc-book-card">
                            <?php if($book->first_image): ?>
                                <img src="<?php echo e(asset('storage/'.$book->first_image)); ?>" alt="<?php echo e($book->name); ?>" loading="lazy"
                                     onerror="this.outerHTML='<div class=\'kc-book-card__ph\' style=\'background:linear-gradient(135deg,<?php echo e($clrs[$i % 4]); ?>22,<?php echo e($clrs[($i+1) % 4]); ?>22)\'>&#128218;</div>'">
                            <?php else: ?>
                                <div class="kc-book-card__ph" style="background:linear-gradient(135deg,<?php echo e($clrs[$i % 4]); ?>28,<?php echo e($clrs[($i+1) % 4]); ?>18)">&#128218;</div>
                            <?php endif; ?>
                            <div class="kc-book-card__meta">
                                <div><?php echo e($book->name); ?></div>
                                <div class="a"><?php echo e($book->author); ?></div>
                                <div style="margin-top:4px">
                                    <?php if($book->discountPrice && $book->discountPrice < $book->price): ?>
                                        <?php echo e(number_format($book->discountPrice)); ?> UZS
                                    <?php else: ?>
                                        <?php echo e(number_format($book->price)); ?> UZS
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <?php $__currentLoopData = ['Dune','1984','Sapiens','Alxemik']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="kc-book-card">
                        <div class="kc-book-card__ph" style="background:linear-gradient(135deg,<?php echo e($clrs[$i % 4]); ?>28,<?php echo e($clrs[($i+1) % 4]); ?>18)">&#128218;</div>
                        <div class="kc-book-card__meta"><div><?php echo e($t); ?></div><div class="a"><?php echo e(__('landing.ticker.placeholder_author')); ?></div><div style="margin-top:4px">45 000 UZS</div></div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = ['Dune','1984','Sapiens','Alxemik']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="kc-book-card">
                        <div class="kc-book-card__ph" style="background:linear-gradient(135deg,<?php echo e($clrs[$i % 4]); ?>28,<?php echo e($clrs[($i+1) % 4]); ?>18)">&#128218;</div>
                        <div class="kc-book-card__meta"><div><?php echo e($t); ?></div><div class="a"><?php echo e(__('landing.ticker.placeholder_author')); ?></div><div style="margin-top:4px">45 000 UZS</div></div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>

    <section class="section cc-reviews">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-reviews">
                    <h2 class="section-heading"><?php echo e(__('landing.reviews.heading_line1')); ?><br><?php echo e(__('landing.reviews.heading_line2')); ?></h2>
                </div>
                <div class="features-grid cc-reviews">
                    <?php if($landingUgcReviews->isNotEmpty()): ?>
                        <?php $__currentLoopData = $landingUgcReviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ugcPost): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $stars   = (int) round($ugcPost->ai_post_score ?? 5);
                                $stars   = max(1, min(5, $stars));
                                $excerpt = mb_strtolower(mb_substr(trim($ugcPost->text), 0, 1))
                                           . mb_substr(trim($ugcPost->text), 1);
                                $excerpt = mb_strlen($ugcPost->text) > 160
                                           ? mb_substr($excerpt, 0, 157) . '…'
                                           : $excerpt;
                                $handle  = trim(($ugcPost->user->name ?? '') . ' ' . ($ugcPost->user->lastname ?? ''))
                                           ?: __('landing.reviews.user_default');
                            ?>
                            <div class="review-card">
                                <div class="stars" aria-hidden="true">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <img src="<?php echo e(asset('vendor/popcorn/images/review-star.webp')); ?>"
                                             width="14" height="14" alt=""
                                             class="star-icon<?php echo e($s > $stars ? ' kc-star-dim' : ''); ?>"
                                             loading="lazy">
                                    <?php endfor; ?>
                                </div>
                                <h3 class="review-title">&ldquo;<?php echo e($excerpt); ?>&rdquo;</h3>
                                <div class="u-mt-auto"><?php echo e($handle); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <?php $__currentLoopData = __('landing.reviews.fallback'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="review-card">
                                <div class="stars" aria-hidden="true"><?php $__currentLoopData = range(1,5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><img src="<?php echo e(asset('vendor/popcorn/images/review-star.webp')); ?>" width="14" height="14" alt="" class="star-icon" loading="lazy"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
                                <h3 class="review-title">&ldquo;<?php echo e($fb['quote']); ?>&rdquo;</h3>
                                <div class="u-mt-auto"><?php echo e($fb['author']); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section cc-home-faq" id="faq">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-faq">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div><?php echo e(__('landing.faq.eyebrow')); ?></div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h2 class="section-heading"><?php echo e(__('landing.faq.heading_line1')); ?><br><?php echo e(__('landing.faq.heading_line2')); ?></h2>
                </div>
                <div class="faq-wrap kc-faq">
                    <?php $__currentLoopData = __('landing.faq.items'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <details class="faq-item kc-faq-details">
                            <summary class="faq-header">
                                <div><?php echo e($item['q']); ?></div>
                                <div class="faq-icon w-embed">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0003 0.833984C15.0629 0.833984 19.167 4.93804 19.167 10.0007C19.167 15.0632 15.0629 19.1673 10.0003 19.1673C4.93774 19.1673 0.83366 15.0632 0.83366 10.0006C0.83366 4.93804 4.93774 0.833984 10.0003 0.833984ZM13.3337 7.57214L14.5122 8.75065L10.0003 13.2625L5.48849 8.75065L6.66699 7.57214L10.0003 10.9055L13.3337 7.57214Z" fill="currentColor"></path>
                                    </svg>
                                </div>
                            </summary>
                            <div class="faq-answer">
                                <div class="faq-answer-inner w-richtext"><p><?php echo e($item['a']); ?></p></div>
                            </div>
                        </details>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section cc-cta-banner" id="business">
        <div class="page-padding">
            <div class="container">
                <div class="cta-banner">
                    <div class="cta-banner-left">
                        <div class="cta-banner-left-content">
                            <h3 class="heading-l"><?php echo e(__('landing.business.heading_steps')); ?></h3>
                            <ul role="list" class="steps-list w-list-unstyled">
                                <li class="steps-list-item"><div class="steps-list-step">1</div><div><?php echo e(__('landing.business.step1_label')); ?></div></li>
                                <li class="steps-list-item"><div class="steps-list-step">2</div><div><?php echo e(__('landing.business.step2_label')); ?></div></li>
                                <li class="steps-list-item"><div class="steps-list-step">3</div><div><?php echo e(__('landing.business.step3_label')); ?></div></li>
                            </ul>
                            <div class="timed-tag u-rainbow">
                                <div><?php echo e(__('landing.business.timed_tag')); ?></div>
                                <div class="svg-embed w-embed">
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M7.24634 0.730242C3.42974 0.778366 0.374344 3.91179 0.422468 7.7284C0.470591 11.545 3.60402 14.6004 7.42063 14.5523C11.2372 14.5042 14.2926 11.3707 14.2445 7.55412C14.1855 3.74226 11.0585 0.693119 7.24634 0.730242ZM11.453 5.11651L6.33504 10.3651C6.22416 10.4788 6.07743 10.5371 5.93 10.539C5.78256 10.5409 5.63442 10.4863 5.5207 10.3754L3.18798 8.10077C2.95995 7.87842 2.95536 7.51444 3.17771 7.28642C3.40005 7.0584 3.76403 7.05381 3.99206 7.27615L5.91247 9.14874L10.6283 4.31243C10.8507 4.0844 11.2147 4.07982 11.4427 4.30216C11.6707 4.5245 11.6753 4.88848 11.453 5.11651Z" fill="#212121"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cta-banner-right">
                        <div class="div-block-3">
                            <h3 class="heading-l"><?php echo e(__('landing.business.title_line1')); ?><br><?php echo e(__('landing.business.title_line2')); ?></h3>
                            <div class="cta-banner-price"><?php echo e(__('landing.business.price')); ?></div>
                            <div class="u-weight-600"><?php echo e(__('landing.business.join')); ?></div>
                        </div>
                        <div class="btn-wrapper cc-cta">
                            <a href="https://play.google.com/store/apps/details?id=com.kitobchi.business.kitobchibusiness" target="_blank" rel="noopener noreferrer" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.business.kitobchibusiness" data-appstore="https://apps.apple.com/uz/app/kitobchi-business/id6753881071" aria-label="<?php echo e(__('landing.business.apply_aria')); ?>">
                                <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                                <div class="cta-inner cc-dark">
                                    <div><strong><?php echo e(__('landing.business.apply')); ?></strong></div>
                                </div>
                            </a>
                        </div>
                        <?php if($shotBiz): ?>
                            <img src="<?php echo e($shotBiz); ?>" loading="lazy" width="360" height="732" alt="" class="cta-banner-mockup" sizes="(max-width: 479px) 100vw, 360px">
                            <img src="<?php echo e($shotBiz); ?>" loading="lazy" width="380" height="215" alt="" class="cta-banner-img-mobile" sizes="(max-width: 479px) 100vw, 380px">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.landing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>