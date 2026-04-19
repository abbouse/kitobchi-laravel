<?php $__env->startSection('title','Buyurtma #'.$order->id); ?>
<?php $__env->startSection('page-title','Buyurtma #'.$order->id); ?>
<?php $__env->startSection('breadcrumb','Panel / Buyurtmalar / #'.$order->id); ?>

<?php $__env->startSection('content'); ?>

<?php
  $statusMap = [
    'A' => ['warning', 'Kutilmoqda'],
    'P' => ['muted',   'Qadoqlanmoqda'],
    'B' => ['info',    "Yo'lda"],
    'C' => ['success', 'Yetkazildi'],
    'F' => ['danger',  'Bekor qilindi'],
  ];
  $st = $statusMap[$order->status] ?? ['muted', $order->status];

  $payMap = [
    '0' => ['muted',   'Naqd (qabul qilinganida)'],
    '1' => ['info',    'Karta (kutilmoqda)'],
    '2' => ['success', "To'langan ✓"],
    '3' => ['danger',  'Rad etildi'],
  ];
  $pay = $payMap[(string)$order->paymentStatus] ?? ['muted', '—'];

  $isCancelled    = $order->status === 'F';
  $isGiftToOther  = (bool)($order->is_gift_to_other ?? false);
  $withPackaging  = (bool)($order->with_packaging   ?? false);
  $packagingPrice = (int)($order->packaging_price    ?? 0);

  $itemsSubtotal  = collect($items)->where('type','!=','gift')
                      ->sum(fn($i) => ($i['item_price'] ?? 0) * ($i['count_item'] ?? 1));
  $deliveryPrice  = (int)($order->deliveryPrice  ?? 0);
  $discountAmount = (int)($order->discountAmount ?? 0);
  $cashbackAmount = (int)($order->cashbackAmount ?? 0);
  $certAmount     = (int)($order->giftCertAmount ?? 0);
?>


<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.orders.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.orders.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> 
    <div class="flex flex-wrap items-center gap-2">
      <h1 class="page-title mb-0">Buyurtma #<?php echo e($order->id); ?></h1>
      <span class="s-pill <?php echo e($st[0]); ?>"><?php echo e($st[1]); ?></span>
      <?php if($isGiftToOther): ?>
        <span class="s-pill info" style="font-size:11px">👤 Boshqasiga sovg'a</span>
      <?php endif; ?>
      <?php if($withPackaging): ?>
        <span class="s-pill muted" style="font-size:11px">📦 Qadoqlangan</span>
      <?php endif; ?>
    </div>
   <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> 
    <p class="page-sub mt-1">
      <?php echo e($order->created_at?->format('d.m.Y H:i')); ?>

      · <?php echo e($order->deliveryType); ?>

      <?php if($order->user): ?>
        · <?php echo e($order->user->name); ?> <?php echo e($order->user->lastname); ?>

      <?php endif; ?>
    </p>
   <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex flex-wrap gap-2">
      <?php if(!in_array($order->status,['C','F'])): ?>
      <form method="POST" action="<?php echo e(route('panel.orders.cancel', $order)); ?>"
            onsubmit="return confirm('Buyurtmani bekor qilasizmi?')">
        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
        <button class="btn-p danger ghost" style="gap:6px">
          <i class="bi bi-x-circle"></i> Bekor qilish
        </button>
      </form>
      <?php endif; ?>
      <div class="dropdown">
        <button class="btn-p ghost" data-bs-toggle="dropdown" style="gap:8px">
          <i class="bi bi-pencil-square" style="font-size:13px"></i> Status
          <i class="bi bi-chevron-down" style="font-size:10px"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end"
            style="background:var(--p-surface);border:1px solid var(--p-border);
                   border-radius:10px;min-width:170px;padding:6px">
          <?php $__currentLoopData = ['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val=>$lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li>
            <form method="POST" action="<?php echo e(route('panel.orders.status', $order)); ?>">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <input type="hidden" name="status" value="<?php echo e($val); ?>">
              <button type="submit" class="dropdown-item"
                      style="color:<?php echo e($val===$order->status?'var(--p-accent)':'var(--p-text)'); ?>;
                             font-size:13px;padding:8px 14px;border-radius:6px;
                             background:<?php echo e($val===$order->status?'var(--p-elevated)':'transparent'); ?>">
                <?php echo e($val===$order->status?'● ':'○ '); ?><?php echo e($lbl); ?>

              </button>
            </form>
          </li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
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


<?php if($isCancelled): ?>
<div class="fade-up mb-3"
     style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);
            border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px">
  <i class="bi bi-exclamation-triangle-fill" style="color:var(--p-danger);font-size:18px"></i>
  <div>
    <div style="font-size:13px;font-weight:600;color:var(--p-danger)">Buyurtma bekor qilingan</div>
    <div style="font-size:12px;color:var(--p-muted);margin-top:2px">
      Barcha chegirmalar, cashback va sertifikatlar qaytarilgan
    </div>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">


