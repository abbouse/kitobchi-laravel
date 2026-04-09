<?php $__env->startSection('title', $user->name.' '.$user->lastname.' — Profil'); ?>
<?php $__env->startSection('page-title', $user->name.' '.$user->lastname); ?>

<?php $__env->startSection('content'); ?>

<?php $activeSection = request('section', 'orders'); ?>


<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="<?php echo e(route('panel.users.index')); ?>" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title"><?php echo e($user->name); ?> <?php echo e($user->lastname); ?></h1>
      <p class="page-sub">
        ID: #<?php echo e($user->id); ?>

        · <?php echo e($user->created_at?->format('d.m.Y')); ?> da qo'shilgan
        <?php if($user->last_seen_at): ?>
          · <?php echo e(\Carbon\Carbon::parse($user->last_seen_at)->diffForHumans()); ?>

        <?php endif; ?>
      </p>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <form method="POST" action="<?php echo e(route('panel.users.toggle-premium', $user)); ?>">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <button class="btn-p <?php echo e($user->is_premium ? 'danger' : 'warning'); ?> ghost">
        <i class="bi bi-star<?php echo e($user->is_premium ? '-fill' : ''); ?>"></i>
        <?php echo e($user->is_premium ? 'Premium olish' : 'Premium berish'); ?>

      </button>
    </form>
    <a href="<?php echo e(route('panel.users.edit', $user)); ?>" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>
</div>


<div class="p-card mb-3 fade-up">

  
  <div style="padding:20px 24px 16px;display:flex;align-items:center;
              gap:18px;flex-wrap:wrap;border-bottom:1px solid var(--p-border)">

    
    <div style="width:66px;height:66px;border-radius:50%;overflow:hidden;flex-shrink:0;
                background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                display:flex;align-items:center;justify-content:center;
                font-size:24px;font-weight:700;color:#fff">
      <?php if($user->avatar): ?>
        <img src="<?php echo e($user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
        <?php echo e(strtoupper(substr($user->name ?? 'U', 0, 1))); ?>

      <?php endif; ?>
    </div>

    
    <div style="flex:1;min-width:180px">
      <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:3px">
        <span style="font-size:16px;font-weight:700;color:var(--p-text)">
          <?php echo e($user->name); ?> <?php echo e($user->lastname); ?>

        </span>
        <?php if($user->is_premium): ?>
          <span class="s-pill warning" style="font-size:10px">⭐ Premium</span>
        <?php endif; ?>
        <?php if($user->isVerified): ?>
          <span class="s-pill success" style="font-size:10px">Ishonchli</span>
        <?php endif; ?>
        <?php if($user->isSupport ?? false): ?>
          <span class="s-pill info" style="font-size:10px">Support</span>
        <?php endif; ?>
      </div>
      <?php if($user->role_emoji || $user->role_title): ?>
        <div style="font-size:12px;color:var(--p-hint);margin-bottom:3px">
          <?php echo e($user->role_emoji); ?> <?php echo e($user->role_title); ?>

          <?php if($user->role_place): ?> · <?php echo e($user->role_place); ?> <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if($user->bio): ?>
        <div style="font-size:12px;color:var(--p-muted);font-style:italic;line-height:1.5">
          "<?php echo e(Str::limit($user->bio, 100)); ?>"
        </div>
      <?php endif; ?>
    </div>

    
    <div style="display:flex;flex-direction:column;gap:6px;min-width:200px">
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-telephone" style="color:var(--p-muted);font-size:12px;width:14px"></i>
        <span style="font-size:13px;color:var(--p-text);font-family:'DM Mono',monospace">
          <?php echo e($user->phone_number ?: '—'); ?>

        </span>
      </div>
      <?php if($user->email): ?>
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-envelope" style="color:var(--p-muted);font-size:12px;width:14px"></i>
        <span style="font-size:12px;color:var(--p-muted)"><?php echo e($user->email); ?></span>
      </div>
      <?php endif; ?>
      <?php if($user->is_premium && $user->premium_until): ?>
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-star-fill" style="color:var(--p-warning);font-size:11px;width:14px"></i>
        <span style="font-size:12px;color:var(--p-warning)">
          Premium: <?php echo e(\Carbon\Carbon::parse($user->premium_until)->format('d.m.Y')); ?> gacha
        </span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  
  <?php
    $stripStats = [
      ['bi-bag-check',  'Buyurtmalar', $orderCount,                              'accent'],
      ['bi-cash-stack', 'Xarid',       number_format($totalSpent).' UZS',        'success'],
      ['bi-wallet2',    'Balans',      number_format($user->real_balance??0),     'warning'],
      ['bi-star-fill',  'Cashback',    number_format($user->cashback??0),         'info'],
      ['bi-gift',       'Sertifikat',  $giftCertCount,                            'accent'],
      ['bi-box-seam',   'Mystery Box', $mysterySubCount,                          'muted'],
      ['bi-cart3',      'Savat',       $cartItems->count(),                       'muted'],
      ['bi-people',     'Followers',   $followersCount,                           'muted'],
      ['bi-robot',      'AI limit',    $user->ai_limit??0,                        'muted'],
    ];
  ?>
  <div style="display:flex;flex-wrap:wrap">
    <?php $__currentLoopData = $stripStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$icon,$lbl,$val,$clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div style="flex:1;min-width:80px;padding:10px 12px;text-align:center;
                <?php echo e($i>0?'border-left:1px solid var(--p-border)':''); ?>">
      <div style="font-size:14px;font-weight:700;font-family:'DM Mono',monospace;
                  color:var(--p-<?php echo e($clr); ?>)"><?php echo e($val); ?></div>
      <div style="font-size:9px;color:var(--p-hint);margin-top:2px;text-transform:uppercase;
                  letter-spacing:.06em;display:flex;align-items:center;
                  justify-content:center;gap:3px">
        <i class="bi <?php echo e($icon); ?>" style="font-size:9px"></i><?php echo e($lbl); ?>

      </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</div>


