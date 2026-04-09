<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.kpi-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;padding:20px;position:relative;overflow:hidden;transition:border-color .2s,transform .2s;}
.kpi-card:hover{border-color:var(--p-border2);transform:translateY(-2px);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.kpi-card.green::before{background:var(--p-success)}.kpi-card.blue::before{background:var(--p-accent)}
.kpi-card.yellow::before{background:var(--p-warning)}.kpi-card.red::before{background:var(--p-danger)}
.kpi-card.cyan::before{background:var(--p-info)}.kpi-card.purple::before{background:#7c5cfc}
.kpi-card.teal::before{background:#14b8a6}.kpi-card.pink::before{background:#ec4899}
.kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;margin-bottom:14px;}
.kpi-value{font-size:26px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text);letter-spacing:-.5px;line-height:1;margin-bottom:5px;}
.kpi-label{font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;font-weight:500;margin-bottom:12px;}
.kpi-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--p-border);font-size:12px;}
.kpi-change{display:inline-flex;align-items:center;gap:3px;font-size:12px;font-weight:600;}
.kpi-change.up{color:var(--p-success)}.kpi-change.down{color:var(--p-danger)}.kpi-change.neutral{color:var(--p-muted)}
.dash-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;overflow:hidden;}
.dash-card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 0;margin-bottom:16px;}
.dash-card-title{font-size:14px;font-weight:600;color:var(--p-text);}
.dash-card-sub{font-size:12px;color:var(--p-hint);margin-top:2px;}
.dash-card-body{padding:0 20px 20px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--p-success);box-shadow:0 0 0 2px rgba(34,201,142,.25);animation:pulse 2s infinite;flex-shrink:0;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 2px rgba(34,201,142,.25)}50%{box-shadow:0 0 0 5px rgba(34,201,142,.05)}}
.dash-prog{margin-bottom:13px;}
.dash-prog-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
.dash-prog-label{font-size:12px;color:var(--p-muted);}
.dash-prog-val{font-size:12px;font-weight:600;color:var(--p-text);font-family:'DM Mono',monospace;}
.dash-prog-track{height:5px;background:var(--p-elevated);border-radius:10px;overflow:hidden;}
.dash-prog-fill{height:100%;border-radius:10px;transition:width .8s cubic-bezier(.4,0,.2,1);}
.o-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;}
.o-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.ob-c{background:var(--p-success-d);color:var(--p-success)}.ob-a{background:var(--p-warning-d);color:var(--p-warning)}
.ob-b{background:var(--p-info-d);color:var(--p-info)}.ob-f{background:var(--p-danger-d);color:var(--p-danger)}.ob-p{background:var(--p-elevated);color:var(--p-muted)}
.d-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#fff;flex-shrink:0;overflow:hidden;}
.d-av img{width:100%;height:100%;object-fit:cover;}
.top-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--p-border);}
.top-row:last-child{border-bottom:none;}
.book-thumb{width:36px;height:50px;border-radius:5px;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.book-thumb img{width:100%;height:100%;object-fit:cover;}
.rank-num{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;font-family:'DM Mono',monospace;flex-shrink:0;}
.rn-1{background:rgba(245,166,35,.2);color:var(--p-warning)}.rn-2{background:rgba(139,145,168,.12);color:var(--p-muted)}
.rn-3{background:rgba(205,127,50,.18);color:#cd7f32}.rn-n{background:transparent;color:var(--p-hint)}
.stat-grid{display:grid;border-top:1px solid var(--p-border);margin-top:16px;padding-top:16px;}
.stat-cell{text-align:center;}.stat-cell+.stat-cell{border-left:1px solid var(--p-border);}
.stat-cell-val{font-size:17px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text);}
.stat-cell-lbl{font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-top:2px;}
.fin-row{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid var(--p-border);}
.fin-row:last-child{border-bottom:none;}
.fin-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-right:12px;}
.fin-val{font-family:'DM Mono',monospace;font-size:13px;font-weight:600;text-align:right;}
.fin-month{font-size:10px;color:var(--p-hint);text-align:right;margin-top:1px;font-family:'DM Mono',monospace;}
.alert-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:9px;margin-bottom:8px;font-size:13px;}
.alert-item:last-child{margin-bottom:0;}
.alert-item.danger{background:var(--p-danger-d);border:1px solid rgba(255,92,106,.2);color:var(--p-danger);}
.alert-item.warning{background:var(--p-warning-d);border:1px solid rgba(245,166,35,.2);color:var(--p-warning);}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>


