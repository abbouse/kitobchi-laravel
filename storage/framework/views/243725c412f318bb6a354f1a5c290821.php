<?php $__env->startSection('title', isset($user) ? 'Tahrirlash: '.$user->name : 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('page-title', isset($user) ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Foydalanuvchilar / ' . (isset($user) ? 'Tahrirlash' : 'Yaratish')); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(isset($user) ? route('panel.users.show', $user) : route('panel.users.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(isset($user) ? route('panel.users.show', $user) : route('panel.users.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($user) ? $user->name.' '.$user->lastname : 'Yangi foydalanuvchi'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($user) ? "ID #$user->id" : "Yangi foydalanuvchi yaratish"); ?> <?php $__env->endSlot(); ?>
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
      action="<?php echo e(isset($user) ? route('panel.users.update', $user) : route('panel.users.store')); ?>"
      enctype="multipart/form-data">
  <?php echo csrf_field(); ?>
  <?php if(isset($user)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">

    
    <div class="xl:col-span-8 fade-up d1">
      <div class="p-card">
        <div class="p-card-header">
          <div class="p-card-title">Asosiy ma'lumotlar</div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">
          <div class="">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name" class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('name', $user->name ?? '')); ?>" required placeholder="Foydalanuvchi ismi">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Familiya</label>
            <input type="text" name="lastname" class="p-form-control"
                   value="<?php echo e(old('lastname', $user->lastname ?? '')); ?>" placeholder="Familiya">
          </div>
          <div class="">
            <label class="p-form-label">Telefon raqami <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="phone_number" class="p-form-control <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('phone_number', $user->phone_number ?? '')); ?>" placeholder="+998901234567" required>
            <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Email</label>
            <input type="email" name="email" class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('email', $user->email ?? '')); ?>" placeholder="email@example.com">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Lavozim / Position</label>
            <input type="text" name="position" class="p-form-control"
                   value="<?php echo e(old('position', $user->position ?? '')); ?>" placeholder="O'quvchi, Yozuvchi...">
          </div>
          <div class="">
            <label class="p-form-label">Bio</label>
            <input type="text" name="bio" class="p-form-control"
                   value="<?php echo e(old('bio', $user->bio ?? '')); ?>" placeholder="Qisqa bio">
          </div>
        </div>
      </div>
    </div>

    
    <div class="fade-up">

      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Avatar</div>
        <?php if(isset($user) && $user->avatar): ?>
        <div style="margin-bottom:10px">
          <img src="<?php echo e(Storage::url($user->avatar)); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover" alt="">
        </div>
        <?php endif; ?>
        <label class="p-form-label">Rasm yuklash</label>
        <input type="file" name="avatar" class="p-form-control" accept="image/*">
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG, WebP · max 2MB</div>
      </div>

      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Moliyaviy ma'lumotlar</div>
        <div class="mb-3">
          <label class="p-form-label">Haqiqiy balans (UZS)</label>
          <input type="number" name="real_balance" class="p-form-control"
                 value="<?php echo e(old('real_balance', $user->real_balance ?? 0)); ?>" step="1" min="0">
        </div>
        <div class="mb-3">
          <label class="p-form-label">Cashback (UZS)</label>
          <input type="number" name="cashback" class="p-form-control"
                 value="<?php echo e(old('cashback', $user->cashback ?? 0)); ?>" step="1" min="0">
        </div>
        <div>
          <label class="p-form-label">AI cheklovi (token)</label>
          <input type="number" name="ai_limit" class="p-form-control"
                 value="<?php echo e(old('ai_limit', $user->ai_limit ?? 0)); ?>" step="1" min="0">
        </div>
      </div>

      
      <div class="p-card">
        <div class="p-card-title mb-3">Holat va ruxsatlar</div>
        <div class="flex items-center justify-between mb-3" style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Tasdiqlangan</div>
            <div style="font-size:11px;color:var(--p-hint)">Telefon tasdiqlangan</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="isVerified" value="1"
                   <?php echo e(old('isVerified', $user->isVerified ?? false) ? 'checked' : ''); ?>>
          </div>
        </div>
        <div class="flex items-center justify-between" style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Premium</div>
            <div style="font-size:11px;color:var(--p-hint)">1 yil muddatga beriladi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_premium" value="1"
                   <?php echo e(old('is_premium', $user->is_premium ?? false) ? 'checked' : ''); ?>>
          </div>
        </div>
      </div>
    </div>

    
    <div class=" fade-up d3">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          <?php echo e(isset($user) ? 'Saqlash' : 'Yaratish'); ?>

        </button>
        <a href="<?php echo e(isset($user) ? route('panel.users.show', $user) : route('panel.users.index')); ?>" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>
    </div>

  </div>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/users/edit.blade.php ENDPATH**/ ?>