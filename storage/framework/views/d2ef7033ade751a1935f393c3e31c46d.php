<?php $__env->startSection('title', 'Buyurtmalar'); ?>
<?php $__env->startSection('page-title', 'Buyurtmalar'); ?>
<?php $__env->startSection('page-eyebrow', 'Order operations'); ?>

<?php $__env->startSection('content'); ?>
<?php
  use App\Support\AdminOrderStatusPresenter;
  $statusTabs = [
    'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
    'shipped' => ['Yo‘lda', $counts['shipped'] ?? 0],
    'paid' => ['Yakunlangan', $counts['paid'] ?? 0],
    'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ];

  $statusLabel = function ($status) {
    return AdminOrderStatusPresenter::mainOrder($status);
  };

  $paymentLabel = function ($status) {
    return AdminOrderStatusPresenter::payment($status);
  };

  $statusBadgeClass = function ($label) {
    return match ($label) {
      'Yetib bordi', 'Mijoz qabul qildi' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
      'Kutilmoqda', 'Qadoqlanmoqda' => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
      "Yo'lda", 'Qaytgan' => 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis',
      'Bekor qilingan' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
      default => 'text-bg-light border',
    };
  };
?>

<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Order operations','title' => 'Buyurtmalar','subtitle' => 'Mijoz buyurtmalarini status, to‘lov va vaqt bo‘yicha boshqarish uchun markaziy navbat. List sahifaning o‘zidan qidirish, filtrlash va tezkor detailga o‘tish mumkin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Order operations','title' => 'Buyurtmalar','subtitle' => 'Mijoz buyurtmalarini status, to‘lov va vaqt bo‘yicha boshqarish uchun markaziy navbat. List sahifaning o‘zidan qidirish, filtrlash va tezkor detailga o‘tish mumkin.']); ?>
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-p ghost">
      <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
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
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Kutilayotgan','value' => number_format($counts['pending'] ?? 0),'meta' => 'Tasdiqlash yoki jarayon boshlanishini kutayotgan buyurtmalar','icon' => 'clock-history','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Kutilayotgan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['pending'] ?? 0)),'meta' => 'Tasdiqlash yoki jarayon boshlanishini kutayotgan buyurtmalar','icon' => 'clock-history','tone' => 'warning']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Yo‘ldagi buyurtmalar','value' => number_format($counts['shipped'] ?? 0),'meta' => 'Kuryer yoki logistika oqimida bo‘lgan buyurtmalar','icon' => 'truck','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Yo‘ldagi buyurtmalar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['shipped'] ?? 0)),'meta' => 'Kuryer yoki logistika oqimida bo‘lgan buyurtmalar','icon' => 'truck','tone' => 'info']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Yakunlangan','value' => number_format($counts['paid'] ?? 0),'meta' => 'To‘langan va muvaffaqiyatli yakunlangan buyurtmalar','icon' => 'check2-circle','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Yakunlangan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['paid'] ?? 0)),'meta' => 'To‘langan va muvaffaqiyatli yakunlangan buyurtmalar','icon' => 'check2-circle','tone' => 'success']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Bekor qilingan','value' => number_format($counts['cancelled'] ?? 0),'meta' => 'User, admin yoki tizim tomonidan bekor qilingan oqimlar','icon' => 'x-octagon','tone' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Bekor qilingan','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($counts['cancelled'] ?? 0)),'meta' => 'User, admin yoki tizim tomonidan bekor qilingan oqimlar','icon' => 'x-octagon','tone' => 'danger']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Filter va qidiruv','meta' => 'Buyurtmalarni ID, mijoz yoki holat bo‘yicha saralash mumkin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Filter va qidiruv','meta' => 'Buyurtmalarni ID, mijoz yoki holat bo‘yicha saralash mumkin.']); ?>
    <div class="row g-3 align-items-center">
      <div class="col-12 col-xl-5">
        <form method="GET" class="kc-search">
          <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
          <i class="bi bi-search kc-search__icon"></i>
          <input
            type="text"
            name="search"
            value="<?php echo e(request('search')); ?>"
            placeholder="ID, mijoz, telefon yoki holat..."
            class="form-control">
        </form>
      </div>
      <div class="col-12 col-xl-7">
        <div class="nav nav-pills gap-2 justify-content-xl-end">
          <?php $__currentLoopData = $statusTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a
              href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>"
              class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
              <?php echo e($label); ?>

              <span class="badge rounded-pill text-bg-light ms-2 kc-mono"><?php echo e(number_format($count)); ?></span>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
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

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Buyurtmalar jadvali','meta' => $orders->total() . ' ta buyurtma yozuvi topildi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Buyurtmalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($orders->total() . ' ta buyurtma yozuvi topildi.')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Buyurtma</th>
            <th>Mijoz</th>
            <th>Tovarlar</th>
            <th>Summa</th>
            <th>To‘lov</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $currentStatusLabel = $statusLabel($order->status_code ?? $order->status);
              $currentPaymentLabel = $paymentLabel($order->payment_status_code ?? $order->paymentStatus);
              $itemsCount = (int) collect($order->items ?? [])->sum('count_item');
            ?>
            <tr>
              <td>
                <div class="fw-bold">#ORD-<?php echo e($order->id); ?></div>
                <div class="small text-secondary">Platformadagi buyurtma</div>
              </td>
              <td>
                <div class="fw-semibold"><?php echo e(trim(($order->user?->name ?? 'Mehmon').' '.($order->user?->lastname ?? ''))); ?></div>
                <div class="small text-secondary"><?php echo e($order->user?->phone_number ?: 'Telefon yo‘q'); ?></div>
              </td>
              <td class="kc-mono"><?php echo e(number_format($itemsCount)); ?> ta</td>
              <td class="kc-mono fw-semibold"><?php echo e(number_format((float) $order->amount, 0)); ?> UZS</td>
              <td>
                <span class="badge rounded-pill text-bg-light border"><?php echo e($currentPaymentLabel); ?></span>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <span class="badge rounded-pill <?php echo e($statusBadgeClass($currentStatusLabel)); ?>"><?php echo e($currentStatusLabel); ?></span>
                  <form method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                      <option value="A" <?php if(in_array(($order->status_code ?? $order->status), ['A','pending'], true)): echo 'selected'; endif; ?>>Kutilmoqda</option>
                      <option value="P" <?php if(in_array(($order->status_code ?? $order->status), ['P','packing'], true)): echo 'selected'; endif; ?>>Qadoqlanmoqda</option>
                      <option value="B" <?php if(in_array(($order->status_code ?? $order->status), ['B','in_delivery'], true)): echo 'selected'; endif; ?>>Yetkazilmoqda</option>
                      <option value="C" <?php if(in_array(($order->status_code ?? $order->status), ['C','delivered'], true)): echo 'selected'; endif; ?>>Yetib bordi</option>
                      <option value="D" <?php if(in_array(($order->status_code ?? $order->status), ['D','customer_received'], true)): echo 'selected'; endif; ?>>Mijoz qabul qildi</option>
                      <option value="F" <?php if(in_array(($order->status_code ?? $order->status), ['F','cancelled','returned'], true)): echo 'selected'; endif; ?>>Bekor qilingan</option>
                    </select>
                  </form>
                </div>
              </td>
              <td>
                <div class="fw-semibold"><?php echo e(optional($order->created_at)->format('d.m.Y')); ?></div>
                <div class="small text-secondary"><?php echo e(optional($order->created_at)->format('H:i')); ?></div>
              </td>
              <td class="text-end">
                <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="btn-p primary sm">
                  <i class="bi bi-eye me-1"></i>Ko‘rish
                </a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-secondary">
                Buyurtmalar topilmadi.
              </td>
            </tr>
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

  <?php if(method_exists($orders, 'links')): ?>
    <div><?php echo e($orders->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/orders/index.blade.php ENDPATH**/ ?>