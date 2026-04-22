<?php $__env->startSection('title', __('errors.400_title').' — '.__('errors.meta_title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="kc-error-card">
        <div class="kc-error-pill-wrap">
            <div class="eyebrow-pill">
                <div class="eyebrow-pill-inner"><div>400</div></div>
                <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
            </div>
        </div>
        <h1 class="section-heading kc-error-heading"><?php echo e(__('errors.400_title')); ?></h1>
        <p class="subheading kc-error-lead"><?php echo e(__('errors.400_lead')); ?></p>
        <div class="kc-error-actions">
            <a href="<?php echo e(url('/')); ?>" class="cta w-inline-block">
                <div class="cta-bg u-rainbow u-blur-perf"></div>
                <div class="cta-inner">
                    <div><strong><?php echo e(__('errors.cta_home')); ?></strong></div>
                </div>
            </a>
            <button type="button" class="kc-error-secondary" onclick="history.length > 1 ? history.back() : (location.href='<?php echo e(url('/')); ?>')">
                <?php echo e(__('errors.cta_back')); ?>

            </button>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.error-public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/errors/400.blade.php ENDPATH**/ ?>