<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">
      <?php echo e(now()->format('d.m.Y, l')); ?> &nbsp;·&nbsp;
      <span style="color:var(--p-success)">
        <span class="live-dot" style="width:6px;height:6px;vertical-align:middle"></span>
        Real vaqt
      </span>
    </p>
  </div>
  <a href="<?php echo e(route('panel.dashboard',['clear_cache'=>1])); ?>" class="btn-p ghost">
    <i class="bi bi-arrow-clockwise"></i> Yangilash
  </a>
</div>


<?php if(count($alerts)): ?>
<div class="fade-up mb-4">
  <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$color,$icon,$title,$desc,$url]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="alert-item <?php echo e($color); ?>">
    <i class="bi <?php echo e($icon); ?>" style="font-size:16px;flex-shrink:0"></i>
    <div style="flex:1"><span style="font-weight:600"><?php echo e($title); ?>:</span> <?php echo e($desc); ?></div>
    <a href="<?php echo e($url); ?>" style="color:inherit;font-weight:700;white-space:nowrap;text-decoration:none">Ko'rish →</a>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>


<div class="row g-3 mb-4">

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card green">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Jami daromad</div>
          <div class="kpi-value"><?php echo e(number_format($totalRevenue/1_000_000,1)); ?><span style="font-size:16px;color:var(--p-muted)">M</span></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">UZS · To'langan</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-success-d);color:var(--p-success)"><i class="bi bi-graph-up-arrow"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up"><i class="bi bi-arrow-up-right"></i> Bugun: <?php echo e(number_format($todayRevenue/1000)); ?>K</span>
        <span style="color:var(--p-hint)">Bu oy: <?php echo e(number_format($monthRevenue/1_000_000,1)); ?>M</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card blue">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Buyurtmalar</div>
          <div class="kpi-value"><?php echo e(number_format($totalOrders)); ?></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta jami</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-accent-d);color:var(--p-accent)"><i class="bi bi-bag-check"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up"><i class="bi bi-plus"></i> <?php echo e($todayOrders); ?> bugun</span>
        <div class="d-flex gap-2">
          <span style="color:var(--p-warning);font-size:11px"><?php echo e($pendingOrders); ?> kutmoqda</span>
          <span style="color:var(--p-danger);font-size:11px"><?php echo e($cancelledOrders); ?> bekor</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card cyan">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Yakunlanish</div>
          <div class="kpi-value"><?php echo e($completionRate); ?><span style="font-size:16px;color:var(--p-muted)">%</span></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px"><?php echo e($completedOrders); ?> yetkazildi</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-info-d);color:var(--p-info)"><i class="bi bi-patch-check"></i></div>
      </div>
      <div class="kpi-footer">
        <div style="flex:1;margin-right:10px">
          <div class="dash-prog-track" style="height:6px">
            <div class="dash-prog-fill" style="width:<?php echo e($completionRate); ?>%;background:var(--p-info)"></div>
          </div>
        </div>
        <span style="font-size:11px;color:var(--p-hint)"><?php echo e($cancellationRate); ?>% bekor</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card yellow">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Foydalanuvchilar</div>
          <div class="kpi-value"><?php echo e(number_format($totalUsers)); ?></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta ro'yxatdan o'tgan</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-warning-d);color:var(--p-warning)"><i class="bi bi-people"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up">
          <span class="live-dot" style="width:6px;height:6px;vertical-align:middle;margin-right:3px"></span>
          <?php echo e($onlineUsers); ?> online
        </span>
        <span style="color:var(--p-hint);font-size:11px">+<?php echo e($newUsersToday); ?> bugun</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card pink">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Gift Sertifikatlar</div>
          <div class="kpi-value"><?php echo e(number_format($giftTotal)); ?></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta jami</div>
        </div>
        <div class="kpi-icon" style="background:rgba(236,72,153,.1);color:#ec4899"><i class="bi bi-gift"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change <?php echo e($giftUsed>0?'up':'neutral'); ?>">
          <i class="bi bi-check-circle"></i> <?php echo e($giftUsed); ?> ishlatildi
        </span>
        <?php if($giftPending>0): ?>
          <span style="color:var(--p-warning);font-size:11px"><?php echo e($giftPending); ?> kutmoqda</span>
        <?php else: ?>
          <span style="color:var(--p-hint);font-size:11px"><?php echo e($giftSent); ?> yuborilgan</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card teal">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Mystery Box</div>
          <div class="kpi-value"><?php echo e(number_format($mysteryActive)); ?></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">faol obuna</div>
        </div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.1);color:#14b8a6"><i class="bi bi-box-seam"></i></div>
      </div>
      <div class="kpi-footer">
        <?php if($mysteryDueCount>0): ?>
          <span class="kpi-change down"><i class="bi bi-exclamation-triangle"></i> <?php echo e($mysteryDueCount); ?> navbatda</span>
        <?php else: ?>
          <span class="kpi-change neutral">Navbat yo'q</span>
        <?php endif; ?>
        <span style="color:var(--p-hint);font-size:11px"><?php echo e($mysteryPending); ?> kutmoqda</span>
      </div>
    </div>
  </div>
