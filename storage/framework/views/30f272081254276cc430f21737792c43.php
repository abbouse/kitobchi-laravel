<?php $__env->startSection('title', 'Reels'); ?>
<?php $__env->startSection('page-title', 'Reels'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Reels <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Video kolleksiyalar boshqaruvi <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.reels.create')); ?>" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi Reel
      </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
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
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
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
              <i class="bi bi-play-circle mr-1"></i>
              <?php echo e($reel->items_count); ?> ta video
            </span>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($reel->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="flex gap-1">
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
          <td colspan="6" class="p-empty-cell">
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
  <div class="p-card-footer">
    <div class="p-card-footer-meta">
      <?php echo e($reels->firstItem()); ?>–<?php echo e($reels->lastItem()); ?> / <?php echo e($reels->total()); ?>

    </div>
    <?php echo e($reels->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/reels/index.blade.php ENDPATH**/ ?>