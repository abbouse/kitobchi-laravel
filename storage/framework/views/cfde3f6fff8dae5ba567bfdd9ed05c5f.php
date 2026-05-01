<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<div x-data="{
  tab: localStorage.getItem('a122-dash-tab') || 'main',
  init() {
    this.$nextTick(() => {
      if (this.tab !== 'main' && !_tabInitialized[this.tab]) {
        _tabInitialized[this.tab] = true;
        setTimeout(() => _initTab(this.tab), 80);
      }
    });
  },
  switchTab(tab) {
    this.tab = tab;
    localStorage.setItem('a122-dash-tab', tab);
    this.$nextTick(() => {
      if (!_tabInitialized[tab]) {
        _tabInitialized[tab] = true;
        setTimeout(() => _initTab(tab), 80);
      } else {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 30);
      }
    });
  }
}" x-init="init()">
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['class' => 'fade-up']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'fade-up']); ?>
   <?php $__env->slot('heading', null, []); ?> Dashboard <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Asosiy metrikalar, buyurtmalar oqimi va operatsion holat bir joyda. <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('admin.dashboard.live')); ?>" class="btn-p ghost" target="_blank">
      <i class="bi bi-broadcast-pin"></i> Live monitor
    </a>
    <a href="<?php echo e(route('admin.dashboard',['clear_cache'=>1])); ?>" class="btn-p ghost">
      <i class="bi bi-arrow-clockwise"></i> Yangilash
    </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>

<div class="dash-seg-bar mb-4 fade-up" id="dashSegBar">
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'main' }" @click="switchTab('main')"><i class="bi bi-grid-1x2"></i><span>Asosiy</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'orders' }" @click="switchTab('orders')"><i class="bi bi-bag-check"></i><span>Buyurtmalar</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'finance' }" @click="switchTab('finance')"><i class="bi bi-bar-chart-line"></i><span>Moliya</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'users' }" @click="switchTab('users')"><i class="bi bi-people"></i><span>Foydalanuvchilar</span></button>
  <button type="button" class="dash-seg-btn" :class="{ 'active': tab === 'catalog' }" @click="switchTab('catalog')"><i class="bi bi-building"></i><span>Biznes</span></button>
</div>

<?php
  $dashAdmin = $admin ?? auth('panel')->user();
  $dashQuick = [];
  if ($dashAdmin?->hasPermission('orders'))    { $dashQuick[] = ['Buyurtmalar','bi-bag-check-fill',route('admin.orders.index'),$pendingOrders>0?$pendingOrders.' ta kutilmoqda':'Barcha statuslar','rgba(70,95,255,.12)','var(--p-accent)']; }
  if ($dashAdmin?->hasPermission('users'))     { $dashQuick[] = ['Foydalanuvchilar','bi-people-fill',route('admin.users.index'),number_format($totalUsers).' ro\'yxatda','rgba(18,183,106,.12)','var(--p-success)']; }
  if ($dashAdmin?->hasPermission('books'))     { $kb=(int)($kangarooHumanReviewBooks??0); $dashQuick[] = ['Kitoblar','bi-book-fill',route('admin.books.index'),$kb>0?$kb.' ta Kangaroo navbati':'Katalog','rgba(11,111,168,.12)','var(--p-info)']; }
  if ($dashAdmin?->hasPermission('stationery')){ $ks=(int)($kangarooHumanReviewStationery??0); $dashQuick[] = ['Kanstovar','bi-pencil-square',route('admin.stationery.index'),$ks>0?$ks.' ta Kangaroo navbati':'Mahsulotlar','rgba(247,144,9,.12)','var(--p-warning)']; }
  if ($dashAdmin?->hasPermission('sellers'))   { $dashQuick[] = ['Sotuvchilar','bi-shop-window',route('admin.sellers.index'),$pendingSellers>0?$pendingSellers.' ariza':'Do\'konlar','rgba(124,92,252,.12)','#7c5cfc']; }
  if ($dashAdmin?->hasPermission('settings')) {
    $kUgc=(int)($kangarooUgcAdminQueue??0);
    if ($kUgc>0) { $dashQuick[] = ['UGC (Kangaroo)','bi-stars',route('admin.book-club.moderation-queue'),$kUgc.' ta admin navbati','rgba(247,144,9,.18)','var(--p-warning)']; }
    $pendingPay=($pendingSellerTxCount??0)+($pendingCourierTxCount??0);
    $dashQuick[] = ['Tranzaksiyalar','bi-arrow-left-right',route('admin.transactions.index'),$pendingPay>0?$pendingPay.' kutilayotgan':'Hisob-kitoblar','rgba(70,95,255,.1)','var(--p-accent)'];
    $dashQuick[] = ['Shikoyatlar','bi-flag-fill',route('admin.complaints.index'),'Moderatsiya','rgba(240,68,56,.1)','var(--p-danger)'];
    $dashQuick[] = ['Support','bi-headset',route('admin.support.index'),'Murojaatlar','rgba(100,116,139,.15)','var(--p-muted)'];
    $dashQuick[] = ['Sozlamalar','bi-gear-fill',route('admin.settings.index'),'Tizim','rgba(100,116,139,.12)','var(--p-hint)'];
  }
?>


