<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<?php
  // Mirrors DashboardController::assetFromStorage so raw DB filenames render
  // as proper URLs in <img src> instead of 404-ing as bare paths.
  $resolveImg = function ($value) {
      $v = trim((string) $value);
      if ($v === '') return null;
      if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://') || str_starts_with($v, '/')) {
          return $v;
      }
      return asset('storage/' . ltrim($v, '/'));
  };
?>

<div x-data="{
  tab: localStorage.getItem('a122-dash-tab') || 'main',
  init() {
    this.$nextTick(() => {
      if (this.tab !== 'main' && !_tabInitialized[this.tab]) {
        _tabInitialized[this.tab] = true;
        setTimeout(() => _ensureTabInit(this.tab), 80);
      }
    });
  },
  switchTab(tab) {
    this.tab = tab;
    localStorage.setItem('a122-dash-tab', tab);
    this.$nextTick(() => {
      if (!_tabInitialized[tab]) {
        _tabInitialized[tab] = true;
        setTimeout(() => _ensureTabInit(tab), 80);
      } else {
        setTimeout(() => window.dispatchEvent(new Event('resize')), 30);
      }
    });
  }
}" x-init="init()">
<?php
  $dashAdmin = $admin ?? auth('panel')->user();
  $dashQuick = [];
  if ($dashAdmin?->hasPermission('orders'))    { $dashQuick[] = ['Buyurtmalar','bi-bag-check-fill',route('admin.orders.index'),$pendingOrders>0?$pendingOrders.' ta kutilmoqda':'Barcha statuslar','rgba(70,95,255,.12)','var(--p-accent)']; }
  if ($dashAdmin?->hasPermission('users'))     { $dashQuick[] = ['Foydalanuvchilar','bi-people-fill',route('admin.users.index'),number_format($totalUsers).' ro\'yxatda','rgba(18,183,106,.12)','var(--p-success)']; }
  if ($dashAdmin?->hasPermission('books'))     { $kb=(int)($kangarooHumanReviewBooks??0); $dashQuick[] = ['Kitoblar','bi-book-fill',route('admin.books.index'),$kb>0?$kb.' ta Kangaroo navbati':'Katalog','rgba(11,111,168,.12)','var(--p-info)']; }
  if ($dashAdmin?->hasPermission('stationery')){ $ks=(int)($kangarooHumanReviewStationery??0); $dashQuick[] = ['Kanstovar','bi-pencil-square',route('admin.stationery.index'),$ks>0?$ks.' ta Kangaroo navbati':'Mahsulotlar','rgba(247,144,9,.12)','var(--p-warning)']; }
  if ($dashAdmin?->hasPermission('sellers'))   { $dashQuick[] = ['Sotuvchilar','bi-shop-window',route('admin.sellers.index'),$pendingSellers>0?$pendingSellers.' ariza':'Do\'konlar','rgba(124,92,252,.12)','#7c5cfc']; }
  if ($dashAdmin?->hasPermission('settings')) {
    $pendingPay=($pendingSellerTxCount??0)+($pendingCourierTxCount??0);
    $dashQuick[] = ['Tranzaksiyalar','bi-arrow-left-right',route('admin.transactions.index'),$pendingPay>0?$pendingPay.' kutilayotgan':'Hisob-kitoblar','rgba(70,95,255,.1)','var(--p-accent)'];
    $dashQuick[] = ['Shikoyatlar','bi-flag-fill',route('admin.complaints.index'),'Moderatsiya','rgba(240,68,56,.1)','var(--p-danger)'];
    $dashQuick[] = ['Support','bi-headset',route('admin.support.index'),'Murojaatlar','rgba(100,116,139,.15)','var(--p-muted)'];
    $dashQuick[] = ['Sozlamalar','bi-gear-fill',route('admin.settings.index'),'Tizim','rgba(100,116,139,.12)','var(--p-hint)'];
  }
?>

