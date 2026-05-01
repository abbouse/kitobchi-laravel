<?php $__env->startSection('title', 'Buyurtmalar'); ?>
<?php $__env->startSection('page-title', 'Buyurtmalar'); ?>

<?php $__env->startSection('content'); ?>
<div>
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
     <?php $__env->slot('heading', null, []); ?> Buyurtmalar <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($orders->total()); ?> ta buyurtma yozuvi topildi. <?php $__env->endSlot(); ?>
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
      <div class="a122-index-header__title">Filter va qidiruv</div>
      <div class="a122-index-header__meta">Buyurtmalarni ID, mijoz yoki status bo‘yicha filtrlash mumkin.</div>
    </div>
    <div class="a122-index-header__actions">
      <form method="GET" class="a122-index-search-form">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <i data-lucide="search" class="w-4 h-4"></i>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID, mijoz, holat..." class="a122-index-search-input">
      </form>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
    <?php $__currentLoopData = [
      'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
      'shipped' => ['Yo‘lda', $counts['shipped'] ?? 0],
      'paid' => ['Yakunlangan', $counts['paid'] ?? 0],
      'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
      'all' => ['Barchasi', $counts['all'] ?? 0],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
        <?php echo e($label); ?> <span><?php echo e($count); ?></span>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="hidden">
    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
        $statusLabel = match ((string) $order->status) {
          'A', 'P' => 'pending',
          'B' => 'shipped',
          'C' => 'paid',
          'F' => 'cancelled',
          default => 'pending',
        };
        $paymentLabel = match ((int) $order->paymentStatus) {
          2 => 'Paid',
          1 => 'Card',
          0 => 'Cash',
          default => 'Other',
        };
      ?>
      <div class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="font-semibold">#ORD-<?php echo e($order->id); ?></div>
            <div class="text-xs text-gray-500"><?php echo e(trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? ''))); ?></div>
          </div>
          <span class="badge <?php echo e($statusLabel === 'paid' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : ($statusLabel === 'shipped' ? 'badge-info' : 'badge-danger'))); ?>"><?php echo e($statusLabel); ?></span>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
          <div><div class="text-xs text-gray-500">Tovarlar</div><div><?php echo e((int) collect($order->items ?? [])->sum('count_item')); ?> ta</div></div>
          <div><div class="text-xs text-gray-500">Summa</div><div><?php echo e(number_format((float) $order->amount, 0)); ?> UZS</div></div>
          <div><div class="text-xs text-gray-500">To'lov</div><div><?php echo e($paymentLabel); ?></div></div>
          <div><div class="text-xs text-gray-500">Sana</div><div><?php echo e(optional($order->created_at)->format('Y-m-d')); ?></div></div>
        </div>
        <div class="mt-4 flex justify-end">
          <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="card p-6 text-sm text-gray-500">Buyurtmalar topilmadi.</div>
    <?php endif; ?>
  </div>

  <div class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Buyurtmalar jadvali</div>
        <div class="a122-section-head__meta">Statusni shu jadvalning o‘zidan boshqarish va buyurtma tafsilotiga tez o‘tish mumkin.</div>
      </div>
    </div>
    <div class="a122-section-body">
    <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Buyurtma</th><th>Mijoz</th><th>Tovarlar</th><th>Summa</th><th>To'lov</th><th>Holat</th><th>Sana</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusLabel = match ((string) $order->status) {
                'A', 'P' => 'pending',
                'B' => 'shipped',
                'C' => 'paid',
                'F' => 'cancelled',
                default => 'pending',
              };
              $paymentLabel = match ((int) $order->paymentStatus) {
                2 => 'Paid',
                1 => 'Card',
                0 => 'Cash',
                default => 'Other',
              };
            ?>
            <tr>
              <td><span class="font-semibold">#ORD-<?php echo e($order->id); ?></span></td>
              <td><?php echo e(trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? ''))); ?></td>
              <td><?php echo e((int) collect($order->items ?? [])->sum('count_item')); ?></td>
              <td><?php echo e(number_format((float) $order->amount, 0)); ?> UZS</td>
              <td><span class="badge badge-muted"><?php echo e($paymentLabel); ?></span></td>
              <td>
                <form method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>" class="inline-flex">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('PATCH'); ?>
                  <select name="status" class="a122-inline-status" onchange="this.form.submit()">
                    <option value="A" <?php if($order->status === 'A'): echo 'selected'; endif; ?>>Yangi</option>
                    <option value="P" <?php if($order->status === 'P'): echo 'selected'; endif; ?>>Qadoqlanmoqda</option>
                    <option value="B" <?php if($order->status === 'B'): echo 'selected'; endif; ?>>Yo'lda</option>
                    <option value="C" <?php if($order->status === 'C'): echo 'selected'; endif; ?>>Yakunlangan</option>
                    <option value="F" <?php if($order->status === 'F'): echo 'selected'; endif; ?>>Bekor qilingan</option>
                  </select>
                </form>
              </td>
              <td><?php echo e(optional($order->created_at)->format('Y-m-d')); ?></td>
              <td>
                <div class="flex items-center justify-end gap-1 flex-wrap">
                  <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>
  </div>
</div>
<?php if(isset($orders) && method_exists($orders, 'links')): ?>
  <div class="mt-4"><?php echo e($orders->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/orders/index.blade.php ENDPATH**/ ?>