<?php $__env->startSection('title', 'Gift sertifikatlar'); ?>
<?php $__env->startSection('page-title', 'Gift sertifikatlar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Commerce','title' => 'Gift sertifikatlar','subtitle' => ''.e($certs->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Commerce','title' => 'Gift sertifikatlar','subtitle' => ''.e($certs->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Kod, telefon yoki ism" class="form-control">
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

  <div class="row g-3">
    <div class="col-12 col-xl-7">
      <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Tariflar','meta' => 'Nominal variantlar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tariflar','meta' => 'Nominal variantlar']); ?>
        <form method="POST" action="<?php echo e(route('admin.gift-certificates.options')); ?>" class="row g-3">
          <?php echo csrf_field(); ?>
          <?php echo method_field('PUT'); ?>
          <?php for($i = 0; $i < 4; $i++): ?>
            <div class="col-12 col-md-3">
              <label class="form-label">Variant <?php echo e($i + 1); ?></label>
              <input
                type="number"
                name="options[]"
                class="form-control"
                min="1000"
                step="1000"
                value="<?php echo e(old("options.$i", $giftCertificateOptions[$i] ?? '')); ?>"
                placeholder="300000">
            </div>
          <?php endfor; ?>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn-p primary">
              <i class="bi bi-floppy"></i>
              <span>Saqlash</span>
            </button>
          </div>
        </form>
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
    </div>
    <div class="col-12 col-xl-5">
      <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Faol nominal','meta' => ''.e(count($giftCertificateOptions)).' ta variant']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Faol nominal','meta' => ''.e(count($giftCertificateOptions)).' ta variant']); ?>
        <div class="d-flex flex-wrap gap-2">
          <?php $__empty_1 = true; $__currentLoopData = $giftCertificateOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis"><?php echo e(number_format($amount)); ?> UZS</span>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span class="text-secondary">Variant yo‘q.</span>
          <?php endif; ?>
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
    </div>
  </div>

  <div class="row g-3">
    <?php $__currentLoopData = [
      ['Jami', $counts['all'] ?? 0, 'bi-gift', 'primary'],
      ['Faol', $counts['active'] ?? 0, 'bi-send', 'info'],
      ['Ishlatilgan', $counts['used'] ?? 0, 'bi-check-circle', 'success'],
      ['Bekor', $counts['cancelled'] ?? 0, 'bi-x-circle', 'danger'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
        'all' => ['Barchasi', $counts['all'] ?? 0],
        'pending_payment' => ['Kutilmoqda', $counts['pending_payment'] ?? 0],
        'active' => ['Faol', $counts['active'] ?? 0],
        'used' => ['Ishlatilgan', $counts['used'] ?? 0],
        'cancelled' => ['Bekor qilingan', $counts['cancelled'] ?? 0],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Sertifikatlar jadvali','meta' => $certs->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sertifikatlar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($certs->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Kod</th>
            <th>Sotib olgan</th>
            <th>Qabul qiluvchi</th>
            <th class="text-end">Miqdor</th>
            <th>Holat</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $certs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusClass = match($cert->status) {
                'active', 'sent' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'used' => 'text-bg-secondary',
                'paid' => 'text-bg-info-subtle border border-info-subtle text-info-emphasis',
                'cancelled', 'payment_cancelled' => 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $statusLabel = match($cert->status) {
                'pending_payment' => 'To‘lov kutilmoqda',
                'paid' => 'To‘landi',
                'active', 'sent' => 'Faol',
                'used' => 'Ishlatildi',
                'cancelled' => 'Bekor qilindi',
                'payment_cancelled' => 'To‘lovsiz bekor',
                default => $cert->status,
              };
            ?>
            <tr>
              <td><code class="kc-inline-code"><?php echo e($cert->code); ?></code></td>
              <td>
                <?php if($cert->buyer): ?>
                  <a href="<?php echo e(route('admin.users.show', $cert->buyer_user_id)); ?>" class="fw-semibold text-decoration-none">
                    <?php echo e($cert->buyer->name); ?> <?php echo e($cert->buyer->lastname); ?>

                  </a>
                  <div class="small text-secondary"><?php echo e($cert->buyer->phone_number); ?></div>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($cert->recipient): ?>
                  <a href="<?php echo e(route('admin.users.show', $cert->recipient_user_id)); ?>" class="fw-semibold text-decoration-none">
                    <?php echo e($cert->recipient->name); ?>

                  </a>
                <?php elseif($cert->recipient_name || $cert->recipient_phone): ?>
                  <div><?php echo e($cert->recipient_name ?: '—'); ?></div>
                  <div class="small text-secondary"><?php echo e($cert->recipient_phone); ?></div>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end fw-semibold"><?php echo e(number_format($cert->nominal_uzs)); ?> UZS</td>
              <td><span class="badge rounded-pill <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
              <td class="text-secondary text-nowrap"><?php echo e($cert->created_at?->format('d.m.Y')); ?></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="<?php echo e(route('admin.gift-certificates.show', $cert)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if(!in_array($cert->status, ['used', 'cancelled'])): ?>
                    <form method="POST" action="<?php echo e(route('admin.gift-certificates.cancel', $cert)); ?>" onsubmit="return confirm('Bekor qilinsinmi?')">
                      <?php echo csrf_field(); ?>
                      <button class="btn btn-sm btn-light border kc-table-action text-danger" title="Bekor qilish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="text-center py-5 text-secondary">Sertifikat topilmadi.</td></tr>
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

  <?php if($certs->hasPages()): ?>
    <div><?php echo e($certs->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/gift-certificates/index.blade.php ENDPATH**/ ?>