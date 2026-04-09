<?php $__env->startSection('title','Buyurtmalar'); ?>
<?php $__env->startSection('page-title','Buyurtmalar'); ?>
<?php $__env->startSection('breadcrumb','Panel / Buyurtmalar'); ?>

<?php $__env->startSection('content'); ?>


<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Buyurtmalar</h1>
    <p class="page-sub">Barcha buyurtmalar ro'yxati</p>
  </div>
  <a href="<?php echo e(route('panel.orders.export', request()->all())); ?>" class="btn-p ghost" style="gap:6px">
    <i class="bi bi-download"></i> Export
  </a>
</div>


<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3 fade-up d1">
    <div class="metric-card" style="border-top-color:var(--p-warning)">
      <div class="metric-icon" style="background:var(--p-warning-d);color:var(--p-warning)">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="metric-label">Kutilmoqda</div>
      <div class="metric-value"><?php echo e(number_format($counts['A'])); ?></div>
      <span class="s-pill warning" style="font-size:11px">Yangi buyurtmalar</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d2">
    <div class="metric-card" style="border-top-color:var(--p-info)">
      <div class="metric-icon" style="background:rgba(59,130,246,.12);color:var(--p-info)">
        <i class="bi bi-truck"></i>
      </div>
      <div class="metric-label">Yo'lda</div>
      <div class="metric-value"><?php echo e(number_format($counts['B'])); ?></div>
      <span class="s-pill info" style="font-size:11px">Yetkazilmoqda</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d3">
    <div class="metric-card" style="border-top-color:var(--p-success)">
      <div class="metric-icon" style="background:var(--p-success-d);color:var(--p-success)">
        <i class="bi bi-currency-dollar"></i>
      </div>
      <div class="metric-label">Bugun daromad</div>
      <div class="metric-value"><?php echo e(number_format($stats['today_revenue']/1000000, 1)); ?>M</div>
      <span class="s-pill success" style="font-size:11px"><?php echo e($stats['today_count']); ?> ta buyurtma</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d4">
    <div class="metric-card" style="border-top-color:var(--p-accent)">
      <div class="metric-icon" style="background:var(--p-accent-d);color:var(--p-accent)">
        <i class="bi bi-graph-up-arrow"></i>
      </div>
      <div class="metric-label">Jami daromad</div>
      <div class="metric-value"><?php echo e(number_format($stats['total_revenue']/1000000, 1)); ?>M</div>
      <span class="s-pill accent" style="font-size:11px">UZS</span>
    </div>
  </div>
</div>


<div class="tab-pills fade-up mb-3">
  <?php
    $tabs = [
      'A'   => ['Kutilmoqda',     'warning'],
      'P'   => ['Qadoqlanmoqda',  'muted'],
      'B'   => ["Yo'lda",         'info'],
      'C'   => ['Yetkazildi',     'success'],
      'F'   => ['Bekor',          'danger'],
      'all' => ['Barchasi',       'accent'],
    ];
  ?>
  <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $pill]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(route('panel.orders.index', array_merge(request()->except('tab','page'), ['tab'=>$key]))); ?>"
     class="tab-pill <?php echo e($tab === $key ? 'active' : ''); ?>">
    <?php echo e($label); ?>

    <span class="tab-count" style="<?php echo e($tab===$key ? 'background:var(--p-accent);color:#fff' : ''); ?>">
      <?php echo e($counts[$key]); ?>

    </span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" action="<?php echo e(route('panel.orders.index')); ?>" id="orderFilter" class="fade-up">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="filter-bar mb-3">
    <div class="search-box" style="width:220px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="<?php echo e(request('search')); ?>"
             placeholder="ID, ism, telefon..."
             onchange="orderFilter.submit()"/>
    </div>
    <select name="payment_status" class="p-form-control" style="width:170px" onchange="orderFilter.submit()">
      <option value="">To'lov holati</option>
      <option value="0" <?php echo e(request('payment_status')==='0'?'selected':''); ?>>Qabul qilinganida</option>
      <option value="1" <?php echo e(request('payment_status')==='1'?'selected':''); ?>>Karta (kutilmoqda)</option>
      <option value="2" <?php echo e(request('payment_status')==='2'?'selected':''); ?>>To'langan</option>
      <option value="3" <?php echo e(request('payment_status')==='3'?'selected':''); ?>>Rad etildi</option>
    </select>
    <select name="delivery_type" class="p-form-control" style="width:160px" onchange="orderFilter.submit()">
      <option value="">Yetkazish turi</option>
      <option value="Kuryer"  <?php echo e(request('delivery_type')==='Kuryer'?'selected':''); ?>>Kuryer</option>
      <option value="Starex"  <?php echo e(request('delivery_type')==='Starex'?'selected':''); ?>>Starex (Pochta)</option>
    </select>
    <select name="gift_filter" class="p-form-control" style="width:140px" onchange="orderFilter.submit()">
      <option value="">Hammasi</option>
      <option value="gift"     <?php echo e(request('gift_filter')==='gift'?'selected':''); ?>>🎁 Sovg'ali</option>
      <option value="other"    <?php echo e(request('gift_filter')==='other'?'selected':''); ?>>👤 Boshqasiga</option>
      <option value="packaging"<?php echo e(request('gift_filter')==='packaging'?'selected':''); ?>>📦 Qadoqlangan</option>
    </select>
    <?php if(request()->hasAny(['search','payment_status','delivery_type','gift_filter'])): ?>
    <a href="<?php echo e(route('panel.orders.index',['tab'=>$tab])); ?>" class="btn-p ghost" style="gap:5px">
      <i class="bi bi-x-circle"></i> Tozalash
    </a>
    <?php endif; ?>
  </div>
