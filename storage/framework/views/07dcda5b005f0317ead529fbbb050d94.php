<?php $__env->startSection('title', 'Yangi admin'); ?>
<?php $__env->startSection('page-title', 'Yangi admin yaratish'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
  <div class="col-xl-7">

    <form method="POST" action="<?php echo e(route('panel.admins.store')); ?>">
      <?php echo csrf_field(); ?>

      
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Admin ma'lumotlari</div>
        </div>
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
                     value="<?php echo e(old('name')); ?>" required maxlength="100">
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
                     value="<?php echo e(old('email')); ?>" required>
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
              <label class="p-label">Parol <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password"
                     class="p-form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     required minlength="8" autocomplete="new-password">
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
              <label class="p-label">Parolni tasdiqlash <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password_confirmation"
                     class="p-form-control" required autocomplete="new-password">
            </div>

          </div>
        </div>
      </div>

      
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Rol va ruxsatlar</div>
        </div>
        <div class="dash-card-body">

          <label class="p-label mb-2">Rol <span style="color:var(--p-danger)">*</span></label>
          <div class="d-flex gap-2 mb-3" id="roleCards">
            <?php $__currentLoopData = [
              ['superadmin', 'Superadmin', 'bi-shield-fill-check', 'danger',  'Barcha ruxsatlar'],
              ['admin',      'Admin',      'bi-person-badge',       'accent',  'Ko\'pchilik ruxsatlar'],
              ['moderator',  'Moderator',  'bi-eye',                'warning', 'Cheklangan ruxsatlar'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$val, $lbl, $icon, $clr, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label style="flex:1;cursor:pointer">
              <input type="radio" name="role" value="<?php echo e($val); ?>"
                     class="d-none role-radio"
                     <?php echo e(old('role','moderator') === $val ? 'checked' : ''); ?>>
              <div class="role-card" data-role="<?php echo e($val); ?>"
                   style="border:2px solid var(--p-border);border-radius:10px;
                          padding:14px;text-align:center;transition:all .2s;
                          <?php echo e(old('role','moderator') === $val ? 'border-color:var(--p-'.$clr.');background:var(--p-'.$clr.'-d, var(--p-elevated))' : ''); ?>">
                <i class="bi <?php echo e($icon); ?>"
                   style="font-size:22px;color:var(--p-<?php echo e($clr); ?>);display:block;margin-bottom:6px"></i>
                <div style="font-size:13px;font-weight:600;color:var(--p-text)"><?php echo e($lbl); ?></div>
                <div style="font-size:11px;color:var(--p-hint);margin-top:2px"><?php echo e($desc); ?></div>
              </div>
            </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

          
          <div id="permissionsBlock" style="<?php echo e(old('role','moderator') === 'superadmin' ? 'display:none' : ''); ?>">
            <label class="p-label mb-2">Ruxsatlar</label>
            <?php
              $allPerms = [
                'users'      => ['Foydalanuvchilar', 'bi-people'],
                'books'      => ['Kitoblar',         'bi-book'],
                'stationery' => ['Kanstovar',        'bi-pencil-square'],
                'orders'     => ['Buyurtmalar',      'bi-bag-check'],
                'sellers'    => ['Sotuvchilar',      'bi-shop-window'],
                'couriers'   => ['Kuryerlar',        'bi-bicycle'],
                'promocodes' => ['Promokodlar',      'bi-ticket-perforated'],
                'settings'   => ['Sozlamalar',       'bi-gear'],
                'admins'     => ['Adminlar',         'bi-shield-check'],
              ];
              $oldPerms = old('permissions', []);
            ?>
            <div class="d-flex flex-wrap gap-2">
              <?php $__currentLoopData = $allPerms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm => [$label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <label style="cursor:pointer;background:var(--p-elevated);
                            border:1px solid var(--p-border);border-radius:8px;
                            padding:8px 14px;display:flex;align-items:center;gap:7px;
                            transition:all .15s" class="perm-label">
                <input type="checkbox" name="permissions[]" value="<?php echo e($perm); ?>"
                       style="accent-color:var(--p-accent);width:16px;height:16px"
                       <?php echo e(in_array($perm, $oldPerms) ? 'checked' : ''); ?>>
                <i class="bi <?php echo e($icon); ?>" style="color:var(--p-muted);font-size:14px"></i>
                <span style="font-size:12px;color:var(--p-text)"><?php echo e($label); ?></span>
              </label>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="mt-2">
              <button type="button" onclick="toggleAllPerms(true)"
                      class="btn-p ghost sm">Barchasini belgilash</button>
              <button type="button" onclick="toggleAllPerms(false)"
                      class="btn-p ghost sm ms-1">Barchasini olib tashlash</button>
            </div>
          </div>

          
          <div class="mt-3">
            <label class="p-label">Holat</label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" name="is_active" value="1" checked
                     style="width:18px;height:18px;accent-color:var(--p-accent)">
              <span style="font-size:13px;color:var(--p-text)">Faol (kirish mumkin)</span>
            </label>
          </div>

        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo e(route('panel.admins.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p">
          <i class="bi bi-person-plus"></i> Admin yaratish
        </button>
      </div>
    </form>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Rol tanlash
document.querySelectorAll('.role-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-card').forEach(c => {
      c.style.borderColor = 'var(--p-border)';
      c.style.background  = '';
    });
    const card = document.querySelector(`.role-card[data-role="${this.value}"]`);
    const colors = { superadmin: 'danger', admin: 'accent', moderator: 'warning' };
    const clr = colors[this.value] || 'accent';
    card.style.borderColor = `var(--p-${clr})`;
    card.style.background  = `var(--p-elevated)`;

    document.getElementById('permissionsBlock').style.display =
      this.value === 'superadmin' ? 'none' : '';
  });
});

function toggleAllPerms(state) {
  document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = state);
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/admins/create.blade.php ENDPATH**/ ?>