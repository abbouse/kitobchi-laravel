<?php if($paginator->hasPages()): ?>
  <?php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $from = max(1, $current - 2);
    $to = min($last, $current + 2);
  ?>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="small text-secondary">
      <span class="fw-semibold text-dark"><?php echo e(number_format($paginator->firstItem())); ?></span>
      –
      <span class="fw-semibold text-dark"><?php echo e(number_format($paginator->lastItem())); ?></span>
      / <?php echo e(number_format($paginator->total())); ?> ta natija
    </div>

    <nav aria-label="Pagination">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?php echo e($paginator->onFirstPage() ? 'disabled' : ''); ?>">
          <a class="page-link rounded-pill px-3" href="<?php echo e($paginator->onFirstPage() ? '#' : $paginator->previousPageUrl()); ?>" tabindex="<?php echo e($paginator->onFirstPage() ? '-1' : '0'); ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>

        <?php if($from > 1): ?>
          <li class="page-item">
            <a class="page-link rounded-pill px-3" href="<?php echo e($paginator->url(1)); ?>">1</a>
          </li>
          <?php if($from > 2): ?>
            <li class="page-item disabled"><span class="page-link rounded-pill px-3">…</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php $__currentLoopData = range($from, $to); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li class="page-item <?php echo e($page === $current ? 'active' : ''); ?>">
            <?php if($page === $current): ?>
              <span class="page-link rounded-pill px-3"><?php echo e($page); ?></span>
            <?php else: ?>
              <a class="page-link rounded-pill px-3" href="<?php echo e($paginator->url($page)); ?>"><?php echo e($page); ?></a>
            <?php endif; ?>
          </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($to < $last): ?>
          <?php if($to < $last - 1): ?>
            <li class="page-item disabled"><span class="page-link rounded-pill px-3">…</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link rounded-pill px-3" href="<?php echo e($paginator->url($last)); ?>"><?php echo e($last); ?></a>
          </li>
        <?php endif; ?>

        <li class="page-item <?php echo e($paginator->hasMorePages() ? '' : 'disabled'); ?>">
          <a class="page-link rounded-pill px-3" href="<?php echo e($paginator->hasMorePages() ? $paginator->nextPageUrl() : '#'); ?>" tabindex="<?php echo e($paginator->hasMorePages() ? '0' : '-1'); ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
<?php endif; ?>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/partials/pagination.blade.php ENDPATH**/ ?>