<?php $__env->startSection('title', 'Kitobchi — Original kitoblar va atirlar online do\'koni'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">

    <!-- PiyolaMarket Circular Categories Section ("Kataloglar") -->
    <div class="kc-pm-cat-section">
        <h2 class="h3 fw-black text-primary u-mb-m">Kataloglar</h2>
        <div class="kc-pm-cat-row">
            <a href="<?php echo e(route('web.catalog')); ?>" class="kc-pm-cat-card">
                <div class="kc-pm-cat-avatar">
                    <img src="<?php echo e(asset('images/logo/logo_blue.png')); ?>" alt="Barchasi">
                </div>
                <div class="kc-pm-cat-name">Barchasi</div>
            </a>

            <?php
                try {
                    $bookCategories = Cache::remember('web_top_categories_avatars', 600, function() {
                        return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(10)->get();
                    });
                } catch (\Throwable $e) {
                    $bookCategories = collect();
                }
            ?>

            <?php $__currentLoopData = $bookCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('web.catalog', ['category' => $cat->id])); ?>" class="kc-pm-cat-card">
                    <div class="kc-pm-cat-avatar">
                        <?php if($cat->image): ?>
                            <img src="<?php echo e(asset('storage/' . $cat->image)); ?>" alt="<?php echo e($cat->name); ?>">
                        <?php else: ?>
                            <div class="fw-black text-primary fs-4"><?php echo e(mb_substr($cat->name, 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="kc-pm-cat-name"><?php echo e($cat->name); ?></div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- Section 1: Xaridorgir Mahsulotlar (Bestsellers Grid) -->
    <div class="u-mt-l u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <h2 class="h3 fw-black text-primary mb-0">Xaridorgir mahsulotlar</h2>
            <a href="<?php echo e(route('web.catalog')); ?>" class="text-primary text-decoration-none fw-bold small">
                Barchasi &rarr;
            </a>
        </div>

        <?php if(isset($featuredBooks) && $featuredBooks->isNotEmpty()): ?>
            <div class="kc-pm-grid">
                <?php $__currentLoopData = $featuredBooks->take(15); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    ?>
                    <div class="kc-pm-product-card">
                        <a href="<?php echo e($url); ?>" class="text-decoration-none color-inherit">
                            <div class="kc-pm-cover-wrap">
                                <img src="<?php echo e($img); ?>" alt="<?php echo e($book->name); ?>" class="kc-pm-cover-img" loading="lazy">
                                <?php if($isDiscounted): ?>
                                    <span class="kc-pm-discount-pill">-<?php echo e(round((($book->price - $price) / $book->price) * 100)); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="kc-pm-title"><?php echo e($book->name); ?></h3>
                            <div class="kc-pm-author"><?php echo e($book->author ?: 'Kitobchi'); ?></div>
                        </a>
                        <div class="kc-pm-card-bottom">
                            <div>
                                <div class="kc-pm-price"><?php echo e(number_format($price)); ?> so'm</div>
                                <?php if($isDiscounted): ?>
                                    <div class="kc-pm-old-price"><?php echo e(number_format($book->price)); ?> so'm</div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="kc-pm-add-btn" onclick="addToCart(<?php echo e($book->id); ?>, '<?php echo e(addslashes($book->name)); ?>', <?php echo e($price); ?>, '<?php echo e($img); ?>')" title="Savatchaga qo'shish">
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