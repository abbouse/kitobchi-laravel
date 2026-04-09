
<div class="sidebar-brand">
  <div class="brand-icon">K</div>
  <div>
    <div class="brand-name">kitobchi.</div>
    <div class="brand-badge">
      <?php echo e(auth('panel')->user()?->getRoleLabelAttribute() ?? 'admin'); ?> · v1.0
    </div>
  </div>
</div>


<nav class="sidebar-nav">

  
  <div class="nav-section">Asosiy</div>

  <a href="<?php echo e(route('panel.dashboard')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.dashboard') ? 'active' : ''); ?>">
    <i class="bi bi-grid-1x2"></i> Dashboard
  </a>

  <?php if(auth('panel')->user()?->hasPermission('users')): ?>
  <a href="<?php echo e(route('panel.users.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.users.*') ? 'active' : ''); ?>">
    <i class="bi bi-people"></i> Foydalanuvchilar
  </a>
  <?php endif; ?>

  
  <div class="nav-section">Mahsulotlar</div>

  <?php if(auth('panel')->user()?->hasPermission('books')): ?>
  <a href="<?php echo e(route('panel.books.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.books.*') ? 'active' : ''); ?>">
    <i class="bi bi-book"></i> Kitoblar
    <?php $pendingBooks = \App\Models\Books::where('is_approved', 0)->count(); ?>
    <?php if($pendingBooks > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingBooks); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('stationery')): ?>
  <a href="<?php echo e(route('panel.stationery.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.stationery.*') ? 'active' : ''); ?>">
    <i class="bi bi-pencil-square"></i> Kanstovar
    <?php $pendingStat = \App\Models\Stationery::where('is_approved', 0)->count(); ?>
    <?php if($pendingStat > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingStat); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <a href="<?php echo e(route('panel.book-categories.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.book-categories.*') ? 'active' : ''); ?>">
    <i class="bi bi-tags"></i> Kitob kategoriyalari
  </a>

  <a href="<?php echo e(route('panel.stationery-categories.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.stationery-categories.*') ? 'active' : ''); ?>">
    <i class="bi bi-tag"></i> Kanstovar kategoriyalari
  </a>

  <a href="<?php echo e(route('panel.reels.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.reels.*') ? 'active' : ''); ?>">
    <i class="bi bi-collection-play"></i> Reels
  </a>

  <a href="<?php echo e(route('panel.market-news.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.market-news.*') ? 'active' : ''); ?>">
    <i class="bi bi-newspaper"></i> Market yangiliklari
  </a>
  <?php endif; ?>

  
  <?php if(auth('panel')->user()?->hasPermission('orders')): ?>
  <div class="nav-section">Buyurtmalar</div>

  <a href="<?php echo e(route('panel.orders.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.orders.*') ? 'active' : ''); ?>">
    <i class="bi bi-bag-check"></i> Buyurtmalar
    <?php $newOrders = \App\Models\Sold::where('status', 'A')->count(); ?>
    <?php if($newOrders > 0): ?>
      <span class="nav-badge success"><?php echo e($newOrders); ?></span>
    <?php endif; ?>
  </a>

  <a href="<?php echo e(route('panel.seller-orders.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.seller-orders.*') ? 'active' : ''); ?>">
    <i class="bi bi-shop"></i> Seller buyurtmalari
  </a>

  <a href="<?php echo e(route('panel.courier-orders.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.courier-orders.*') ? 'active' : ''); ?>">
    <i class="bi bi-truck"></i> Kuryer buyurtmalari
  </a>
  <?php endif; ?>

  
  <div class="nav-section">Biznes</div>

  <?php if(auth('panel')->user()?->hasPermission('sellers')): ?>
  <a href="<?php echo e(route('panel.sellers.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.sellers.*') ? 'active' : ''); ?>">
    <i class="bi bi-shop-window"></i> Sotuvchilar
    <?php $pendingSellers = \App\Models\Seller::where('status', 'pending')->count(); ?>
    <?php if($pendingSellers > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingSellers); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <a href="<?php echo e(route('panel.seller-transactions.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.seller-transactions.*') ? 'active' : ''); ?>">
    <i class="bi bi-arrow-left-right"></i> Tranzaksiyalar
    <?php
      try {
        $pendingTx = \App\Models\SellerTransaction::where('status', 'pending')->count()
                   + \App\Models\CourierTransaction::where('status', 'pending')->count();
      } catch(\Exception $e) { $pendingTx = 0; }
    ?>
    <?php if($pendingTx > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingTx); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('couriers')): ?>
  <a href="<?php echo e(route('panel.couriers.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.couriers.*') ? 'active' : ''); ?>">
    <i class="bi bi-bicycle"></i> Kuryerlar
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('promocodes')): ?>
  <a href="<?php echo e(route('panel.promocodes.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.promocodes.*') ? 'active' : ''); ?>">
    <i class="bi bi-ticket-perforated"></i> Promokodlar
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <a href="<?php echo e(route('panel.seller-ads.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.seller-ads.*') ? 'active' : ''); ?>">
    <i class="bi bi-megaphone"></i> Reklamalar
    <?php $pendingAds = \App\Models\SellerAd::where('moderation', 'pending')->count(); ?>
    <?php if($pendingAds > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingAds); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  
  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <div class="nav-section">Sovg'alar</div>

  <a href="<?php echo e(route('panel.gift-certificates.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.gift-certificates.*') ? 'active' : ''); ?>">
    <i class="bi bi-gift"></i> Gift sertifikatlar
    <?php
      try {
        $pendingGifts = \App\Models\GiftCertificate::where('status','pending_payment')->count();
      } catch(\Exception $e) { $pendingGifts = 0; }
    ?>
    <?php if($pendingGifts > 0): ?>
      <span class="nav-badge warning"><?php echo e($pendingGifts); ?></span>
    <?php endif; ?>
  </a>

  <a href="<?php echo e(route('panel.mystery-box.subscriptions')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.mystery-box.*') ? 'active' : ''); ?>">
    <i class="bi bi-box-seam"></i> Mystery Box
    <?php
      try {
        $dueBox = \App\Models\MysteryBoxSubscription::where('status','active')
          ->where('next_delivery_at','<=',now())->count();
      } catch(\Exception $e) { $dueBox = 0; }
    ?>
    <?php if($dueBox > 0): ?>
      <span class="nav-badge danger"><?php echo e($dueBox); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  
  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <div class="nav-section">Jamiyat</div>

  <a href="<?php echo e(route('panel.book-club.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.book-club.*') ? 'active' : ''); ?>">
    <i class="bi bi-chat-quote-fill"></i> Book Club
    <?php
      try {
        $newBcPosts = \App\Models\BookClub::where('is_deleted', false)
          ->where('created_at', '>=', now()->subDay())->count();
      } catch(\Exception $e) { $newBcPosts = 0; }
    ?>
    <?php if($newBcPosts > 0): ?>
      <span class="nav-badge info"><?php echo e($newBcPosts); ?></span>
    <?php endif; ?>
  </a>

  <a href="<?php echo e(route('panel.chats.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.chats.*') ? 'active' : ''); ?>">
    <i class="bi bi-chat-dots"></i> Chat kuzatuv
  </a>

  <a href="<?php echo e(route('panel.reports.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.reports.*') ? 'active' : ''); ?>">
    <i class="bi bi-flag-fill"></i> Shikoyatlar
    <?php
      try {
        $pendingReports = \App\Models\Report::where('status', 'pending')->count();
      } catch(\Exception $e) { $pendingReports = 0; }
    ?>
    <?php if($pendingReports > 0): ?>
      <span class="nav-badge danger"><?php echo e($pendingReports); ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>

  
  <div class="nav-section">Tizim</div>

  <?php if(auth('panel')->user()?->hasPermission('settings')): ?>
  <a href="<?php echo e(route('panel.fcm-notifications.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.fcm-notifications.*') ? 'active' : ''); ?>">
    <i class="bi bi-bell-fill"></i> Push bildirishnomalar
  </a>

  <a href="<?php echo e(route('panel.bot-tickets.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.bot-tickets.*') ? 'active' : ''); ?>">
    <i class="bi bi-headset"></i> Support
    <?php $queueTickets = \App\Models\BotTicket::where('status', 'queue')->count(); ?>
    <?php if($queueTickets > 0): ?>
      <span class="nav-badge danger"><?php echo e($queueTickets); ?></span>
    <?php endif; ?>
  </a>

  <a href="<?php echo e(route('panel.settings.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.settings.*') ? 'active' : ''); ?>">
    <i class="bi bi-gear"></i> Sozlamalar
  </a>
  <?php endif; ?>

  <?php if(auth('panel')->user()?->hasPermission('admins')): ?>
  <a href="<?php echo e(route('panel.admins.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.admins.*') ? 'active' : ''); ?>">
    <i class="bi bi-shield-check"></i> Adminlar
  </a>

  <a href="<?php echo e(route('panel.api-clients.index')); ?>"
     class="nav-link <?php echo e(request()->routeIs('panel.api-clients.*') ? 'active' : ''); ?>">
    <i class="bi bi-key-fill"></i> API Mijozlar
  </a>
  <?php endif; ?>