<div class="a122-section fade-up mb-4">
<div class="a122-section-body p-0">
<div class="dash-hero-strip">
  <div class="dash-hero-metric">
    <div class="dash-hero-label">GMV (brutto)</div>
    <div class="dash-hero-val"><?php echo e(number_format($gmvTotal/1_000_000,1)); ?><span class="dash-hero-unit">M</span></div>
    <div class="dash-hero-sub">Bu oy <?php echo e(number_format($gmvMonth/1_000_000,1)); ?>M UZS</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">To'langan daromad</div>
    <div class="dash-hero-val"><?php echo e(number_format($totalRevenue/1_000_000,1)); ?><span class="dash-hero-unit">M</span></div>
    <div class="dash-hero-sub">Bugun +<?php echo e(number_format($todayRevenue/1000)); ?>K UZS</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">Aktiv buyurtmalar</div>
    <div class="dash-hero-val"><?php echo e(number_format($pendingOrders+$packingOrders+$onwayOrders)); ?></div>
    <div class="dash-hero-sub"><span class="live-dot live-dot--xs"></span>&ensp;<?php echo e(number_format($onlineUsers)); ?> online</div>
  </div>
  <div class="dash-hero-metric">
    <div class="dash-hero-label">Kutilayotgan to'lovlar</div>
    <div class="dash-hero-val <?php echo e(($pendingSellerTxCount+$pendingCourierTxCount)>0?'dash-hero-val--warn':''); ?>"><?php echo e(number_format($pendingSellerTxCount+$pendingCourierTxCount)); ?></div>
    <div class="dash-hero-sub">Seller + Kuryer arizalar</div>
  </div>
</div>
</div>
</div>


<?php if(!empty($alerts)): ?>
<div class="a122-section fade-up mb-4">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Diqqat talab qiladigan holatlar</div>
      <div class="a122-section-head__meta">Moderatsiya, to‘lov va navbatlar bo‘yicha tezkor signal bloklari.</div>
    </div>
  </div>
  <div class="a122-section-body">
  <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$color,$icon,$title,$desc,$url]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="alert-item <?php echo e($color); ?>">
    <i class="bi <?php echo e($icon); ?> alert-item__icon"></i>
    <div class="alert-item__body"><span class="alert-item__title"><?php echo e($title); ?>:</span> <?php echo e($desc); ?></div>
    <a href="<?php echo e($url); ?>" class="alert-item__link">Ko'rish →</a>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
</div>
<?php endif; ?>


<?php if(count($dashQuick)): ?>
<div class="a122-section mb-5 fade-up">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Tezkor bo‘limlar</div>
      <div class="a122-section-head__meta">Eng ko‘p ishlatiladigan boshqaruv sahifalariga bir bosishda o‘tish.</div>
    </div>
  </div>
  <div class="a122-section-body">
<div class="dash-quick-grid">
  <?php $__currentLoopData = $dashQuick; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e($q[2]); ?>" class="dash-quick-card">
      <div class="dq-ico" style="background:<?php echo e($q[4]); ?>;color:<?php echo e($q[5]); ?>"><i class="bi <?php echo e($q[1]); ?>"></i></div>
      <span class="dq-lbl"><?php echo e($q[0]); ?></span>
      <span class="dq-hint"><?php echo e($q[3]); ?></span>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
</div>
</div>
<?php endif; ?>





<div class="dash-tab-panel" id="dash-panel-main" x-show="tab === 'main'" x-cloak>

  <div class="dash-insight-grid mb-4 fade-up">
    <div class="dash-insight-pill dip-success">
      <div class="dip-lbl">Bugungi daromad</div>
      <div class="dip-val"><?php echo e(number_format($todayRevenue/1_000_000,2)); ?><span class="dip-val-unit"> M</span></div>
    </div>
    <div class="dash-insight-pill">
      <div class="dip-lbl">Bugun buyurtma</div>
      <div class="dip-val"><?php echo e(number_format($todayOrders)); ?><span class="dip-val-unit"> ta</span></div>
    </div>
    <div class="dash-insight-pill dip-accent">
      <div class="dip-lbl">Hafta daromad</div>
      <div class="dip-val"><?php echo e(number_format($weekRevenue/1_000_000,2)); ?><span class="dip-val-unit"> M</span></div>
    </div>
    <div class="dash-insight-pill dip-info">
      <div class="dip-lbl">Hafta buyurtma</div>
      <div class="dip-val"><?php echo e(number_format($weekOrders)); ?><span class="dip-val-unit"> ta</span></div>
    </div>
    <div class="dash-insight-pill dip-warning">
      <div class="dip-lbl">Kutilmoqda</div>
      <div class="dip-val"><?php echo e(number_format($pendingOrders)); ?><span class="dip-val-unit"> ta</span></div>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6 xl:gap-3 fade-up">

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--success"><i class="bi bi-graph-up-arrow"></i></div>
        <span class="kpi-change up"><i class="bi bi-arrow-up-short kpi-change-ico"></i> Bugun: <?php echo e(number_format($todayRevenue/1000)); ?>K</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Jami daromad</div>
        <div class="kpi-value"><?php echo e(number_format($totalRevenue/1_000_000,1)); ?><span class="kpi-value-unit"> M UZS</span></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Bu oy</span>
        <span class="kpi-foot-mono"><?php echo e(number_format($monthRevenue/1_000_000,1)); ?>M</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--accent"><i class="bi bi-bag-check"></i></div>
        <span class="kpi-change up"><i class="bi bi-plus kpi-change-ico"></i> <?php echo e($todayOrders); ?> bugun</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Buyurtmalar</div>
        <div class="kpi-value"><?php echo e(number_format($totalOrders)); ?></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-warn"><i class="bi bi-clock kpi-footer-ico"></i> <?php echo e($pendingOrders); ?> kutmoqda</span>
        <span class="kpi-foot-danger"><i class="bi bi-x-circle kpi-footer-ico"></i> <?php echo e($cancelledOrders); ?></span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--info"><i class="bi bi-patch-check"></i></div>
        <span class="kpi-change <?php echo e($completionRate>=70?'up':'neutral'); ?>"><?php echo e($completionRate); ?>%</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Yakunlanish</div>
        <div class="kpi-value"><?php echo e(number_format($completedOrders)); ?></div>
      </div>
      <div class="kpi-footer">
        <div class="kpi-prog-cell">
          <div class="dash-prog-track dash-prog-track--thin">
            <div class="dash-prog-fill" style="width:<?php echo e($completionRate); ?>%;background:var(--p-info)"></div>
          </div>
        </div>
        <span class="kpi-foot-hint-xs"><?php echo e($cancellationRate); ?>% bekor</span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--warning"><i class="bi bi-people"></i></div>
        <span class="kpi-change up"><span class="live-dot live-dot--xs"></span><?php echo e($onlineUsers); ?> online</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Foydalanuvchilar</div>
        <div class="kpi-value"><?php echo e(number_format($totalUsers)); ?></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Bugun yangi</span>
        <span class="kpi-foot-mono kpi-foot-mono--success">+<?php echo e($newUsersToday); ?></span>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--gift"><i class="bi bi-gift"></i></div>
        <span class="kpi-change <?php echo e($giftUsed>0?'up':'neutral'); ?>"><i class="bi bi-check-circle kpi-change-ico-sm"></i> <?php echo e($giftUsed); ?> ishlatildi</span>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Gift Sertifikat</div>
        <div class="kpi-value"><?php echo e(number_format($giftTotal)); ?></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Faol</span>
        <?php if($giftPending>0): ?>
          <span class="kpi-foot-warn-strong"><?php echo e($giftPending); ?> kutmoqda</span>
        <?php else: ?>
          <span class="kpi-foot-mono"><?php echo e($giftSent); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="kpi-card">
      <div class="flex items-start justify-between">
        <div class="kpi-icon kpi-icon--teal"><i class="bi bi-box-seam"></i></div>
        <?php if($mysteryDueCount>0): ?>
          <span class="kpi-change down"><i class="bi bi-exclamation-triangle kpi-change-ico-sm"></i> <?php echo e($mysteryDueCount); ?> navbat</span>
        <?php else: ?>
          <span class="kpi-change neutral">Navbat yo'q</span>
        <?php endif; ?>
      </div>
      <div class="kpi-stack">
        <div class="kpi-label">Mystery Box</div>
        <div class="kpi-value"><?php echo e(number_format($mysteryActive)); ?></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-foot-muted">Faol obuna</span>
        <span class="kpi-foot-hint-sm"><?php echo e($mysteryPending); ?> kutmoqda</span>
      </div>
    </div>

  </div>