</div>


<div class="row g-3 mb-4">
  <div class="col-xl-8 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div><div class="dash-card-title">Oylik daromad</div><div class="dash-card-sub">Oxirgi 6 oy · UZS</div></div>
        <a href="<?php echo e(route('panel.orders.index')); ?>" class="btn-p ghost sm">Buyurtmalar <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body"><div id="chartRevenue" style="min-height:260px"></div></div>
    </div>
  </div>
  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div><div class="dash-card-title">Holat taqsimoti</div><div class="dash-card-sub">Jami <?php echo e(number_format($totalOrders)); ?> ta</div></div>
      </div>
      <div class="dash-card-body">
        <div id="chartDonut" style="min-height:200px"></div>
        <div class="stat-grid" style="grid-template-columns:repeat(2,1fr);gap:0">
          <?php $__currentLoopData = [['Yetkazildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="stat-cell" style="padding:10px 6px">
            <div class="stat-cell-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
            <div class="stat-cell-lbl"><?php echo e($l); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>
  </div>
</div>


<?php if($isSuperAdmin): ?>
<div class="row g-3 mb-4">

  
  <div class="col-xl-5 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi bi-calculator me-1" style="color:var(--p-accent)"></i>Moliyaviy hisobot</div>
        <div class="dash-card-sub">Jami · Bu oy</div>
      </div>
      <div class="dash-card-body">

        <?php
          $finRows = [
            ['accent',  'bi-activity',         'GMV (brutto aylanma)',  'Barcha buyurtmalar summasi',                      $gmvTotal,            $gmvMonth,            false],
            ['success', 'bi-check-circle',      "To'langan daromad",    'paymentStatus = 2',                               $totalRevenue,        $monthRevenue,        false],
            ['info',    'bi-truck',             'Yetkazish daromadi',   'Delivery fee',                                    $totalDeliveryIncome, $monthDeliveryIncome, false],
            ['purple',  'bi-percent',           'Seller komissiya',     "O'rtacha {$avgCommissionPct}%",                  $totalCommissionEarned,$monthCommissionEarned,false],
            ['teal',    'bi-box-seam',          'Mystery Box daromad',  'Faol + yakunlangan',                              $mysteryRevTotal,     $mysteryRevMonth,     false],
            ['pink',    'bi-gift',              'Gift Sertifikat',      'Ishlatilgan · Buyurtmada: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0,false],
        ];
          $finCosts = [
            ['danger',  'bi-ticket-perforated', 'Promokod chegirma',    "{$promoOrdersCount} ta buyurtmada",               $totalPromoDiscount,  $monthPromoDiscount,  true],
            ['warning', 'bi-cash-stack',        'Cashback to\'lovi',    "Foydalanuvchilarga qaytarildi",                  $totalCashbackPaid,   $monthCashbackPaid,   true],
            ['muted',   'bi-shop-window',       'Seller to\'lovlari',   "Approved · Kutilmoqda: ".number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout,true],
            ['muted',   'bi-bicycle',           'Kuryer to\'lovlari',   "Delivered · Kutilmoqda: ".number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout,true],
            ['danger',  'bi-x-circle',          'Bekor buyurtma',       'status=F buyurtmalar',                            $cancelledRevLoss,    $cancelledMonthLoss,  true],
        ];
        ?>

        <?php $__currentLoopData = $finRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$clr,$ico,$lbl,$sub,$total,$month,$minus]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="fin-row">
          <?php
            $finBg  = match($clr){ 'purple'=>'rgba(124,92,252,.12)', 'teal'=>'rgba(20,184,166,.1)', 'pink'=>'rgba(236,72,153,.1)', default=>"var(--p-{$clr}-d)" };
            $finClr = match($clr){ 'purple'=>'#7c5cfc', 'teal'=>'#14b8a6', 'pink'=>'#ec4899', default=>"var(--p-{$clr})" };
          ?>
          <div class="fin-icon" style="background:<?php echo e($finBg); ?>;color:<?php echo e($finClr); ?>">
            <i class="bi <?php echo e($ico); ?>"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;color:var(--p-text)"><?php echo e($lbl); ?></div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px"><?php echo e($sub); ?></div>
          </div>
          <div>
            <div class="fin-val" style="color:<?php echo e($finClr); ?>">
              <?php echo e(number_format($total/1_000_000,1)); ?>M
            </div>
            <?php if($month > 0): ?><div class="fin-month">Bu oy: <?php echo e(number_format($month/1000)); ?>K</div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div style="height:1px;background:var(--p-border);margin:12px 0"></div>

        <?php $__currentLoopData = $finCosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$clr,$ico,$lbl,$sub,$total,$month,$minus]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="fin-row">
          <?php $costBg = $clr==='muted' ? 'var(--p-elevated)' : "var(--p-{$clr}-d)"; ?>
          <div class="fin-icon" style="background:<?php echo e($costBg); ?>;color:var(--p-<?php echo e($clr); ?>)">
            <i class="bi <?php echo e($ico); ?>"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;color:var(--p-<?php echo e($clr); ?>)"><?php echo e($lbl); ?></div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px"><?php echo e($sub); ?></div>
          </div>
          <div>
            <div class="fin-val" style="color:var(--p-<?php echo e($clr); ?>)">−<?php echo e(number_format($total/1_000_000,1)); ?>M</div>
            <?php if($month > 0): ?><div class="fin-month">Bu oy: −<?php echo e(number_format($month/1000)); ?>K</div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div style="height:1px;background:var(--p-border);margin:12px 0"></div>

        
        <div class="fin-row" style="background:var(--p-success-d);border-radius:10px;padding:12px;margin:-4px">
          <div class="fin-icon" style="background:var(--p-success-d);color:var(--p-success)">
            <i class="bi bi-stars"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;font-weight:700;color:var(--p-text)">Platform sof foyda</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px">Komissiya + Yetkazish − Chiqimlar</div>
          </div>
          <div>
            <div class="fin-val" style="color:var(--p-success);font-size:17px">
              <?php echo e(number_format($platformProfit/1_000_000,2)); ?>M
            </div>
            <div class="fin-month">Bu oy: <?php echo e(number_format($platformProfitMonth/1000)); ?>K</div>
          </div>
        </div>

      </div>
    </div>
  </div>

  
  <div class="col-xl-7 fade-up">

    <div class="dash-card mb-3">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">AOV trend (o'rtacha buyurtma)</div>
          <div class="dash-card-sub">Joriy: <?php echo e(number_format($avgOrderValue)); ?> UZS · O'rtacha komissiya: <?php echo e($avgCommissionPct); ?>%</div>
        </div>
      </div>
      <div class="dash-card-body"><div id="chartAov" style="min-height:130px"></div></div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Mahsulot turi</div>
            <div class="dash-card-sub">Daromad bo'yicha</div>
          </div>
          <div class="dash-card-body">
            <?php
              $typeTotal = max(1,$revenueByType['book']+$revenueByType['stationery']);
              $bookPct = round($revenueByType['book']/$typeTotal*100,1);
              $statPct = round($revenueByType['stationery']/$typeTotal*100,1);
            ?>
            <div id="chartTypePie" style="min-height:120px"></div>
            <?php $__currentLoopData = [['Kitoblar',$revenueByType['book'],'accent','bi-book',$bookPct],['Kanstovar',$revenueByType['stationery'],'warning','bi-pencil-square',$statPct]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i,$p]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi <?php echo e($i); ?>" style="font-size:13px;color:var(--p-<?php echo e($c); ?>);width:16px"></i>
              <span style="flex:1;font-size:12px;color:var(--p-muted)"><?php echo e($l); ?></span>
              <span style="font-size:12px;font-weight:600;font-family:'DM Mono',monospace;color:var(--p-text)"><?php echo e(number_format($v/1000)); ?>K</span>
              <span class="s-pill <?php echo e($c); ?>" style="font-size:10px;min-width:36px;text-align:center"><?php echo e($p); ?>%</span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Xaridorlar (bu oy)</div>
            <div class="dash-card-sub">Yangi vs Takroriy</div>
          </div>
          <div class="dash-card-body">
            <?php $totalB=max(1,$repeatBuyersMonth+$newBuyersMonth); $repeatPct=$totalB>1?round($repeatBuyersMonth/$totalB*100):0; ?>
            <div id="chartBuyers" style="min-height:120px"></div>
            <?php $__currentLoopData = [['Yangi xaridor',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'accent','2+ marta']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$s]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="d-flex align-items-center gap-2 mb-2">
              <div style="width:10px;height:10px;border-radius:50%;background:var(--p-<?php echo e($c); ?>);flex-shrink:0"></div>
              <div style="flex:1"><div style="font-size:12px;color:var(--p-muted)"><?php echo e($l); ?></div><div style="font-size:10px;color:var(--p-hint)"><?php echo e($s); ?></div></div>
              <span style="font-size:13px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text)"><?php echo e(number_format($v)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--p-border)">
              <div class="d-flex justify-content-between" style="font-size:11px;color:var(--p-hint);margin-bottom:4px">
                <span>Qayta qaytish</span>
                <span style="color:var(--p-accent);font-weight:600"><?php echo e($repeatPct); ?>%</span>
              </div>
              <div class="dash-prog-track">
                <div class="dash-prog-fill" style="width:<?php echo e($repeatPct); ?>%;background:var(--p-accent)"></div>
              </div>
            </div>
            <?php if($deliveryTypeSplit->count()): ?>
            <div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--p-border)">
              <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Yetkazish turi</div>
              <?php $__currentLoopData = $deliveryTypeSplit->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="d-flex justify-content-between mb-1" style="font-size:12px">
                <span style="color:var(--p-muted)"><?php echo e($dt->deliveryType); ?></span>
                <span style="font-family:'DM Mono',monospace;color:var(--p-text);font-weight:600"><?php echo e(number_format($dt->cnt)); ?> ta</span>
              </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>


