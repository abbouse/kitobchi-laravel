<?php $__env->startSection('title', 'Kuryerlar'); ?>
<?php $__env->startSection('page-title', 'Kuryerlar'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Kuryerlar</h1>
    <p class="page-sub">Barcha kuryerlar boshqaruvi</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo e(route('panel.couriers.export', request()->all())); ?>" class="btn-p ghost">
      <i class="bi bi-download"></i> Export
    </a>
    <a href="<?php echo e(route('panel.couriers.create')); ?>" class="btn-p primary">
      <i class="bi bi-plus-lg"></i> Yangi kuryer
    </a>
  </div>
</div>


<div class="row g-3 mb-4 fade-up">
  <?php $__currentLoopData = [
    ['Jami',        $counts['all'],      'accent',  'bi-bicycle'],
    ['Tasdiqlangan',$counts['approved'], 'success', 'bi-check-circle'],
    ['Kutilmoqda',  $counts['pending'],  'warning', 'bi-hourglass'],
    ['Rad etilgan', $counts['rejected'], 'danger',  'bi-x-circle'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:700;font-family:'DM Mono',monospace;
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
    'all'      => ['Barchasi',      $counts['all']],
    'approved' => ['Tasdiqlangan',  $counts['approved']],
    'pending'  => ['Kutilmoqda',    $counts['pending']],
    'rejected' => ['Rad etilgan',   $counts['rejected']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(route('panel.couriers.index', array_merge(request()->except('tab','page'), ['tab'=>$key]))); ?>"
     class="tab-pill <?php echo e($tab===$key ? 'active' : ''); ?>">
    <?php echo e($label); ?>

    <span class="tab-count"><?php echo e($cnt); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" action="<?php echo e(route('panel.couriers.index')); ?>" id="courierFilter">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="filter-bar fade-up mb-3">
    <div class="search-box" style="width:200px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>"
             placeholder="Ism, telefon, ID...">
    </div>
    <select name="region" class="p-form-control" style="width:160px"
            onchange="courierFilter.submit()">
      <option value="">Barcha viloyat</option>
      <?php $__currentLoopData = $regions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($r); ?>" <?php echo e(request('region')===$r ? 'selected' : ''); ?>><?php echo e($r); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn-p primary">
      <i class="bi bi-funnel"></i> Filter
    </button>
    <?php if(request()->hasAny(['search','region'])): ?>
    <a href="<?php echo e(route('panel.couriers.index', ['tab'=>$tab])); ?>" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    <?php endif; ?>
  </div>
</form>


<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kuryer</th>
          <th>Telefon</th>
          <th>Viloyat</th>
          <th style="text-align:right">Balans</th>
          <th>Holat</th>
          <th>Qo'shilgan</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $couriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $courier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          [$stCls, $stLbl] = match($courier->status) {
            'approved' => ['success', 'Tasdiqlangan'],
            'rejected' => ['danger',  'Rad etildi'],
            default    => ['warning', 'Kutilmoqda'],
          };
        ?>
        <tr>
          <td>
            <span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">
              #<?php echo e($courier->id); ?>

            </span>
          </td>

          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,#14b8a6,#0d9488);
                          display:flex;align-items:center;justify-content:center;
                          font-size:13px;font-weight:700;color:#fff">
                <?php if($courier->photo): ?>
                  <img src="<?php echo e(Storage::url($courier->photo)); ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($courier->first_name, 0, 1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  <?php echo e($courier->first_name); ?> <?php echo e($courier->last_name); ?>

                </div>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e($courier->region); ?></div>
              </div>
            </div>
          </td>

          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)">
            <?php echo e($courier->phone_number); ?>

          </td>

          <td style="font-size:13px;color:var(--p-muted)"><?php echo e($courier->region); ?></td>

          <td style="text-align:right;font-family:'DM Mono',monospace;
                     font-weight:500;color:var(--p-text)">
            <?php echo e(number_format($courier->balance ?? 0)); ?>

            <span style="font-size:11px;color:var(--p-hint)">UZS</span>
          </td>

          <td>
            <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
          </td>

          <td style="font-size:12px;color:var(--p-hint);font-family:'DM Mono',monospace">
            <?php echo e($courier->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="d-flex gap-1 align-items-center">
              <a href="<?php echo e(route('panel.couriers.show', $courier)); ?>"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="<?php echo e(route('panel.couriers.edit', $courier)); ?>"
                 class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>

              
              <?php if($courier->status === 'pending'): ?>
              <form method="POST"
                    action="<?php echo e(route('panel.couriers.approve', $courier)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              <form method="POST"
                    action="<?php echo e(route('panel.couriers.reject', $courier)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p danger sm" title="Rad etish">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              <?php elseif($courier->status === 'rejected'): ?>
              <form method="POST"
                    action="<?php echo e(route('panel.couriers.approve', $courier)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p ghost sm" title="Qayta tasdiqlash"
                        style="color:var(--p-success)">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              <?php endif; ?>

              <form method="POST"
                    action="<?php echo e(route('panel.couriers.destroy', $courier)); ?>"
                    onsubmit="return confirm('Kuryerni o\'chirishni tasdiqlaysizmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-bicycle"
               style="font-size:32px;display:block;margin-bottom:8px"></i>
            Kuryerlar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($couriers->hasPages()): ?>
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($couriers->firstItem()); ?>–<?php echo e($couriers->lastItem()); ?> / <?php echo e($couriers->total()); ?>

    </div>
    <?php echo e($couriers->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/couriers/index.blade.php ENDPATH**/ ?>