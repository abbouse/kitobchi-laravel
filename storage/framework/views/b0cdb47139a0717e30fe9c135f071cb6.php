<?php $__env->startSection('title', 'Kitobchi — Online Kitoblar va Kanselyariya Marketpleysi'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">

    <!-- Marketplace Hero Banner Section -->
    <div class="p-4 p-md-5 rounded-4 bg-white border u-mb-xl shadow-sm position-relative overflow-hidden">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill u-mb-s">
                    ✨ Rasmiy Kitoblar va Kanselyariya Marketpleysi
                </span>
                <h1 class="display-6 fw-black text-dark u-my-s" style="letter-spacing: -1px; line-height: 1.25;">
                    Sevimli kitoblaringizni vebda darhol buyurtma qiling!
                </h1>
                <p class="text-muted fs-6 u-mb-l" style="max-width: 540px;">
                    10,000+ dan ortiq original badiiy, psixologiya, biznes kitoblari va o'quv qurollari. Butun O'zbekiston bo'ylab 1-3 kunda tezkor yetkazib berish!
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?php echo e(route('web.catalog')); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold">
                        Katalogga o'tish &rarr;
                    </a>
                    <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" class="btn btn-outline-dark btn-lg rounded-pill px-4 fw-semibold">
                        📲 Mobil ilovada ochish
                    </a>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block text-center position-relative">
                <img src="<?php echo e(asset('images/logo/logo_blue.png')); ?>" alt="Kitobchi Hero" class="img-fluid" style="max-height: 220px; filter: drop-shadow(0 15px 30px rgba(79, 70, 229, 0.2));">
            </div>
        </div>
    </div>

    <!-- Trust Stats Strip -->
    <div class="row g-3 text-center u-mb-xl">
        <div class="col-4">
            <div class="p-3 bg-white rounded-3 border">
                <div class="h3 fw-extrabold text-primary mb-0"><?php echo e(number_format($landingSalesCount ?: 25000)); ?>+</div>
                <small class="text-muted">Muvaffaqiyatli buyurtmalar</small>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 bg-white rounded-3 border">
                <div class="h3 fw-extrabold text-success mb-0"><?php echo e(number_format($landingCustomersCount ?: 18000)); ?>+</div>
                <small class="text-muted">Mamnun xaridorlar</small>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 bg-white rounded-3 border">
                <div class="h3 fw-extrabold text-indigo mb-0"><?php echo e(number_format($landingPartnerStoresCount ?: 120)); ?>+</div>
                <small class="text-muted">Hamkor nashriyotlar</small>
            </div>
        </div>
    </div>

    <!-- Bestseller Books Section -->
    <div class="u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <div>
                <h2 class="h4 fw-extrabold text-dark mb-0">🔥 Ommabop kitoblar</h2>
                <small class="text-muted">Eng ko'p xarid qilinayotgan sara adabiyotlar</small>
            </div>
            <a href="<?php echo e(route('web.catalog')); ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                Barchasini ko'rish &rarr;
            </a>
        </div>

        <?php if(isset($featuredBooks) && $featuredBooks->isNotEmpty()): ?>
            <div class="kc-mk-grid">
                <?php $__currentLoopData = $featuredBooks->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    ?>
                    <div class="kc-product-card">
                        <a href="<?php echo e($url); ?>" class="text-decoration-none color-inherit">
                            <div class="kc-card-cover-wrap">
                                <img src="<?php echo e($img); ?>" alt="<?php echo e($book->name); ?>" class="kc-card-cover-img" loading="lazy">
                                <?php if($isDiscounted): ?>
                                    <span class="kc-discount-badge">-<?php echo e(round((($book->price - $price) / $book->price) * 100)); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="kc-card-title"><?php echo e($book->name); ?></h3>
                            <div class="kc-card-author"><?php echo e($book->author ?: 'Kitobchi'); ?></div>
                        </a>
                        <div class="kc-card-bottom">
                            <div>
                                <div class="kc-card-price-main"><?php echo e(number_format($price)); ?> <small>UZS</small></div>
                                <?php if($isDiscounted): ?>
                                    <div class="kc-card-price-old"><?php echo e(number_format($book->price)); ?> UZS</div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="kc-add-cart-btn" onclick="addToCart(<?php echo e($book->id); ?>, '<?php echo e(addslashes($book->name)); ?>', <?php echo e($price); ?>, '<?php echo e($img); ?>')" title="Savatga qo'shish">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded-3 border">
                <p class="text-muted">Katalogda hozircha kitoblar yuklanmoqda...</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Verified Buyer Reviews -->
    <?php if(isset($landingUgcReviews) && $landingUgcReviews->isNotEmpty()): ?>
        <div class="p-4 bg-white rounded-4 border u-mb-xl">
            <div class="u-mb-m">
                <h2 class="h4 fw-extrabold text-dark mb-0">💬 Xaridorlarimiz fikrlari</h2>
                <small class="text-muted">Kitobxonlarning haqiqiy baholari</small>
            </div>
            <div class="row g-3">
                <?php $__currentLoopData = $landingUgcReviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $author = $review->user ? ($review->user->name ?: 'Foydalanuvchi') : 'Kitobxon';
                    ?>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="badge bg-primary rounded-circle p-2" style="width: 32px; height: 32px; display: grid; place-items: center;">
                                    <?php echo e(mb_substr($author, 0, 1)); ?>

                                </div>
                                <div>
                                    <div class="fw-bold text-dark fs-6"><?php echo e($author); ?></div>
                                    <div class="text-warning small">&#11088; <?php echo e(number_format(($review->ai_post_score ?: 90) / 20, 1)); ?></div>
                                </div>
                            </div>
                            <p class="text-muted small mb-0" style="line-height: 1.5;">
                                "<?php echo e(\Illuminate\Support\Str::limit($review->text, 140)); ?>"
                            </p>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>