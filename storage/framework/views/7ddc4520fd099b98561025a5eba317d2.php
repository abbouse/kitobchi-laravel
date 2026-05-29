<?php $__env->startSection('title', 'Reklamalar'); ?>
<?php $__env->startSection('page-title', 'Reklamalar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Marketing','title' => 'Reklamalar','subtitle' => ''.e($ads->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Marketing','title' => 'Reklamalar','subtitle' => ''.e($ads->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <?php if(request('type')): ?>
        <input type="hidden" name="type" value="<?php echo e(request('type')); ?>">
      <?php endif; ?>
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID yoki seller ID" class="form-control">
    </form>
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

  <div class="kc-filter-card">
    <div class="d-flex flex-wrap align-items-center gap-3">
      <div class="nav nav-pills flex-wrap">
        <?php $__currentLoopData = [
          ['pending', 'Kutilmoqda'],
          ['approved', 'Tasdiqlangan'],
          ['rejected', 'Rad etilgan'],
          ['active', 'Faol'],
          ['expired', 'Muddati o‘tgan'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
            <?php echo e($label); ?>

            <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($counts[$key] ?? 0)); ?></span>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <form method="GET" class="d-flex align-items-center gap-2 ms-lg-auto">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
        <select name="type" class="form-select form-select-sm" style="width: 12rem;">
          <option value="">Barcha tur</option>
          <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($type); ?>" <?php echo e(request('type') === $type ? 'selected' : ''); ?>><?php echo e($type); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn btn-sm btn-primary" type="submit" title="Filtrlash">
          <i class="bi bi-funnel"></i>
        </button>
        <a href="<?php echo e(route('admin.ads.index', ['tab' => $tab])); ?>" class="btn btn-sm btn-light border" title="Tozalash">
          <i class="bi bi-x-lg"></i>
        </a>
      </form>
    </div>
  </div>

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Reklamalar jadvali','meta' => $ads->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Reklamalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ads->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Banner</th>
            <th>Sotuvchi</th>
            <th>Tur</th>
            <th>Harakat</th>
            <th>Summa</th>
            <th>Muddat</th>
            <th>Moderatsiya</th>
            <th>To‘lov</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $ads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $moderationClass = match($ad->moderation) {
                'approved' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'rejected' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $moderationLabel = match($ad->moderation) {
                'approved' => 'Tasdiqlangan',
                'rejected' => 'Rad etilgan',
                default => 'Kutilmoqda',
              };
              $paymentClass = match($ad->paymentStatus) {
                'paid' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'failed' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $paymentLabel = match($ad->paymentStatus) {
                'paid' => 'To‘langan',
                'failed' => 'Muvaffaqiyatsiz',
                default => 'Kutilmoqda',
              };
              $expired = $ad->expire_at && $ad->expire_at <= now();
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($ad->id); ?></td>
              <td>
                <?php if($ad->banner_img): ?>
                  <a href="<?php echo e($ad->banner_img); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo e($ad->banner_img); ?>" alt="" class="rounded border" style="width:72px;height:38px;object-fit:cover;">
                  </a>
                <?php else: ?>
                  <div class="d-flex align-items-center justify-content-center rounded border text-secondary" style="width:72px;height:38px;">
                    <i class="bi bi-image"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if($ad->seller): ?>
                  <a href="<?php echo e(route('admin.sellers.show', $ad->seller_id)); ?>" class="fw-semibold text-decoration-none">
                    <?php echo e(Str::limit($ad->seller->shop_name ?? $ad->seller_id, 18)); ?>

                  </a>
                <?php else: ?>
                  <span class="text-secondary">#<?php echo e($ad->seller_id); ?></span>
                <?php endif; ?>
              </td>
              <td><span class="badge rounded-pill text-bg-secondary"><?php echo e($ad->type); ?></span></td>
              <td class="small text-secondary"><?php echo e($ad->action); ?> / <?php echo e($ad->product_type); ?> #<?php echo e($ad->product_id); ?></td>
              <td class="fw-semibold"><?php echo e(number_format($ad->amount)); ?></td>
              <td>
                <span class="<?php echo e($expired ? 'text-danger' : 'text-secondary'); ?>"><?php echo e($ad->expire_at ? \Carbon\Carbon::parse($ad->expire_at)->format('d.m.Y') : '—'); ?></span>
              </td>
              <td><span class="badge rounded-pill <?php echo e($moderationClass); ?>"><?php echo e($moderationLabel); ?></span></td>
              <td><span class="badge rounded-pill <?php echo e($paymentClass); ?>"><?php echo e($paymentLabel); ?></span></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <?php if($ad->moderation === 'pending'): ?>
                    <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Tasdiqlash">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="<?php echo e(route('admin.ads.show', $ad)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="10" class="text-center py-5 text-secondary">Reklama topilmadi.</td></tr>
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

  <?php if($ads->hasPages()): ?>
    <div><?php echo e($ads->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/ads/index.blade.php ENDPATH**/ ?>