<?php $__env->startSection('title', 'Gift Sertifikatlar'); ?>
<?php $__env->startSection('page-title', 'Gift Sertifikatlar'); ?>

<?php $__env->startSection('content'); ?>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Gift sertifikatlar</div>
    <div class="a122-index-header__meta"><?php echo e($certs->total()); ?> ta sertifikat topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Kod, telefon yoki ism bo'yicha qidiring">
    </form>
  </div>
</div>


<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <?php $__currentLoopData = [
    ['Jami',        $counts['all'],             'accent',  'bi-gift'],
    ['Faol',        $counts['active'],          'info',    'bi-send'],
    ['Ishlatilgan', $counts['used'],             'success', 'bi-check-circle'],
    ['Bekor',       $counts['cancelled'],        'danger',  'bi-x-circle'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:700;font-family:'JetBrains Mono',monospace;
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
    'active'          => ['Faol',             $counts['active']],
    'used'            => ['Ishlatilgan',      $counts['used']],
    'cancelled'       => ['Bekor qilingan',   $counts['cancelled']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => [$l, $c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-count"><?php echo e($c); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
            'sent'             => 'success',
            'cancelled'        => 'danger',
            'payment_cancelled'=> 'danger',
            'pending_payment'  => 'warning',
            default            => 'warning',
          };
          $stLbl = match($c->status){
            'pending_payment'  => "To'lov kutilmoqda",
            'paid'             => "To'landi",
            'active'           => 'Faol',
            'sent'             => 'Faol',
            'used'             => 'Ishlatildi',
            'cancelled'        => 'Bekor qilindi',
            'payment_cancelled'=> "To'lovsiz bekor",
            default            => $c->status,   // ← 'default' emas, default keyword
          };
        ?>
        <tr>
          <td>
            <code style="font-family:'JetBrains Mono',monospace;font-size:12px;
                         font-weight:600;color:var(--p-accent);
                         background:var(--p-accent-d);padding:2px 8px;border-radius:5px">
              <?php echo e($c->code); ?>

            </code>
          </td>

          <td>
            <?php if($c->buyer): ?>
            <a href="<?php echo e(route('admin.users.show',$c->buyer_user_id)); ?>"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              <?php echo e($c->buyer->name); ?> <?php echo e($c->buyer->lastname); ?>

            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($c->buyer->phone_number); ?>

            </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td>
            <?php if($c->recipient): ?>
            <a href="<?php echo e(route('admin.users.show',$c->recipient_user_id)); ?>"
               style="font-size:12.5px;color:var(--p-text);text-decoration:none">
              <?php echo e($c->recipient->name); ?>

            </a>
            <?php elseif($c->recipient_name || $c->recipient_phone): ?>
            <div style="font-size:12.5px;color:var(--p-muted)">
              <?php echo e($c->recipient_name); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($c->recipient_phone); ?>

            </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:700;color:var(--p-success);font-size:13px">
            <?php echo e(number_format($c->nominal_uzs)); ?> UZS
          </td>

          <td><span class="s-pill <?php echo e($stCls); ?>" style="font-size:10px"><?php echo e($stLbl); ?></span></td>

          <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace;
                     white-space:nowrap">
            <?php echo e($c->created_at?->format('d.m.Y')); ?>

          </td>

          <td>
            <div class="flex gap-1">
              <a href="<?php echo e(route('admin.gift-certificates.show',$c)); ?>"
                 class="btn-p ghost sm"><i class="bi bi-eye"></i></a>

              <?php if(!in_array($c->status,['used','cancelled'])): ?>
              <form method="POST"
                    action="<?php echo e(route('admin.gift-certificates.cancel',$c)); ?>"
                    onsubmit="return confirm('Bekor qilinsinmi?')">
                <?php echo csrf_field(); ?>
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
  <div class="flex justify-between items-center px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($certs->firstItem()); ?>–<?php echo e($certs->lastItem()); ?> / <?php echo e($certs->total()); ?>

    </div>
    <?php echo e($certs->links('a122.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/gift-certificates/index.blade.php ENDPATH**/ ?>