<?php
  $user = $user ?? null;
  $val = fn($k, $d='') => old($k, data_get($user, $k, $d));
?>

<form method="POST" action="<?php echo e($action); ?>" class="space-y-6">
  <?php echo csrf_field(); ?>
  <?php if(($method ?? 'POST') !== 'POST'): ?> <?php echo method_field($method); ?> <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card p-6 lg:col-span-2 space-y-4">
      <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-emerald-500"></i> Asosiy ma'lumotlar</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Ism *</label><input name="name" required value="<?php echo e($val('name')); ?>" class="input"><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-rose-500 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Familiya</label><input name="lastname" value="<?php echo e($val('lastname')); ?>" class="input"></div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Email *</label><input name="email" required value="<?php echo e($val('email')); ?>" class="input"><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-rose-500 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Telefon</label><input name="phone_number" value="<?php echo e($val('phone_number')); ?>" class="input"><?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-rose-500 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
        <div><label class="text-xs font-medium text-gray-500 mb-1 block">Pozitsiya</label><input name="position" value="<?php echo e($val('position')); ?>" class="input"></div>
      </div>
    </div>

    <div class="card p-6 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="settings" class="w-4 h-4 text-emerald-500"></i> Sozlamalar</h3>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="isVerified" value="1" <?php echo e($val('isVerified') ? 'checked' : ''); ?> class="rounded"> Tasdiqlangan foydalanuvchi</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_premium" value="1" <?php echo e($val('is_premium') ? 'checked' : ''); ?> class="rounded"> Premium</label>
      <div class="kpi-soft">
        <div class="metric-label">Admin eslatmasi</div>
        <div class="metric-meta mt-2">Telefon va email maydonlari account identifikatori sifatida ishlatiladi. Yangilashda dublikat cheklovlari saqlanadi.</div>
      </div>
    </div>
  </div>

  <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2">
    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-secondary">Bekor qilish</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check" class="w-4 h-4"></i> Saqlash</button>
  </div>
</form>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/users/_form.blade.php ENDPATH**/ ?>