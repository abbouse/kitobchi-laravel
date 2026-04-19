<?php $__env->startSection('title', isset($stationery) ? 'Tahrirlash: '.$stationery->name : 'Yangi mahsulot'); ?>
<?php $__env->startSection('page-title', isset($stationery) ? 'Tahrirlash' : 'Yangi mahsulot'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($stationery) ? $stationery->name : 'Yangi mahsulot'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($stationery) ? 'Mahsulotni tahrirlash · ID: #'.$stationery->id : 'Yangi kantselyariya mahsuloti'); ?> <?php $__env->endSlot(); ?>
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


    <form method="POST"
          action="<?php echo e(isset($stationery) ? route('panel.stationery.update', $stationery) : route('panel.stationery.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(isset($stationery)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Moderatsiya sozlamalari</div>
          <div class="dash-card-sub">Faqat admin tomonidan o'zgartiriladi</div>
        </div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Tasdiqlash holati</label>
              <select name="is_approved" class="p-form-control <?php $__errorArgs = ['is_approved'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <option value="0" <?php echo e(old('is_approved', $stationery->is_approved ?? 0)==0?'selected':''); ?>>Kutilmoqda</option>
                <option value="1" <?php echo e(old('is_approved', $stationery->is_approved ?? 0)==1?'selected':''); ?>>Tasdiqlangan</option>
                <option value="2" <?php echo e(old('is_approved', $stationery->is_approved ?? 0)==2?'selected':''); ?>>Rad etilgan</option>
              </select>
              <?php $__errorArgs = ['is_approved'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="">
              <label class="p-form-label">Ko'rinish</label>
              <select name="is_hidden" class="p-form-control">
                <option value="0" <?php echo e(old('is_hidden', $stationery->is_hidden ?? 0)==0?'selected':''); ?>>Ko'rinadi</option>
                <option value="1" <?php echo e(old('is_hidden', $stationery->is_hidden ?? 0)==1?'selected':''); ?>>Yashirilgan</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Narx va ombor</div>
        </div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Narx (UZS) <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="price" class="p-form-control <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('price', $stationery->price ?? '')); ?>" min="0" required>
              <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="">
              <label class="p-form-label">Chegirma narx (UZS)</label>
              <input type="number" name="discount_price" class="p-form-control <?php $__errorArgs = ['discount_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('discount_price', $stationery->discount_price ?? '')); ?>" min="0">
              <?php $__errorArgs = ['discount_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="">
              <label class="p-form-label">Ombordagi miqdor <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="stock" class="p-form-control <?php $__errorArgs = ['stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('stock', $stationery->stock ?? '')); ?>" min="0" required>
              <?php $__errorArgs = ['stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
          </div>
        </div>
      </div>

      
      <?php if(isset($stationery)): ?>
      <div class="p-card mb-3" style="opacity:.7">
        <div class="dash-card-head">
          <div class="dash-card-title">Sotuvchi ma'lumotlari</div>
          <div class="dash-card-sub"><i class="bi bi-lock"></i> Faqat o'qish</div>
        </div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php $__currentLoopData = [
              ['Nomi',        $stationery->name],
              ['Kategoriya',  $stationery->category?->name_uz],
              ['Material',    $stationery->material ?? '—'],
              ['Sotuvchi',    $stationery->seller?->shop_name ?? $stationery->seller_id],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k, $v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="">
              <label class="p-form-label"><?php echo e($k); ?></label>
              <input type="text" class="p-form-control" value="<?php echo e($v); ?>" disabled>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="flex gap-2 justify-end">
        <a href="<?php echo e(isset($stationery) ? route('panel.stationery.show', $stationery) : route('panel.stationery.index')); ?>"
           class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/stationeries/edit.blade.php ENDPATH**/ ?>