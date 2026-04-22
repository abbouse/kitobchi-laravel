<?php $__env->startSection('title', 'API Mijozlar'); ?>
<?php $__env->startSection('page-title', 'API Mijozlar'); ?>

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
   <?php $__env->slot('heading', null, []); ?> API mijozlar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Tashqi ilovalar va servislar uchun App ID, Secret hamda ruxsat darajalari shu modulda boshqariladi. <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('admin.api-clients.create')); ?>" class="btn-p primary">Yangi mijoz</a>
   <?php $__env->endSlot(); ?>
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

<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'active' => ['Faol', $counts['active'] ?? 0],
    'inactive' => ['Nofaol', $counts['inactive'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key, 'page' => null])); ?>" class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
      <?php echo e($label); ?> <span><?php echo e($count); ?></span>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 mb-4">
  <?php $__currentLoopData = [
    [App\Models\ApiClient::count(),             'Jami',     'accent',  'bi-key'],
    [App\Models\ApiClient::where('is_active',1)->count(), 'Faol', 'success', 'bi-check-circle'],
    [App\Models\ApiClient::where('is_active',0)->count(), 'Nofaol','danger','bi-x-circle'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$v,$l,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
          <?php echo e($v); ?>

        </div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          <?php echo e($l); ?>

        </div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Integratsiyalar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($clients->total()); ?> ta API mijoz topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Nomi, App ID, secret yoki huquq bo'yicha qidiring">
    </form>
  </div>
</div>

<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Nomi</th>
          <th>App ID</th>
          <th>App Secret</th>
          <th>Huquqlar</th>
          <th>Holat</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#<?php echo e($c->id); ?></td>

          <td>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)"><?php echo e($c->name); ?></div>
          </td>

          <td>
            <div class="flex items-center gap-2">
              <code style="font-size:12px;color:var(--p-accent);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'JetBrains Mono',monospace">
                <?php echo e($c->app_id); ?>

              </code>
              <button onclick="copyText('<?php echo e($c->app_id); ?>')"
                      class="btn-p ghost sm" title="Nusxalash">
                <i class="bi bi-copy" style="font-size:11px"></i>
              </button>
            </div>
          </td>

          <td>
            <div class="flex items-center gap-2">
              <code id="secret-<?php echo e($c->id); ?>"
                    style="font-size:12px;color:var(--p-muted);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'JetBrains Mono',monospace;
                           letter-spacing:.05em">
                <?php echo e(str_repeat('•', 12)); ?><?php echo e(substr($c->app_secret, -4)); ?>

              </code>
              <button onclick="toggleSecret(<?php echo e($c->id); ?>, '<?php echo e(addslashes($c->app_secret)); ?>')"
                      class="btn-p ghost sm" id="eye-<?php echo e($c->id); ?>" title="Ko'rsatish">
                <i class="bi bi-eye" style="font-size:11px"></i>
              </button>
              <button onclick="copyText('<?php echo e(addslashes($c->app_secret)); ?>')"
                      class="btn-p ghost sm" title="Nusxalash">
                <i class="bi bi-copy" style="font-size:11px"></i>
              </button>
            </div>
          </td>

          <td>
            <?php
              $abilities = is_string($c->abilities)
                ? json_decode($c->abilities, true)
                : (is_array($c->abilities) ? $c->abilities : []);
            ?>
            <div class="flex flex-wrap gap-1">
              <?php $__currentLoopData = $abilities ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <span class="s-pill accent" style="font-size:9.5px"><?php echo e($ab); ?></span>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </td>

          <td>
            <form method="POST"
                  action="<?php echo e(route('admin.api-clients.toggle',$c)); ?>"
                  style="display:inline">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <button class="s-pill <?php echo e($c->is_active ? 'success' : 'danger'); ?>"
                      style="font-size:10px;border:none;cursor:pointer;padding:3px 10px"
                      title="Holat o'zgartirish">
                <?php echo e($c->is_active ? '● Faol' : '○ Nofaol'); ?>

              </button>
            </form>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($c->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="flex gap-1">
              <a href="<?php echo e(route('admin.api-clients.edit',$c)); ?>"
                 class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>

              <form method="POST"
                    action="<?php echo e(route('admin.api-clients.regenerate',$c)); ?>"
                    onsubmit="return confirm('Eski secret kalit endi ishlamaydi. Davom etilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p ghost sm" title="Yangi kalit yaratish"
                        style="color:var(--p-warning)">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
              </form>

              <form method="POST"
                    action="<?php echo e(route('admin.api-clients.destroy',$c)); ?>"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-key" style="font-size:28px;display:block;margin-bottom:8px"></i>
            API mijozlar yo'q
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($clients->hasPages()): ?>
  <div class="mt-4"><?php echo e($clients->links('a122.partials.pagination')); ?></div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const secretVisible = {};

function toggleSecret(id, secret) {
  const el  = document.getElementById('secret-' + id);
  const eye = document.getElementById('eye-' + id).querySelector('i');
  if (secretVisible[id]) {
    el.textContent = '•'.repeat(12) + secret.slice(-4);
    eye.className  = 'bi bi-eye';
    secretVisible[id] = false;
  } else {
    el.textContent = secret;
    eye.className  = 'bi bi-eye-slash';
    secretVisible[id] = true;
  }
}

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Toast
    const t = document.createElement('div');
    t.textContent = 'Nusxalandi!';
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:9999;
      background:var(--p-surface);border:1px solid var(--p-border);
      padding:8px 16px;border-radius:8px;font-size:13px;
      color:var(--p-success);box-shadow:0 4px 20px rgba(0,0,0,.15)`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 1800);
  });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/api-clients/index.blade.php ENDPATH**/ ?>