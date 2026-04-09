<?php $__env->startSection('title', 'Tahrirlash: '.$marketNews->title); ?>
<?php $__env->startSection('page-title', 'Yangilik tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="<?php echo e(route('panel.market-news.show', $marketNews)); ?>" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title"><?php echo e($marketNews->title); ?></h1>
    <p class="page-sub">Yangilikni tahrirlash</p>
  </div>
</div>

<form method="POST" action="<?php echo e(route('panel.market-news.update', $marketNews)); ?>"
      enctype="multipart/form-data">
  <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
  <?php echo $__env->make('panel.market-news._form', compact('marketNews'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/market-news/edit.blade.php ENDPATH**/ ?>