<?php $__env->startSection('title', $seller->shop_name); ?>
<?php $__env->startSection('page-title', $seller->shop_name); ?>

<?php $__env->startSection('content'); ?>

<?php
  $st    = trim((string)($seller->status ?? ''));
  $stCls = match($st){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
  $stLbl = match($st){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda' };
  $types = $seller->activity_types; // accessor — har doim array
?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.sellers.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.sellers.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e($seller->shop_name); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex gap-2 flex-wrap">
        <?php if($st !== 'approved'): ?>
        <form method="POST" action="<?php echo e(route('panel.sellers.approve', $seller)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
        </form>
        <?php endif; ?>
        <?php if($st !== 'rejected'): ?>
        <form method="POST" action="<?php echo e(route('panel.sellers.reject', $seller)); ?>"
              onsubmit="return confirm('Rad etasizmi?')">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Rad etish</button>
        </form>
        <?php endif; ?>
        <a href="<?php echo e(route('panel.sellers.edit', $seller)); ?>" class="btn-p ghost">
          <i class="bi bi-pencil"></i> Tahrirlash
        </a>
      </div>
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
    [$seller->books->count(),                          'Kitoblar',    'accent',  'bi-book'],
    [$seller->stationeries->count(),                   'Kanstovar',   'warning', 'bi-pencil-square'],
    [number_format($orderCount),                       'Buyurtmalar', 'success', 'bi-bag-check'],
    [number_format($totalRevenue/1_000_000,1).'M UZS', 'Daromad',     'info',    'bi-graph-up'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$val,$lbl,$clr,$icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:16px">
      <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;font-size:18px;
                  background:var(--p-<?php echo e($clr); ?>-d,var(--p-elevated));
                  color:var(--p-<?php echo e($clr); ?>);display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-text)"><?php echo e($val); ?></div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em"><?php echo e($lbl); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-3">

  
  <div class="xl:col-span-4">

    <div class="p-card mb-3 fade-up">
      <div style="text-align:center;padding:24px 20px 16px">
        <div style="width:72px;height:72px;border-radius:12px;margin:0 auto 12px;overflow:hidden;
                    background:linear-gradient(135deg,var(--p-warning),#f97316);
                    display:flex;align-items:center;justify-content:center;
                    font-size:28px;font-weight:700;color:#fff">
          <?php if($seller->photo): ?>
            <img src="<?php echo e(Storage::url($seller->photo)); ?>"
                 style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?php echo e(strtoupper(substr($seller->shop_name, 0, 1))); ?>

          <?php endif; ?>
        </div>
        <div style="font-size:17px;font-weight:700;color:var(--p-text)">
          <?php echo e($seller->shop_name); ?>

        </div>
        <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
          <?php echo e($seller->firstname); ?> <?php echo e($seller->lastname); ?>

        </div>
        <div class="flex justify-center gap-2 mt-2">
          <span class="s-pill <?php echo e($stCls); ?>" style="font-size:11px"><?php echo e($stLbl); ?></span>
          <?php if($seller->is_hidden): ?>
            <span class="s-pill danger" style="font-size:11px">Yashirin</span>
          <?php endif; ?>
        </div>
      </div>

      <div style="border-top:1px solid var(--p-border);padding:16px 20px 8px">
        <?php $__currentLoopData = [
          ['bi-telephone',   'Telefon',         $seller->phone_number],
          ['bi-geo-alt',     'Viloyat',          $seller->region],
          ['bi-star-fill',   'Reyting',          number_format($seller->rating ?? 0, 1).' / 5.0'],
          ['bi-bag-check',   'Muvaffaqiyatli',   number_format($seller->successful_orders ?? 0).' buyurtma'],
          ['bi-wallet2',     'Balans',           number_format($seller->balance ?? 0).' UZS'],
          ['bi-percent',     'Komissiya',        ($seller->commission_percent ? $seller->commission_percent.'%' : 'Global')],
          ['bi-calendar',    "Qo'shildi",        $seller->created_at?->format('d.m.Y')],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon,$label,$value]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-start gap-3 mb-3">
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

        <?php if(count($types)): ?>
        <div class="flex flex-wrap gap-1 mt-1 pb-2">
          <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="s-pill accent" style="font-size:11px"><?php echo e($type); ?></span>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if($isMainShop): ?>
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <a href="<?php echo e(route('panel.sellers.staff.create', $seller)); ?>" class="btn-p ghost"
           style="width:100%;justify-content:center">
          <i class="bi bi-person-plus"></i> Hodim qo'shish
        </a>
      </div>
      <?php else: ?>
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">Asosiy do'kon</div>
        <?php if($parentShop): ?>
        <a href="<?php echo e(route('panel.sellers.show', $parentShop)); ?>"
           style="font-size:13px;font-weight:500;color:var(--p-accent);text-decoration:none">
          <?php echo e($parentShop->shop_name); ?> →
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    
    <?php if($locations->count()): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-geo-alt mr-1"></i> Manzillar</div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e($locations->count()); ?> ta</span>
      </div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:9px 0;border-bottom:1px solid var(--p-border);
                    <?php echo e($loop->last ? 'border-bottom:none' : ''); ?>">
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">
            <?php echo e($loc->fullAddress); ?>

            <?php if($loc->is_main): ?>
              <span class="s-pill success ml-1" style="font-size:10px">Asosiy</span>
            <?php endif; ?>
          </div>
          <?php if($loc->description ?? null): ?>
          <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
            <?php echo e($loc->description); ?>

          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($banLogs->count()): ?>
    <div class="p-card mb-3 fade-up"
         style="border-color:rgba(255,92,106,.2);background:var(--p-danger-d)">
      <div class="p-card-header">
        <div class="p-card-title" style="color:var(--p-danger)">
          <i class="bi bi-exclamation-triangle-fill mr-1"></i> Ban loglari
        </div>
        <span class="s-pill danger" style="font-size:10px"><?php echo e($banLogs->count()); ?></span>
      </div>
      <div style="padding:0 18px 14px">
        <?php $__currentLoopData = $banLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:9px 0;border-bottom:1px solid rgba(255,92,106,.15);
                    <?php echo e($loop->last ? 'border-bottom:none' : ''); ?>">
          <div class="flex items-center justify-between mb-1">
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

  
  <div class="xl:col-span-8">

    <?php $activeTab = request('section', 'orders'); ?>
    <div class="flex gap-2 flex-wrap mb-3 fade-up">
      <?php $__currentLoopData = [
        ['orders',       'Buyurtmalar',    'bi-bag-check',  $orderCount],
        ['transactions', 'Tranzaksiyalar', 'bi-credit-card', null],
        ['staff',        'Hodimlar',       'bi-people',      $staff->count()],
        ['ads',          'Reklamalar',     'bi-megaphone',   $ads->count()],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key,$lbl,$icon,$cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['section'=>$key])); ?>"
         class="btn-p <?php echo e($activeTab===$key?'':'ghost'); ?> sm" style="gap:5px">
        <i class="bi <?php echo e($icon); ?>"></i> <?php echo e($lbl); ?>

        <?php if($cnt !== null): ?>
          <span class="tab-badge"><?php echo e($cnt); ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if($activeTab === 'orders'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">So'nggi buyurtmalar</div>
        <a href="<?php echo e(route('panel.seller-orders.index', ['seller_id'=>$seller->id])); ?>"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>#ID</th><th>Summa</th><th>Yetkazish</th><th>Status</th><th>Sana</th></tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $os = trim((string)($order->status ?? ''));
              $oCls = match($os){
                'completed'=>'success','processing'=>'info',
                'cancelled'=>'danger',default=>'warning'
              };
              $oLbl = match($os){
                'completed'=>'Yakunlandi','processing'=>'Jarayonda',
                'cancelled'=>'Bekor',default=>'Kutilmoqda'
              };
            ?>
            <tr>
              <td>
                <a href="<?php echo e(route('panel.seller-orders.show', $order->id)); ?>"
                   style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">
                  #<?php echo e($order->order_id ?? $order->id); ?>

                </a>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:500;color:var(--p-text)">
                <?php echo e(number_format($order->amount ?? 0)); ?> UZS
              </td>
              <td style="font-size:12px;color:var(--p-muted)">
                <?php echo e($order->delivery_type ?? '—'); ?>

              </td>
              <td><span class="s-pill <?php echo e($oCls); ?>" style="font-size:11px"><?php echo e($oLbl); ?></span></td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                <?php echo e($order->created_at?->format('d.m H:i')); ?>

              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">
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
        <div class="p-card-title">Tranzaksiyalar</div>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>#</th><th>Miqdor</th><th>Komissiya</th><th>Toza</th><th>Status</th><th>Sana</th></tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $txS   = trim((string)($tx->status ?? ''));
              $txCls = match($txS){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
              $txLbl = match($txS){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda' };
            ?>
            <tr>
              <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#<?php echo e($tx->id); ?></td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                <?php echo e(number_format($tx->amount ?? 0)); ?>

              </td>
              <td style="font-size:12px;color:var(--p-danger)">
                <?php echo e($tx->commissionPercent ?? 0); ?>%
                <?php if($tx->commissionPrice ?? null): ?>
                  <span style="color:var(--p-hint)">(<?php echo e(number_format($tx->commissionPrice)); ?>)</span>
                <?php endif; ?>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-success)">
                <?php echo e(number_format($tx->netAmount ?? $tx->amount ?? 0)); ?>

              </td>
              <td><span class="s-pill <?php echo e($txCls); ?>" style="font-size:11px"><?php echo e($txLbl); ?></span></td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                <?php echo e($tx->created_at?->format('d.m.Y H:i')); ?>

              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:24px;color:var(--p-hint)">
                Tranzaksiyalar yo'q
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'staff'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Hodimlar</div>
        <?php if($isMainShop): ?>
        <a href="<?php echo e(route('panel.sellers.staff.create', $seller)); ?>" class="btn-p primary sm">
          <i class="bi bi-plus"></i> Qo'shish
        </a>
        <?php endif; ?>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>Hodim</th><th>Telefon</th><th>Rol</th><th>Holat</th><th></th></tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $ms   = trim((string)($member->staff_status ?? 'active'));
              $mCls = $ms === 'active' ? 'success' : 'muted';
              $mLbl = $ms === 'active' ? 'Faol' : 'Nofaol';
            ?>
            <tr>
              <td>
                <div class="flex items-center gap-2">
                  <div style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                              display:flex;align-items:center;justify-content:center;
                              font-size:12px;font-weight:600;color:#fff">
                    <?php if($member->photo): ?>
                      <img src="<?php echo e(Storage::url($member->photo)); ?>"
                           style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                      <?php echo e(strtoupper(substr($member->firstname ?? 'H', 0, 1))); ?>

                    <?php endif; ?>
                  </div>
                  <span style="font-size:13px;font-weight:500;color:var(--p-text)">
                    <?php echo e($member->firstname); ?> <?php echo e($member->lastname); ?>

                  </span>
                </div>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
                <?php echo e($member->phone_number); ?>

              </td>
              <td>
                <span class="s-pill muted" style="font-size:11px">
                  <?php echo e(\App\Http\Controllers\Panel\SellerController::ROLES[$member->role ?? ''] ?? ($member->role ?? '—')); ?>

                </span>
              </td>
              <td><span class="s-pill <?php echo e($mCls); ?>" style="font-size:11px"><?php echo e($mLbl); ?></span></td>
              <td>
                <div class="flex gap-1">
                  <form method="POST"
                        action="<?php echo e(route('panel.sellers.staff.toggle', $member)); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <button class="btn-p ghost sm"
                            title="<?php echo e($ms === 'active' ? "To'xtatish" : 'Faollashtirish'); ?>">
                      <i class="bi bi-<?php echo e($ms === 'active' ? 'pause' : 'play'); ?>-fill"></i>
                    </button>
                  </form>
                  <a href="<?php echo e(route('panel.sellers.edit', $member)); ?>" class="btn-p ghost sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">
                Hodimlar yo'q
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'ads'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Reklamalar</div>
        <a href="<?php echo e(route('panel.seller-ads.index', ['seller_id'=>$seller->id])); ?>"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>Reklama</th><th>Format</th><th>Moderatsiya</th><th>Muddat</th></tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $ads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $adM   = trim((string)($ad->moderation ?? ''));
              $adCls = match($adM){ 'approved'=>'success','rejected'=>'danger',default=>'warning' };
              $adLbl = match($adM){ 'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda' };
            ?>
            <tr>
              <td>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                  <?php echo e(Str::limit($ad->title ?? $ad->name ?? '—', 30)); ?>

                </div>
                <?php if($ad->subtitle ?? null): ?>
                <div style="font-size:11px;color:var(--p-hint)"><?php echo e(Str::limit($ad->subtitle, 40)); ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--p-muted)"><?php echo e($ad->format ?? '—'); ?></td>
              <td>
                <span class="s-pill <?php echo e($adCls); ?>" style="font-size:11px"><?php echo e($adLbl); ?></span>
              </td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                <?php if($ad->expires_at ?? null): ?>
                  <?php echo e(\Carbon\Carbon::parse($ad->expires_at)->format('d.m.Y')); ?>

                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="4" style="text-align:center;padding:24px;color:var(--p-hint)">
                Reklamalar yo'q
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
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/sellers/show.blade.php ENDPATH**/ ?>