<?php $__env->startSection('title', isset($admin) ? 'Tahrirlash: '.$admin->name : 'Yangi admin'); ?>
<?php $__env->startSection('page-title', isset($admin) ? 'Adminni tahrirlash' : 'Yangi admin'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Adminlar / ' . (isset($admin) ? 'Tahrirlash' : 'Yaratish')); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(isset($admin) ? route('admin.admins.show', $admin) : route('admin.admins.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(isset($admin) ? route('admin.admins.show', $admin) : route('admin.admins.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($admin) ? $admin->name : 'Yangi admin'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($admin) ? "ID #$admin->id · ".$admin->role_label : 'Yangi admin yaratish'); ?> <?php $__env->endSlot(); ?>
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

<?php
$allPermissions = [
    'users'       => ['icon'=>'bi-people',        'label'=>'Foydalanuvchilar'],
    'books'       => ['icon'=>'bi-book',           'label'=>'Kitoblar'],
    'stationery'  => ['icon'=>'bi-pencil-square',  'label'=>'Kanstovar'],
    'orders'      => ['icon'=>'bi-bag-check',      'label'=>'Buyurtmalar'],
    'sellers'     => ['icon'=>'bi-shop-window',    'label'=>'Sotuvchilar'],
    'hubs'        => ['icon'=>'bi-box-seam',       'label'=>'Hublar'],
    'couriers'    => ['icon'=>'bi-bicycle',        'label'=>'Kuryerlar'],
    'promocodes'  => ['icon'=>'bi-ticket-perforated','label'=>'Promokodlar'],
    'discounts'   => ['icon'=>'bi-percent',        'label'=>'Chegirmalar'],
    'settings'    => ['icon'=>'bi-gear',           'label'=>'Sozlamalar & Kategoriyalar'],
    'admins'      => ['icon'=>'bi-shield-check',   'label'=>'Admin boshqaruvi'],
];
$currentPerms = $admin->permissions ?? [];
?>

<form method="POST"
      action="<?php echo e(isset($admin) ? route('admin.admins.update', $admin) : route('admin.admins.store')); ?>">
  <?php echo csrf_field(); ?>
  <?php if(isset($admin)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

    
    <div class="xl:col-span-7 fade-up">
      <div class="p-card">
        <div class="p-card-title mb-3">Asosiy ma'lumotlar</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                   value="<?php echo e(old('name', $admin->name ?? '')); ?>" required>
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
            <label class="p-form-label">Email <span style="color:var(--p-danger)">*</span></label>
            <input type="email" name="email" class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('email', $admin->email ?? '')); ?>" required>
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
            <label class="p-form-label">Parol <?php echo e(isset($admin) ? '(ixtiyoriy)' : '*'); ?></label>
            <input type="password" name="password" class="p-form-control"
                   placeholder="<?php echo e(isset($admin) ? 'O\'zgartirish uchun to\'ldiring...' : 'Kamida 8 belgi'); ?>"
                   <?php echo e(isset($admin) ? '' : 'required'); ?>>
          </div>
          <div class="">
            <label class="p-form-label">Parolni tasdiqlang</label>
            <input type="password" name="password_confirmation" class="p-form-control" placeholder="Parolni qaytaring">
          </div>
          <div class="">
            <label class="p-form-label">Rol <span style="color:var(--p-danger)">*</span></label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="roleSelector">
              <?php $__currentLoopData = ['superadmin'=>['Super Admin','Barcha bo\'limlarga cheksiz kirish','danger'],'admin'=>['Admin','Ko\'pgina bo\'limlar boshqaruvi','accent'],'moderator'=>['Moderator','Faqat belgilangan bo\'limlar','warning']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val=>[$label,$desc,$color]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="md:col-span-4">
                <label style="display:block;cursor:pointer">
                  <input type="radio" name="role" value="<?php echo e($val); ?>"
                         <?php echo e(old('role', $admin->role ?? 'moderator') === $val ? 'checked' : ''); ?>

                         style="display:none" class="role-radio">
                  <div class="role-card" data-role="<?php echo e($val); ?>" style="padding:14px;border-radius:10px;border:2px solid var(--p-border);background:var(--p-elevated);transition:all .15s;text-align:center">
                    <div style="font-size:14px;font-weight:600;color:var(--p-<?php echo e($color); ?>,var(--p-accent))"><?php echo e($label); ?></div>
                    <div style="font-size:11px;color:var(--p-hint);margin-top:3px"><?php echo e($desc); ?></div>
                  </div>
                </label>
              </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="xl:col-span-5 fade-up">
      <div class="p-card" id="permissionsCard">
        <div class="p-card-title mb-1">Ruxsatlar</div>
        <div style="font-size:12px;color:var(--p-hint);margin-bottom:16px">
          Superadmin uchun avtomatik barcha ruxsatlar beriladi
        </div>

        <div id="permissionsWrap">
          <?php $__currentLoopData = $allPermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="flex items-center justify-between mb-3"
               style="padding:10px;background:var(--p-elevated);border-radius:8px">
            <div class="flex items-center gap-2">
              <i class="bi <?php echo e($info['icon']); ?>" style="font-size:14px;color:var(--p-muted)"></i>
              <span style="font-size:13px;color:var(--p-text)"><?php echo e($info['label']); ?></span>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input perm-check" type="checkbox"
                     name="permissions[]" value="<?php echo e($perm); ?>" id="perm_<?php echo e($perm); ?>"
                     <?php echo e(in_array($perm, old('permissions', $currentPerms)) ? 'checked' : ''); ?>>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

          <div class="flex gap-2 mt-2">
            <button type="button" class="btn-p ghost sm" onclick="toggleAllPerms(true)">
              Barchasini belgilash
            </button>
            <button type="button" class="btn-p ghost sm" onclick="toggleAllPerms(false)">
              Barchasini olib tashlash
            </button>
          </div>
        </div>
      </div>

      
      <div class="p-card mt-3">
        <div class="flex items-center justify-between">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Aktiv</div>
            <div style="font-size:11px;color:var(--p-hint)">Tizimga kira oladi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   <?php echo e(old('is_active', $admin->is_active ?? true) ? 'checked' : ''); ?>>
          </div>
        </div>
      </div>
    </div>

    
    <div class=" fade-up d3">
      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i>
          <?php echo e(isset($admin) ? 'Saqlash' : 'Yaratish'); ?>

        </button>
        <a href="<?php echo e(isset($admin) ? route('admin.admins.show',$admin) : route('admin.admins.index')); ?>"
           class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>
  </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
// Role tanlanganda UI yangilanishi
function updateRoleUI() {
  const selected = document.querySelector('.role-radio:checked')?.value;
  document.querySelectorAll('.role-card').forEach(card => {
    const isActive = card.dataset.role === selected;
    card.style.borderColor = isActive ? 'var(--p-accent)' : 'var(--p-border)';
    card.style.background  = isActive ? 'var(--p-accent-d)' : 'var(--p-elevated)';
  });

  // Superadmin uchun permissionlarni yashirish
  const wrap = document.getElementById('permissionsWrap');
  if (selected === 'superadmin') {
    wrap.style.opacity = '.4';
    wrap.style.pointerEvents = 'none';
  } else {
    wrap.style.opacity = '1';
    wrap.style.pointerEvents = 'auto';
  }
}

document.querySelectorAll('.role-radio').forEach(r => {
  r.addEventListener('change', updateRoleUI);
});

// Role cardga click
document.querySelectorAll('.role-card').forEach(card => {
  card.addEventListener('click', () => {
    const radio = document.querySelector(`.role-radio[value="${card.dataset.role}"]`);
    if (radio) { radio.checked = true; updateRoleUI(); }
  });
});

updateRoleUI();

// Barcha permission toggle
function toggleAllPerms(state) {
  document.querySelectorAll('.perm-check').forEach(c => c.checked = state);
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/admins/edit.blade.php ENDPATH**/ ?>