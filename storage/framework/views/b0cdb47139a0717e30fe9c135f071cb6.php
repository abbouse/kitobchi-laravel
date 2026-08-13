<?php $__env->startSection('title', 'Kitobchi — Online kitob va kanselyariya marketpleysi'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">

    <!-- PiyolaMarket Circular Categories Section ("Kataloglar") -->
    <div class="piyola-cat-section">
        <h2 class="piyola-cat-title">Kataloglar</h2>
        <div class="piyola-cat-row">
            <a href="<?php echo e(route('web.catalog')); ?>" class="piyola-cat-item">
                <div class="piyola-cat-avatar">
                    <img src="<?php echo e(asset('images/logo/logo_blue.png')); ?>" alt="Barchasi">
                </div>
                <div class="piyola-cat-name">Barchasi</div>
            </a>

            <?php
                try {
                    $bookCategories = Cache::remember('web_top_categories_piyola_merged', 600, function() {
                        return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(12)->get();
                    });
                } catch (\Throwable $e) {
                    $bookCategories = collect();
                }
            ?>

            <?php $__currentLoopData = $bookCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('web.catalog', ['category' => $cat->id])); ?>" class="piyola-cat-item">
                    <div class="piyola-cat-avatar">
                        <?php if($cat->image): ?>
                            <img src="<?php echo e(asset('storage/' . $cat->image)); ?>" alt="<?php echo e($cat->name); ?>">
                        <?php else: ?>
                            <div class="fw-black text-primary fs-4"><?php echo e(mb_substr($cat->name, 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="piyola-cat-name"><?php echo e($cat->name); ?></div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- Section 1: Xaridorgir Kitoblar (Bestsellers Grid with UGC Ratings) -->
    <div class="u-mt-l u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <div>
                <h2 class="h3 fw-black text-primary mb-0">🔥 Xaridorgir mahsulotlar</h2>
                <small class="text-muted">Eng ko'p xarid qilingan original adabiyotlar</small>
            </div>
            <a href="<?php echo e(route('web.catalog')); ?>" class="text-primary text-decoration-none fw-bold small">
                Barchasi &rarr;
            </a>
        </div>

        <?php if(isset($featuredBooks) && $featuredBooks->isNotEmpty()): ?>
            <div class="piyola-product-grid">
                <?php $__currentLoopData = $featuredBooks->take(15); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                        $rating = $book->ugc_aggregate_score > 0 ? number_format($book->ugc_aggregate_score, 1) : '5.0';
                    ?>
                    <div class="piyola-card">
                        <a href="<?php echo e($url); ?>" class="text-decoration-none color-inherit">
                            <div class="piyola-card-cover">
                                <img src="<?php echo e($img); ?>" alt="<?php echo e($book->name); ?>" loading="lazy">
                                <?php if($isDiscounted): ?>
                                    <span class="piyola-discount-tag">-<?php echo e(round((($book->price - $price) / $book->price) * 100)); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="text-warning small fw-bold">⭐ <?php echo e($rating); ?></span>
                                <?php if($book->ugc_reviews_count): ?>
                                    <span class="text-muted" style="font-size: 11px;">(<?php echo e($book->ugc_reviews_count); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="piyola-card-title"><?php echo e($book->name); ?></h3>
                            <div class="piyola-card-author"><?php echo e($book->author ?: 'Kitobchi'); ?></div>
                        </a>
                        <div class="piyola-card-footer">
                            <div>
                                <div class="piyola-card-price"><?php echo e(number_format($price)); ?> so'm</div>
                                <?php if($isDiscounted): ?>
                                    <div class="piyola-card-old-price"><?php echo e(number_format($book->price)); ?> so'm</div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="piyola-add-cart-btn" onclick="addToCart(<?php echo e($book->id); ?>, '<?php echo e(addslashes($book->name)); ?>', <?php echo e($price); ?>, '<?php echo e($img); ?>')" title="Savatchaga qo'shish">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="p-5 bg-white rounded-4 border text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>