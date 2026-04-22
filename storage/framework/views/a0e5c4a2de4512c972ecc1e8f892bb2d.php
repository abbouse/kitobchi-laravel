<?php $__env->startSection('title', 'Admin: '.$admin->name); ?>
<?php $__env->startSection('page-title', $admin->name); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.admins.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.admins.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e($admin->name); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> ID: #<?php echo e($admin->id); ?> · <?php echo e($admin->getRoleLabelAttribute()); ?> <?php $__env->endSlot(); ?>
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


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  
  <div class="xl:col-span-8">

    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Admin ma'lumotlari</div>
        <div class="flex gap-2">
          <a href="<?php echo e(route('admin.admins.edit', $admin)); ?>" class="btn-p ghost sm">
            <i class="bi bi-pencil"></i> Tahrirlash
          </a>
          <?php if($admin->id !== auth('panel')->id()): ?>
          <form method="POST" action="<?php echo e(route('admin.admins.toggle', $admin)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <button class="btn-p <?php echo e($admin->is_active ? 'danger' : ''); ?> ghost sm">
              <i class="bi bi-<?php echo e($admin->is_active ? 'lock' : 'unlock'); ?>"></i>
              <?php echo e($admin->is_active ? 'Bloklash' : 'Faollashtirish'); ?>

            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="dash-card-body">
        <div class="flex items-center gap-4 mb-4">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;
                      color:#fff;flex-shrink:0;overflow:hidden">
            <?php if($admin->avatar): ?>
              <img src="<?php echo e($admin->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($admin->name, 0, 1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--p-text)"><?php echo e($admin->name); ?></div>
            <div style="font-size:13px;color:var(--p-hint)"><?php echo e($admin->email); ?></div>
            <div class="flex gap-2 mt-2">
              <span class="s-pill" style="background:rgba(<?php echo e($admin->getRoleColorAttribute() ?? '79,124,255'); ?>,.15);color:var(--p-accent)">
                <?php echo e($admin->getRoleLabelAttribute()); ?>

              </span>
              <span class="s-pill <?php echo e($admin->is_active ? 'success' : 'danger'); ?>">
                <?php echo e($admin->is_active ? 'Faol' : 'Bloklangan'); ?>

              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php $__currentLoopData = [
            ['ID',              '#'.$admin->id],
            ['Email',           $admin->email],
            ['Rol',             $admin->getRoleLabelAttribute()],
            ['Oxirgi IP',       $admin->last_ip ?? '—'],
            ['Oxirgi kirish',   $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
            ["Qo'shildi",       $admin->created_at?->format('d.m.Y H:i')],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k, $v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px"><?php echo e($k); ?></div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Ruxsatlar</div></div>
      <div class="dash-card-body">
        <?php if($admin->isSuperAdmin()): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
                      background:var(--p-accent-d);border-radius:8px;border:1px solid rgba(79,124,255,.2)">
            <i class="bi bi-shield-fill-check" style="color:var(--p-accent);font-size:20px"></i>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--p-accent)">Superadmin</div>
              <div style="font-size:11px;color:var(--p-hint)">Barcha ruxsatlarga ega</div>
            </div>
          </div>
        <?php else: ?>
          <?php
            $allPerms = ['users','books','stationery','orders','sellers','couriers','promocodes','discounts','settings','admins'];
            $adminPerms = $admin->permissions ?? [];
          ?>
          <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = $allPerms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="s-pill <?php echo e(in_array($perm, $adminPerms) ? 'success' : 'muted'); ?>"
                  style="font-size:12px;padding:5px 12px">
              <i class="bi bi-<?php echo e(in_array($perm, $adminPerms) ? 'check-circle-fill' : 'x-circle'); ?> mr-1"></i>
              <?php echo e($perm); ?>

            </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  
  <div class="xl:col-span-4">
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Amallar</div></div>
      <div class="dash-card-body flex flex-col gap-2">
        <a href="<?php echo e(route('admin.admins.edit', $admin)); ?>" class="btn-p primary" style="justify-content:center">
          <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        <?php if($admin->id !== auth('panel')->id()): ?>
        <form method="POST" action="<?php echo e(route('admin.admins.toggle', $admin)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p <?php echo e($admin->is_active ? 'danger' : 'ghost'); ?>" style="width:100%;justify-content:center">
            <i class="bi bi-<?php echo e($admin->is_active ? 'lock' : 'unlock'); ?>"></i>
            <?php echo e($admin->is_active ? 'Bloklash' : 'Faollashtirish'); ?>

          </button>
        </form>
        <form method="POST" action="<?php echo e(route('admin.admins.destroy', $admin)); ?>"
              onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button class="btn-p danger ghost" style="width:100%;justify-content:center">
            <i class="bi bi-trash"></i> O'chirish
          </button>
        </form>
        <?php endif; ?>
        <a href="<?php echo e(route('admin.admins.index')); ?>" class="btn-p ghost" style="justify-content:center">
          <i class="bi bi-arrow-left"></i> Ro'yxatga
        </a>
      </div>
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/admins/show.blade.php ENDPATH**/ ?>