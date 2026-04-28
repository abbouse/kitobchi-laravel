<?php $__env->startSection('title', 'Foydalanuvchilar'); ?>
<?php $__env->startSection('page-title', 'Foydalanuvchilar'); ?>

<?php $__env->startSection('content'); ?>
<div>
  <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    <div>
      <h2 class="text-xl font-bold tracking-tight">Foydalanuvchilar</h2>
      <p class="text-xs text-gray-500 mt-0.5"><?php echo e($users->total()); ?> ta yozuv topildi</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="GET" class="relative min-w-[220px]">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism, email yoki rol bo'yicha qidirish..." class="input !pl-9 !py-2 w-full">
      </form>
      <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
    <?php $__currentLoopData = [
      'all' => ['Barchasi', $counts['all'] ?? 0],
      'buyers' => ['Xarid qilganlar', $counts['buyers'] ?? 0],
      'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
      'active' => ['Faol', $counts['active'] ?? 0],
      'premium' => ['Premium', $counts['premium'] ?? 0],
      'blocked' => ['Bloklangan', $counts['blocked'] ?? 0],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
        <?php echo e($label); ?> <span><?php echo e($count); ?></span>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="hidden">
    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="card p-4">
        <div class="flex items-start gap-3">
          <?php echo $__env->make('a122.partials.avatar', [
            'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
            'image' => $user->avatar,
            'class' => 'w-11 h-11 rounded-2xl text-sm',
          ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-sm"><?php echo e(trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—'); ?></div>
            <div class="text-xs text-gray-500 break-all"><?php echo e($user->email ?: '—'); ?></div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <span class="badge badge-info"><?php echo e($user->position ?: 'User'); ?></span>
              <span class="badge <?php echo e($user->isBlocked() ? 'badge-danger' : ($user->isVerified ? 'badge-success' : 'badge-warning')); ?>"><?php echo e($user->isBlocked() ? 'blocked' : ($user->isVerified ? 'active' : 'pending')); ?></span>
              <span class="text-xs text-gray-500"><?php echo e(optional($user->created_at)->format('Y-m-d')); ?></span>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
            <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
          </div>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="card p-6 text-sm text-gray-500">Foydalanuvchilar topilmadi.</div>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Foydalanuvchi</th><th>Rol</th><th>Status</th><th>Qo'shilgan</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <?php echo $__env->make('a122.partials.avatar', [
                    'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
                    'image' => $user->avatar,
                    'class' => 'w-9 h-9 rounded-full text-xs',
                  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <div>
                    <div class="font-semibold"><?php echo e(trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—'); ?></div>
                    <div class="text-xs text-gray-500"><?php echo e($user->email ?: '—'); ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-info"><?php echo e($user->position ?: 'User'); ?></span></td>
              <td>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="badge <?php echo e($user->isVerified ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($user->isVerified ? 'active' : 'pending'); ?></span>
                  <?php if($user->isBlocked()): ?>
                    <span class="badge badge-danger">blocked</span>
                  <?php endif; ?>
                  <?php if($user->is_premium): ?>
                    <span class="badge badge-info">premium</span>
                  <?php endif; ?>
                </div>
              </td>
              <td><?php echo e(optional($user->created_at)->format('Y-m-d')); ?></td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <form method="POST" action="<?php echo e(route('admin.users.verify', $user)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="<?php echo e($user->isVerified ? 'Tasdiqni bekor qilish' : 'Tasdiqlash'); ?>">
                      <i data-lucide="<?php echo e($user->isVerified ? 'badge-x' : 'badge-check'); ?>" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.users.premium', $user)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="<?php echo e($user->is_premium ? 'Premiumni o‘chirish' : 'Premiumni yoqish'); ?>">
                      <i data-lucide="<?php echo e($user->is_premium ? 'gem' : 'sparkles'); ?>" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                  <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="text-center text-sm text-gray-500 py-8">Foydalanuvchilar topilmadi.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php if(isset($users) && method_exists($users, 'links')): ?>
  <div class="mt-4"><?php echo e($users->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/index.blade.php ENDPATH**/ ?>