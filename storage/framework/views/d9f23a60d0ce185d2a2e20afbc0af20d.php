<?php $__env->startSection('title', $courier->first_name.' '.$courier->last_name); ?>
<?php $__env->startSection('page-title', $courier->first_name.' '.$courier->last_name); ?>

<?php $__env->startSection('content'); ?>

<?php
  $cs    = trim((string)($courier->status ?? ''));
  $stCls = match($cs) { 'approved'=>'success', 'rejected'=>'danger', default=>'warning' };
  $stLbl = match($cs) { 'approved'=>'Tasdiqlangan', 'rejected'=>'Rad etildi', default=>'Kutilmoqda' };
?>

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.couriers.index')); ?>" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title"><?php echo e($courier->first_name); ?> <?php echo e($courier->last_name); ?></h1>
      <p class="page-sub" style="display:flex;align-items:center;gap:8px">
        ID: #<?php echo e($courier->id); ?>

        <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <?php if($cs === 'pending'): ?>
      <form method="POST" action="<?php echo e(route('panel.couriers.approve', $courier)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
      </form>
      <form method="POST" action="<?php echo e(route('panel.couriers.reject', $courier)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Rad etish</button>
      </form>
    <?php elseif($cs === 'rejected'): ?>
      <form method="POST" action="<?php echo e(route('panel.couriers.approve', $courier)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <button class="btn-p ghost">
          <i class="bi bi-arrow-counterclockwise"></i> Qayta tasdiqlash
        </button>
      </form>
    <?php elseif($cs === 'approved'): ?>
      <form method="POST" action="<?php echo e(route('panel.couriers.reject', $courier)); ?>"
            onsubmit="return confirm('Kuryerni bloklaysizmi?')">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <button class="btn-p danger ghost">
          <i class="bi bi-slash-circle"></i> Bloklash
        </button>
      </form>
    <?php endif; ?>
    <a href="<?php echo e(route('panel.couriers.edit', $courier)); ?>" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>
</div>