</div>




<div class="dash-tab-panel" id="dash-panel-orders" x-show="tab === 'orders'" x-cloak>

  <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4 mb-4 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Buyurtmalar — 7 kun</div>
          <div class="dash-card-sub">Soni bo'yicha</div>
        </div>
        <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn-p ghost sm">Ro'yxat <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartOrdersWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">Daromad — 7 kun</div>
          <div class="dash-card-sub">Mln UZS (to'langan)</div>
        </div>
      </div>
      <div class="dash-card-body pt-0">
        <div class="dash-chart-surface"><div id="chartRevenueWeek" class="dash-chart-host dash-chart-host--220"></div></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4">
    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div class="dash-card-title">Holat bo'yicha</div>
          <div class="dash-card-sub">Jami <?php echo e(number_format($totalOrders)); ?> ta</div>
        </div>
        <div class="dash-card-body pt-0">
          <div class="dash-chart-surface"><div id="chartDonut" class="dash-chart-host dash-chart-host--210"></div></div>
          <div class="donut-stat-row">
            <?php $__currentLoopData = [['Yetkazildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Qadoqda',$packingOrders,'accent'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => [$l,$v,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($idx>0): ?><div class="stat-divider"></div><?php endif; ?>
            <div class="stat-cell donut-stat-cell">
              <div class="stat-cell-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
              <div class="stat-cell-lbl"><?php echo e($l); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>
    </div>

    <div class="xl:col-span-8 fade-up">
      <div class="dash-card">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">So'nggi buyurtmalar</div>
            <div class="dash-card-sub">Oxirgi 10 ta</div>
          </div>
          <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="dash-card-body p-0">
          <div class="recent-orders-wrap">
            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $bc=match($order['status']){'Yetkazildi'=>'ob-c',"Yo'lda"=>'ob-b','Qadoqlanmoqda'=>'ob-pk','Kutilmoqda'=>'ob-a','Bekor qilindi'=>'ob-f',default=>'ob-p'}; ?>
            <div class="ro-row">
              <div class="ro-id"><span class="p-mono-id">#<?php echo e($order['id']); ?></span><?php if($order['gift']): ?><span class="ro-gift">🎁</span><?php endif; ?></div>
              <div class="ro-customer">
                <div class="d-av d-av--accent d-av--sm-text"><?php if($order['avatar']): ?><img src="<?php echo e($order['avatar']); ?>"><?php else: ?><?php echo e(strtoupper(substr($order['customer'],0,1))); ?><?php endif; ?></div>
                <span class="ro-name"><?php echo e($order['customer']); ?></span>
              </div>
              <div class="ro-amount"><?php echo e($order['amount']); ?> <span class="p-currency-suffix">UZS</span></div>
              <div class="ro-status"><span class="o-badge <?php echo e($bc); ?>"><?php echo e($order['status']); ?></span></div>
              <div class="ro-date"><?php echo e($order['date']); ?></div>
              <div class="ro-action"><a href="<?php echo e(route('admin.orders.show',$order['id'])); ?>" class="btn-p ghost sm"><i class="bi bi-arrow-right"></i></a></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="dash-empty"><i class="bi bi-bag-x dash-empty__ico"></i>Buyurtmalar yo'q</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php $hasMysteryQueue = $mysteryDueToday->count() || $mysteryDueSoon->count(); ?>
  <?php if($hasMysteryQueue): ?>
  <div class="dash-card fade-up">
    <div class="dash-card-head">
      <div>
        <div class="dash-card-title"><i class="bi bi-box-seam mr-1 <?php echo e($mysteryDueCount>0?'dash-title-ico--danger':'dash-title-ico--accent'); ?>"></i>Mystery Box navbati</div>
        <div class="dash-card-sub">
          <?php if($mysteryDueCount>0): ?>
            <span class="dash-sub-danger"><?php echo e($mysteryDueCount); ?> ta kechikdi</span>
          <?php else: ?>
            <?php echo e($mysteryDueSoon->count()); ?> ta 7 kun ichida
          <?php endif; ?>
        </div>
      </div>
      <a href="<?php echo e(route('admin.mystery-box.subscriptions',['tab'=>'active'])); ?>" class="btn-p ghost sm">Barchasi</a>
    </div>
    <div class="dash-card-body">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
        <?php $__currentLoopData = $mysteryDueToday->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('admin.mystery-box.subscription',$sub)); ?>" class="dash-row-link">
          <div class="d-av d-av--teal"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div class="dash-row-main">
            <div class="dash-row-title--md"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div class="dash-row-meta--plain"><?php echo e($sub->plan?->name_uz); ?> · <?php echo e($sub->next_delivery_at?->diffForHumans()); ?></div>
          </div>
          <span class="s-pill danger s-pill--dash-tight">Navbatda</span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = $mysteryDueSoon->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('admin.mystery-box.subscription',$sub)); ?>" class="dash-row-link dash-row-link--compact">
          <div class="d-av d-av--teal"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div class="dash-row-main">
            <div class="dash-row-title--sm"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div class="dash-row-meta--2xs"><?php echo e($sub->next_delivery_at?->format('d.m.Y')); ?></div>
          </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>




<div class="dash-tab-panel" id="dash-panel-finance" x-show="tab === 'finance'" x-cloak>

  <div class="dash-card mb-4 fade-up">
    <div class="dash-card-head">
      <div>
        <div class="dash-card-title">Daromad dinamikasi</div>
        <div class="dash-card-sub">Oy / Hafta / Bugun · mln UZS</div>
      </div>
      <div class="flex items-center gap-2">
        <div class="period-toggle" id="revPeriodToggle">
          <button class="period-btn active" data-period="month" onclick="switchRevPeriod(this,'month')">Oy</button>
          <button class="period-btn" data-period="week" onclick="switchRevPeriod(this,'week')">Hafta</button>
          <button class="period-btn" data-period="today" onclick="switchRevPeriod(this,'today')">Bugun</button>
        </div>
        <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn-p ghost sm">Buyurtmalar <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="dash-card-body pt-0">
      <div class="dash-chart-surface"><div id="chartRevenue" class="dash-chart-host dash-chart-host--288"></div></div>
    </div>
  </div>

  <?php if($isSuperAdmin): ?>
  <?php
    $finRows = [
      ['accent','bi-activity','GMV (brutto)','Barcha buyurtmalar',$gmvTotal,$gmvMonth],
      ['success','bi-check-circle',"To'langan daromad",'paymentStatus = 2',$totalRevenue,$monthRevenue],
      ['info','bi-truck','Yetkazish','Delivery fee',$totalDeliveryIncome,$monthDeliveryIncome],
      ['purple','bi-percent','Seller komissiya',"O'rtacha {$avgCommissionPct}%",$totalCommissionEarned,$monthCommissionEarned],
      ['teal','bi-box-seam','Mystery Box','Faol + yakunlangan',$mysteryRevTotal,$mysteryRevMonth],
      ['pink','bi-gift','Gift Sertifikat','Ishlatilgan: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0],
    ];
    $finCosts = [
      ['danger','bi-ticket-perforated','Promokod',"{$promoOrdersCount} ta buyurtmada",$totalPromoDiscount,$monthPromoDiscount],
      ['warning','bi-cash-stack','Cashback','Foydalanuvchilarga qaytarildi',$totalCashbackPaid,$monthCashbackPaid],
      ['muted','bi-shop-window','Seller payout','Kutilmoqda: '.number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout],
      ['muted','bi-bicycle','Kuryer payout','Kutilmoqda: '.number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout],
      ['danger','bi-x-circle','Bekor yo\'qotish','status=F',$cancelledRevLoss,$cancelledMonthLoss],
    ];
  ?>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-5 fade-up">
      <div class="fin-card">
        <div class="fin-section">
          <div class="fin-section-label fin-section-label--income"><i class="bi bi-arrow-up-circle-fill"></i> Daromadlar</div>
          <?php $__currentLoopData = $finRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$clr,$ico,$lbl,$sub,$total,$month]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $bg=match($clr){'purple'=>'rgba(124,92,252,.13)','teal'=>'rgba(20,184,166,.11)','pink'=>'rgba(236,72,153,.1)',default=>"var(--p-{$clr}-d)"};
            $clrVal=match($clr){'purple'=>'#7c5cfc','teal'=>'#14b8a6','pink'=>'#ec4899',default=>"var(--p-{$clr})"};
          ?>
          <div class="fin2-row">
            <div class="fin2-ico" style="background:<?php echo e($bg); ?>;color:<?php echo e($clrVal); ?>"><i class="bi <?php echo e($ico); ?>"></i></div>
            <div class="fin2-body">
              <div class="fin2-name"><?php echo e($lbl); ?></div>
              <div class="fin2-sub"><?php echo e($sub); ?></div>
            </div>
            <div class="fin2-nums">
              <div class="fin2-total" style="color:<?php echo e($clrVal); ?>"><?php echo e(number_format($total/1_000_000,1)); ?><span class="fin2-unit">M</span></div>
              <?php if($month>0): ?><div class="fin2-month"><?php echo e(number_format($month/1000)); ?>K / oy</div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="fin-section fin-section--cost">
          <div class="fin-section-label fin-section-label--cost"><i class="bi bi-arrow-down-circle-fill"></i> Chiqimlar</div>
          <?php $__currentLoopData = $finCosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$clr,$ico,$lbl,$sub,$total,$month]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php $costBg=$clr==='muted'?'var(--p-elevated)':"var(--p-{$clr}-d)"; ?>
          <div class="fin2-row">
            <div class="fin2-ico" style="background:<?php echo e($costBg); ?>;color:var(--p-<?php echo e($clr); ?>)"><i class="bi <?php echo e($ico); ?>"></i></div>
            <div class="fin2-body">
              <div class="fin2-name fin2-name--cost"><?php echo e($lbl); ?></div>
              <div class="fin2-sub"><?php echo e($sub); ?></div>
            </div>
            <div class="fin2-nums">
              <div class="fin2-total fin2-total--cost">−<?php echo e(number_format($total/1_000_000,1)); ?><span class="fin2-unit">M</span></div>
              <?php if($month>0): ?><div class="fin2-month"><?php echo e(number_format($month/1000)); ?>K / oy</div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="fin-profit-row">
          <div class="fin-profit-ico"><i class="bi bi-stars"></i></div>
          <div class="fin-profit-body">
            <div class="fin-profit-label">Platform sof foyda</div>
            <div class="fin-profit-sub">Komissiya + Yetkazish − Chiqimlar</div>
          </div>
          <div class="fin-profit-val">
            <div class="fin-profit-num"><?php echo e(number_format($platformProfit/1_000_000,2)); ?><span class="fin2-unit"> M</span></div>
            <div class="fin2-month">Bu oy: <?php echo e(number_format($platformProfitMonth/1000)); ?>K</div>
          </div>
        </div>
      </div>
    </div>

    <div class="xl:col-span-7 fade-up flex flex-col gap-4">
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">AOV dinamikasi</div>
          <div class="dash-card-sub">Joriy: <?php echo e(number_format($avgOrderValue)); ?> UZS · Komissiya: <?php echo e($avgCommissionPct); ?>%</div>
        </div>
        <div class="dash-card-body"><div id="chartAov" class="dash-chart-host dash-chart-host--130"></div></div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Mahsulot turi</div>
            <div class="dash-card-sub">Daromad ulushi</div>
          </div>
          <div class="dash-card-body">
            <?php $typeTotal=max(1,$revenueByType['book']+$revenueByType['stationery']);$bookPct=round($revenueByType['book']/$typeTotal*100,1);$statPct=round($revenueByType['stationery']/$typeTotal*100,1); ?>
            <div id="chartTypePie" class="dash-chart-host dash-chart-host--120"></div>
            <?php $__currentLoopData = [['Kitoblar',$revenueByType['book'],'accent','bi-book',$bookPct],['Kanstovar',$revenueByType['stationery'],'warning','bi-pencil-square',$statPct]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i,$p]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="type-legend-row">
              <i class="bi <?php echo e($i); ?> type-legend-ico type-legend-ico--<?php echo e($c); ?>"></i>
              <span class="type-legend-label"><?php echo e($l); ?></span>
              <span class="type-legend-val"><?php echo e(number_format($v/1000)); ?>K</span>
              <span class="s-pill <?php echo e($c); ?> type-legend-pill"><?php echo e($p); ?>%</span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Xaridorlar (bu oy)</div>
            <div class="dash-card-sub">Yangi vs Takroriy</div>
          </div>
          <div class="dash-card-body">
            <?php $totalB=max(1,$repeatBuyersMonth+$newBuyersMonth);$repeatPct=$totalB>1?round($repeatBuyersMonth/$totalB*100):0; ?>
            <div id="chartBuyers" class="dash-chart-host dash-chart-host--120"></div>
            <?php $__currentLoopData = [['Yangi',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'accent','2+ marta']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$s]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="buyer-legend-row">
              <div class="buyer-legend-dot buyer-legend-dot--<?php echo e($c); ?>"></div>
              <div class="buyer-legend-stack">
                <div class="buyer-legend-name"><?php echo e($l); ?></div>
                <div class="buyer-legend-sub"><?php echo e($s); ?></div>
              </div>
              <span class="buyer-legend-count"><?php echo e(number_format($v)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div class="dash-tile-divider">
              <div class="dash-repeat-head"><span>Qayta qaytish</span><span class="dash-repeat-pct"><?php echo e($repeatPct); ?>%</span></div>
              <div class="dash-prog-track"><div class="dash-prog-fill" style="width:<?php echo e($repeatPct); ?>%;background:var(--p-accent)"></div></div>
            </div>
          </div>
        </div>
      </div>
      <?php if($deliveryTypeSplit->count()): ?>
      <div class="dash-card">
        <div class="dash-card-head"><div class="dash-card-title">Yetkazish turlari</div></div>
        <div class="dash-card-body">
          <?php $__currentLoopData = $deliveryTypeSplit->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="dash-delivery-line">
            <span class="dash-delivery-name"><?php echo e($dt->deliveryType); ?></span>
            <span class="dash-delivery-val"><?php echo e(number_format($dt->cnt)); ?> ta</span>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="dash-empty"><i class="bi bi-lock dash-empty__ico"></i>Moliyaviy hisobot faqat superadmin uchun</div>
  <?php endif; ?>

</div>




<div class="dash-tab-panel" id="dash-panel-users" x-show="tab === 'users'" x-cloak>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Foydalanuvchilar holati</div>
            <div class="dash-card-sub"><?php echo e(number_format($totalUsers)); ?> ta jami ro'yxatda</div>
          </div>
          <a href="<?php echo e(route('admin.users.index')); ?>" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="dash-card-body">
          <div class="user-mini-strip">
            <?php $__currentLoopData = [[$totalUsers,'Jami','text'],[$onlineUsers,'Online','success'],[$premiumUsers,'Premium','warning'],[$newUsersToday,'+Bugun','accent']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$v,$l,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="user-mini-cell">
              <div class="user-mini-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
              <div class="user-mini-lbl"><?php echo e($l); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <div class="mt-3">
            <?php $__currentLoopData = [['Online (5 min)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'accent'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolyat (30+ kun)',$isolatedUsers,'danger']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="user-prog-row">
              <div class="user-prog-left">
                <span class="user-prog-dot" style="background:var(--p-<?php echo e($c); ?>)"></span>
                <span class="user-prog-lbl"><?php echo e($l); ?></span>
              </div>
              <div class="user-prog-mid">
                <div class="user-prog-bar">
                  <div class="user-prog-fill" style="width:<?php echo e($totalUsers>0?min(round($v/$totalUsers*100),100):0); ?>%;background:var(--p-<?php echo e($c); ?>)"></div>
                </div>
              </div>
              <span class="user-prog-val"><?php echo e(number_format($v)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <div class="user-sparkline-block">
            <div class="sparkline-cap">Yangi userlar — 7 kun</div>
            <div id="chartUserSparkline" class="dash-chart-host dash-chart-host--60"></div>
          </div>
          <?php if($isolatedUsers>0): ?>
          <div class="alert-item danger alert-item--mt alert-item--compact">
            <i class="bi bi-person-x alert-item__i--shrink"></i>
            <span><?php echo e(number_format($isolatedUsers)); ?> ta user 30+ kun yo'q</span>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="alert-item__link">Ko'rish →</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Hozir online</div>
            <div class="dash-card-sub dash-card-sub--row"><span class="live-dot"></span>&ensp;<?php echo e($onlineUsers); ?> nafar</div>
          </div>
        </div>
        <div class="dash-card-body">
          <?php $__empty_1 = true; $__currentLoopData = $onlineUsersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('admin.users.show',$u->id)); ?>" class="dash-row-link">
            <div class="d-av d-av--accent">
              <?php if($u->avatar): ?><img src="<?php echo e($u->avatar); ?>"><?php else: ?><?php echo e(strtoupper(substr($u->name??'U',0,1))); ?><?php endif; ?>
            </div>
            <div class="dash-row-main">
              <div class="dash-row-title"><?php echo e($u->name); ?> <?php echo e($u->lastname); ?></div>
              <div class="dash-row-meta"><?php echo e($u->last_seen_at ? \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() : '—'); ?></div>
            </div>
            <span class="live-dot"></span>
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="dash-empty"><i class="bi bi-wifi-off dash-empty__ico"></i>Hozir hech kim online emas</div>
          <?php endif; ?>
          <a href="<?php echo e(route('admin.users.index')); ?>" class="btn-p ghost btn-p-block-dash mt-3">Barcha foydalanuvchilar <i class="bi bi-arrow-right ml-1"></i></a>
        </div>
      </div>
    </div>

    <div class="xl:col-span-4 fade-up">
      <div class="dash-card" style="height:100%">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Top mijozlar</div>
            <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
          </div>
          <a href="<?php echo e(route('admin.users.index')); ?>" class="btn-p ghost sm">Barchasi</a>
        </div>
        <div class="dash-card-body">
          <?php $__empty_1 = true; $__currentLoopData = $topBuyers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $buyer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('admin.users.show',$buyer->user_id)); ?>" class="top-buyer-row">
            <span class="rank-num <?php echo e($i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n'))); ?>"><?php echo e($i+1); ?></span>
            <div class="d-av d-av--accent">
              <?php if($buyer->user?->avatar): ?><img src="<?php echo e($buyer->user->avatar); ?>"><?php else: ?><?php echo e(strtoupper(substr($buyer->user?->name??'U',0,1))); ?><?php endif; ?>
            </div>
            <div class="top-buyer-body">
              <div class="dash-row-title--md"><?php echo e($buyer->user?$buyer->user->name.' '.$buyer->user->lastname:'ID:'.$buyer->user_id); ?></div>
              <div class="top-row-rev-hint"><?php echo e($buyer->order_count); ?> ta buyurtma</div>
            </div>
            <div class="top-buyer-spend">
              <div class="top-buyer-amount"><?php echo e(number_format($buyer->total_spent/1000)); ?>K</div>
              <div class="top-row-count-hint">UZS</div>
            </div>
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="dash-empty"><i class="bi bi-person-x dash-empty__ico"></i>Ma'lumot yo'q</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>




<div class="dash-tab-panel" id="dash-panel-catalog" x-show="tab === 'catalog'" x-cloak>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">

    <div class="xl:col-span-7 fade-up">
      <div class="dash-card" style="height:100%">
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
              <?php if($img): ?><img src="<?php echo e($img); ?>"><?php else: ?><i class="bi bi-<?php echo e($product->_type==='stationery'?'box':'book'); ?>"></i><?php endif; ?>
            </div>
            <div class="top-row-body">
              <div class="dash-row-title--md"><?php echo e($product->name); ?></div>
              <div class="top-row-meta-row">
                <span class="s-pill <?php echo e($product->_type==='stationery'?'warning':'info'); ?> s-pill--dash-xs"><?php echo e($product->_type==='stationery'?'Kanstovar':'Kitob'); ?></span>
                <span class="top-row-rev-hint"><?php echo e(number_format($product->total_revenue/1000)); ?>K rev.</span>
              </div>
            </div>
            <div class="top-row-count">
              <div class="top-row-count-val"><?php echo e(number_format($product->sold_count)); ?></div>
              <div class="top-row-count-hint">dona</div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="dash-empty"><i class="bi bi-box dash-empty__ico"></i>Ma'lumot yo'q</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="xl:col-span-5 fade-up flex flex-col gap-4">
      <div class="biz-stat-card biz-stat-card--success">
        <div class="biz-stat-head">
          <div class="biz-stat-ico biz-stat-ico--success"><i class="bi bi-shop-window"></i></div>
          <div>
            <div class="biz-stat-title">Sotuvchilar</div>
            <div class="biz-stat-sub">Do'konlar platformada</div>
          </div>
          <a href="<?php echo e(route('admin.sellers.index')); ?>" class="btn-p ghost sm ml-auto">Ko'rish <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="biz-stat-nums">
          <?php $__currentLoopData = [[$approvedSellers,'Faol','success'],[$totalSellers,'Jami','text'],[$pendingSellers,'Ariza','warning']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$v,$l,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="biz-num-cell">
            <div class="biz-num-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
            <div class="biz-num-lbl"><?php echo e($l); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="biz-stat-bar-wrap">
          <div class="biz-stat-bar-label">
            <span>Faollik darajasi</span>
            <span><?php echo e($totalSellers>0?round($approvedSellers/$totalSellers*100):0); ?>%</span>
          </div>
          <div class="biz-prog-track">
            <div class="biz-prog-fill biz-prog-fill--success" style="width:<?php echo e($totalSellers>0?round($approvedSellers/$totalSellers*100):0); ?>%"></div>
          </div>
        </div>
        <?php if($pendingSellers>0): ?>
        <div class="alert-item warning alert-item--compact mt-2">
          <i class="bi bi-clock"></i><span><?php echo e($pendingSellers); ?> ta yangi ariza</span>
          <a href="<?php echo e(route('admin.sellers.index',['tab'=>'pending'])); ?>" class="alert-item__link">Ko'rish →</a>
        </div>
        <?php endif; ?>
      </div>

      <div class="biz-stat-card biz-stat-card--info">
        <div class="biz-stat-head">
          <div class="biz-stat-ico biz-stat-ico--info"><i class="bi bi-bicycle"></i></div>
          <div>
            <div class="biz-stat-title">Kuryerlar</div>
            <div class="biz-stat-sub">Faol yetkazuvchilar</div>
          </div>
          <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn-p ghost sm ml-auto">Ko'rish <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="biz-stat-nums">
          <?php $__currentLoopData = [[$activeCouriers,'Faol','info'],[$totalCouriers,'Jami','text'],[0,'Navbatda','muted']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$v,$l,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="biz-num-cell">
            <div class="biz-num-val" style="color:var(--p-<?php echo e($c); ?>)"><?php echo e(number_format($v)); ?></div>
            <div class="biz-num-lbl"><?php echo e($l); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="biz-stat-bar-wrap">
          <div class="biz-stat-bar-label">
            <span>Faollik darajasi</span>
            <span><?php echo e($totalCouriers>0?round($activeCouriers/$totalCouriers*100):0); ?>%</span>
          </div>
          <div class="biz-prog-track">
            <div class="biz-prog-fill biz-prog-fill--info" style="width:<?php echo e($totalCouriers>0?round($activeCouriers/$totalCouriers*100):0); ?>%"></div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const isDark = document.documentElement.classList.contains('dark');
const C = {
  text:isDark?'#eef0f7':'#1a1d2e', muted:isDark?'#555c75':'#9ca3af',
  grid:isDark?'rgba(255,255,255,0.05)':'rgba(0,0,0,0.06)',
  surface:isDark?'#181c27':'#ffffff',
  accent:'#4f7cff', success:isDark?'#22c98e':'#16a34a',
  warning:isDark?'#f5a623':'#d97706', danger:isDark?'#ff5c6a':'#dc2626',
  info:isDark?'#38bdf8':'#0284c7', teal:'#14b8a6', pink:'#ec4899', purple:'#7c5cfc',
};

<?php
  $revLabels   = collect($monthlyRevenue)->pluck('month')->toJson();
  $revAmounts  = collect($monthlyRevenue)->map(fn($m)=>round($m['total']/1_000_000,1))->toJson();
  $weekLabels  = collect($dailyRevenue)->pluck('day')->toJson();
  $weekAmounts = collect($dailyRevenue)->map(fn($d)=>round($d['total']/1_000_000,2))->toJson();
  $ordWeekLabels = collect($dailyOrders)->pluck('day')->toJson();
  $ordWeekCounts = collect($dailyOrders)->pluck('count')->toJson();
?>

const revData = {
  month: { labels:<?php echo $revLabels; ?>,  data:<?php echo $revAmounts; ?>,  formatter:v=>v+'M' },
  week:  { labels:<?php echo $weekLabels; ?>, data:<?php echo $weekAmounts; ?>, formatter:v=>v+'M' },
  today: { labels:['Bugun'], data:[<?php echo e(round($todayRevenue/1_000_000,2)); ?>], formatter:v=>v+'M' },
};

// ── Tab system ───────────────────────────────────────────────────────────────
const _tabInitialized = { main: true };
function _initTab(tab) {
  if (tab === 'orders')  { try { _initOrderCharts();  } catch(e) { console.error('orders chart:', e); } }
  if (tab === 'finance') { try { _initFinanceCharts(); } catch(e) { console.error('finance chart:', e); } }
  if (tab === 'users')   { try { _initUserCharts();   } catch(e) { console.error('users chart:', e); } }
}

// ── Orders tab ───────────────────────────────────────────────────────────────
function _initOrderCharts() {
  new ApexCharts(document.getElementById('chartOrdersWeek'), {
    series:[{name:'Buyurtmalar',data:<?php echo $ordWeekCounts; ?>}],
    chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:450}},
    colors:[C.accent],stroke:{curve:'smooth',width:2.5},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.35,opacityTo:0.02,stops:[0,90]}},
    dataLabels:{enabled:false},
    xaxis:{categories:<?php echo $ordWeekLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>Math.round(v)}},
    grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
    markers:{size:0,hover:{size:5}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();

  new ApexCharts(document.getElementById('chartRevenueWeek'), {
    series:[{name:'Daromad',data:<?php echo $weekAmounts; ?>}],
    chart:{type:'area',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:450}},
    colors:[C.success],stroke:{curve:'smooth',width:2.5},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.32,opacityTo:0.02,stops:[0,92]}},
    dataLabels:{enabled:false},
    xaxis:{categories:<?php echo $weekLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
    grid:{borderColor:C.grid,strokeDashArray:4,xaxis:{lines:{show:false}}},
    markers:{size:0,hover:{size:5}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartDonut'), {
    series:[<?php echo e($completedOrders); ?>,<?php echo e($onwayOrders); ?>,<?php echo e($packingOrders); ?>,<?php echo e($pendingOrders); ?>,<?php echo e($cancelledOrders); ?>],
    labels:['Yetkazildi',"Yo'lda",'Qadoqlanmoqda','Kutilmoqda','Bekor'],
    colors:[C.success,C.info,C.accent,C.warning,C.danger],
    chart:{type:'donut',height:212,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
    dataLabels:{enabled:false},
    plotOptions:{pie:{donut:{size:'74%',labels:{show:true,total:{show:true,label:'Jami',fontSize:'12px',color:C.muted,formatter:()=>'<?php echo e(number_format($totalOrders)); ?>'},value:{fontSize:'20px',fontWeight:700,color:C.text,fontFamily:'JetBrains Mono,monospace'}}}}}},
    stroke:{width:2,colors:[C.surface]},
    tooltip:{theme:isDark?'dark':'light'},
  }).render();
}

// ── Finance tab ──────────────────────────────────────────────────────────────
let _revChart = null;
function _initFinanceCharts() {
  _revChart = new ApexCharts(document.getElementById('chartRevenue'), {
    series:[{name:'Daromad',data:revData.month.data}],
    chart:{type:'bar',height:288,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif',animations:{enabled:true,speed:500}},
    colors:[C.accent],
    plotOptions:{bar:{borderRadius:8,columnWidth:'46%',dataLabels:{position:'top'}}},
    dataLabels:{enabled:true,formatter:v=>v+'M',offsetY:-22,style:{fontSize:'11px',colors:[C.muted],fontFamily:'JetBrains Mono,monospace'}},
    xaxis:{categories:revData.month.labels,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'12px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
    grid:{borderColor:C.grid,strokeDashArray:5,xaxis:{lines:{show:false}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
    fill:{type:'gradient',gradient:{shade:'dark',type:'vertical',gradientToColors:['#2650cc'],stops:[0,100]}},
  });
  _revChart.render();

  <?php if($isSuperAdmin): ?>
  <?php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); ?>
  new ApexCharts(document.getElementById('chartAov'), {
    series:[{name:'AOV',data:<?php echo $aovVals; ?>}],
    chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    colors:[C.teal],stroke:{curve:'smooth',width:2.5},
    markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
    xaxis:{categories:<?php echo $aovLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
    grid:{borderColor:C.grid,strokeDashArray:4},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartTypePie'), {
    series:[<?php echo e($revenueByType['book']); ?>,<?php echo e($revenueByType['stationery']); ?>],
    labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
  }).render();

  new ApexCharts(document.getElementById('chartBuyers'), {
    series:[<?php echo e($newBuyersMonth); ?>,<?php echo e($repeatBuyersMonth); ?>],
    labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();
  <?php endif; ?>
}

// ── Users tab ────────────────────────────────────────────────────────────────
function _initUserCharts() {
  <?php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); ?>
  new ApexCharts(document.getElementById('chartUserSparkline'), {
    series:[{name:'Yangi user',data:<?php echo $sparkCounts; ?>}],
    chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
    colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
    xaxis:{categories:<?php echo $sparkDays; ?>},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  }).render();
}

// ── Period toggle ────────────────────────────────────────────────────────────
function switchRevPeriod(btn, period) {
  document.querySelectorAll('#revPeriodToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (!_revChart) return;
  const d = revData[period];
  _revChart.updateOptions({
    series:[{name:'Daromad',data:d.data}],
    xaxis:{categories:d.labels},
    dataLabels:{formatter:d.formatter},
    yaxis:{labels:{formatter:d.formatter}},
  });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/dashboard.blade.php ENDPATH**/ ?>