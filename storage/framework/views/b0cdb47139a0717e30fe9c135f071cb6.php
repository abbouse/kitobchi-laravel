<?php $__env->startSection('title', 'Kitobchi — Online Kitoblar va Kanselyariya Marketpleysi'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">

    <!-- Top Compact Category Banner Strip -->
    <div class="p-4 bg-white rounded-3 border u-mb-l d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="h4 fw-extrabold text-dark mb-1">Kitobchi Onlayn Marketpleysi</h1>
            <p class="text-muted small mb-0">Original kitoblar va o'quv qurollarini vebda to'g'ridan-to'g'ri xarid qiling</p>
        </div>
        <a href="<?php echo e(route('web.catalog')); ?>" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold">
            Barcha katalog &rarr;
        </a>
    </div>

    <!-- Section 1: Ommabop Kitoblar Grid (5 columns) -->
    <div class="u-mb-xl">
        <div class="d-flex justify-content-between align-items-center u-mb-m">
            <h2 class="h5 fw-extrabold text-dark mb-0">🔥 Ommabop kitoblar</h2>
            <a href="<?php echo e(route('web.catalog')); ?>" class="text-primary text-decoration-none small fw-bold">Barchasi &rarr;</a>
        </div>

        <?php if(isset($featuredBooks) && $featuredBooks->isNotEmpty()): ?>
            <div class="kc-products-grid">
                <?php $__currentLoopData = $featuredBooks->take(15); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    ?>
                    <div class="kc-card">
                        <a href="<?php echo e($url); ?>" class="text-decoration-none color-inherit">
                            <div class="kc-card-img-wrap">
                                <img src="<?php echo e($img); ?>" alt="<?php echo e($book->name); ?>" class="kc-card-img" loading="lazy">
                                <?php if($isDiscounted): ?>
                                    <span class="kc-badge-sale">-<?php echo e(round((($book->price - $price) / $book->price) * 100)); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="kc-card-name"><?php echo e($book->name); ?></h3>
                            <div class="kc-card-sub"><?php echo e($book->author ?: 'Kitobchi'); ?></div>
                        </a>
                        <div class="kc-card-footer">
                            <div>
                                <div class="kc-price-val"><?php echo e(number_format($price)); ?> so'm</div>
                                <?php if($isDiscounted): ?>
                                    <div class="kc-price-old"><?php echo e(number_format($book->price)); ?> so'm</div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="kc-btn-cart-add" onclick="addToCart(<?php echo e($book->id); ?>, '<?php echo e(addslashes($book->name)); ?>', <?php echo e($price); ?>, '<?php echo e($img); ?>')" title="Savatga qo'shish">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="p-5 bg-white rounded-3 border text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>