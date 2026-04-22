<?php $__env->startSection('title', 'Push Bildirishnomalar'); ?>
<?php $__env->startSection('page-title', 'Push Bildirishnomalar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Push bildirishnomalar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Barcha yuborilgan push xabarlar tarixi <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('admin.push.create')); ?>" class="btn-p primary">
        <i class="bi bi-send"></i> Yangi yuborish
      </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  <?php $__currentLoopData = [
    [$counts['all'] ?? 0, 'Jami yuborish', 'accent', 'bi-bell'],
    [$counts['users'] ?? 0, 'Userlar', 'info', 'bi-people'],
    [$counts['business'] ?? 0, 'Sellerlar', 'warning', 'bi-shop-window'],
    [$counts['courier'] ?? 0, 'Kuryerlar', 'success', 'bi-bicycle'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-<?php echo e($tone); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($tone); ?>)">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)"><?php echo e($value); ?></div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)"><?php echo e($label); ?></div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Yuborilgan pushlar</div>
    <div class="a122-index-header__meta"><?php echo e($notifications->total()); ?> ta bildirishnoma tarixi ko'rinmoqda</div>
  </div>
</div>


<div class="p-card p-0 fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
            <form method="POST" action="<?php echo e(route('admin.push.destroy', $notif)); ?>"
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
  <?php echo e($notifications->links('a122.partials.pagination')); ?>

  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/push/index.blade.php ENDPATH**/ ?>