<div class="row g-3 mb-4">
  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Foydalanuvchilar holati</div>
        <div class="dash-card-sub"><?php echo e(number_format($totalUsers)); ?> ta jami</div>
      </div>
      <div class="dash-card-body">
        <?php $__currentLoopData = [['Online (5 daqiqa)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'accent'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolat (30+ kun)',$isolatedUsers,'danger'],['Bu hafta yangi',$newUsersWeek,'muted']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="dash-prog">
          <div class="dash-prog-top">
            <span class="dash-prog-label"><?php echo e($l); ?></span>
            <span class="dash-prog-val"><?php echo e(number_format($v)); ?></span>
          </div>
          <div class="dash-prog-track">
            <div class="dash-prog-fill" style="width:<?php echo e($totalUsers>0?min(round($v/$totalUsers*100),100):0); ?>%;background:var(--p-<?php echo e($c); ?>)"></div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);gap:0">
          <?php $__currentLoopData = [['Jami',$totalUsers,'text'],['Premium',$premiumUsers,'warning'],['+Bugun',$newUsersToday,'success']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="stat-cell" style="padding:10px 0">
            <div class="stat-cell-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
            <div class="stat-cell-lbl"><?php echo e($l); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px">7 kunlik yangi userlar</div>
          <div id="chartUserSparkline" style="min-height:60px"></div>
        </div>
        <?php if($isolatedUsers>0): ?>
        <div class="alert-item danger" style="margin-top:14px">
          <i class="bi bi-person-x" style="flex-shrink:0"></i>
          <span><?php echo e(number_format($isolatedUsers)); ?> ta user 30+ kun yo'q</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Hozir online</div>
        <div class="dash-card-sub" style="display:flex;align-items:center;gap:6px">
          <span class="live-dot"></span> <?php echo e($onlineUsers); ?> nafar
        </div>
      </div>
      <div class="dash-card-body">
        <?php $__empty_1 = true; $__currentLoopData = $onlineUsersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <a href="<?php echo e(route('panel.users.show',$u->id)); ?>" style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc)">
            <?php if($u->avatar): ?><img src="<?php echo e($u->avatar); ?>"><?php else: ?><?php echo e(strtoupper(substr($u->name??'U',0,1))); ?><?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:500;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo e($u->name); ?> <?php echo e($u->lastname); ?></div>
            <div style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace"><?php echo e($u->last_seen_at ? \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() : '—'); ?></div>
          </div>
          <span class="live-dot"></span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          <i class="bi bi-wifi-off" style="font-size:28px;display:block;margin-bottom:8px"></i>Hozir hech kim online emas
        </div>
        <?php endif; ?>
        <a href="<?php echo e(route('panel.users.index')); ?>" class="btn-p ghost" style="width:100%;justify-content:center;margin-top:14px">
          Barcha foydalanuvchilar <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mahsulotlar</div>
        <div class="dash-card-sub">Eng ko'p sotilganlar</div>
      </div>
      <div class="dash-card-body">
        <?php $__empty_1 = true; $__currentLoopData = $topMixedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $imgs=is_array($product->images)?$product->images:json_decode($product->images??'[]',true);$img=$imgs[0]??null; ?>
        <div class="top-row">
          <span class="rank-num <?php echo e($i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n'))); ?>"><?php echo e($i+1); ?></span>
          <div class="book-thumb">
            <?php if($img): ?>
              <img src="<?php echo e($img); ?>">
            <?php else: ?>
              <i class="bi bi-<?php echo e($product->_type==='stationery'?'box':'book'); ?>" style="color:var(--p-hint);font-size:13px"></i>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo e($product->name); ?></div>
            <div style="display:flex;align-items:center;gap:4px;margin-top:2px">
              <span class="s-pill <?php echo e($product->_type==='stationery'?'warning':'info'); ?>" style="font-size:9px;padding:1px 5px"><?php echo e($product->_type==='stationery'?'Kanstovar':'Kitob'); ?></span>
              <span style="font-size:11px;color:var(--p-hint)"><?php echo e(number_format($product->total_revenue/1000)); ?>K UZS</span>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:13px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text)"><?php echo e(number_format($product->sold_count)); ?></div>
            <div style="font-size:10px;color:var(--p-hint)">ta</div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="text-align:center;padding:30px;color:var(--p-hint)"><i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>Ma'lumot yo'q</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<div class="row g-3 mb-4">

  <?php if($mysteryDueToday->count()||$mysteryDueSoon->count()): ?>
  <div class="col-xl-4 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">
            <i class="bi bi-box-seam me-1" style="color:<?php echo e($mysteryDueCount>0?'var(--p-danger)':'var(--p-accent)'); ?>"></i>Mystery Box navbati
          </div>
          <div class="dash-card-sub">
            <?php if($mysteryDueCount>0): ?>
              <span style="color:var(--p-danger)"><?php echo e($mysteryDueCount); ?> ta kechikdi!</span>
            <?php else: ?>
              7 kun ichida <?php echo e($mysteryDueSoon->count()); ?> ta
            <?php endif; ?>
          </div>
        </div>
        <a href="<?php echo e(route('panel.mystery-box.subscriptions',['tab'=>'active'])); ?>" class="btn-p ghost sm">Barchasi</a>
      </div>
      <div class="dash-card-body">
        <?php if($mysteryDueToday->count()): ?>
        <div style="font-size:11px;color:var(--p-danger);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px"><i class="bi bi-exclamation-triangle me-1"></i>Bugun / Kechikkan</div>
        <?php $__currentLoopData = $mysteryDueToday; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('panel.mystery-box.subscription',$sub)); ?>" style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,#14b8a6,#0d9488)"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div style="font-size:11px;color:var(--p-hint)"><?php echo e($sub->plan?->name_uz); ?> · <?php echo e($sub->next_delivery_at?->diffForHumans()); ?></div>
          </div>
          <span class="s-pill danger" style="font-size:10px">Navbatda</span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
        <?php if($mysteryDueSoon->count()): ?>
        <div style="font-size:11px;color:var(--p-hint);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin:<?php echo e($mysteryDueToday->count()?'12px':'0'); ?> 0 8px">Yaqin 7 kun</div>
        <?php $__currentLoopData = $mysteryDueSoon; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('panel.mystery-box.subscription',$sub)); ?>" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,#14b8a6,#0d9488)"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text)"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div style="font-size:10px;color:var(--p-hint)"><?php echo e($sub->next_delivery_at?->format('d.m.Y')); ?></div>
          </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-xl-<?php echo e(($mysteryDueToday->count()||$mysteryDueSoon->count())?'4':'5'); ?> fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mijozlar</div>
        <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
      </div>
      <div class="dash-card-body">
        <table class="p-table">
          <thead><tr><th>#</th><th>Mijoz</th><th>Buyurtma</th><th style="text-align:right">Xarid</th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $topBuyers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $buyer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><span class="rank-num <?php echo e($i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n'))); ?>"><?php echo e($i+1); ?></span></td>
              <td>
                <a href="<?php echo e(route('panel.users.show',$buyer->user_id)); ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none">
                  <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc)">
                    <?php if($buyer->user?->avatar): ?><img src="<?php echo e($buyer->user->avatar); ?>"><?php else: ?><?php echo e(strtoupper(substr($buyer->user?->name??'U',0,1))); ?><?php endif; ?>
                  </div>
                  <span style="font-size:12.5px;font-weight:500;color:var(--p-text)"><?php echo e($buyer->user?$buyer->user->name.' '.$buyer->user->lastname:'ID:'.$buyer->user_id); ?></span>
                </a>
              </td>
              <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)"><?php echo e($buyer->order_count); ?> ta</td>
              <td style="text-align:right;font-family:'DM Mono',monospace;font-size:13px;font-weight:700;color:var(--p-success)"><?php echo e(number_format($buyer->total_spent/1000)); ?>K</td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--p-hint)">Ma'lumot yo'q</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-<?php echo e(($mysteryDueToday->count()||$mysteryDueSoon->count())?'4':'7'); ?> fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div><div class="dash-card-title">So'nggi buyurtmalar</div><div class="dash-card-sub">Oxirgi 10 ta</div></div>
        <a href="<?php echo e(route('panel.orders.index')); ?>" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive">
          <table class="p-table" style="min-width:400px">
            <thead><tr><th>#ID</th><th>Mijoz</th><th>Summa</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php $bc=match($order['status']){'Yetkazildi'=>'ob-c',"Yo'lda"=>'ob-b','Kutilmoqda'=>'ob-a','Bekor qilindi'=>'ob-f',default=>'ob-p'}; ?>
              <tr>
                <td>
                  <span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-weight:600">#<?php echo e($order['id']); ?></span>
                  <?php if($order['gift']): ?><span>🎁</span><?php endif; ?>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc);font-size:11px">
                      <?php if($order['avatar']): ?><img src="<?php echo e($order['avatar']); ?>"><?php else: ?><?php echo e(strtoupper(substr($order['customer'],0,1))); ?><?php endif; ?>
                    </div>
                    <span style="font-size:12.5px;font-weight:500;color:var(--p-text)"><?php echo e($order['customer']); ?></span>
                  </div>
                </td>
                <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-text);font-size:13px"><?php echo e($order['amount']); ?> <span style="font-size:10px;color:var(--p-hint)">UZS</span></td>
                <td><span class="o-badge <?php echo e($bc); ?>"><?php echo e($order['status']); ?></span></td>
                <td style="font-size:11px;color:var(--p-hint);font-family:'DM Mono',monospace;white-space:nowrap"><?php echo e($order['date']); ?></td>
                <td><a href="<?php echo e(route('panel.orders.show',$order['id'])); ?>" class="btn-p ghost sm"><i class="bi bi-eye"></i></a></td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--p-hint)">Buyurtmalar yo'q</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="row g-3 mb-2">
  <?php $__currentLoopData = [['Sotuvchilar',$approvedSellers,$totalSellers,'sellers.index','success','bi-shop-window',$pendingSellers,'Yangi ariza'],['Kuryerlar',$activeCouriers,$totalCouriers,'couriers.index','info','bi-bicycle',0,'']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$title,$active,$total,$route,$color,$icon,$pending,$pendingLbl]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-md-6 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi <?php echo e($icon); ?> me-1" style="color:var(--p-<?php echo e($color); ?>)"></i><?php echo e($title); ?></div>
        <a href="<?php echo e(route('panel.'.$route)); ?>" class="btn-p ghost sm">Ko'rish</a>
      </div>
      <div class="dash-card-body">
        <div style="display:flex;align-items:center;gap:20px">
          <div style="text-align:center">
            <div style="font-size:36px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-<?php echo e($color); ?>)"><?php echo e(number_format($active)); ?></div>
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">Faol</div>
          </div>
          <div style="flex:1">
            <div class="dash-prog-top">
              <span class="dash-prog-label">Faollik</span>
              <span class="dash-prog-val"><?php echo e($total>0?round($active/$total*100):0); ?>%</span>
            </div>
            <div class="dash-prog-track">
              <div class="dash-prog-fill" style="width:<?php echo e($total>0?round($active/$total*100):0); ?>%;background:var(--p-<?php echo e($color); ?>)"></div>
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:6px">Jami: <?php echo e(number_format($total)); ?> ta</div>
            <?php if($pending>0): ?>
            <div class="alert-item warning" style="margin-top:8px;padding:6px 10px">
              <i class="bi bi-clock" style="flex-shrink:0;font-size:13px"></i><span><?php echo e($pending); ?> ta <?php echo e($pendingLbl); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const isDark = document.documentElement.getAttribute('data-bs-theme') !== 'light';