<div class="xl:col-span-8">

  
  <div class="p-card mb-3 fade-up d1">
    <div class="p-card-header">
      <div>
        <div class="p-card-title">Buyurtma tarkibi</div>
        <div class="p-card-sub">
          <?php echo e(collect($items)->where('type','!=','gift')->sum('count_item')); ?> ta mahsulot
        </div>
      </div>
    </div>

    <div class="table-responsive kc-twrap">
      <table class="p-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mahsulot</th>
            <th>Do'kon</th>
            <th style="text-align:right">Narxi</th>
            <th style="text-align:center">Soni</th>
            <th style="text-align:right">Jami</th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $isGiftItem = ($item['type'] ?? '') === 'gift';
            $cover = $item['cover'] ?? null;
            $rowPrice = ($item['item_price'] ?? 0) * ($item['count_item'] ?? 1);
          ?>
          <tr style="<?php echo e($isGiftItem ? 'background:rgba(245,166,35,.04)' : ''); ?>">
            <td style="color:var(--p-hint);font-size:12px"><?php echo e($i + 1); ?></td>

            <td>
              <div class="flex items-center gap-3">
                
                <div style="width:42px;height:56px;border-radius:6px;overflow:hidden;
                            background:var(--p-elevated);flex-shrink:0;
                            display:flex;align-items:center;justify-content:center">
                  <?php if($cover): ?>
                    <img src="<?php echo e(asset('storage/'.$cover)); ?>"
                         style="width:100%;height:100%;object-fit:cover" alt="">
                  <?php elseif($isGiftItem): ?>
                    <span style="font-size:22px">🎁</span>
                  <?php else: ?>
                    <i class="bi bi-book" style="color:var(--p-hint);font-size:16px"></i>
                  <?php endif; ?>
                </div>
                
                <div>
                  <div style="font-size:13px;font-weight:600;color:var(--p-text);
                              max-width:220px;white-space:nowrap;overflow:hidden;
                              text-overflow:ellipsis">
                    <?php echo e($item['name'] ?? '—'); ?>

                  </div>
                  <?php if($item['author'] ?? null): ?>
                  <div style="font-size:11px;color:var(--p-hint)">
                    <?php echo e($item['author']); ?>

                  </div>
                  <?php endif; ?>
                  <?php if($item['material'] ?? null): ?>
                  <div style="font-size:11px;color:var(--p-hint)">
                    <?php echo e($item['material']); ?>

                  </div>
                  <?php endif; ?>
                  <?php if($item['color_name'] ?? null): ?>
                  <div style="font-size:11px;color:var(--p-hint)">
                    <i class="bi bi-circle-fill" style="font-size:8px"></i>
                    <?php echo e($item['color_name']); ?>

                  </div>
                  <?php endif; ?>
                  <div style="font-size:10px;color:var(--p-border);
                              font-family:'JetBrains Mono',monospace;margin-top:2px">
                    ID: <?php echo e($item['item_id'] ?? '—'); ?>

                    · <?php echo e(strtoupper($item['type'] ?? 'book')); ?>

                    <?php if($isGiftItem): ?>
                      <span class="s-pill accent" style="font-size:9px;padding:1px 5px">SOVG'A</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>

            <td>
              <?php if(($item['product'] ?? null)?->seller ?? null): ?>
              <div style="font-size:12px;font-weight:500;color:var(--p-text)">
                <?php echo e($item['product']->seller->shop_name); ?>

              </div>
              <?php else: ?>
              <span style="color:var(--p-hint);font-size:12px">—</span>
              <?php endif; ?>
            </td>

            <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                       font-size:13px;font-weight:500;color:var(--p-text)">
              <?php if($isGiftItem): ?>
                <span class="s-pill success" style="font-size:11px">Bepul</span>
              <?php else: ?>
                <?php echo e(number_format($item['item_price'] ?? 0)); ?>

              <?php endif; ?>
            </td>

            <td style="text-align:center;font-family:'JetBrains Mono',monospace;
                       font-size:13px;color:var(--p-muted)">
              <?php echo e($item['count_item'] ?? 1); ?>

            </td>

            <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                       font-size:14px;font-weight:700;color:var(--p-text)">
              <?php if(!$isGiftItem): ?>
                <?php echo e(number_format($rowPrice)); ?>

              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>

    
    <div style="padding:16px;background:var(--p-elevated);border-radius:0 0 12px 12px;
                border-top:1px solid var(--p-border)">

      <?php
        $rows = [
          ['label' => 'Mahsulotlar jami', 'value' => $itemsSubtotal, 'sign' => '', 'color' => 'var(--p-text)'],
        ];
        if($deliveryPrice > 0)
          $rows[] = ['label'=>'Yetkazib berish','value'=>$deliveryPrice,'sign'=>'+','color'=>'var(--p-muted)'];
        else
          $rows[] = ['label'=>'Yetkazib berish','value'=>null,'sign'=>'','color'=>'var(--p-success)','free'=>true];

        if($withPackaging && $packagingPrice > 0)
          $rows[] = ['label'=>'Qadoqlash 📦','value'=>$packagingPrice,'sign'=>'+','color'=>'var(--p-muted)'];

        if($discountAmount > 0)
          $rows[] = ['label'=>'Promokod '.$order->promocode,'value'=>$discountAmount,'sign'=>'-','color'=>'var(--p-success)'];

        if($cashbackAmount > 0)
          $rows[] = ['label'=>'Cashback','value'=>$cashbackAmount,'sign'=>'-','color'=>'var(--p-info)'];

        if($certAmount > 0)
          $rows[] = ['label'=>'Gift sertifikat #'.$order->gift_certificate_id,'value'=>$certAmount,'sign'=>'-','color'=>'var(--p-warning)'];
      ?>

      <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="flex justify-between items-center mb-2"
           style="font-size:13px">
        <span style="color:var(--p-muted)">
          <?php echo e($row['label']); ?>

          <?php if($isCancelled && in_array($row['sign'],['-'])): ?>
            <span class="s-pill warning" style="font-size:10px;margin-left:4px">qaytarildi</span>
          <?php endif; ?>
        </span>
        <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:<?php echo e($row['color']); ?>">
          <?php if($row['free'] ?? false): ?>
            <span class="s-pill success" style="font-size:11px">Bepul</span>
          <?php else: ?>
            <?php echo e($row['sign']); ?> <?php echo e(number_format($row['value'])); ?> UZS
          <?php endif; ?>
        </span>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      
      <div style="border-top:2px solid var(--p-border);padding-top:12px;margin-top:8px"
           class="flex justify-between items-center">
        <span style="font-size:15px;font-weight:700;color:var(--p-text)">Umumiy to'lov</span>
        <span style="font-size:22px;font-weight:800;font-family:'JetBrains Mono',monospace;
                     color:<?php echo e($isCancelled ? 'var(--p-danger)' : 'var(--p-accent)'); ?>">
          <?php echo e(number_format($order->amount)); ?>

          <span style="font-size:13px;font-weight:500;color:var(--p-hint)">UZS</span>
        </span>
      </div>
    </div>
  </div>

  
  <?php if($order->address): ?>
  <?php
    $rawAddr = $order->address;
    if(is_string($rawAddr)) $rawAddr = json_decode($rawAddr, true) ?? [];
    $addr = (isset($rawAddr[0]) && is_array($rawAddr[0])) ? $rawAddr[0] : $rawAddr;
  ?>
  <div class="p-card mb-3 fade-up d2">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-geo-alt mr-1" style="color:var(--p-accent)"></i>
        Yetkazish manzili
      </div>
      <?php if(($addr['lat'] ?? null) && ($addr['lon'] ?? null)): ?>
      <a href="https://maps.yandex.uz/?text=<?php echo e($addr['lat']); ?>+<?php echo e($addr['lon']); ?>&z=16"
         target="_blank" class="btn-p ghost sm">
        <i class="bi bi-map"></i> Xaritada
      </a>
      <?php endif; ?>
    </div>
    <div style="padding:4px 0">
      <?php
        $addrRows = [
          ['icon'=>'bi-house',          'label'=>'Manzil',         'value'=>$addr['fullAddress'] ?? null],
          ['icon'=>'bi-person',         'label'=>'Qabul qiluvchi', 'value'=>$addr['fullName']    ?? null],
          ['icon'=>'bi-telephone',      'label'=>'Telefon',        'value'=>$addr['phoneNumber'] ?? null, 'link'=>'tel'],
        ];
      ?>
      <?php $__currentLoopData = $addrRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if($r['value']): ?>
      <div class="flex items-start gap-3"
           style="padding:10px 0;border-bottom:1px solid var(--p-border)">
        <div style="width:32px;height:32px;border-radius:8px;background:var(--p-elevated);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?php echo e($r['icon']); ?>" style="font-size:14px;color:var(--p-accent)"></i>
        </div>
        <div>
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:2px"><?php echo e($r['label']); ?></div>
          <?php if(isset($r['link']) && $r['link']==='tel'): ?>
            <a href="tel:<?php echo e($r['value']); ?>"
               style="font-size:14px;font-weight:600;color:var(--p-accent);
                      text-decoration:none;font-family:'JetBrains Mono',monospace">
              <?php echo e($r['value']); ?>

            </a>
          <?php else: ?>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($r['value']); ?>

            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
  <?php endif; ?>

  
  <?php if($isGiftToOther): ?>
  <div class="p-card fade-up d3"
       style="border:1px solid rgba(59,130,246,.25);
              background:linear-gradient(135deg,rgba(59,130,246,.04),transparent)">
    <div class="p-card-header">
      <div class="p-card-title" style="color:var(--p-info)">
        <i class="bi bi-gift mr-1"></i> Qabul qiluvchi ma'lumotlari
      </div>
      <span class="s-pill info" style="font-size:11px">Boshqasiga sovg'a</span>
    </div>
    <div style="padding:4px 0">
      <?php
        $recipientRows = [
          ['icon'=>'bi-person-fill',    'label'=>'Ism',     'value'=>$order->recipient_name    ?? null],
          ['icon'=>'bi-telephone-fill', 'label'=>'Telefon', 'value'=>$order->recipient_phone   ?? null, 'link'=>'tel'],
          ['icon'=>'bi-geo-alt-fill',   'label'=>'Viloyat', 'value'=>$order->recipient_region  ?? null],
          ['icon'=>'bi-house-fill',     'label'=>'Manzil',  'value'=>$order->recipient_address ?? null],
        ];
      ?>
      <?php $__currentLoopData = $recipientRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if($r['value']): ?>
      <div class="flex items-center gap-3"
           style="padding:10px 0;border-bottom:1px solid var(--p-border)">
        <div style="width:32px;height:32px;border-radius:8px;
                    background:rgba(59,130,246,.1);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?php echo e($r['icon']); ?>" style="font-size:13px;color:var(--p-info)"></i>
        </div>
        <div>
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:1px"><?php echo e($r['label']); ?></div>
          <?php if(isset($r['link']) && $r['link']==='tel'): ?>
            <a href="tel:<?php echo e($r['value']); ?>"
               style="font-size:14px;font-weight:600;color:var(--p-info);
                      text-decoration:none;font-family:'JetBrains Mono',monospace">
              <?php echo e($r['value']); ?>

            </a>
          <?php else: ?>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($r['value']); ?>

            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
  <?php endif; ?>

