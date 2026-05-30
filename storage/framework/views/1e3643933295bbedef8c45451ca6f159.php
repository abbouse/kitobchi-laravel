<?php $__env->startSection('title', 'Chat kuzatuv'); ?>
<?php $__env->startSection('page-title', 'Chat kuzatuv'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Community','title' => 'Chat kuzatuv','subtitle' => ''.e($conversations->total()).' ta suhbat']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Community','title' => 'Chat kuzatuv','subtitle' => ''.e($conversations->total()).' ta suhbat']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

  <div class="row g-3">
    <?php $__currentLoopData = [
      [$counts['user'] ?? 0, 'User chat', 'bi-people', 'primary'],
      [$counts['seller'] ?? 0, 'Seller chat', 'bi-shop-window', 'warning'],
      [$counts['ai'] ?? 0, 'AI chat', 'bi-robot', 'info'],
      [($counts['user'] ?? 0) + ($counts['seller'] ?? 0) + ($counts['ai'] ?? 0), 'Jami kanal', 'bi-chat-dots', 'success'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-<?php echo e($tone); ?>-subtle text-<?php echo e($tone); ?>">
            <i class="bi <?php echo e($icon); ?>"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value"><?php echo e(number_format($value)); ?></div>
            <div class="a122-stat-tile__label"><?php echo e($label); ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'user' => ['User chat', $counts['user'] ?? 0],
        'seller' => ['Seller chat', $counts['seller'] ?? 0],
        'ai' => ['AI chat', $counts['ai'] ?? 0],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
          <?php echo e($label); ?>

          <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($count)); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Suhbatlar jadvali','meta' => $conversations->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Suhbatlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($conversations->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0 data-table">
        <thead>
          <?php if($tab === 'ai'): ?>
            <tr>
              <th>Foydalanuvchi</th>
              <th>Xabarlar</th>
              <th>So‘nggi xabar</th>
              <th>Vaqt</th>
              <th class="text-end">Amallar</th>
            </tr>
          <?php elseif($tab === 'seller'): ?>
            <tr>
              <th>ID</th>
              <th>Foydalanuvchi</th>
              <th>Do‘kon</th>
              <th>So‘nggi faollik</th>
              <th class="text-end">Amallar</th>
            </tr>
          <?php else: ?>
            <tr>
              <th>ID</th>
              <th>Yuboruvchi</th>
              <th>Qabul qiluvchi</th>
              <th>So‘nggi faollik</th>
              <th class="text-end">Amallar</th>
            </tr>
          <?php endif; ?>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php if($tab === 'ai'): ?>
              <tr>
                <td>
                  <a href="<?php echo e(route('admin.users.show', $conv->user_id)); ?>" class="fw-semibold text-decoration-none"><?php echo e($conv->name); ?> <?php echo e($conv->lastname); ?></a>
                  <div class="small text-secondary"><?php echo e($conv->phone_number); ?></div>
                </td>
                <td class="fw-semibold"><?php echo e($conv->messages_count ?? '—'); ?></td>
                <td class="text-secondary text-truncate" style="max-width: 18rem;"><?php echo e(Str::limit($conv->last_message ?? '—', 54)); ?></td>
                <td class="text-secondary text-nowrap"><?php echo e($conv->last_at ? \Carbon\Carbon::parse($conv->last_at)->diffForHumans() : '—'); ?></td>
                <td class="text-end">
                  <a href="<?php echo e(route('admin.chats.show-ai', $conv->user_id)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            <?php elseif($tab === 'seller'): ?>
              <tr>
                <td class="text-secondary">#<?php echo e($conv->id); ?></td>
                <td><?php echo $__env->make('a122.chats._user-cell', ['name' => $conv->user1_name, 'lname' => $conv->user1_lastname, 'avatar' => $conv->user1_avatar, 'uid' => $conv->user_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                <td>
                  <?php if($conv->seller_name): ?>
                    <a href="<?php echo e(route('admin.sellers.show', $conv->seller_id_val)); ?>" class="fw-semibold text-decoration-none"><i class="bi bi-shop-window me-1"></i><?php echo e($conv->seller_name); ?></a>
                  <?php else: ?>
                    <span class="text-secondary">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-secondary text-nowrap"><?php echo e($conv->last_message_at ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() : '—'); ?></td>
                <td class="text-end">
                  <a href="<?php echo e(route('admin.chats.show', $conv->id)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            <?php else: ?>
              <tr>
                <td class="text-secondary">#<?php echo e($conv->id); ?></td>
                <td><?php echo $__env->make('a122.chats._user-cell', ['name' => $conv->user1_name, 'lname' => $conv->user1_lastname, 'avatar' => $conv->user1_avatar, 'uid' => $conv->user_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                <td>
                  <?php if($conv->user2_name): ?>
                    <?php echo $__env->make('a122.chats._user-cell', ['name' => $conv->user2_name, 'lname' => $conv->user2_lastname, 'avatar' => $conv->user2_avatar, 'uid' => $conv->receiver_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <?php else: ?>
                    <span class="text-secondary">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-secondary text-nowrap"><?php echo e($conv->last_message_at ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() : '—'); ?></td>
                <td class="text-end">
                  <a href="<?php echo e(route('admin.chats.show', $conv->id)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="text-center py-5 text-secondary">Suhbat topilmadi.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>

  <?php if($conversations->hasPages()): ?>
    <div><?php echo e($conversations->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/chats/index.blade.php ENDPATH**/ ?>