const C = {
  text:isDark?'#eef0f7':'#1a1d2e', muted:isDark?'#555c75':'#9ca3af',
  grid:isDark?'rgba(255,255,255,0.05)':'rgba(0,0,0,0.06)',
  surface:isDark?'#181c27':'#ffffff',
  accent:'#4f7cff', success:isDark?'#22c98e':'#16a34a',
  warning:isDark?'#f5a623':'#d97706', danger:isDark?'#ff5c6a':'#dc2626',
  info:isDark?'#38bdf8':'#0284c7', teal:'#14b8a6', pink:'#ec4899', purple:'#7c5cfc',
};

<?php $revLabels=collect($monthlyRevenue)->pluck('month')->toJson(); $revAmounts=collect($monthlyRevenue)->map(fn($m)=>round($m['total']/1_000_000,1))->toJson(); ?>

new ApexCharts(document.getElementById('chartRevenue'),{
  series:[{name:'Daromad (mln)',data:<?php echo $revAmounts; ?>}],
  chart:{type:'bar',height:260,toolbar:{show:false},background:'transparent',fontFamily:'DM Sans, sans-serif',animations:{enabled:true,speed:600}},
  colors:[C.accent],plotOptions:{bar:{borderRadius:7,columnWidth:'46%',dataLabels:{position:'top'}}},
  dataLabels:{enabled:true,formatter:v=>v+'M',offsetY:-22,style:{fontSize:'11px',colors:[C.muted],fontFamily:'DM Mono, monospace'}},
  xaxis:{categories:<?php echo $revLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'12px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
  grid:{borderColor:C.grid,strokeDashArray:5,xaxis:{lines:{show:false}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
  fill:{type:'gradient',gradient:{shade:'dark',type:'vertical',gradientToColors:['#2650cc'],stops:[0,100]}},
}).render();

new ApexCharts(document.getElementById('chartDonut'),{
  series:[<?php echo e($completedOrders); ?>,<?php echo e($onwayOrders); ?>,<?php echo e($pendingOrders); ?>,<?php echo e($cancelledOrders); ?>],
  labels:['Yetkazildi',"Yo'lda",'Kutilmoqda','Bekor'],colors:[C.success,C.info,C.warning,C.danger],
  chart:{type:'donut',height:200,toolbar:{show:false},background:'transparent',fontFamily:'DM Sans, sans-serif'},
  legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
  dataLabels:{enabled:false},
  plotOptions:{pie:{donut:{size:'74%',labels:{show:true,total:{show:true,label:'Jami',fontSize:'12px',color:C.muted,formatter:()=>'<?php echo e(number_format($totalOrders)); ?>'},value:{fontSize:'20px',fontWeight:700,color:C.text,fontFamily:'DM Mono, monospace'}}}}},
  stroke:{width:2,colors:[C.surface]},tooltip:{theme:isDark?'dark':'light'},
}).render();

<?php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); ?>
new ApexCharts(document.getElementById('chartUserSparkline'),{
  series:[{name:'Yangi user',data:<?php echo $sparkCounts; ?>}],
  chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
  colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
  xaxis:{categories:<?php echo $sparkDays; ?>},tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();

<?php if($isSuperAdmin): ?>
<?php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); ?>
new ApexCharts(document.getElementById('chartAov'),{
  series:[{name:'AOV',data:<?php echo $aovVals; ?>}],
  chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'DM Sans, sans-serif'},
  colors:[C.teal],stroke:{curve:'smooth',width:2.5},
  markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
  xaxis:{categories:<?php echo $aovLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
  grid:{borderColor:C.grid,strokeDashArray:4},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
}).render();

new ApexCharts(document.getElementById('chartTypePie'),{
  series:[<?php echo e($revenueByType['book']); ?>,<?php echo e($revenueByType['stationery']); ?>],
  labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
}).render();

new ApexCharts(document.getElementById('chartBuyers'),{
  series:[<?php echo e($newBuyersMonth); ?>,<?php echo e($repeatBuyersMonth); ?>],
  labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/dashboard.blade.php ENDPATH**/ ?>