<div class="d-flex flex-column gap-4 mb-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'A122 control room','title' => 'Operatsiyalar, moliya va moderatsiya bitta nazorat sahifasida','subtitle' => 'Bugungi oqim, kutilayotgan navbatlar va muhim signal bloklari shu yerga yig‘ildi. Maqsad: tez o‘qish, tez saralash va ortiqcha yurmasdan qaror qilish.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'A122 control room','title' => 'Operatsiyalar, moliya va moderatsiya bitta nazorat sahifasida','subtitle' => 'Bugungi oqim, kutilayotgan navbatlar va muhim signal bloklari shu yerga yig‘ildi. Maqsad: tez o‘qish, tez saralash va ortiqcha yurmasdan qaror qilish.']); ?>
    <a href="<?php echo e(route('admin.dashboard.live')); ?>" class="btn btn-dark rounded-pill px-4" target="_blank">
      <i class="bi bi-broadcast-pin me-2"></i>Live monitor
    </a>
    <a href="<?php echo e(route('admin.dashboard',['clear_cache'=>1])); ?>" class="btn btn-outline-secondary rounded-pill px-4">
      <i class="bi bi-arrow-clockwise me-2"></i>Yangilash
    </a>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Aktiv buyurtmalar','value' => number_format($pendingOrders + $packingOrders + $onwayOrders),'meta' => 'Kutilayotgan, qadoqlanayotgan va yo‘ldagi buyurtmalar','icon' => 'bag-check','tone' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Aktiv buyurtmalar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($pendingOrders + $packingOrders + $onwayOrders)),'meta' => 'Kutilayotgan, qadoqlanayotgan va yo‘ldagi buyurtmalar','icon' => 'bag-check','tone' => 'primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Online foydalanuvchilar','value' => number_format($onlineUsers),'meta' => 'Hozir ilova ichida faol bo‘lgan foydalanuvchilar','icon' => 'wifi','tone' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Online foydalanuvchilar','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($onlineUsers)),'meta' => 'Hozir ilova ichida faol bo‘lgan foydalanuvchilar','icon' => 'wifi','tone' => 'info']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Kutilayotgan payout','value' => number_format($pendingSellerTxCount + $pendingCourierTxCount),'meta' => 'Seller va kuryer payout navbatlari','icon' => 'cash-stack','tone' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Kutilayotgan payout','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($pendingSellerTxCount + $pendingCourierTxCount)),'meta' => 'Seller va kuryer payout navbatlari','icon' => 'cash-stack','tone' => 'warning']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <?php if (isset($component)) { $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.stat-card','data' => ['label' => 'Bugungi daromad','value' => number_format($todayRevenue / 1000000, 2) . '<span class=&quot;fs-5 text-secondary ms-1&quot;>M</span>','meta' => 'Kunlik paid revenue','icon' => 'graph-up-arrow','tone' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Bugungi daromad','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($todayRevenue / 1000000, 2) . '<span class=&quot;fs-5 text-secondary ms-1&quot;>M</span>'),'meta' => 'Kunlik paid revenue','icon' => 'graph-up-arrow','tone' => 'success']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $attributes = $__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__attributesOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6)): ?>
<?php $component = $__componentOriginal3c3cb599308b2d9971dae437d0b6bab6; ?>
<?php unset($__componentOriginal3c3cb599308b2d9971dae437d0b6bab6); ?>
<?php endif; ?>
    </div>
  </div>

  <?php if(!empty($alerts)): ?>
    <div class="row g-3">
      <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$color,$icon,$title,$desc,$url]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $class = match($color) {
            'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            'success' => 'alert-success',
            'info' => 'alert-primary',
            default => 'alert-secondary',
          };
        ?>
        <div class="col-12 col-xl-6">
          <a href="<?php echo e($url); ?>" class="alert <?php echo e($class); ?> kc-alert-card d-flex align-items-start gap-3 mb-0 text-decoration-none">
            <i class="bi <?php echo e($icon); ?> fs-4"></i>
            <span>
              <span class="d-block fw-bold text-dark"><?php echo e($title); ?></span>
              <span class="d-block small text-dark-emphasis"><?php echo e($desc); ?></span>
            </span>
          </a>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  <?php endif; ?>

  <?php if(count($dashQuick)): ?>
    <?php if (isset($component)) { $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.section-card','data' => ['title' => 'Tezkor bo‘limlar','meta' => 'Adminning eng ko‘p ishlatiladigan ish yo‘llari.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tezkor bo‘limlar','meta' => 'Adminning eng ko‘p ishlatiladigan ish yo‘llari.']); ?>
      <div class="row g-3">
        <?php $__currentLoopData = $dashQuick; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-12 col-md-6 col-xl-3">
            <a href="<?php echo e($q[2]); ?>" class="kc-quick-link">
              <span class="kc-quick-link__icon" style="background:<?php echo e($q[4]); ?>;color:<?php echo e($q[5]); ?>">
                <i class="bi <?php echo e($q[1]); ?>"></i>
              </span>
              <div class="kc-quick-link__title"><?php echo e($q[0]); ?></div>
              <div class="kc-quick-link__meta"><?php echo e($q[3]); ?></div>
            </a>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $attributes = $__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__attributesOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb)): ?>
