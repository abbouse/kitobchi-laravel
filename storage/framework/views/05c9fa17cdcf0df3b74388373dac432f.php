<?php $__env->startSection('title', 'Sotuvchilar'); ?>
<?php $__env->startSection('page-title', 'Sotuvchilar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Sotuvchilar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Do'konlar va sotuvchilar boshqaruvi <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.sellers.export', request()->all())); ?>" class="btn-p ghost">
        <i class="bi bi-download"></i> Export
      </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>



<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <?php $__currentLoopData = [
    ['Kutilmoqda',    $counts['pending'],  'warning', 'bi-hourglass'],
    ['Tasdiqlangan',  $counts['approved'], 'success', 'bi-shop-window'],
    ['Rad etilgan',   $counts['rejected'], 'danger',  'bi-x-circle'],
    ['Jami',          $counts['all'],      'accent',  'bi-grid'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-<?php echo e($c); ?>)"><?php echo e($v); ?></div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em"><?php echo e($l); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'pending'  => 'Kutilmoqda',
    'approved' => 'Tasdiqlangan',
    'rejected' => 'Rad etilgan',
    'all'      => 'Barchasi',
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(route('panel.sellers.index', array_merge(request()->except('tab','page'), ['tab'=>$key]))); ?>"
     class="tab-pill <?php echo e($tab===$key ? 'active' : ''); ?>">
    <?php echo e($label); ?>

    <span class="tab-count"><?php echo e($counts[$key]); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" action="<?php echo e(route('panel.sellers.index')); ?>" id="sellerFilter">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="filter-bar fade-up mb-3">
    <div class="search-box" style="width:220px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>"
             placeholder="Do'kon nomi, telefon, ID...">
    </div>
    <select name="region" class="p-form-control" style="width:160px"
            onchange="sellerFilter.submit()">
      <option value="">Barcha viloyat</option>
      <?php $__currentLoopData = $regions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($r); ?>" <?php echo e(request('region')===$r ? 'selected' : ''); ?>><?php echo e($r); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>
    <?php if(request()->hasAny(['search','region'])): ?>
    <a href="<?php echo e(route('panel.sellers.index', ['tab'=>$tab])); ?>" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    <?php endif; ?>
  </div>
</form>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Sotuvchilar ro'yxati</div>
    <div class="p-card-sub"><?php echo e($sellers->total()); ?> ta natija</div>
  </div>
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th><th>Do'kon</th><th>Telefon</th><th>Viloyat</th>
          <th>Faoliyat</th><th>Balans</th><th>Holat</th><th>Kitob</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $sellers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seller): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $st = trim((string)($seller->status ?? ''));
          $stCls = match($st){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
          $stLbl = match($st){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda' };
          $types = $seller->activity_types; // accessor har doim array qaytaradi
        ?>
        <tr>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
              #<?php echo e($seller->id); ?>

            </span>
          </td>
          <td>
            <div class="flex items-center gap-2">
              <div style="width:34px;height:34px;border-radius:8px;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-warning),#f97316);
                          display:flex;align-items:center;justify-content:center;
                          font-size:14px;font-weight:700;color:#fff">
                <?php if($seller->photo): ?>
                  <img src="<?php echo e(Storage::url($seller->photo)); ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($seller->shop_name, 0, 1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  <?php echo e($seller->shop_name); ?>

                </div>
                <div style="font-size:11px;color:var(--p-hint)">
                  <?php echo e($seller->firstname); ?> <?php echo e($seller->lastname); ?>

                </div>
              </div>
            </div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            <?php echo e($seller->phone_number); ?>

          </td>
          <td style="font-size:13px;color:var(--p-muted)"><?php echo e($seller->region); ?></td>
          <td>
            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <span class="s-pill accent"
                    style="font-size:10px;padding:2px 7px;margin-right:2px"><?php echo e($type); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:500;color:var(--p-text)">
            <?php echo e(number_format($seller->balance ?? 0)); ?>

            <span style="font-size:11px;color:var(--p-hint)">UZS</span>
          </td>
          <td>
            <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;
                     color:var(--p-text)">
            <?php echo e($seller->books_count); ?>

          </td>
          <td>
            <div class="flex gap-1 items-center">
              <?php if($st !== 'approved'): ?>
              <form method="POST" action="<?php echo e(route('panel.sellers.approve', $seller)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              <?php endif; ?>
              <?php if($st !== 'rejected'): ?>
              <form method="POST" action="<?php echo e(route('panel.sellers.reject', $seller)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p danger sm" title="Rad etish">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              <?php endif; ?>
              <a href="<?php echo e(route('panel.sellers.show', $seller)); ?>" class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="<?php echo e(route('panel.sellers.edit', $seller)); ?>" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-shop" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Sotuvchilar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($sellers->hasPages()): ?>
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($sellers->firstItem()); ?>–<?php echo e($sellers->lastItem()); ?> / <?php echo e($sellers->total()); ?>

    </div>
    <div class="p-pagination">
      <?php if($sellers->onFirstPage()): ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      <?php else: ?>
        <a href="<?php echo e($sellers->previousPageUrl()); ?>" class="p-page-btn">
          <i class="bi bi-chevron-left"></i>
        </a>
      <?php endif; ?>
      <?php $__currentLoopData = $sellers->getUrlRange(
        max(1, $sellers->currentPage()-2),
        min($sellers->lastPage(), $sellers->currentPage()+2)
      ); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>"
           class="p-page-btn <?php echo e($page===$sellers->currentPage() ? 'active' : ''); ?>">
          <?php echo e($page); ?>

        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($sellers->hasMorePages()): ?>
        <a href="<?php echo e($sellers->nextPageUrl()); ?>" class="p-page-btn">
          <i class="bi bi-chevron-right"></i>
        </a>
      <?php else: ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/sellers/index.blade.php ENDPATH**/ ?>