<?php $__env->startSection('title', 'Yangi Reel'); ?>
<?php $__env->startSection('page-title', 'Yangi Reel'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="<?php echo e(route('panel.reels.index')); ?>" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="page-title">Yangi Reel yaratish</h1>
</div>

<div class="row justify-content-center">
  <div class="col-xl-8">
    <form method="POST" action="<?php echo e(route('panel.reels.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php echo $__env->make('panel.reels._form', ['reel' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/reels/create.blade.php ENDPATH**/ ?>