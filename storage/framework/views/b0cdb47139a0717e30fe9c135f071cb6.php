<?php $__env->startSection('title', 'Читай-город uslubidagi Kitobchi onlayn kitoblar do\'koni'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">

    <!-- Chitai-Gorod Shelf Box 1: Ommabop Kitoblar (Bestsellers Shelf) -->
    <div class="cg-shelf-box">
        <div class="cg-shelf-header">
            <h2 class="cg-shelf-title">🔥 Ommabop kitoblar</h2>
            <a href="<?php echo e(route('web.catalog')); ?>" class="cg-shelf-link">Barchasini ko'rish &rarr;</a>
        </div>

        <?php if(isset($featuredBooks) && $featuredBooks->isNotEmpty()): ?>
            <div class="cg-product-grid">
                <?php $__currentLoopData = $featuredBooks->take(15); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $slug = \Illuminate\Support\Str::slug($book->name);
                        $url = route('web.books.show', ['id' => $book->id, 'slug' => $slug]);
                        $img = $book->first_image ? asset('storage/' . $book->first_image) : asset('images/logo/logo_blue.png');
                        $isDiscounted = $book->discountPrice > 0 && $book->discountPrice < $book->price;
                        $price = $isDiscounted ? $book->discountPrice : $book->price;
                    ?>
                    <div class="cg-product-card">
                        <a href="<?php echo e($url); ?>" class="text-decoration-none color-inherit">
                            <div class="cg-card-image-wrap">
                                <img src="<?php echo e($img); ?>" alt="<?php echo e($book->name); ?>" loading="lazy">
                                <?php if($isDiscounted): ?>
                                    <span class="cg-badge-sale">-<?php echo e(round((($book->price - $price) / $book->price) * 100)); ?>%</span>
                                <?php endif; ?>
                            </div>

                            <div class="cg-card-price-row">
                                <span class="cg-card-price"><?php echo e(number_format($price)); ?> so'm</span>
                                <?php if($isDiscounted): ?>
                                    <span class="cg-card-old-price"><?php echo e(number_format($book->price)); ?> so'm</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="cg-card-title"><?php echo e($book->name); ?></h3>
                            <div class="cg-card-author"><?php echo e($book->author ?: 'Kitobchi'); ?></div>
                        </a>

                        <button type="button" class="cg-card-btn" onclick="addToCart(<?php echo e($book->id); ?>, '<?php echo e(addslashes($book->name)); ?>', <?php echo e($price); ?>, '<?php echo e($img); ?>')">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span>Savatga</span>
                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                Katalog tayyorlanmoqda...
            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.marketplace', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/welcome.blade.php ENDPATH**/ ?>