<?php $component = $__componentOriginal6c55ae2c9251ebabe977f3f2190280eb; ?>
<?php unset($__componentOriginal6c55ae2c9251ebabe977f3f2190280eb); ?>
<?php endif; ?>
  <?php endif; ?>

  <div class="kc-tab-card p-3">
    <div class="nav nav-pills flex-wrap" id="dashSegBar">
      <button type="button" class="nav-link" :class="{ 'active': tab === 'main' }" @click="switchTab('main')"><i class="bi bi-grid-1x2 me-2"></i>Asosiy</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'orders' }" @click="switchTab('orders')"><i class="bi bi-bag-check me-2"></i>Buyurtmalar</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'finance' }" @click="switchTab('finance')"><i class="bi bi-bar-chart-line me-2"></i>Moliya</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'users' }" @click="switchTab('users')"><i class="bi bi-people me-2"></i>Foydalanuvchilar</button>
      <button type="button" class="nav-link" :class="{ 'active': tab === 'catalog' }" @click="switchTab('catalog')"><i class="bi bi-building me-2"></i>Biznes</button>
    </div>
  </div>
</div>





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

  <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-6 fade-up">

    <div class="col">
    <div class="kpi-card h-100">
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
    </div>

    <div class="col">
    <div class="kpi-card h-100">
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
    </div>

    <div class="col">
    <div class="kpi-card h-100">
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
    </div>

    <div class="col">
    <div class="kpi-card h-100">
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
    </div>

    <div class="col">
    <div class="kpi-card h-100">
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
    </div>

    <div class="col">
    <div class="kpi-card h-100">
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
</div>