<div class="row g-3 mb-4 fade-up">
  <?php $__currentLoopData = [
    [$orderCount,                                  'Buyurtmalar',  'accent',  'bi-bicycle'],
    [number_format($totalEarned/1000).'K UZS',     'Jami topdi',   'success', 'bi-cash-stack'],
    [number_format($pendingPay/1000).'K UZS',      'Kutilmoqda',   'warning', 'bi-hourglass-split'],
    [number_format($courier->balance ?? 0).' UZS', 'Balans',       'info',    'bi-wallet2'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$val,$lbl,$clr,$icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:16px">
      <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;font-size:18px;
                  background:var(--p-<?php echo e($clr); ?>-d,var(--p-elevated));
                  color:var(--p-<?php echo e($clr); ?>);display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;
                    color:var(--p-text)"><?php echo e($val); ?></div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em"><?php echo e($lbl); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="row g-3">

  
  <div class="col-xl-4">

    <div class="p-card mb-3 fade-up">
      <div style="text-align:center;padding:24px 20px 16px">
        <div style="width:72px;height:72px;border-radius:50%;margin:0 auto 12px;overflow:hidden;
                    background:linear-gradient(135deg,#14b8a6,#0d9488);
                    display:flex;align-items:center;justify-content:center;
                    font-size:26px;font-weight:700;color:#fff">
          <?php if($courier->photo): ?>
            <img src="<?php echo e(asset('storage/'.$courier->photo)); ?>"
                 style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?php echo e(strtoupper(substr($courier->first_name, 0, 1))); ?>

          <?php endif; ?>
        </div>
        <div style="font-size:17px;font-weight:700;color:var(--p-text)">
          <?php echo e($courier->first_name); ?> <?php echo e($courier->last_name); ?>

        </div>
        <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
          <?php echo e($courier->phone_number); ?>

        </div>
        <div class="mt-2">
          <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
        </div>
      </div>

      <div style="border-top:1px solid var(--p-border);padding:16px 20px 8px">
        <?php $__currentLoopData = [
          ['bi-telephone', 'Telefon',    $courier->phone_number],
          ['bi-geo-alt',   'Viloyat',    $courier->region],
          ['bi-wallet2',   'Balans',     number_format($courier->balance ?? 0).' UZS'],
          ['bi-calendar',  "Qo'shildi", $courier->created_at?->format('d.m.Y')],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon, $label, $value]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="d-flex align-items-start gap-3 mb-3">
          <div style="width:28px;height:28px;border-radius:7px;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi <?php echo e($icon); ?>" style="font-size:12px;color:var(--p-muted)"></i>
          </div>
          <div>
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em"><?php echo e($label); ?></div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($value); ?></div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <?php if($courier->fcm_token): ?>
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em;margin-bottom:4px">FCM Token</div>
        <div style="font-family:'DM Mono',monospace;font-size:10px;color:var(--p-muted);
                    word-break:break-all;background:var(--p-elevated);
                    padding:6px 8px;border-radius:6px">
          <?php echo e(Str::limit($courier->fcm_token, 60)); ?>

        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if($devices->count()): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-phone me-1"></i> Qurilmalar</div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e($devices->count()); ?> ta</span>
      </div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:9px 0;border-bottom:1px solid var(--p-border);
                    <?php echo e($loop->last ? 'border-bottom:none' : ''); ?>">
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">
            <?php echo e($dev->device_name ?: "Noma'lum qurilma"); ?>

          </div>
          <div style="font-size:11px;color:var(--p-hint);margin-top:1px">
            <?php echo e($dev->platform); ?>

            <?php if($dev->created_at): ?> · <?php echo e(\Carbon\Carbon::parse($dev->created_at)->format('d.m.Y')); ?> <?php endif; ?>
            <?php if($dev->fcm_token ?? null): ?>
              <span class="s-pill success" style="font-size:9px;margin-left:4px">FCM</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if($banLogs->count()): ?>
    <div class="p-card fade-up" style="border-color:rgba(255,92,106,.2);background:var(--p-danger-d)">
      <div class="p-card-header">
        <div class="p-card-title" style="color:var(--p-danger)">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> Ban loglari
        </div>
      </div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = $banLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:10px 0;border-bottom:1px solid rgba(255,92,106,.15);
                    <?php echo e($loop->last ? 'border-bottom:none' : ''); ?>">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="s-pill <?php echo e(($log->type ?? '')=='warning' ? 'warning' : 'muted'); ?>"
                  style="font-size:10px">
              <?php echo e(($log->type ?? '') === 'warning' ? 'Ogohlantirish' : 'Ban'); ?>

            </span>
            <span style="font-size:10px;color:var(--p-hint)">
              <?php echo e($log->created_at?->format('d.m.Y')); ?>

            </span>
          </div>
          <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($log->title); ?></div>
          <?php if($log->message ?? null): ?>
          <div style="font-size:12px;color:var(--p-muted);margin-top:2px">
            <?php echo e(Str::limit($log->message, 80)); ?>

          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="col-xl-8">

    <?php $activeTab = request('section', 'orders'); ?>
    <div class="d-flex gap-2 flex-wrap mb-3 fade-up">
      <?php $__currentLoopData = [
        ['orders',       'Buyurtmalar',    'bi-bicycle',    $orderCount],
        ['transactions', 'Tranzaksiyalar', 'bi-credit-card', null],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key,$lbl,$icon,$cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['section'=>$key])); ?>"
         class="btn-p <?php echo e($activeTab===$key?'':'ghost'); ?> sm" style="gap:5px">
        <i class="bi <?php echo e($icon); ?>"></i> <?php echo e($lbl); ?>

        <?php if($cnt !== null): ?><span class="tab-badge"><?php echo e($cnt); ?></span><?php endif; ?>
      </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if($activeTab === 'orders'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">So'nggi buyurtmalar</div>
        <a href="<?php echo e(route('panel.courier-orders.index', ['courier_id'=>$courier->id])); ?>"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>#Buyurtma</th><th>Mijoz</th><th>Summa</th>
              <th>Kuryer haq</th><th>Status</th><th>Sana</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $oCls = match($order->status ?? '') {
                'delivered'   => 'ob-c',
                'in_delivery' => 'ob-b',
                'pending'     => 'ob-a',
                'rejected'    => 'ob-f',
                default       => 'ob-p',
              };
              $oLbl = match($order->status ?? '') {
                'delivered'   => 'Yetkazildi',
                'in_delivery' => "Yo'lda",
                'pending'     => 'Kutilmoqda',
                'rejected'    => 'Rad etildi',
                default       => $order->status ?? '—',
              };
            ?>
            <tr>
              <td>
                <a href="<?php echo e(route('panel.orders.show', $order->order_id)); ?>"
                   style="font-family:'DM Mono',monospace;color:var(--p-accent);font-weight:600">
                  #<?php echo e($order->order_id); ?>

                </a>
              </td>
              <td>
                <?php if($order->user): ?>
                <div class="d-flex align-items-center gap-2">
                  <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                              display:flex;align-items:center;justify-content:center;
                              font-size:11px;font-weight:600;color:#fff">
                    <?php if($order->user->avatar): ?>
                      <img src="<?php echo e($order->user->avatar); ?>"
                           style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?><?php echo e(strtoupper(substr($order->user->name ?? 'U', 0, 1))); ?><?php endif; ?>
                  </div>
                  <span style="font-size:12px;color:var(--p-text)">
                    <?php echo e($order->user->name); ?> <?php echo e($order->user->lastname); ?>

                  </span>
                </div>
                <?php else: ?>
                  <span style="color:var(--p-hint);font-size:12px">#<?php echo e($order->user_id); ?></span>
                <?php endif; ?>
              </td>
              <td style="font-family:'DM Mono',monospace;font-weight:600;
                         font-size:13px;color:var(--p-text)">
                <?php echo e(number_format($order->amount)); ?>

              </td>
              <td style="font-family:'DM Mono',monospace;font-size:12px;
                         color:var(--p-success);font-weight:600">
                <?php echo e(number_format($order->courierPrice)); ?>

              </td>
              <td><span class="o-badge <?php echo e($oCls); ?>"><?php echo e($oLbl); ?></span></td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                <?php echo e($order->created_at?->format('d.m H:i')); ?>

              </td>
              <td>
                <a href="<?php echo e(route('panel.orders.show', $order->order_id)); ?>"
                   class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:30px;color:var(--p-hint)">
                Buyurtmalar yo'q
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if($activeTab === 'transactions'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div>
          <div class="p-card-title">Chiqim tranzaksiyalari</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
            Tasdiqlangan:
            <span style="color:var(--p-success);font-weight:600">
              <?php echo e(number_format($totalEarned)); ?> UZS
            </span>
            · Kutilmoqda:
            <span style="color:var(--p-warning);font-weight:600">
              <?php echo e(number_format($pendingPay)); ?> UZS
            </span>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>#</th><th>Karta</th><th>Miqdor</th>
              <th>Komissiya</th><th>Toza miqdor</th><th>Status</th><th>Sana</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $txS   = trim((string)($tx->status ?? ''));
              $txCls = match($txS) { 'approved'=>'success', 'rejected'=>'danger', default=>'warning' };
              $txLbl = match($txS) { 'approved'=>'Tasdiqlangan', 'rejected'=>'Rad etildi', default=>'Kutilmoqda' };
            ?>
            <tr>
              <td style="font-family:'DM Mono',monospace;color:var(--p-accent)">#<?php echo e($tx->id); ?></td>
              <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)">
                <?php echo e($tx->card ? '****'.substr($tx->card, -4) : '—'); ?>

              </td>
              <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-text)">
                <?php echo e(number_format($tx->amount)); ?>

              </td>
              <td style="font-size:12px;color:var(--p-danger)">
                <?php echo e($tx->commissionPercent ?? 0); ?>%
                <?php if($tx->commissionPrice ?? null): ?>
                  <span style="color:var(--p-hint)">(<?php echo e(number_format($tx->commissionPrice)); ?>)</span>
                <?php endif; ?>
              </td>
              <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-success)">
                <?php echo e(number_format($tx->netAmount ?? $tx->amount)); ?>

              </td>
              <td>
                <span class="s-pill <?php echo e($txCls); ?>" style="font-size:11px"><?php echo e($txLbl); ?></span>
              </td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                <?php echo e($tx->created_at?->format('d.m.Y H:i')); ?>

              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:30px;color:var(--p-hint)">
                Tranzaksiyalar yo'q
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/couriers/show.blade.php ENDPATH**/ ?>