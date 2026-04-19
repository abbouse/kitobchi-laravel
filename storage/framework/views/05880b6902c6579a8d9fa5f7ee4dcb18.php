<?php $__env->startSection('title', 'Reklama narxlari'); ?>
<?php $__env->startSection('page-title', 'Reklama narxlari'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.seller-ads.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.seller-ads.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Reklama narxlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Har bir reklama turi uchun kunlik narx <?php $__env->endSlot(); ?>
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


    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Reklama turlari narxlari</div></div>
      <div class="dash-card-body">
        <form method="POST" action="<?php echo e(route('panel.seller-ads.settings.update')); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

          <?php $__currentLoopData = $settings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="background:var(--p-elevated);border-radius:10px;padding:16px;margin-bottom:12px">
            <div style="font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;margin-bottom:12px;letter-spacing:.07em">
              <i class="bi bi-megaphone mr-1"></i> <?php echo e($setting->type); ?>

            </div>
            <input type="hidden" name="settings[<?php echo e($i); ?>][type]" value="<?php echo e($setting->type); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="">
                <label class="p-form-label">Kunlik narx (UZS)</label>
                <input type="number" name="settings[<?php echo e($i); ?>][price]" class="p-form-control"
                       value="<?php echo e(old("settings.$i.price", $setting->price)); ?>" min="0" required>
              </div>
              <div class="">
                <label class="p-form-label">Viloyat (ixtiyoriy)</label>
                <input type="text" name="settings[<?php echo e($i); ?>][region]" class="p-form-control"
                       value="<?php echo e(old("settings.$i.region", $setting->region)); ?>"
                       placeholder="Toshkent">
              </div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

          <?php if($settings->isEmpty()): ?>
          <div style="text-align:center;padding:30px;color:var(--p-hint)">
            <i class="bi bi-megaphone" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Reklama turlari sozlamasi yo'q
          </div>
          <?php endif; ?>

          <?php if($settings->isNotEmpty()): ?>
          <div class="flex justify-end mt-2">
            <button class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/seller-ads/show.blade.php ENDPATH**/ ?>