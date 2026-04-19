<?php $__env->startSection('title', 'Push Bildirishnomalar'); ?>
<?php $__env->startSection('page-title', 'Push Bildirishnomalar'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Push bildirishnomalar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Barcha yuborilgan push xabarlar tarixi <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.fcm-notifications.create')); ?>" class="btn-p primary">
        <i class="bi bi-send"></i> Yangi yuborish
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


<div class="tab-pills fade-up mb-3">
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>'all','page'=>1])); ?>"
     class="tab-pill <?php echo e($tab==='all'?'active':''); ?>">
    Barchasi <span class="tab-badge"><?php echo e($counts['all']); ?></span>
  </a>
  <?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$key,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$key?'active':''); ?>">
    <i class="bi <?php echo e($t['icon']); ?>"></i> <?php echo e(Str::before($t['label'], ' (')); ?>

    <span class="tab-badge"><?php echo e($counts[$key]); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="filter-bar mb-3 fade-up">
  <form method="GET" class="flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control"
           placeholder="Sarlavha yoki matn bo'yicha..."
           value="<?php echo e(request('search')); ?>" style="width:260px">
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i></button>
    <a href="<?php echo e(route('panel.fcm-notifications.index', ['tab'=>$tab])); ?>"
       class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>


<div class="p-card p-0 fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Sarlavha</th>
          <th>Matn</th>
          <th>Qabul qiluvchi</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $t = $targets[$notif->who] ?? ['label'=>$notif->who,'color'=>'muted','icon'=>'bi-bell'];
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">
            #<?php echo e($notif->id); ?>

          </td>
          <td>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($notif->name); ?>

            </div>
          </td>
          <td style="max-width:280px">
            <div style="font-size:12px;color:var(--p-muted);
                        overflow:hidden;display:-webkit-box;
                        -webkit-line-clamp:2;-webkit-box-orient:vertical">
              <?php echo e($notif->description); ?>

            </div>
          </td>
          <td>
            <span class="s-pill <?php echo e($t['color']); ?>"
                  style="display:inline-flex;align-items:center;gap:5px;font-size:12px">
              <i class="bi <?php echo e($t['icon']); ?>"></i>
              <?php echo e($t['label']); ?>

            </span>
          </td>
          <td style="font-size:12px;color:var(--p-hint);white-space:nowrap;font-family:'JetBrains Mono',monospace">
            <?php echo e($notif->created_at?->format('d.m.Y H:i')); ?>

          </td>
          <td>
            <form method="POST" action="<?php echo e(route('panel.fcm-notifications.destroy', $notif)); ?>"
                  onsubmit="return confirm('O\'chirilsinmi?')">
              <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
              <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:50px;color:var(--p-hint)">
            <i class="bi bi-bell-slash" style="font-size:36px;display:block;margin-bottom:12px"></i>
            Bildirishnomalar yo'q
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($notifications->hasPages()): ?>
  <div class="p-pagination"><?php echo e($notifications->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/fcm-notifications/index.blade.php ENDPATH**/ ?>