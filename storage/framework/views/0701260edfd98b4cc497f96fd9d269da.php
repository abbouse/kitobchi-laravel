<?php $__env->startSection('title', 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('page-title', 'Yangi foydalanuvchi'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
  <div class="col-xl-7">
    <form method="POST" action="<?php echo e(route('panel.users.store')); ?>">
      <?php echo csrf_field(); ?>

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Shaxsiy ma'lumotlar</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            <div class="col-sm-6">
              <label class="p-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('name')); ?>" required maxlength="30">
              <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Familiya</label>
              <input type="text" name="lastname"
                     class="p-form-control <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('lastname')); ?>" maxlength="30">
              <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Telefon <span style="color:var(--p-danger)">*</span></label>
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
                     placeholder="998901234567" maxlength="12">
              <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Email</label>
              <input type="email" name="email"
                     class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('email')); ?>" maxlength="40">
              <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Jinsi</label>
              <select name="sex" class="p-form-control">
                <option value="">Tanlanmagan</option>
                <option value="male"   <?php echo e(old('sex')==='male'?'selected':''); ?>>Erkak</option>
                <option value="female" <?php echo e(old('sex')==='female'?'selected':''); ?>>Ayol</option>
              </select>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Premium</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="is_premium" value="0">
                <input type="checkbox" name="is_premium" value="1"
                       <?php echo e(old('is_premium') ? 'checked' : ''); ?>

                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Premium foydalanuvchi</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo e(route('panel.users.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p">
          <i class="bi bi-person-plus"></i> Yaratish
        </button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/users/create.blade.php ENDPATH**/ ?>