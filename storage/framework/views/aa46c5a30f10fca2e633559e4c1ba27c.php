<?php $__env->startSection('title', 'Gift Sertifikatlar'); ?>
<?php $__env->startSection('page-title', 'Gift Sertifikatlar'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Gift Sertifikatlar</h1>
    <p class="page-sub">Foydalanuvchilar sotib olgan sovg'a sertifikatlari</p>
  </div>
</div>


<div class="row g-3 mb-4 fade-up">
  <?php $__currentLoopData = [
    ['Jami',        $counts['all'],             'accent',  'bi-gift'],
    ['Yuborilgan',  $counts['sent'],             'info',    'bi-send'],
    ['Ishlatilgan', $counts['used'],             'success', 'bi-check-circle'],
    ['Bekor',       $counts['cancelled'],        'danger',  'bi-x-circle'],
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
    'all'             => ['Barchasi',        $counts['all']],
    'pending_payment' => ['Kutilmoqda',       $counts['pending_payment']],
    'sent'            => ['Yuborilgan',       $counts['sent']],
    'used'            => ['Ishlatilgan',      $counts['used']],
    'cancelled'       => ['Bekor qilingan',   $counts['cancelled']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => [$l, $c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-count"><?php echo e($c); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="search-box" style="width:250px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="<?php echo e(request('search')); ?>"
           placeholder="Kod, telefon, ism...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
  <?php if(request('search')): ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['search'=>null])); ?>" class="btn-p ghost">
    <i class="bi bi-x"></i>
  </a>
  <?php endif; ?>
</form>


<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>Kod</th>
          <th>Sotib olgan</th>
          <th>Qabul qiluvchi</th>
          <th style="text-align:right">Miqdor</th>
          <th>Holat</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $certs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $stCls = match($c->status){
            'active'           => 'success',
            'used'             => 'muted',
            'paid'             => 'info',
            'sent'             => 'accent',
            'cancelled'        => 'danger',
            'payment_cancelled'=> 'danger',
            'pending_payment'  => 'warning',
            default            => 'warning',
          };
          $stLbl = match($c->status){
            'pending_payment'  => "To'lov kutilmoqda",
            'paid'             => "To'landi",
            'active'           => 'Faol',
            'sent'             => 'Yuborildi',
            'used'             => 'Ishlatildi',
            'cancelled'        => 'Bekor qilindi',
            'payment_cancelled'=> "To'lovsiz bekor",
            default            => $c->status,   // ← 'default' emas, default keyword
          };
        ?>
        <tr>
          <td>
            <code style="font-family:'DM Mono',monospace;font-size:12px;
                         font-weight:600;color:var(--p-accent);
                         background:var(--p-accent-d);padding:2px 8px;border-radius:5px">
              <?php echo e($c->code); ?>

            </code>
          </td>

          <td>
            <?php if($c->buyer): ?>
            <a href="<?php echo e(route('panel.users.show',$c->buyer_user_id)); ?>"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              <?php echo e($c->buyer->name); ?> <?php echo e($c->buyer->lastname); ?>

            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'DM Mono',monospace">
              <?php echo e($c->buyer->phone_number); ?>

            </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td>
            <?php if($c->recipient): ?>
            <a href="<?php echo e(route('panel.users.show',$c->recipient_user_id)); ?>"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              <?php echo e($c->recipient->name); ?>

            </a>
            <?php elseif($c->recipient_name || $c->recipient_phone): ?>
            <div style="font-size:12.5px;color:var(--p-muted)">
              <?php echo e($c->recipient_name); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint);font-family:'DM Mono',monospace">
              <?php echo e($c->recipient_phone); ?>

            </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td style="text-align:right;font-family:'DM Mono',monospace;
                     font-weight:700;color:var(--p-success);font-size:13px">
            <?php echo e(number_format($c->nominal_uzs)); ?> UZS
          </td>

          <td><span class="s-pill <?php echo e($stCls); ?>" style="font-size:10px"><?php echo e($stLbl); ?></span></td>

          <td style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace;
                     white-space:nowrap">
            <?php echo e($c->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="d-flex gap-1">
              <a href="<?php echo e(route('panel.gift-certificates.show',$c)); ?>"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>

              <?php if(!in_array($c->status,['used','cancelled'])): ?>
              <form method="POST"
                    action="<?php echo e(route('panel.gift-certificates.cancel',$c)); ?>"
                    onsubmit="return confirm('Bekor qilinsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn-p danger sm"><i class="bi bi-x-lg"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-gift" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Sertifikatlar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($certs->hasPages()): ?>
  <div class="d-flex justify-content-between align-items-center px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($certs->firstItem()); ?>–<?php echo e($certs->lastItem()); ?> / <?php echo e($certs->total()); ?>

    </div>
    <?php echo e($certs->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/gift-certificates/index.blade.php ENDPATH**/ ?>