</form>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Buyurtmalar ro'yxati</div>
      <div class="p-card-sub"><?php echo e($orders->total()); ?> ta natija</div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="p-table" style="min-width:900px">
      <thead>
        <tr>
          <th style="width:80px">#ID</th>
          <th>Mijoz</th>
          <th>Mahsulotlar</th>
          <th>Summa</th>
          <th>To'lov</th>
          <th>Yetkazish</th>
          <th>Belgilar</th>
          <th>Status</th>
          <th style="width:80px">Sana</th>
          <th style="width:50px"></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $statusMap = [
            'A' => ['warning','Kutilmoqda'],
            'P' => ['muted','Qadoqlanmoqda'],
            'B' => ['info',"Yo'lda"],
            'C' => ['success','Yetkazildi'],
            'F' => ['danger','Bekor'],
          ];
          $st = $statusMap[$order->status] ?? ['muted',$order->status];

          $payMap = [
            '0'=>['muted','Naqd'],
            '1'=>['info','Karta'],
            '2'=>['success',"To'langan"],
            '3'=>['danger','Rad'],
          ];
          $pay = $payMap[(string)$order->paymentStatus] ?? ['muted','—'];

          $itemCount = collect($order->items ?? [])->where('type','!=','gift')->sum('count_item');
          $previewItems = collect($order->items ?? [])->where('type','!=','gift')->take(2);
        ?>
        <tr>
          
          <td>
            <a href="<?php echo e(route('panel.orders.show', $order)); ?>"
               style="font-family:'DM Mono',monospace;color:var(--p-accent);
                      font-weight:700;font-size:14px;text-decoration:none">
              #<?php echo e($order->id); ?>

            </a>
          </td>

          
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="av av-blue" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                <?php echo e(strtoupper(substr($order->user?->name ?? 'U', 0, 1))); ?>

              </div>
              <div>
                <div style="font-size:13px;font-weight:600;color:var(--p-text);white-space:nowrap">
                  <?php echo e($order->user ? $order->user->name.' '.$order->user->lastname : 'Mehmon'); ?>

                </div>
                <div style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace">
                  <?php echo e($order->user?->phone_number ?? '—'); ?>

                </div>
              </div>
            </div>
          </td>

          
          <td>
            <div style="max-width:200px">
              <?php $__currentLoopData = $previewItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div style="font-size:12px;color:var(--p-text);
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                          max-width:200px">
                <?php echo e($item['name'] ?? '—'); ?>

              </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php if($itemCount > 2): ?>
              <div style="font-size:11px;color:var(--p-hint)">+ <?php echo e($itemCount - 2); ?> ta ko'proq</div>
              <?php endif; ?>
            </div>
          </td>

          
          <td>
            <div style="font-family:'DM Mono',monospace;font-weight:700;
                        color:var(--p-text);font-size:14px;white-space:nowrap">
              <?php echo e(number_format($order->amount)); ?>

              <span style="font-size:10px;font-weight:400;color:var(--p-hint)">UZS</span>
            </div>
            <?php if($order->discountAmount > 0): ?>
            <div style="font-size:11px;color:var(--p-success)">
              -<?php echo e(number_format($order->discountAmount)); ?> chegirma
            </div>
            <?php endif; ?>
            <?php if(($order->packaging_price ?? 0) > 0): ?>
            <div style="font-size:11px;color:var(--p-muted)">
              +<?php echo e(number_format($order->packaging_price)); ?> qadoq
            </div>
            <?php endif; ?>
          </td>

          
          <td>
            <span class="s-pill <?php echo e($pay[0]); ?>" style="font-size:11px"><?php echo e($pay[1]); ?></span>
          </td>

          
          <td>
            <div style="font-size:12px;color:var(--p-text);font-weight:500">
              <?php echo e($order->deliveryType ?? '—'); ?>

            </div>
            <?php if($order->deliveryPrice > 0): ?>
            <div style="font-size:11px;color:var(--p-hint)">
              <?php echo e(number_format($order->deliveryPrice)); ?> UZS
            </div>
            <?php else: ?>
            <div style="font-size:11px;color:var(--p-success)">Bepul</div>
            <?php endif; ?>
          </td>

          
          <td>
            <div class="d-flex gap-1 flex-wrap">
              <?php if($order->gift): ?>
              <span class="s-pill accent" style="font-size:10px" title="Sovg'ali">🎁</span>
              <?php endif; ?>
              <?php if($order->is_gift_to_other ?? false): ?>
              <span class="s-pill info" style="font-size:10px" title="Boshqasiga sovg'a">👤</span>
              <?php endif; ?>
              <?php if($order->with_packaging ?? false): ?>
              <span class="s-pill muted" style="font-size:10px" title="Qadoqlash">📦</span>
              <?php endif; ?>
              <?php if($order->cashbackAmount > 0): ?>
              <span class="s-pill success" style="font-size:10px" title="Cashback ishlatildi">💰</span>
              <?php endif; ?>
              <?php if($order->giftCertAmount > 0): ?>
              <span class="s-pill warning" style="font-size:10px" title="Sertifikat ishlatildi">🎟</span>
              <?php endif; ?>
              <?php if($order->promocode): ?>
              <span class="s-pill accent" style="font-size:10px" title="Promokod: <?php echo e($order->promocode); ?>">%</span>
              <?php endif; ?>
            </div>
          </td>

          
          <td>
            <div class="d-flex align-items-center gap-1">
              <span class="s-pill <?php echo e($st[0]); ?>" style="font-size:11px"><?php echo e($st[1]); ?></span>
              <div class="dropdown">
                <button class="btn-p ghost sm p-0"
                        style="width:22px;height:22px;border-radius:6px;padding:0"
                        data-bs-toggle="dropdown">
                  <i class="bi bi-chevron-down" style="font-size:10px"></i>
                </button>
                <ul class="dropdown-menu"
                    style="background:var(--p-surface);border:1px solid var(--p-border);
                           border-radius:10px;min-width:150px;padding:5px">
                  <?php $__currentLoopData = ['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val=>$lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <li>
                    <form method="POST" action="<?php echo e(route('panel.orders.status', $order)); ?>">
                      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                      <input type="hidden" name="status" value="<?php echo e($val); ?>">
                      <button type="submit" class="dropdown-item"
                              style="color:<?php echo e($val===$order->status?'var(--p-accent)':'var(--p-text)'); ?>;
                                     font-size:12px;padding:6px 12px;border-radius:6px">
                        <?php echo e($val===$order->status?'✓ ':''); ?><?php echo e($lbl); ?>

                      </button>
                    </form>
                  </li>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
              </div>
            </div>
          </td>

          
          <td style="font-size:11px;color:var(--p-hint);
                     font-family:'DM Mono',monospace;white-space:nowrap">
            <?php echo e($order->created_at?->format('d.m')); ?><br>
            <span style="font-size:10px"><?php echo e($order->created_at?->format('H:i')); ?></span>
          </td>

          
          <td>
            <a href="<?php echo e(route('panel.orders.show', $order)); ?>"
               class="btn-p ghost sm" style="padding:5px 8px">
              <i class="bi bi-arrow-right"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="10" style="text-align:center;padding:60px 20px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:10px;
                                          color:var(--p-border)"></i>
            <div style="font-size:14px">Buyurtmalar topilmadi</div>
            <div style="font-size:12px;margin-top:4px">Filtrni o'zgartirib ko'ring</div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  
  <?php if($orders->hasPages()): ?>
  <div class="d-flex align-items-center justify-content-between"
       style="padding:12px 16px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($orders->firstItem()); ?>–<?php echo e($orders->lastItem()); ?> / <?php echo e($orders->total()); ?> ta
    </div>
    <div class="p-pagination">
      <?php if($orders->onFirstPage()): ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      <?php else: ?>
        <a href="<?php echo e($orders->previousPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      <?php endif; ?>
      <?php $__currentLoopData = $orders->getUrlRange(max(1,$orders->currentPage()-2),min($orders->lastPage(),$orders->currentPage()+2)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page=>$url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($url); ?>" class="p-page-btn <?php echo e($page===$orders->currentPage()?'active':''); ?>"><?php echo e($page); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($orders->hasMorePages()): ?>
        <a href="<?php echo e($orders->nextPageUrl()); ?>" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      <?php else: ?>
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/orders/index.blade.php ENDPATH**/ ?>