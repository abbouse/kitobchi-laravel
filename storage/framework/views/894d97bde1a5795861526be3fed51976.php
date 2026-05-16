<?php
  $user = $user ?? null;
  $val = fn($k, $d='') => old($k, data_get($user, $k, $d));
?>

<form method="POST" action="<?php echo e($action); ?>" class="kc-form-shell mt-4">
  <?php echo csrf_field(); ?>
  <?php if(($method ?? 'POST') !== 'POST'): ?> <?php echo method_field($method); ?> <?php endif; ?>

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="kc-form-card">
        <div class="kc-form-card__title">
          <span class="kc-form-card__icon"><i class="bi bi-person"></i></span>
          <span>Asosiy ma’lumotlar</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="user_name">Ism</label>
            <input id="user_name" name="name" required value="<?php echo e($val('name')); ?>" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_lastname">Familiya</label>
            <input id="user_lastname" name="lastname" value="<?php echo e($val('lastname')); ?>" class="form-control <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['lastname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_email">Email</label>
            <input id="user_email" name="email" required value="<?php echo e($val('email')); ?>" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_phone">Telefon</label>
            <input id="user_phone" name="phone_number" value="<?php echo e($val('phone_number')); ?>" class="form-control <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_position">Foydalanuvchi darajasi</label>
            <?php ($currentPosition = $val('position')); ?>
            <select id="user_position" name="position" class="form-select <?php $__errorArgs = ['position'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="reader" <?php echo e(in_array($currentPosition, ['', 'reader', "O'quvchi"], true) ? 'selected' : ''); ?>>Kitobxon</option>
              <option value="active_reader" <?php echo e($currentPosition === 'active_reader' ? 'selected' : ''); ?>>Faol kitobxon</option>
              <option value="book_lover" <?php echo e($currentPosition === 'book_lover' ? 'selected' : ''); ?>>Kitob muxlisi</option>
              <option value="reviewer" <?php echo e($currentPosition === 'reviewer' ? 'selected' : ''); ?>>Sharhlovchi</option>
              <option value="collector" <?php echo e($currentPosition === 'collector' ? 'selected' : ''); ?>>Kitob yig'uvchi</option>
              <option value="book_club_star" <?php echo e($currentPosition === 'book_club_star' ? 'selected' : ''); ?>>Book Club yulduzi</option>
              <option value="market_explorer" <?php echo e($currentPosition === 'market_explorer' ? 'selected' : ''); ?>>Market kashfiyotchisi</option>
              <option value="literary_mentor" <?php echo e($currentPosition === 'literary_mentor' ? 'selected' : ''); ?>>Adabiy yo'lboshchi</option>
            </select>
            <?php $__errorArgs = ['position'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="user_staff_role">Moderatsiya roli</label>
            <?php ($currentStaffRole = $val('staff_role')); ?>
            <select id="user_staff_role" name="staff_role" class="form-select <?php $__errorArgs = ['staff_role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="">Yo‘q</option>
              <option value="moderator" <?php echo e($currentStaffRole === 'moderator' ? 'selected' : ''); ?>>Moderator</option>
              <option value="administrator" <?php echo e($currentStaffRole === 'administrator' ? 'selected' : ''); ?>>Administrator</option>
            </select>
            <?php $__errorArgs = ['staff_role'];
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

    <div class="col-xl-4">
      <div class="kc-form-card">
        <div class="kc-form-card__title">
          <span class="kc-form-card__icon"><i class="bi bi-sliders"></i></span>
          <span>Sozlamalar</span>
        </div>

        <div class="kc-checkbox-stack">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="isVerified" value="1" <?php echo e($val('isVerified') ? 'checked' : ''); ?>>
            <span class="form-check-label">Tasdiqlangan foydalanuvchi</span>
          </label>

          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="is_premium" value="1" <?php echo e($val('is_premium') ? 'checked' : ''); ?>>
            <span class="form-check-label">Premium</span>
          </label>
        </div>

        <?php if($user && $user->isBlocked()): ?>
          <div class="alert alert-danger mt-4 mb-0 rounded-4 border-0 shadow-sm">
            <div class="fw-semibold">Akkaunt bloklangan</div>
            <div class="small mt-1">Muddat: <?php echo e($user->activeBlockLabel()); ?></div>
          </div>
        <?php endif; ?>

        <div class="kc-note-card mt-4">
          <span class="kc-note-card__title">Admin eslatmasi</span>
          Telefon va email maydonlari account identifikatori sifatida ishlatiladi. Yangilashda dublikat cheklovlari saqlanadi.
        </div>
      </div>
    </div>
  </div>

  <div class="kc-form-actions">
    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">Bekor qilish</a>
    <button type="submit" class="btn btn-primary rounded-pill px-4">
      <i class="bi bi-check2 me-2"></i>Saqlash
    </button>
  </div>
</form>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/_form.blade.php ENDPATH**/ ?>