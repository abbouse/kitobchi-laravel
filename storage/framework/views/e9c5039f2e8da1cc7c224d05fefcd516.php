<?php $__env->startSection('title', 'Yangiliklar'); ?>
<?php $__env->startSection('page-title', 'Market Yangiliklari'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Market Yangiliklari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Marketpleysda ko'rinadigan bannerlar <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.market-news.create')); ?>" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi yangilik
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



<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <?php $__currentLoopData = [
    ['Jami',    $counts['all'],    'accent',  'bi-newspaper'],
    ['Faol',    $counts['active'], 'success', 'bi-check-circle'],
    ['Yangilik',$counts['news'],   'info',    'bi-chat-text'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-4">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
          <?php echo e($v); ?>

        </div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          <?php echo e($l); ?>

        </div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Rasm</th>
          <th>Sarlavha</th>
          <th>Joylashuv</th>
          <th>Action</th>
          <th>Manzil</th>
          <th>Status</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $news; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $actionColor = match($item->action){
            'to_shop'    => 'warning',
            'to_product' => 'info',
            default      => 'muted',
          };
          $actionIcon = match($item->action){
            'to_shop'    => 'bi-shop-window',
            'to_product' => 'bi-book',
            default      => 'bi-newspaper',
          };
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($item->id); ?>

          </td>

          <td>
            <?php if($item->imgUrl): ?>
            <div style="width:52px;height:36px;border-radius:6px;overflow:hidden;
                        background:var(--p-elevated)">
              <img src="<?php echo e(asset('storage/'.$item->imgUrl)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            </div>
            <?php else: ?>
            <div style="width:52px;height:36px;border-radius:6px;background:var(--p-elevated);
                        display:flex;align-items:center;justify-content:center">
              <i class="bi bi-image" style="color:var(--p-hint);font-size:14px"></i>
            </div>
            <?php endif; ?>
          </td>

          <td>
            <div style="font-size:13px;font-weight:500;color:var(--p-text);
                        max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?php echo e($item->title); ?>

            </div>
          </td>

          <td>
            <span class="s-pill muted" style="font-size:10px">
              <?php echo e($item->align === 'top' ? '⬆ Yuqori' : '↔ O\'rta'); ?>

            </span>
          </td>

          <td>
            <span class="s-pill <?php echo e($actionColor); ?>" style="font-size:10px">
              <i class="bi <?php echo e($actionIcon); ?> mr-1"></i>
              <?php echo e($item->action_label); ?>

            </span>
          </td>

          <td>
            <?php if($item->action === 'to_shop' && $item->seller): ?>
              <a href="<?php echo e(route('panel.sellers.show', $item->action_id)); ?>"
                 style="font-size:12px;color:var(--p-warning);text-decoration:none">
                <?php echo e($item->seller->shop_name); ?>

              </a>
            <?php elseif($item->action === 'to_product' && $item->book): ?>
              <a href="<?php echo e(route('panel.books.show', $item->action_id)); ?>"
                 style="font-size:12px;color:var(--p-info);text-decoration:none">
                <?php echo e(Str::limit($item->book->name, 24)); ?>

              </a>
            <?php elseif($item->action_id): ?>
              <span style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                #<?php echo e($item->action_id); ?>

              </span>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td>
            <form method="POST"
                  action="<?php echo e(route('panel.market-news.toggle', $item)); ?>"
                  style="display:inline">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <button class="s-pill <?php echo e($item->status ? 'success' : 'danger'); ?>"
                      style="font-size:10px;border:none;cursor:pointer;padding:3px 10px">
                <?php echo e($item->status ? '● Faol' : '○ Nofaol'); ?>

              </button>
            </form>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($item->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="flex gap-1">
              <a href="<?php echo e(route('panel.market-news.show', $item)); ?>"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?php echo e(route('panel.market-news.edit', $item)); ?>"
                 class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST"
                    action="<?php echo e(route('panel.market-news.destroy', $item)); ?>"
                    onsubmit="return confirm('O\'chirilsinmi?')">
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
          <td colspan="9" class="p-empty-cell">
            <i class="bi bi-newspaper" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Yangiliklar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($news->hasPages()): ?>
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($news->firstItem()); ?>–<?php echo e($news->lastItem()); ?> / <?php echo e($news->total()); ?>

    </div>
    <?php echo e($news->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/market-news/index.blade.php ENDPATH**/ ?>