<div class="dash-tab-panel" id="dash-panel-orders" x-show="tab === 'orders'" x-cloak>

  <div class="row g-3 mb-4 fade-up">
    <div class="col-12 col-lg-6">
    <div class="dash-card h-100">
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
    </div>
    <div class="col-12 col-lg-6">
    <div class="dash-card h-100">
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
  </div>

  <div class="row g-4 mb-4">
    <div class="col-12 col-xl-4 fade-up">
      <div class="dash-card h-100">
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

    <div class="col-12 col-xl-8 fade-up">
      <div class="dash-card h-100">
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
                <div class="d-av d-av--accent d-av--sm-text"><?php $av = $resolveImg($order['avatar'] ?? null); ?> <?php if($av): ?><img src="<?php echo e($av); ?>" alt=""><?php else: ?><?php echo e(strtoupper(substr($order['customer'],0,1))); ?><?php endif; ?></div>
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
      <div class="row g-2 row-cols-1 row-cols-md-2">
        <?php $__currentLoopData = $mysteryDueToday->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col">
        <a href="<?php echo e(route('admin.mystery-box.subscription',$sub)); ?>" class="dash-row-link">
          <div class="d-av d-av--teal"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div class="dash-row-main">
            <div class="dash-row-title--md"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div class="dash-row-meta--plain"><?php echo e($sub->plan?->name_uz); ?> · <?php echo e($sub->next_delivery_at?->diffForHumans()); ?></div>
          </div>
          <span class="s-pill danger s-pill--dash-tight">Navbatda</span>
        </a>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = $mysteryDueSoon->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col">
        <a href="<?php echo e(route('admin.mystery-box.subscription',$sub)); ?>" class="dash-row-link dash-row-link--compact">
          <div class="d-av d-av--teal"><?php echo e(strtoupper(substr($sub->user?->name??'M',0,1))); ?></div>
          <div class="dash-row-main">
            <div class="dash-row-title--sm"><?php echo e($sub->user?->name); ?> <?php echo e($sub->user?->lastname); ?></div>
            <div class="dash-row-meta--2xs"><?php echo e($sub->next_delivery_at?->format('d.m.Y')); ?></div>
          </div>
        </a>
        </div>
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

  <div class="row g-4">
    <div class="col-12 col-xl-5 fade-up">
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

    <div class="col-12 col-xl-7 fade-up d-flex flex-column gap-4">
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">AOV dinamikasi</div>
          <div class="dash-card-sub">Joriy: <?php echo e(number_format($avgOrderValue)); ?> UZS · Komissiya: <?php echo e($avgCommissionPct); ?>%</div>
        </div>
        <div class="dash-card-body"><div id="chartAov" class="dash-chart-host dash-chart-host--130"></div></div>
      </div>
      <div class="row g-4 row-cols-1 row-cols-md-2">
        <div class="col">
        <div class="dash-card h-100">
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
        </div>
        <div class="col">
        <div class="dash-card h-100">
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

      <?php if(count($salesGeoCountries)): ?>
      <div class="dash-card">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Hududlar bo‘yicha sotuvlar</div>
            <div class="dash-card-sub">Davlatni tanlang, sotuv bo‘lgan viloyatlar avtomatik chiqadi</div>
          </div>
        </div>
        <div class="dash-card-body pt-0">
          <div class="period-toggle mb-3" id="salesGeoCountryToggle">
            <?php $__currentLoopData = $salesGeoCountries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <button
                class="period-btn <?php echo e($salesGeoDefaultCountry === $country['key'] ? 'active' : ''); ?>"
                data-country="<?php echo e($country['key']); ?>"
                onclick="switchSalesGeoCountry(this,'<?php echo e($country['key']); ?>')"
              >
                <?php echo e($country['label']); ?>

              </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>

          <div class="row g-4">
            <div class="col-12 col-xl-8">
              <div class="dash-chart-surface">
                <div id="chartSalesGeo" class="dash-chart-host dash-chart-host--220"></div>
              </div>
            </div>
            <div class="col-12 col-xl-4">
              <div id="salesGeoCountrySummary" class="row row-cols-2 g-3 mb-3"></div>
              <div id="salesGeoRegionList" class="space-y-2"></div>
            </div>
          </div>
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

  <div class="row g-4">

    <div class="col-12 col-xl-4 fade-up">
      <div class="dash-card h-100">
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

    <div class="col-12 col-xl-4 fade-up">
      <div class="dash-card h-100">
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
              <?php $av = $resolveImg($u->avatar ?? null); ?> <?php if($av): ?><img src="<?php echo e($av); ?>" alt=""><?php else: ?><?php echo e(strtoupper(substr($u->name??'U',0,1))); ?><?php endif; ?>
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

    <div class="col-12 col-xl-4 fade-up">
      <div class="dash-card h-100">
        <div class="dash-card-head">
          <div>
            <div class="dash-card-title">Top mijozlar</div>
            <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
          </div>
          <a href="<?php echo e(route('admin.users.index')); ?>" class="btn-p ghost sm">Barchasi</a>
        </div>
        <div class="dash-card-body">
          <?php $__empty_1 = true; $__currentLoopData = $topBuyers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $buyer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php $topBuyerHref = $buyer->user ? route('admin.users.show', $buyer->user) : null; ?>
          <div class="top-buyer-row">
            <span class="rank-num <?php echo e($i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n'))); ?>"><?php echo e($i+1); ?></span>
            <div class="d-av d-av--accent">
              <?php $av = $resolveImg($buyer->user?->avatar); ?> <?php if($av): ?><img src="<?php echo e($av); ?>" alt=""><?php else: ?><?php echo e(strtoupper(substr($buyer->user?->name??'U',0,1))); ?><?php endif; ?>
            </div>
            <div class="top-buyer-body">
              <div class="dash-row-title--md">
                <?php if($topBuyerHref): ?>
                  <a href="<?php echo e($topBuyerHref); ?>" class="hover:underline"><?php echo e($buyer->user ? $buyer->user->name.' '.$buyer->user->lastname : 'ID:'.$buyer->user_id); ?></a>
                <?php else: ?>
                  <?php echo e($buyer->user ? $buyer->user->name.' '.$buyer->user->lastname : 'ID:'.$buyer->user_id); ?>

                <?php endif; ?>
              </div>
              <div class="top-row-rev-hint"><?php echo e($buyer->order_count); ?> ta buyurtma</div>
            </div>
            <div class="top-buyer-spend">
              <div class="top-buyer-amount"><?php echo e(number_format($buyer->total_spent/1000)); ?>K</div>
              <div class="top-row-count-hint">UZS</div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="dash-empty"><i class="bi bi-person-x dash-empty__ico"></i>Ma'lumot yo'q</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>




