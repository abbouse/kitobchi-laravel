<?php $__env->startSection('title', 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('page-title', 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Foydalanuvchilar / Yangi'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.users.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.users.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Yangi foydalanuvchi <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Yangi foydalanuvchi profili yarating <?php $__env->endSlot(); ?>
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


<div class="kc-page-inner kc-page-inner--readable w-full min-w-0">
    <form method="POST" action="<?php echo e(route('panel.users.store')); ?>">
      <?php echo csrf_field(); ?>

      <div class="p-card mb-4 fade-up">
        <div class="p-card-title mb-4">Shaxsiy ma'lumotlar</div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div class="">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name"
                   class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('name')); ?>" required maxlength="100" placeholder="Ism kiriting">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="">
            <label class="p-form-label">Familiya</label>
            <input type="text" name="lastname"
                   class="p-form-control <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('lastname')); ?>" maxlength="100" placeholder="Familiya kiriting">
            <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div class="">
            <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="phone_number"
                   class="p-form-control <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('phone_number')); ?>" required
                   placeholder="998901234567" maxlength="12" pattern="\d{12}">
            <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <small style="font-size:11px;color:var(--p-hint)">Format: 998XXXXXXXXX (12 raqam)</small>
          </div>

          <div class="">
            <label class="p-form-label">Email</label>
            <input type="email" name="email"
                   class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('email')); ?>" maxlength="191" placeholder="example@email.com">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div class="">
            <label class="p-form-label">Jinsi</label>
            <select name="sex" class="p-form-control <?php $__errorArgs = ['sex'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="">— Tanlang —</option>
              <option value="male"   <?php echo e(old('sex')==='male'?'selected':''); ?>>Erkak</option>
              <option value="female" <?php echo e(old('sex')==='female'?'selected':''); ?>>Ayol</option>
            </select>
            <?php $__errorArgs = ['sex'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="">
            <label class="p-form-label">Balans (UZS)</label>
            <input type="number" name="real_balance"
                   class="p-form-control <?php $__errorArgs = ['real_balance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('real_balance', 0)); ?>" min="0" step="100" placeholder="0">
            <?php $__errorArgs = ['real_balance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div style="padding:12px 16px;border-radius:10px;background:var(--p-info-d,rgba(56,189,248,0.10));border:1px solid rgba(56,189,248,0.2);font-size:13px;color:var(--p-info,#38bdf8);margin-bottom:20px;display:flex;align-items:center;gap:10px">
          <i class="bi bi-info-circle-fill" style="flex-shrink:0"></i>
          <div>Yangi foydalanuvchi uchun default parol: <strong>12345678</strong> (siz ularni login qilishdan keyin o'zgartirishlari mumkin)</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border-radius:8px;background:var(--p-elevated);border:1px solid var(--p-border);transition:all .2s">
              <input type="hidden" name="is_premium" value="0">
              <input type="checkbox" name="is_premium" value="1"
                     <?php echo e(old('is_premium') ? 'checked' : ''); ?>

                     style="width:18px;height:18px;accent-color:var(--p-accent);cursor:pointer;flex-shrink:0">
              <div>
                <div style="font-size:13px;color:var(--p-text);font-weight:600">Premium status</div>
                <div style="font-size:11px;color:var(--p-hint);margin-top:2px">Premium foydalanuvchi qilish</div>
              </div>
            </label>
          </div>

          <div class="">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border-radius:8px;background:var(--p-elevated);border:1px solid var(--p-border);transition:all .2s">
              <input type="hidden" name="isVerified" value="0">
              <input type="checkbox" name="isVerified" value="1"
                     <?php echo e(old('isVerified') ? 'checked' : ''); ?>

                     style="width:18px;height:18px;accent-color:var(--p-success);cursor:pointer;flex-shrink:0">
              <div>
                <div style="font-size:13px;color:var(--p-text);font-weight:600">Tasdiqlangan</div>
                <div style="font-size:11px;color:var(--p-hint);margin-top:2px">Email tasdiqlanish holati</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="flex gap-2 justify-end fade-up">
        <a href="<?php echo e(route('panel.users.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-person-plus"></i> Foydalanuvchi yaratish
        </button>
      </div>
    </form>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/users/create.blade.php ENDPATH**/ ?>