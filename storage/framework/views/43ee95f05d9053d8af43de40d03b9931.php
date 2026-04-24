<?php if($paginator->hasPages()): ?>
<div class="a122-pagination">
  <div class="a122-pagination__meta">
    <span style="color:var(--p-text);font-weight:500"><?php echo e(number_format($paginator->firstItem())); ?></span>
    –
    <span style="color:var(--p-text);font-weight:500"><?php echo e(number_format($paginator->lastItem())); ?></span>
    /
    <?php echo e(number_format($paginator->total())); ?> ta natija
  </div>

  <div class="a122-pagination__pages">
    <?php if($paginator->onFirstPage()): ?>
      <span class="a122-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
    <?php else: ?>
      <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="a122-page-btn">
        <i class="bi bi-chevron-left"></i>
      </a>
    <?php endif; ?>

    <?php
      $current = $paginator->currentPage();
      $last = $paginator->lastPage();
      $from = max(1, $current - 2);
      $to = min($last, $current + 2);
    ?>

    <?php if($from > 1): ?>
      <a href="<?php echo e($paginator->url(1)); ?>" class="a122-page-btn">1</a>
      <?php if($from > 2): ?>
        <span class="a122-page-btn disabled" style="cursor:default">…</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php $__currentLoopData = range($from, $to); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if($page === $current): ?>
        <span class="a122-page-btn active"><?php echo e($page); ?></span>
      <?php else: ?>
        <a href="<?php echo e($paginator->url($page)); ?>" class="a122-page-btn"><?php echo e($page); ?></a>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($to < $last): ?>
      <?php if($to < $last - 1): ?>
        <span class="a122-page-btn disabled" style="cursor:default">…</span>
      <?php endif; ?>
      <a href="<?php echo e($paginator->url($last)); ?>" class="a122-page-btn"><?php echo e($last); ?></a>
    <?php endif; ?>

    <?php if($paginator->hasMorePages()): ?>
      <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="a122-page-btn">
        <i class="bi bi-chevron-right"></i>
      </a>
    <?php else: ?>
      <span class="a122-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/pagination.blade.php ENDPATH**/ ?>