<div class="dash-tab-panel" id="dash-panel-catalog" x-show="tab === 'catalog'" x-cloak>

  <div class="row g-4">

    <div class="col-12 col-xl-7 fade-up">
      <div class="dash-card h-100">
        <div class="dash-card-head">
          <div class="dash-card-title">Top mahsulotlar</div>
          <div class="dash-card-sub">Eng ko'p sotilganlar</div>
        </div>
        <div class="dash-card-body">
          <?php $__empty_1 = true; $__currentLoopData = $topMixedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php $imgs=is_array($product->images)?$product->images:json_decode($product->images??'[]',true);$img=$resolveImg($imgs[0] ?? null); ?>
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

    <div class="col-12 col-xl-5 fade-up d-flex flex-column gap-4">
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

function _getApex() {
  if (window.ApexCharts) return window.ApexCharts;
  console.error('ApexCharts not available');
  return null;
}

function _setChartFallback(hostId, message = "Chart yuklanmadi") {
  const host = document.getElementById(hostId);
  if (!host) return null;
  host.innerHTML = `<div class="dash-empty"><i class="bi bi-bar-chart-line dash-empty__ico"></i>${message}</div>`;
  return host;
}

// ── Tab system ───────────────────────────────────────────────────────────────
const _tabInitialized = { main: true };
const _tabInitAttempts = {};
const _chartTabs = new Set(['orders', 'finance', 'users']);

function _canInitChartTab(tab) {
  return !_chartTabs.has(tab) || !!window.ApexCharts;
}

function _scheduleTabInit(tab, attempt = 0) {
  if (!_chartTabs.has(tab)) return;
  const nextAttempt = attempt + 1;
  if (nextAttempt > 20) {
    console.error(`dashboard tab init timeout: ${tab}`);
    return;
  }

  _tabInitAttempts[tab] = nextAttempt;
  setTimeout(() => {
    if (!_canInitChartTab(tab)) {
      _scheduleTabInit(tab, nextAttempt);
      return;
    }
    _initTab(tab);
  }, 120);
}

function _ensureTabInit(tab) {
  if (_canInitChartTab(tab)) {
    _initTab(tab);
    return;
  }
  _scheduleTabInit(tab, _tabInitAttempts[tab] || 0);
}

function _initTab(tab) {
  if (tab === 'orders')  { try { _initOrderCharts();  } catch(e) { console.error('orders chart:', e); } }
  if (tab === 'finance') { try { _initFinanceCharts(); } catch(e) { console.error('finance chart:', e); } }
  if (tab === 'users')   { try { _initUserCharts();   } catch(e) { console.error('users chart:', e); } }
}

// ── Orders tab ───────────────────────────────────────────────────────────────
let _ordersWeekChart = null;
let _revenueWeekChart = null;
let _orderDonutChart = null;
function _initOrderCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartOrdersWeek');
    _setChartFallback('chartRevenueWeek');
    _setChartFallback('chartDonut');
    return;
  }

  const ordersWeekHost = document.getElementById('chartOrdersWeek');
  const revenueWeekHost = document.getElementById('chartRevenueWeek');
  const donutHost = document.getElementById('chartDonut');
  if (!ordersWeekHost || !revenueWeekHost || !donutHost) return;

  const ordersWeekOptions = {
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
  };

  if (_ordersWeekChart) {
    _ordersWeekChart.updateOptions(ordersWeekOptions, true, true);
  } else {
    _ordersWeekChart = new Apex(ordersWeekHost, ordersWeekOptions);
    _ordersWeekChart.render();
  }

  const revenueWeekOptions = {
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
  };

  if (_revenueWeekChart) {
    _revenueWeekChart.updateOptions(revenueWeekOptions, true, true);
  } else {
    _revenueWeekChart = new Apex(revenueWeekHost, revenueWeekOptions);
    _revenueWeekChart.render();
  }

  const donutOptions = {
    series:[<?php echo e($completedOrders); ?>,<?php echo e($onwayOrders); ?>,<?php echo e($packingOrders); ?>,<?php echo e($pendingOrders); ?>,<?php echo e($cancelledOrders); ?>],
    labels:['Yetkazildi',"Yo'lda",'Qadoqlanmoqda','Kutilmoqda','Bekor'],
    colors:[C.success,C.info,C.accent,C.warning,C.danger],
    chart:{type:'donut',height:212,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
    dataLabels:{enabled:false},
    plotOptions:{
      pie:{
        donut:{
          size:'74%',
          labels:{
            show:true,
            total:{
              show:true,
              label:'Jami',
              fontSize:'12px',
              color:C.muted,
              formatter:()=>'<?php echo e(number_format($totalOrders)); ?>'
            },
            value:{
              fontSize:'20px',
              fontWeight:700,
              color:C.text,
              fontFamily:'JetBrains Mono,monospace'
            }
          }
        }
      }
    },
    stroke:{width:2,colors:[C.surface]},
    tooltip:{theme:isDark?'dark':'light'},
  };

  if (_orderDonutChart) {
    _orderDonutChart.updateOptions(donutOptions, true, true);
  } else {
    _orderDonutChart = new Apex(donutHost, donutOptions);
    _orderDonutChart.render();
  }

  setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
}

