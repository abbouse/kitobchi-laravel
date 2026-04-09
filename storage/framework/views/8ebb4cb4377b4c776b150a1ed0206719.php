<?php $__env->startSection('title', 'Reels'); ?>
<?php $__env->startSection('page-title', 'Reels'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Reels</h1>
    <p class="page-sub">Video kolleksiyalar boshqaruvi</p>
  </div>
  <a href="<?php echo e(route('panel.reels.create')); ?>" class="btn-p primary">
    <i class="bi bi-plus-lg"></i> Yangi Reel
  </a>
</div>

<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>Tartib</th>
          <th>Sarlavha</th>
          <th>Tavsif</th>
          <th>Videolar</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $reels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>
            <span style="font-family:'DM Mono',monospace;font-size:13px;
                         color:var(--p-accent);font-weight:600">
              <?php echo e($reel->order); ?>

            </span>
          </td>

          <td>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              <?php echo e($reel->title); ?>

            </div>
          </td>

          <td style="max-width:200px">
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis">
              <?php echo e($reel->description ?: '—'); ?>

            </div>
          </td>

          <td>
            <span class="s-pill <?php echo e($reel->items_count > 0 ? 'accent' : 'muted'); ?>"
                  style="font-size:11px">
              <i class="bi bi-play-circle me-1"></i>
              <?php echo e($reel->items_count); ?> ta video
            </span>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'DM Mono',monospace">
            <?php echo e($reel->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="d-flex gap-1">
              <a href="<?php echo e(route('panel.reels.show', $reel)); ?>"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?php echo e(route('panel.reels.edit', $reel)); ?>"
                 class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST"
                    action="<?php echo e(route('panel.reels.destroy', $reel)); ?>"
                    onsubmit="return confirm('Reel va barcha videolari o\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:48px;color:var(--p-hint)">
            <i class="bi bi-collection-play"
               style="font-size:32px;display:block;margin-bottom:10px"></i>
            Reels topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($reels->hasPages()): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($reels->firstItem()); ?>–<?php echo e($reels->lastItem()); ?> / <?php echo e($reels->total()); ?>

    </div>
    <?php echo e($reels->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/reels/index.blade.php ENDPATH**/ ?>