<?php $__env->startSection('title','Adminlar'); ?>
<?php $__env->startSection('page-title','Admin boshqaruvi'); ?>
<?php $__env->startSection('breadcrumb','Panel / Adminlar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Adminlar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Panel foydalanuvchilari va ularning ruxsatlari <?php $__env->endSlot(); ?>
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

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Adminlar ro‘yxati</div>
    <div class="a122-index-header__meta"><?php echo e($admins->total()); ?> ta admin topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, email yoki rol bo‘yicha qidiring">
    </form>
    <a href="<?php echo e(route('admin.admins.create')); ?>" class="btn-p primary">
      <i class="bi bi-plus-lg"></i> Yangi admin
    </a>
  </div>
</div>

<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'active' => ['Faol', $counts['active'] ?? 0],
    'inactive' => ['Bloklangan', $counts['inactive'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
      <?php echo e($label); ?> <span><?php echo e($count); ?></span>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Adminlar ro'yxati</div>
    <div class="p-card-sub"><?php echo e($admins->total()); ?> ta admin</div>
  </div>

  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>ID</th><th>Admin</th><th>Rol</th>
          <th>Ruxsatlar</th><th>Oxirgi kirish</th>
          <th>Holat</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">#<?php echo e($admin->id); ?></span></td>
          <td>
            <div class="flex items-center gap-2">
              <?php echo $__env->make('a122.partials.avatar', [
                'name' => $admin->name,
                'image' => $admin->avatar,
                'class' => 'av',
              ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($admin->name); ?></div>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e($admin->email); ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php $colors = ['superadmin'=>'danger','admin'=>'accent','moderator'=>'warning']; ?>
            <span class="s-pill <?php echo e($colors[$admin->role] ?? 'muted'); ?>"><?php echo e($admin->role_label); ?></span>
          </td>
          <td>
            <?php if($admin->isSuperAdmin()): ?>
              <span style="font-size:12px;color:var(--p-hint)">Barcha ruxsatlar</span>
            <?php else: ?>
              <div class="flex flex-wrap gap-1">
                <?php $__currentLoopData = array_slice($admin->permissions ?? [], 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="s-pill accent" style="font-size:10px;padding:2px 7px"><?php echo e($perm); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if(count($admin->permissions ?? []) > 3): ?>
                  <span style="font-size:11px;color:var(--p-hint)">+<?php echo e(count($admin->permissions)-3); ?> ta</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if($admin->last_login_at): ?>
              <div style="font-size:12px;color:var(--p-text)"><?php echo e($admin->last_login_at->format('d.m.Y H:i')); ?></div>
              <div style="font-size:11px;color:var(--p-hint)"><?php echo e($admin->last_ip); ?></div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--p-hint)">Hali kirмagan</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($admin->is_active): ?>
              <span class="s-pill success">Aktiv</span>
            <?php else: ?>
              <span class="s-pill danger">Bloklangan</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="flex gap-1">
              <a href="<?php echo e(route('admin.admins.edit', $admin)); ?>" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <?php if($admin->id !== auth('panel')->id()): ?>
              <form method="POST" action="<?php echo e(route('admin.admins.toggle', $admin)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p <?php echo e($admin->is_active ? 'danger' : 'success'); ?> sm"
                        title="<?php echo e($admin->is_active ? 'Bloklash' : 'Faollashtirish'); ?>">
                  <i class="bi bi-<?php echo e($admin->is_active ? 'lock' : 'unlock'); ?>"></i>
                </button>
              </form>
              <form method="POST" action="<?php echo e(route('admin.admins.destroy', $admin)); ?>"
                    onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-shield" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Adminlar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($admins->hasPages()): ?>
    <?php echo e($admins->links('a122.partials.pagination')); ?>

  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/admins/index.blade.php ENDPATH**/ ?>