// ── Finance tab ──────────────────────────────────────────────────────────────
let _revChart = null;
let _salesGeoChart = null;
let _aovChart = null;
let _typePieChart = null;
let _buyersChart = null;
const salesGeoState = {
  selected: <?php echo json_encode($salesGeoDefaultCountry, 15, 512) ?>,
  countries: <?php echo json_encode($salesGeoCountries, 15, 512) ?>,
  regions: <?php echo json_encode($salesGeoRegionsByCountry, 15, 512) ?>,
};
function _initFinanceCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartRevenue');
    _setChartFallback('chartAov');
    _setChartFallback('chartTypePie');
    _setChartFallback('chartBuyers');
    _setChartFallback('chartSalesGeo');
    return;
  }

  const revenueHost = document.getElementById('chartRevenue');
  if (!revenueHost) return;

  const revenueOptions = {
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
  };

  if (_revChart) {
    _revChart.updateOptions(revenueOptions, true, true);
  } else {
    _revChart = new Apex(revenueHost, revenueOptions);
    _revChart.render();
  }

  <?php if($isSuperAdmin): ?>
  <?php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); ?>
  const aovHost = document.getElementById('chartAov');
  const typePieHost = document.getElementById('chartTypePie');
  const buyersHost = document.getElementById('chartBuyers');

  const aovOptions = {
    series:[{name:'AOV',data:<?php echo $aovVals; ?>}],
    chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
    colors:[C.teal],stroke:{curve:'smooth',width:2.5},
    markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
    xaxis:{categories:<?php echo $aovLabels; ?>,axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
    yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
    grid:{borderColor:C.grid,strokeDashArray:4},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
  };
  if (aovHost) {
    if (_aovChart) {
      _aovChart.updateOptions(aovOptions, true, true);
    } else {
      _aovChart = new Apex(aovHost, aovOptions);
      _aovChart.render();
    }
  }

  const typePieOptions = {
    series:[<?php echo e($revenueByType['book']); ?>,<?php echo e($revenueByType['stationery']); ?>],
    labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
  };
  if (typePieHost) {
    if (_typePieChart) {
      _typePieChart.updateOptions(typePieOptions, true, true);
    } else {
      _typePieChart = new Apex(typePieHost, typePieOptions);
      _typePieChart.render();
    }
  }

  const buyersOptions = {
    series:[<?php echo e($newBuyersMonth); ?>,<?php echo e($repeatBuyersMonth); ?>],
    labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
    chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
    dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
    plotOptions:{pie:{donut:{size:'65%'}}},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  };
  if (buyersHost) {
    if (_buyersChart) {
      _buyersChart.updateOptions(buyersOptions, true, true);
    } else {
      _buyersChart = new Apex(buyersHost, buyersOptions);
      _buyersChart.render();
    }
  }
  <?php endif; ?>

  _initSalesGeoChart();
  setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
}

function _initSalesGeoChart() {
  const Apex = _getApex();
  const host = document.getElementById('chartSalesGeo');
  if (!host || !salesGeoState.selected) return;
  if (!Apex) {
    _setChartFallback('chartSalesGeo');
    return;
  }

  const regions = salesGeoState.regions[salesGeoState.selected] || [];
  const categories = regions.map(r => r.label);
  const revenueData = regions.map(r => Number(((r.revenue || 0) / 1000000).toFixed(2)));

  if (_salesGeoChart) {
    _salesGeoChart.updateOptions({
      series:[{name:'Sotuv', data: revenueData}],
      xaxis:{categories},
    });
  } else {
    _salesGeoChart = new Apex(host, {
      series:[{name:'Sotuv', data: revenueData}],
      chart:{type:'bar',height:220,toolbar:{show:false},background:'transparent',fontFamily:'Inter,sans-serif'},
      colors:[C.accent],
      plotOptions:{bar:{horizontal:true,borderRadius:7,barHeight:'58%'}},
      dataLabels:{enabled:false},
      xaxis:{
        categories,
        labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'},
        axisBorder:{show:false},
        axisTicks:{show:false},
      },
      yaxis:{labels:{style:{colors:C.text,fontSize:'12px'}}},
      grid:{borderColor:C.grid,strokeDashArray:4},
      tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
    });
    _salesGeoChart.render();
  }

  _renderSalesGeoSide();
}

