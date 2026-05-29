<?php $__env->startSection('title', 'API mijozlar'); ?>
<?php $__env->startSection('page-title', 'API mijozlar'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Admin','title' => 'API mijozlar','subtitle' => ''.e($clients->total()).' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Admin','title' => 'API mijozlar','subtitle' => ''.e($clients->total()).' ta yozuv']); ?>
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Nomi, App ID yoki huquq" class="form-control">
    </form>
    <a href="<?php echo e(route('admin.api-clients.logs')); ?>" class="btn btn-light border">Audit</a>
    <a href="<?php echo e(route('admin.api-clients.docs')); ?>" class="btn btn-light border">Docs</a>
    <a href="<?php echo e(route('admin.api-clients.create')); ?>" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
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

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      <?php $__currentLoopData = [
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Nofaol', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="nav-link <?php echo e($tab === $key ? 'active' : ''); ?>">
          <?php echo e($label); ?>

          <span class="badge rounded-pill <?php echo e($tab === $key ? 'text-bg-light' : 'text-bg-secondary'); ?>"><?php echo e(number_format($count)); ?></span>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <div class="row g-3">
    <?php $__currentLoopData = [
      ['Jami', $counts['all'] ?? 0, 'bi-key', 'primary'],
      ['Faol', $counts['active'] ?? 0, 'bi-check-circle', 'success'],
      ['Nofaol', $counts['inactive'] ?? 0, 'bi-x-circle', 'danger'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="col-12 col-md-4">
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

  <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Integratsiyalar jadvali','meta' => $clients->total() . ' ta yozuv']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Integratsiyalar jadvali','meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($clients->total() . ' ta yozuv')]); ?>
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nomi</th>
            <th>App ID</th>
            <th>Secret</th>
            <th>Huquqlar</th>
            <th>Limit</th>
            <th>Holat</th>
            <th>Yaratildi</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $abilities = is_string($client->abilities)
                ? json_decode($client->abilities, true)
                : (is_array($client->abilities) ? $client->abilities : []);
            ?>
            <tr>
              <td class="text-secondary">#<?php echo e($client->id); ?></td>
              <td class="fw-semibold"><?php echo e($client->name); ?></td>
              <td>
                <div class="d-inline-flex align-items-center gap-2">
                  <code class="kc-inline-code"><?php echo e($client->app_id); ?></code>
                  <button type="button" onclick="copyText('<?php echo e($client->app_id); ?>')" class="btn btn-sm btn-light border kc-table-action" title="Nusxalash">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </td>
              <td>
                <div class="d-inline-flex align-items-center gap-2">
                  <code id="secret-<?php echo e($client->id); ?>" class="kc-inline-code text-secondary"><?php echo e(str_repeat('•', 12)); ?><?php echo e(substr($client->app_secret, -4)); ?></code>
                  <button type="button" onclick="toggleSecret(<?php echo e($client->id); ?>, '<?php echo e(addslashes($client->app_secret)); ?>')" class="btn btn-sm btn-light border kc-table-action" id="eye-<?php echo e($client->id); ?>" title="Ko‘rsatish">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button type="button" onclick="copyText('<?php echo e(addslashes($client->app_secret)); ?>')" class="btn btn-sm btn-light border kc-table-action" title="Nusxalash">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  <?php $__empty_2 = true; $__currentLoopData = $abilities ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ability): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                    <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis"><?php echo e($ability); ?></span>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                    <span class="text-secondary">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="small text-secondary text-nowrap">
                <?php echo e($client->rate_limit_per_second ?? 8); ?>/s · <?php echo e($client->rate_limit_per_minute ?? 240); ?>/min
              </td>
              <td>
                <form method="POST" action="<?php echo e(route('admin.api-clients.toggle', $client)); ?>" class="d-inline">
                  <?php echo csrf_field(); ?>
                  <?php echo method_field('PATCH'); ?>
                  <button class="badge rounded-pill border <?php echo e($client->is_active ? 'text-bg-success-subtle border-success-subtle text-success-emphasis' : 'text-bg-danger-subtle border-danger-subtle text-danger-emphasis'); ?>" title="Holatni o‘zgartirish">
                    <?php echo e($client->is_active ? 'Faol' : 'Nofaol'); ?>

                  </button>
                </form>
              </td>
              <td class="text-secondary text-nowrap"><?php echo e($client->created_at?->format('d.m.Y')); ?></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="<?php echo e(route('admin.api-clients.edit', $client)); ?>" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="<?php echo e(route('admin.api-clients.regenerate', $client)); ?>" onsubmit="return confirm('Eski secret kalit endi ishlamaydi. Davom etilsinmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <button class="btn btn-sm btn-light border kc-table-action" title="Yangi kalit">
                      <i class="bi bi-arrow-repeat"></i>
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('admin.api-clients.destroy', $client)); ?>" onsubmit="return confirm('O‘chirilsinmi?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="9" class="text-center py-5 text-secondary">API mijoz topilmadi.</td></tr>
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

  <?php if($clients->hasPages()): ?>
    <div><?php echo e($clients->links('a122.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const secretVisible = {};

function toggleSecret(id, secret) {
  const el = document.getElementById('secret-' + id);
  const eye = document.getElementById('eye-' + id).querySelector('i');
  if (secretVisible[id]) {
    el.textContent = '•'.repeat(12) + secret.slice(-4);
    eye.className = 'bi bi-eye';
    secretVisible[id] = false;
  } else {
    el.textContent = secret;
    eye.className = 'bi bi-eye-slash';
    secretVisible[id] = true;
  }
}

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => {
    const toast = document.createElement('div');
    toast.textContent = 'Nusxalandi';
    toast.className = 'kc-copy-toast';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 1800);
  });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/api-clients/index.blade.php ENDPATH**/ ?>