</nav>


<?php $admin = auth('panel')->user(); ?>
<div class="sidebar-footer">

  <div class="user-popup" id="userPopup">
    <div class="user-popup-head">
      <div class="name"><?php echo e($admin?->name); ?> <?php echo e($admin?->lastname); ?></div>
      <div class="role"><?php echo e($admin?->getRoleLabelAttribute() ?? 'Admin'); ?></div>
    </div>
    <a href="<?php echo e(route('panel.profile')); ?>">
      <i class="bi bi-person" style="color:var(--p-accent);font-size:15px"></i>
      Profil
    </a>
    <div class="pop-divider"></div>
    <form method="POST" action="<?php echo e(route('panel.logout')); ?>" style="margin:0">
      <?php echo csrf_field(); ?>
      <button type="submit" style="color:var(--p-danger)">
        <i class="bi bi-box-arrow-right" style="font-size:15px"></i>
        Chiqish
      </button>
    </form>
  </div>

  <div class="user-pill" id="userPill">
    <div class="user-av">
      <?php if($admin?->avatar): ?>
        <img src="<?php echo e(asset('storage/'.$admin->avatar)); ?>" alt="<?php echo e($admin?->name); ?>">
      <?php else: ?>
        <?php echo e(strtoupper(substr($admin?->name ?? 'A', 0, 1))); ?>

      <?php endif; ?>
    </div>
    <div class="user-info">
      <div class="user-name"><?php echo e($admin?->name ?? 'Admin'); ?></div>
      <div class="user-role"><?php echo e($admin?->getRoleLabelAttribute() ?? 'Admin'); ?></div>
    </div>
    <form method="POST" action="<?php echo e(route('panel.theme')); ?>"
          onclick="event.stopPropagation()" style="margin:0">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="theme"
             value="<?php echo e(session('theme','dark') === 'dark' ? 'light' : 'dark'); ?>">
      <button type="submit" class="theme-btn"
              title="<?php echo e(session('theme','dark') === 'dark' ? 'Light mode' : 'Dark mode'); ?>">
        <i class="bi bi-<?php echo e(session('theme','dark') === 'dark' ? 'sun' : 'moon-stars'); ?>"></i>
      </button>
    </form>
  </div>

</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/partials/sidebar.blade.php ENDPATH**/ ?>