function _renderSalesGeoSide() {
  const summaryHost = document.getElementById('salesGeoCountrySummary');
  const listHost = document.getElementById('salesGeoRegionList');
  if (!summaryHost || !listHost || !salesGeoState.selected) return;

  const country = (salesGeoState.countries || []).find(c => c.key === salesGeoState.selected);
  const regions = salesGeoState.regions[salesGeoState.selected] || [];

  if (!country) {
    summaryHost.innerHTML = '';
    listHost.innerHTML = '';
    return;
  }

  summaryHost.innerHTML = `
    <div class="rounded-[18px] border border-slate-200 bg-slate-50 px-3 py-3">
      <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Buyurtmalar</div>
      <div class="mt-1 text-lg font-semibold text-slate-900">${Number(country.orders || 0).toLocaleString()}</div>
    </div>
    <div class="rounded-[18px] border border-slate-200 bg-slate-50 px-3 py-3">
      <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Viloyatlar</div>
      <div class="mt-1 text-lg font-semibold text-slate-900">${Number(country.regions_count || 0).toLocaleString()}</div>
    </div>
  `;

  listHost.innerHTML = regions.map((region, index) => {
    const amount = Number(region.revenue || 0);
    return `
      <div class="rounded-[18px] border border-slate-200 bg-white px-3 py-3">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-900 truncate">${index + 1}. ${region.label}</div>
            <div class="text-xs text-slate-500">${Number(region.orders || 0).toLocaleString()} ta buyurtma</div>
          </div>
          <div class="text-right">
            <div class="text-sm font-semibold text-slate-900">${Math.round(amount / 1000).toLocaleString()}K</div>
            <div class="text-[11px] text-slate-500">UZS</div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function switchSalesGeoCountry(btn, countryKey) {
  document.querySelectorAll('#salesGeoCountryToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  salesGeoState.selected = countryKey;
  _initSalesGeoChart();
}

// ── Users tab ────────────────────────────────────────────────────────────────
let _userSparkChart = null;
function _initUserCharts() {
  const Apex = _getApex();
  if (!Apex) {
    _setChartFallback('chartUserSparkline');
    return;
  }
  const host = document.getElementById('chartUserSparkline');
  if (!host) return;
  <?php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); ?>
  const sparkOptions = {
    series:[{name:'Yangi user',data:<?php echo $sparkCounts; ?>}],
    chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
    colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
    xaxis:{categories:<?php echo $sparkDays; ?>},
    tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
  };
  if (_userSparkChart) {
    _userSparkChart.updateOptions(sparkOptions, true, true);
  } else {
    _userSparkChart = new Apex(host, sparkOptions);
    _userSparkChart.render();
  }
}

// ── Period toggle ────────────────────────────────────────────────────────────
function switchRevPeriod(btn, period) {
  document.querySelectorAll('#revPeriodToggle .period-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (!_revChart) return;
  const d = revData[period];
  if (!d) return;
  _revChart.updateOptions({
    series:[{name:'Daromad',data:d.data}],
    xaxis:{categories:d.labels},
    dataLabels:{formatter:d.formatter},
    yaxis:{labels:{formatter:d.formatter}},
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const activeTab = localStorage.getItem('a122-dash-tab') || 'main';
  if (_chartTabs.has(activeTab)) {
    _tabInitialized[activeTab] = true;
    _ensureTabInit(activeTab);
  }
});

window.addEventListener('a122:charts-ready', () => {
  const activeTab = localStorage.getItem('a122-dash-tab') || 'main';
  if (_chartTabs.has(activeTab)) {
    _tabInitialized[activeTab] = true;
    _ensureTabInit(activeTab);
  }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/dashboard.blade.php ENDPATH**/ ?>