</div>


<div class="xl:col-span-4">

  
  <div class="p-card mb-3 fade-up d1">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-person-circle mr-1" style="color:var(--p-accent)"></i> Mijoz
      </div>
      <?php if($order->user): ?>
      <a href="<?php echo e(route('panel.users.show', $order->user)); ?>"
         class="btn-p ghost sm" style="font-size:11px">
        Profilga <i class="bi bi-arrow-right"></i>
      </a>
      <?php endif; ?>
    </div>
    <?php if($order->user): ?>
    <div class="flex items-center gap-3">
      <div class="av av-blue"
           style="width:48px;height:48px;font-size:18px;flex-shrink:0;border-radius:14px">
        <?php if($order->user->avatar): ?>
          <img src="<?php echo e(Storage::url($order->user->avatar)); ?>" alt=""
               style="width:100%;height:100%;object-fit:cover;border-radius:14px">
        <?php else: ?>
          <?php echo e(strtoupper(substr($order->user->name, 0, 1))); ?>

        <?php endif; ?>
      </div>
      <div>
        <div style="font-size:15px;font-weight:700;color:var(--p-text)">
          <?php echo e($order->user->name); ?> <?php echo e($order->user->lastname); ?>

        </div>
        <div style="font-size:12px;color:var(--p-hint);
                    font-family:'JetBrains Mono',monospace;margin-top:2px">
          <?php echo e($order->user->phone_number); ?>

        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
          ID: <?php echo e($order->user->id); ?>

        </div>
      </div>
    </div>
    <?php else: ?>
    <div style="color:var(--p-hint);font-size:13px;padding:8px 0">Mehmon foydalanuvchi</div>
    <?php endif; ?>
  </div>

  
  <div class="p-card mb-3 fade-up d2">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-credit-card mr-1" style="color:var(--p-accent)"></i> To'lov
      </div>
      <span class="s-pill <?php echo e($pay[0]); ?>" style="font-size:11px"><?php echo e($pay[1]); ?></span>
    </div>

    <?php
      $infoRows = [
        ['label'=>'Buyurtma holati',   'slot'=>'status'],
        ['label'=>'To\'lov holati',    'slot'=>'payment'],
        ['label'=>'Yetkazish',         'slot'=>'delivery'],
        ['label'=>'Yetkazish narxi',   'slot'=>'delivery_price'],
        ['label'=>'Mahsulotlar jami',  'value'=>number_format($itemsSubtotal).' UZS'],
        ['label'=>'Qadoqlash 📦',      'value'=>$withPackaging ? number_format($packagingPrice).' UZS' : null, 'pill'=>'muted'],
        ['label'=>'Promokod',          'slot'=>'promo'],
        ['label'=>'Cashback',          'slot'=>'cashback'],
        ['label'=>'Gift sertifikat',   'slot'=>'cert'],
        ['label'=>'Yaratildi',         'value'=>$order->created_at?->format('d.m.Y H:i')],
        ['label'=>'Yangilandi',        'value'=>$order->updated_at?->format('d.m.Y H:i')],
      ];
    ?>

    <div style="padding:4px 0">
      <?php $__currentLoopData = $infoRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php $skip = false; ?>

      <?php if(isset($row['slot'])): ?>
        <?php if($row['slot']==='status'): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <span class="s-pill <?php echo e($st[0]); ?>" style="font-size:11px"><?php echo e($st[1]); ?></span>
          </div>
        <?php elseif($row['slot']==='payment'): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <span class="s-pill <?php echo e($pay[0]); ?>" style="font-size:11px"><?php echo e($pay[1]); ?></span>
          </div>
        <?php elseif($row['slot']==='delivery'): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <span style="font-size:12px;font-weight:600;color:var(--p-text)">
              <?php echo e($order->deliveryType ?? '—'); ?>

            </span>
          </div>
        <?php elseif($row['slot']==='delivery_price'): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <?php if($deliveryPrice > 0): ?>
              <span style="font-size:12px;font-family:'JetBrains Mono',monospace;
                           font-weight:600;color:var(--p-text)">
                <?php echo e(number_format($deliveryPrice)); ?> UZS
              </span>
            <?php else: ?>
              <span class="s-pill success" style="font-size:11px">Bepul</span>
            <?php endif; ?>
          </div>
        <?php elseif($row['slot']==='promo'): ?>
          <?php if($order->promocode): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <div class="flex items-center gap-2">
              <code style="font-family:'JetBrains Mono',monospace;font-size:12px;
                           font-weight:700;color:var(--p-accent)">
                <?php echo e($order->promocode); ?>

              </code>
              <span style="font-size:12px;color:var(--p-success);font-family:'JetBrains Mono',monospace">
                -<?php echo e(number_format($discountAmount)); ?>

              </span>
              <?php if($isCancelled): ?>
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php elseif($row['slot']==='cashback'): ?>
          <?php if($cashbackAmount > 0): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <div class="flex items-center gap-2">
              <span style="font-size:12px;color:var(--p-info);
                           font-family:'JetBrains Mono',monospace;font-weight:600">
                -<?php echo e(number_format($cashbackAmount)); ?> UZS
              </span>
              <?php if($isCancelled): ?>
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php elseif($row['slot']==='cert'): ?>
          <?php if($certAmount > 0): ?>
          <div class="flex justify-between items-center"
               style="padding:9px 0;border-bottom:1px solid var(--p-border)">
            <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
            <div class="flex items-center gap-2">
              <code style="font-family:'JetBrains Mono',monospace;font-size:11px;
                           color:var(--p-warning)">#<?php echo e($order->gift_certificate_id); ?></code>
              <span style="font-size:12px;color:var(--p-warning);
                           font-family:'JetBrains Mono',monospace;font-weight:600">
                -<?php echo e(number_format($certAmount)); ?>

              </span>
              <?php if($isCancelled): ?>
                <span class="s-pill warning" style="font-size:10px">qaytarildi</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>

      <?php elseif(isset($row['value']) && $row['value'] !== null): ?>
        <div class="flex justify-between items-center"
             style="padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($row['label']); ?></span>
          <?php if(isset($row['pill'])): ?>
            <span class="s-pill <?php echo e($row['pill']); ?>" style="font-size:11px"><?php echo e($row['value']); ?></span>
          <?php else: ?>
            <span style="font-size:12px;font-weight:600;color:var(--p-text)"><?php echo e($row['value']); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div style="margin-top:12px;padding:14px;background:var(--p-elevated);
                border-radius:10px;display:flex;justify-content:space-between;
                align-items:center">
      <span style="font-size:13px;font-weight:600;color:var(--p-muted)">Umumiy to'lov</span>
      <span style="font-size:20px;font-weight:800;font-family:'JetBrains Mono',monospace;
                   color:<?php echo e($isCancelled ? 'var(--p-danger)' : 'var(--p-accent)'); ?>">
        <?php echo e(number_format($order->amount)); ?>

        <span style="font-size:12px;font-weight:500;color:var(--p-hint)">UZS</span>
      </span>
    </div>
  </div>

  
  <?php if($order->gift && isset($orderGift) && $orderGift): ?>
  <div class="p-card mb-3 fade-up d3">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-gift mr-1" style="color:var(--p-accent)"></i> Sovg'a
      </div>
    </div>
    <div class="flex items-center gap-3">
      <div style="width:48px;height:48px;border-radius:10px;overflow:hidden;
                  background:var(--p-elevated);display:flex;align-items:center;
                  justify-content:center;flex-shrink:0">
        <?php if($orderGift->images[0] ?? null): ?>
          <img src="<?php echo e(Storage::url($orderGift->images[0])); ?>"
               style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <span style="font-size:24px">🎁</span>
        <?php endif; ?>
      </div>
      <div>
        <div style="font-size:14px;font-weight:700;color:var(--p-text)">
          <?php echo e($orderGift->name); ?>

        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
          <?php echo e($orderGift->seller?->shop_name ?? 'Kitobchi'); ?>

        </div>
      </div>
      <div style="margin-left:auto">
        <span class="s-pill success" style="font-size:11px">Bepul 🎉</span>
      </div>
    </div>
  </div>
  <?php endif; ?>

  
  <?php if(isset($orderCert) && $orderCert): ?>
  <div class="p-card mb-3 fade-up d3"
       style="border:1px solid rgba(245,166,35,.3)">
    <div class="p-card-header">
      <div class="p-card-title" style="color:var(--p-warning)">
        🎟 Gift Sertifikat
      </div>
      <span class="s-pill <?php echo e($isCancelled ? 'warning' : 'success'); ?>" style="font-size:11px">
        <?php echo e($isCancelled ? 'Qaytarildi' : 'Ishlatildi'); ?>

      </span>
    </div>
    <div style="padding:4px 0">
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Kod</span>
        <code style="font-family:'JetBrains Mono',monospace;font-size:14px;
                     font-weight:800;color:var(--p-warning)"><?php echo e($orderCert->code); ?></code>
      </div>
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Nominal</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
                     font-weight:600;color:var(--p-text)">
          <?php echo e(number_format($orderCert->nominal_uzs)); ?> UZS
        </span>
      </div>
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Chegirma</span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
                     font-weight:600;color:var(--p-warning)">
          -<?php echo e(number_format($certAmount)); ?> UZS
        </span>
      </div>
      <?php if($orderCert->buyer ?? null): ?>
      <div class="flex justify-between" style="padding:7px 0">
        <span style="font-size:12px;color:var(--p-hint)">Sotib olgan</span>
        <span style="font-size:12px;color:var(--p-text);font-weight:500">
          <?php echo e($orderCert->buyer->name ?? '—'); ?>

        </span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  
  <?php if($order->buyerWish): ?>
  <div class="p-card mb-3 fade-up d4">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-chat-quote mr-1" style="color:var(--p-accent)"></i> Xaridor tilagi
      </div>
    </div>
    <div style="font-size:13px;color:var(--p-muted);font-style:italic;
                line-height:1.7;background:var(--p-elevated);border-radius:8px;
                padding:12px;border-left:3px solid var(--p-accent)">
      "<?php echo e($order->buyerWish); ?>"
    </div>
  </div>
  <?php endif; ?>

  
  <?php if($order->qr): ?>
  <div class="p-card fade-up d4">
    <div class="p-card-header">
      <div class="p-card-title">
        <i class="bi bi-qr-code mr-1" style="color:var(--p-accent)"></i> QR kod
      </div>
      <div class="flex gap-2">
        <button onclick="toggleQr()" class="btn-p ghost sm" id="qrToggleBtn">
          <i class="bi bi-eye" id="qrEye"></i>
        </button>
        <button onclick="copyQr()" id="qrCopyBtn" class="btn-p ghost sm">
          <i class="bi bi-copy" id="qrCopyIcon"></i>
          <span id="qrCopyLabel" style="font-size:11px">Nusxa</span>
        </button>
      </div>
    </div>
    <div id="qrMasked" style="font-family:'JetBrains Mono',monospace;font-size:14px;
                              color:var(--p-muted);letter-spacing:.08em;
                              padding:8px 0">
      <?php echo e(str_repeat('•', min(20, strlen($order->qr) - 4))); ?><?php echo e(substr($order->qr,-4)); ?>

    </div>
    <div id="qrFull" style="display:none;padding:10px;background:var(--p-elevated);
                             border-radius:8px;font-family:'JetBrains Mono',monospace;
                             font-size:12px;color:var(--p-text);font-weight:600;
                             word-break:break-all;line-height:1.7;user-select:all">
      <?php echo e($order->qr); ?>

    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<?php $__env->startPush('scripts'); ?>
<?php if($order->qr): ?>
<script>
let qrOpen = false;
const QR_VAL = '<?php echo e(addslashes($order->qr)); ?>';

function toggleQr() {
  qrOpen = !qrOpen;
  document.getElementById('qrMasked').style.display = qrOpen ? 'none'  : 'block';
  document.getElementById('qrFull').style.display   = qrOpen ? 'block' : 'none';
  document.getElementById('qrEye').className        = qrOpen ? 'bi bi-eye-slash' : 'bi bi-eye';
}

function copyQr() {
  navigator.clipboard.writeText(QR_VAL).then(() => {
    const icon  = document.getElementById('qrCopyIcon');
    const label = document.getElementById('qrCopyLabel');
    icon.className    = 'bi bi-check-lg';
    icon.style.color  = 'var(--p-success)';
    label.textContent = 'Nusxalandi!';
    setTimeout(() => {
      icon.className    = 'bi bi-copy';
      icon.style.color  = '';
      label.textContent = 'Nusxa';
    }, 2000);
  });
}
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/orders/show.blade.php ENDPATH**/ ?>