<div class="row g-3">

  
  <div class="col-xl-4">

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-wallet2 me-1"></i>Moliyaviy</div>
      </div>
      <div style="padding:0 18px 8px">
        <?php $__currentLoopData = [
          ['Balans',   number_format($user->real_balance??0).' UZS', 'warning'],
          ['Cashback', number_format($user->cashback??0).' UZS',     'success'],
          ['AI limit', ($user->ai_limit??0).' ta',                   'info'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v,$clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:13px;font-weight:600;font-family:'DM Mono',monospace;
                       color:var(--p-<?php echo e($clr); ?>)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    
    <?php if($devices && count($devices)): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-phone me-1"></i>Qurilmalar</div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e(count($devices)); ?> ta</span>
      </div>
      <div style="padding:0 18px 8px">
        <?php $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;align-items:center;gap:9px;
                    padding:7px 0;border-bottom:1px solid var(--p-border);
                    <?php echo e($loop->last?'border-bottom:none':''); ?>">
          <div style="width:28px;height:28px;border-radius:6px;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center;
                      font-size:12px;color:var(--p-muted);flex-shrink:0">
            <i class="bi bi-<?php echo e(($dev->platform??'')==='ios'?'apple':'android2'); ?>"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?php echo e($dev->device_name ?: Str::limit($dev->device_id??'Qurilma',16)); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint)">
              <?php echo e(strtoupper($dev->platform??'')); ?>

              <?php if($dev->created_at??null): ?>
                · <?php echo e(\Carbon\Carbon::parse($dev->created_at)->format('d.m.Y')); ?>

              <?php endif; ?>
            </div>
          </div>
          <span class="s-pill <?php echo e(($dev->fcm_token??null)?'success':'muted'); ?>"
                style="font-size:9px">FCM</span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($addresses && count($addresses)): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-geo-alt me-1" style="color:var(--p-accent)"></i>Manzillar
        </div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e(count($addresses)); ?> ta</span>
      </div>
      <div style="padding:0 18px 8px">
        <?php $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $isMain = (int)($addr->id??0) === (int)($user->mainAddressID??0); ?>
        <div style="display:flex;gap:9px;padding:9px 0;
                    border-bottom:1px solid var(--p-border);
                    <?php echo e($loop->last?'border-bottom:none':''); ?>">
          <div style="width:26px;height:26px;border-radius:6px;flex-shrink:0;margin-top:1px;
                      background:<?php echo e($isMain?'var(--p-accent-d)':'var(--p-elevated)'); ?>;
                      color:<?php echo e($isMain?'var(--p-accent)':'var(--p-hint)'); ?>;
                      display:flex;align-items:center;justify-content:center;font-size:12px">
            <i class="bi bi-geo-alt<?php echo e($isMain?'-fill':''); ?>"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text);
                        line-height:1.4;margin-bottom:4px">
              <?php echo e($addr->fullAddress ?? '—'); ?>

              <?php if($isMain): ?>
                <span class="s-pill accent" style="font-size:9px;margin-left:3px">Asosiy</span>
              <?php endif; ?>
            </div>
            <?php if(($addr->lat??null) && ($addr->lon??null)): ?>
            <a href="https://maps.yandex.uz/?text=<?php echo e($addr->lat); ?>+<?php echo e($addr->lon); ?>&z=16"
               target="_blank"
               style="font-size:10px;color:var(--p-info);text-decoration:none;
                      display:inline-flex;align-items:center;gap:3px">
              <i class="bi bi-map" style="font-size:10px"></i> Xarita
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if(isset($cards) && $cards->count()): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-credit-card me-1" style="color:var(--p-accent)"></i>Bank kartalar
        </div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e($cards->count()); ?> ta</span>
      </div>
      <div style="padding:0 18px 8px">
        <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $num    = $card->card_number ?? '';
          $masked = strlen($num) >= 16
            ? substr($num,0,4).' **** **** '.substr($num,-4) : ($num ?: '—');
        ?>
        <div style="display:flex;align-items:center;gap:9px;padding:7px 0;
                    border-bottom:1px solid var(--p-border);
                    <?php echo e($loop->last?'border-bottom:none':''); ?>">
          <div style="width:38px;height:24px;border-radius:4px;flex-shrink:0;
                      background:linear-gradient(135deg,#1e2340,#2d3561);
                      display:flex;align-items:center;justify-content:center">
            <i class="bi bi-credit-card-2-front" style="font-size:11px;color:#fff;opacity:.85"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:600;font-family:'DM Mono',monospace;
                        color:var(--p-text);letter-spacing:.04em"><?php echo e($masked); ?></div>
            <div style="font-size:10px;color:var(--p-hint)">
              <?php echo e(\Carbon\Carbon::parse($card->created_at)->format('d.m.Y')); ?>

            </div>
          </div>
          <form method="POST"
                action="<?php echo e(route('panel.users.card.destroy', [$user, $card])); ?>"
                onsubmit="return confirm('<?php echo e(addslashes($masked)); ?> — o\'chirilsinmi?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button class="btn-p danger sm"
                    style="width:26px;height:26px;padding:0;flex-shrink:0">
              <i class="bi bi-trash" style="font-size:10px"></i>
            </button>
          </form>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($followers->count() || $following->count()): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-people me-1"></i>Ijtimoiy</div>
      </div>
      <div style="padding:12px 18px">
        <?php if($followers->count()): ?>
        <div style="<?php echo e($following->count()?'margin-bottom:14px':''); ?>">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:7px">
            Obunachilari · <?php echo e($followersCount); ?> ta
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php $__currentLoopData = $followers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('panel.users.show', $f->id)); ?>"
               title="<?php echo e($f->name); ?> <?php echo e($f->lastname); ?>"
               style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff;text-decoration:none">
              <?php if($f->avatar): ?>
                <img src="<?php echo e($f->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?> <?php echo e(strtoupper(substr($f->name,0,1))); ?> <?php endif; ?>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if($followersCount > 8): ?>
              <span style="font-size:10px;color:var(--p-hint);padding:8px 4px;
                           align-self:center">+<?php echo e($followersCount-8); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if($following->count()): ?>
        <div style="<?php echo e($followers->count()?'border-top:1px solid var(--p-border);padding-top:14px':''); ?>">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:7px">
            Obunalar · <?php echo e($followingCount); ?> ta
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php $__currentLoopData = $following; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('panel.users.show', $f->id)); ?>"
               title="<?php echo e($f->name); ?> <?php echo e($f->lastname); ?>"
               style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-info),#0ea5e9);
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff;text-decoration:none">
              <?php if($f->avatar): ?>
                <img src="<?php echo e($f->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?> <?php echo e(strtoupper(substr($f->name,0,1))); ?> <?php endif; ?>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="col-xl-8">

    <div class="tab-pills fade-up mb-3">
      <?php $__currentLoopData = [
        ['orders',   'Buyurtmalar',  'bi-bag-check',  $orderCount],
        ['cart',     'Savat',        'bi-cart3',       $cartItems->count()],
        ['gifts',    'Sertifikatlar','bi-gift',         $giftCertCount],
        ['mystery',  'Mystery Box',  'bi-box-seam',     $mysterySubCount],
        ['bookclub', 'Book Club',    'bi-chat-quote',   $bcPostsCount + $bcRepostsCount],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key,$lbl,$icon,$cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(request()->fullUrlWithQuery(['section'=>$key])); ?>"
         class="tab-pill <?php echo e($activeSection===$key?'active':''); ?>">
        <i class="bi <?php echo e($icon); ?>" style="font-size:12px"></i>
        <?php echo e($lbl); ?>

        <?php if($cnt > 0): ?>
          <span class="tab-count"><?php echo e($cnt); ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if($activeSection === 'orders'): ?>

      <?php if($orderCount === 0): ?>
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-bag-x"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">Buyurtmalar yo'q</div>
      </div>
      <?php else: ?>
      <div class="p-card fade-up">
        <div class="p-card-header">
          <div class="p-card-title">So'nggi buyurtmalar</div>
          <a href="<?php echo e(route('panel.orders.index', ['user_id'=>$user->id])); ?>"
             class="btn-p ghost sm" style="font-size:11px">
            Barchasi <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Summa</th><th>Status</th><th>To'lov</th><th>Sana</th><th></th></tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $stMap=['A'=>'ob-a','P'=>'ob-p','B'=>'ob-b','C'=>'ob-c','F'=>'ob-f'];
                $stLbl=['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor'];
                $pCls=match($order->paymentStatus){2=>'success',3=>'danger',1=>'warning',default=>'muted'};
                $pLbl=match($order->paymentStatus){2=>"To'langan",3=>'Rad',1=>'Karta',default=>'Naqd'};
              ?>
              <tr>
                <td>
                  <a href="<?php echo e(route('panel.orders.show',$order)); ?>"
                     style="font-family:'DM Mono',monospace;color:var(--p-accent);
                            font-weight:600;font-size:12px">#<?php echo e($order->id); ?></a>
                </td>
                <td style="font-family:'DM Mono',monospace;font-weight:600;
                           font-size:13px;color:var(--p-text)">
                  <?php echo e(number_format($order->amount)); ?>

                </td>
                <td>
                  <span class="o-badge <?php echo e($stMap[$order->status]??'ob-p'); ?>">
                    <?php echo e($stLbl[$order->status]??$order->status); ?>

                  </span>
                </td>
                <td>
                  <span class="s-pill <?php echo e($pCls); ?>" style="font-size:10px"><?php echo e($pLbl); ?></span>
                </td>
                <td style="font-size:11px;color:var(--p-hint);
                           font-family:'DM Mono',monospace">
                  <?php echo e($order->created_at?->format('d.m H:i')); ?>

                </td>
                <td>
                  <a href="<?php echo e(route('panel.orders.show',$order)); ?>" class="btn-p ghost sm">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    
    <?php elseif($activeSection === 'cart'): ?>

      <?php if($cartItems->count() === 0): ?>
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-cart-x"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">Savat bo'sh</div>
      </div>
      <?php else: ?>
      <div class="p-card fade-up">
        <div class="p-card-header">
          <div class="p-card-title">Savatdagi mahsulotlar</div>
          <span style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;
                       color:var(--p-success)">
            <?php echo e(number_format($cartTotal)); ?> UZS
          </span>
        </div>
        <?php $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;align-items:center;gap:12px;
                    padding:11px 18px;border-top:1px solid var(--p-border)">
          <div style="width:<?php echo e($item->product_type==='book'?'34px':'38px'); ?>;
                      height:<?php echo e($item->product_type==='book'?'46px':'38px'); ?>;
                      border-radius:<?php echo e($item->product_type==='book'?'4px':'7px'); ?>;
                      overflow:hidden;flex-shrink:0;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center">
            <?php if($item->image): ?>
              <img src="<?php echo e($item->image); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <i class="bi bi-<?php echo e($item->product_type==='book'?'book':'box'); ?>"
                 style="color:var(--p-hint);font-size:12px"></i>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:4px;margin-bottom:2px">
              <span class="s-pill <?php echo e($item->product_type==='book'?'info':'warning'); ?>"
                    style="font-size:9px">
                <?php echo e($item->product_type==='book'?'Kitob':'Kanstovar'); ?>

              </span>
              <?php if($item->variant): ?>
                <span class="s-pill muted" style="font-size:9px"><?php echo e($item->variant); ?></span>
              <?php endif; ?>
            </div>
            <a href="<?php echo e($item->route); ?>" target="_blank"
               style="font-size:13px;font-weight:500;color:var(--p-text);
                      text-decoration:none;display:block;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?php echo e($item->name); ?>

            </a>
            <div style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace">
              <?php echo e(number_format($item->price)); ?> × <?php echo e($item->count); ?>

            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:13px;font-weight:700;font-family:'DM Mono',monospace;
                        color:var(--p-text)"><?php echo e(number_format($item->subtotal)); ?></div>
            <div style="font-size:10px;color:var(--p-hint)">UZS</div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:11px 18px;border-top:1px solid var(--p-border);
                    background:var(--p-elevated);display:flex;
                    justify-content:space-between;align-items:center">
          <span style="font-size:12px;color:var(--p-hint)">
            <?php echo e($cartItems->count()); ?> ta mahsulot
          </span>
          <span style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;
                       color:var(--p-success)"><?php echo e(number_format($cartTotal)); ?> UZS</span>
        </div>
      </div>
      <?php endif; ?>

    
    <?php elseif($activeSection === 'gifts'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-gift me-1" style="color:var(--p-accent)"></i> Gift Sertifikatlar
        </div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e($giftCertCount); ?> ta</span>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>Kod</th>
              <th>Rol</th>
              <th style="text-align:right">Miqdor</th>
              <th>Qabul qiluvchi</th>
              <th>Holat</th>
              <th>Yaratildi</th>
              <th>Ishlatildi</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $giftCerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $isBuyer = $cert->buyer_user_id === $user->id;
              $stCls = match($cert->status){
                'used'=>'success','sent'=>'accent','paid'=>'info',
                'cancelled'=>'danger',default=>'warning'
              };
              $stLbl = match($cert->status){
                'pending_payment'=>'Kutilmoqda','paid'=>"To'landi",
                'sent'=>'Yuborildi','used'=>'Ishlatildi',
                'cancelled'=>'Bekor',default=>$cert->status
              };
            ?>
            <tr>
              <td>
                <code style="font-family:'DM Mono',monospace;font-size:11px;font-weight:700;
                             color:var(--p-accent);background:var(--p-accent-d);
                             padding:2px 7px;border-radius:4px"><?php echo e($cert->code); ?></code>
              </td>
              <td>
                <span class="s-pill <?php echo e($isBuyer ? 'info' : 'success'); ?>" style="font-size:10px">
                  <?php echo e($isBuyer ? 'Sotib olgan' : 'Qabul qilgan'); ?>

                </span>
              </td>
              <td style="text-align:right;font-family:'DM Mono',monospace;
                         font-weight:700;font-size:13px;color:var(--p-success)">
                <?php echo e(number_format($cert->nominal_uzs)); ?> UZS
              </td>
              <td style="font-size:12px;color:var(--p-muted)">
                <?php if($isBuyer): ?>
                  <?php echo e($cert->recipient_name ?: ($cert->recipient?->name ?? '—')); ?>

                  <?php if($cert->recipient_phone): ?>
                  <div style="font-size:10px;color:var(--p-hint);font-family:'DM Mono',monospace">
                    <?php echo e($cert->recipient_phone); ?>

                  </div>
                  <?php endif; ?>
                <?php else: ?>
                  <a href="<?php echo e(route('panel.users.show',$cert->buyer_user_id)); ?>"
                     style="font-size:12px;color:var(--p-accent);text-decoration:none">
                    <?php echo e($cert->buyer?->name); ?>

                  </a>
                <?php endif; ?>
              </td>
              <td><span class="s-pill <?php echo e($stCls); ?>" style="font-size:10px"><?php echo e($stLbl); ?></span></td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace">
                <?php echo e($cert->created_at?->format('d.m.Y')); ?>

              </td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace">
                <?php echo e($cert->used_at?->format('d.m.Y') ?? '—'); ?>

              </td>
              <td>
                <a href="<?php echo e(route('panel.gift-certificates.show',$cert)); ?>"
                   class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:24px;color:var(--p-hint)">
                Sertifikatlar yo'q
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <?php if($giftCertCount > 0): ?>
      <?php
        $boughtCerts = $giftCerts->where('buyer_user_id',$user->id);
        $receivedUsed = $giftCerts->where('recipient_user_id',$user->id)->where('status','used');
      ?>
      <div style="padding:12px 20px;background:var(--p-elevated);
                  border-top:1px solid var(--p-border);display:flex;gap:24px;flex-wrap:wrap">
        <?php if($boughtCerts->count()): ?>
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Sotib olgan
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;
                      color:var(--p-accent)"><?php echo e($boughtCerts->count()); ?> ta</div>
        </div>
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Jami sarflagan
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;
                      color:var(--p-text)"><?php echo e(number_format($boughtCerts->sum('nominal_uzs'))); ?> UZS</div>
        </div>
        <?php endif; ?>
        <?php if($receivedUsed->count()): ?>
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Qabul qilib ishlatdi
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'DM Mono',monospace;
                      color:var(--p-success)"><?php echo e(number_format($receivedUsed->sum('nominal_uzs'))); ?> UZS</div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    
    <?php elseif($activeSection === 'mystery'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-box-seam me-1" style="color:var(--p-accent)"></i> Mystery Box obunalari
        </div>
        <span class="s-pill muted" style="font-size:10px"><?php echo e($mysterySubCount); ?> ta</span>
      </div>

      <?php $__empty_1 = true; $__currentLoopData = $mysterySubs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php $addr = is_array($sub->address) ? $sub->address : []; ?>
      <div style="padding:16px 20px;border-top:1px solid var(--p-border)">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              <?php echo e($sub->plan?->name_uz ?? '—'); ?>

              <span class="s-pill <?php echo e($sub->status_color); ?>" style="font-size:10px;margin-left:6px">
                <?php echo e($sub->status_label); ?>

              </span>
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
              <?php echo e($sub->total_months); ?> oy ·
              <?php echo e($sub->books_per_month); ?> kitob/oy ·
              <?php echo e(number_format($sub->price_uzs)); ?> UZS ·
              Boshlandi: <?php echo e($sub->started_at?->format('d.m.Y') ?? '—'); ?>

            </div>
          </div>
          <a href="<?php echo e(route('panel.mystery-box.subscription',$sub)); ?>"
             class="btn-p ghost sm"><i class="bi bi-arrow-up-right-square"></i></a>
        </div>

        
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="flex:1;height:7px;background:var(--p-elevated);border-radius:4px;overflow:hidden">
            <div style="height:100%;border-radius:4px;
                        background:var(--p-<?php echo e($sub->status_color); ?>);
                        width:<?php echo e($sub->progress_pct); ?>%"></div>
          </div>
          <span style="font-size:12px;font-family:'DM Mono',monospace;color:var(--p-muted);
                       white-space:nowrap">
            <?php echo e($sub->delivered_months); ?>/<?php echo e($sub->total_months); ?> oy
          </span>
          <?php if($sub->next_delivery_at): ?>
          <span style="font-size:11px;color:<?php echo e($sub->next_delivery_at->isPast() ? 'var(--p-danger)' : 'var(--p-hint)'); ?>;
                       white-space:nowrap">
            <i class="bi bi-calendar3"></i>
            <?php echo e($sub->next_delivery_at->format('d.m.Y')); ?>

          </span>
          <?php endif; ?>
        </div>

        
        <div style="background:var(--p-elevated);border-radius:8px;padding:10px 12px;
                    display:flex;align-items:flex-start;gap:10px">
          <i class="bi bi-geo-alt" style="color:var(--p-accent);font-size:14px;margin-top:2px"></i>
          <div>
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)">
              <?php echo e($addr['fullName'] ?? '—'); ?>

              <?php if($addr['phoneNumber'] ?? null): ?>
              <span style="font-family:'DM Mono',monospace;color:var(--p-hint);font-size:11px">
                · <?php echo e($addr['phoneNumber']); ?>

              </span>
              <?php endif; ?>
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
              <?php echo e($addr['fullAddress'] ?? '—'); ?>

            </div>
          </div>
          <?php if(($addr['lat'] ?? null) && ($addr['lon'] ?? null)): ?>
          <a href="https://maps.yandex.uz/?text=<?php echo e($addr['lat']); ?>+<?php echo e($addr['lon']); ?>&z=16"
             target="_blank" class="btn-p ghost sm" style="margin-left:auto;flex-shrink:0">
            <i class="bi bi-map"></i>
          </a>
          <?php endif; ?>
        </div>

        
        <?php if($sub->deliveries->count()): ?>
        <div style="margin-top:10px;display:flex;gap:5px;flex-wrap:wrap">
          <?php $__currentLoopData = $sub->deliveries->sortBy('month_number'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a href="<?php echo e(route('panel.mystery-box.subscription',$sub)); ?>"
             style="display:flex;align-items:center;gap:4px;padding:4px 10px;
                    border-radius:5px;font-size:11px;font-family:'DM Mono',monospace;
                    background:var(--p-elevated);border:1px solid var(--p-border);
                    text-decoration:none;
                    color:<?php echo e(match($d->status){
                      'delivered'=>'var(--p-success)',
                      'shipped'=>'var(--p-info)',
                      'preparing'=>'var(--p-warning)',
                      default=>'var(--p-hint)'
                    }); ?>"
             title="<?php echo e($d->status_label); ?>">
            <i class="bi bi-<?php echo e(match($d->status){
              'delivered'=>'check-circle-fill',
              'shipped'=>'truck',
              'preparing'=>'box-seam',
              default=>'hourglass'
            }); ?>"></i>
            <?php echo e($d->month_number); ?>-oy
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div style="padding:36px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Mystery Box obunalari yo'q
      </div>
      <?php endif; ?>
    </div>

    
    <?php elseif($activeSection === 'bookclub'): ?>

      <div class="d-flex gap-2 mb-3">
        <?php $__currentLoopData = [['posts','Postlar',$bcPostsCount],['reposts','Repostlar',$bcRepostsCount]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$l,$cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->fullUrlWithQuery(['section'=>'bookclub','bc_tab'=>$k])); ?>"
           class="btn-p <?php echo e($tab===$k?'':'ghost'); ?> sm">
          <?php echo e($l); ?> <span class="tab-badge"><?php echo e($cnt); ?></span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <?php if($bcPosts->count() === 0): ?>
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-chat-square-text"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">
          <?php echo e($tab==='reposts'?'Repostlar':'Postlar'); ?> yo'q
        </div>
      </div>
      <?php else: ?>
        <?php $__currentLoopData = $bcPosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php echo $__env->make('panel.book-club._post-card', ['post'=>$post,'showUser'=>false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php echo e($bcPosts->appends(request()->except('bc_page'))->links('panel.partials.pagination')); ?>

      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/users/show.blade.php ENDPATH**/ ?>