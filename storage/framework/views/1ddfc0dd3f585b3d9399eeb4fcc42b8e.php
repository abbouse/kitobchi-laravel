<?php $__env->startSection('title', 'Profil'); ?>
<?php $__env->startSection('page-title', 'Mening profilim'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
  <div class="col-xl-7">

    <?php $admin = auth('panel')->user(); ?>

    
    <div class="p-card mb-3">
      <div class="dash-card-body" style="text-align:center;padding:32px">
        <div style="width:84px;height:84px;border-radius:50%;
                    background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                    display:flex;align-items:center;justify-content:center;
                    font-size:32px;font-weight:700;color:#fff;
                    margin:0 auto 16px;overflow:hidden;flex-shrink:0">
          <?php if($admin->avatar): ?>
            <img src="<?php echo e(asset('storage/'.$admin->avatar)); ?>"
                 style="width:100%;height:100%;object-fit:cover"
                 alt="<?php echo e($admin->name); ?>">
          <?php else: ?>
            <?php echo e(strtoupper(substr($admin->name, 0, 1))); ?>

          <?php endif; ?>
        </div>
        <div style="font-size:20px;font-weight:700;color:var(--p-text)"><?php echo e($admin->name); ?></div>
        <div style="font-size:13px;color:var(--p-hint)"><?php echo e($admin->email); ?></div>
        <div class="mt-2 d-flex justify-content-center gap-2">
          <span class="s-pill accent"><?php echo e($admin->getRoleLabelAttribute()); ?></span>
          <span class="s-pill <?php echo e($admin->is_active ? 'success' : 'danger'); ?>">
            <?php echo e($admin->is_active ? 'Faol' : 'Bloklangan'); ?>

          </span>
        </div>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlarni tahrirlash</div></div>
      <div class="dash-card-body">
        
        <form method="POST" action="<?php echo e(route('panel.profile.update')); ?>"
              enctype="multipart/form-data">
          <?php echo csrf_field(); ?>

          <div class="row g-3">

            
            <div class="col-12">
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
                     value="<?php echo e(old('name', $admin->name)); ?>" required maxlength="100">
              <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="col-12">
              <label class="p-label">Email <span style="color:var(--p-danger)">*</span></label>
              <input type="email" name="email"
                     class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('email', $admin->email)); ?>" required>
              <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="col-12">
              <label class="p-label">Avatar rasmi</label>
              <input type="file" name="avatar"
                     class="p-form-control <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     accept="image/jpeg,image/png,image/jpg">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
                JPG, JPEG, PNG · Maksimal 2MB
              </div>
              <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="col-12" style="padding-top:8px">
              <div style="border-top:1px solid var(--p-border);padding-top:16px;
                          font-size:12px;font-weight:600;color:var(--p-muted);
                          text-transform:uppercase;letter-spacing:.07em">
                Parolni o'zgartirish
                <span style="font-weight:400;font-size:11px;text-transform:none;letter-spacing:0">
                  (ixtiyoriy — to'ldirmasangiz o'zgarmaydi)
                </span>
              </div>
            </div>

            <div class="col-12">
              <label class="p-label">Joriy parol</label>
              <input type="password" name="current_password"
                     class="p-form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     autocomplete="current-password">
              <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Yangi parol</label>
              <input type="password" name="password"
                     class="p-form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     autocomplete="new-password" minlength="8">
              <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-sm-6">
              <label class="p-label">Yangi parolni tasdiqlash</label>
              <input type="password" name="password_confirmation"
                     class="p-form-control" autocomplete="new-password">
            </div>

          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="reset" class="btn-p ghost">Tozalash</button>
            <button type="submit" class="btn-p">
              <i class="bi bi-check-lg"></i> Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

    
    <div class="p-card mt-3">
      <div class="dash-card-body" style="padding:12px 20px">
        <div class="row g-2" style="font-size:12px;color:var(--p-hint)">
          <div class="col-sm-4">
            <i class="bi bi-geo me-1"></i>
            Oxirgi IP: <strong style="color:var(--p-muted)"><?php echo e($admin->last_ip ?? '—'); ?></strong>
          </div>
          <div class="col-sm-4">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            Kirdi: <strong style="color:var(--p-muted)">
              <?php echo e($admin->last_login_at
                  ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i')
                  : '—'); ?>

            </strong>
          </div>
          <div class="col-sm-4">
            <i class="bi bi-calendar me-1"></i>
            Yaratildi: <strong style="color:var(--p-muted)"><?php echo e($admin->created_at?->format('d.m.Y')); ?></strong>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/auth/profile.blade.php ENDPATH**/ ?>