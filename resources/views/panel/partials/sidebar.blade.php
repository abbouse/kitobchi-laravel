{{-- Brand --}}
<div class="sidebar-brand">
  <div class="brand-icon">K</div>
  <div>
    <div class="brand-name">kitobchi.</div>
    <div class="brand-badge">
      {{ auth('panel')->user()?->getRoleLabelAttribute() ?? 'admin' }} · v1.0
    </div>
  </div>
</div>

{{-- Nav --}}
<nav class="sidebar-nav">

  {{-- ══ Asosiy ════════════════════════════════════════════════ --}}
  <div class="nav-section">Asosiy</div>

  <a href="{{ route('panel.dashboard') }}"
     class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}">
    <i class="bi bi-grid-1x2"></i> Dashboard
  </a>

  @if(auth('panel')->user()?->hasPermission('users'))
  <a href="{{ route('panel.users.index') }}"
     class="nav-link {{ request()->routeIs('panel.users.*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Foydalanuvchilar
  </a>
  @endif

  {{-- ══ Mahsulotlar ════════════════════════════════════════════ --}}
  <div class="nav-section">Mahsulotlar</div>

  @if(auth('panel')->user()?->hasPermission('books'))
  <a href="{{ route('panel.books.index') }}"
     class="nav-link {{ request()->routeIs('panel.books.*') ? 'active' : '' }}">
    <i class="bi bi-book"></i> Kitoblar
    @php $pendingBooks = \App\Models\Books::where('is_approved', 0)->count(); @endphp
    @if($pendingBooks > 0)
      <span class="nav-badge warning">{{ $pendingBooks }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('stationery'))
  <a href="{{ route('panel.stationery.index') }}"
     class="nav-link {{ request()->routeIs('panel.stationery.*') ? 'active' : '' }}">
    <i class="bi bi-pencil-square"></i> Kanstovar
    @php $pendingStat = \App\Models\Stationery::where('is_approved', 0)->count(); @endphp
    @if($pendingStat > 0)
      <span class="nav-badge warning">{{ $pendingStat }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.book-categories.index') }}"
     class="nav-link {{ request()->routeIs('panel.book-categories.*') ? 'active' : '' }}">
    <i class="bi bi-tags"></i> Kitob kategoriyalari
  </a>

  <a href="{{ route('panel.stationery-categories.index') }}"
     class="nav-link {{ request()->routeIs('panel.stationery-categories.*') ? 'active' : '' }}">
    <i class="bi bi-tag"></i> Kanstovar kategoriyalari
  </a>

  <a href="{{ route('panel.reels.index') }}"
     class="nav-link {{ request()->routeIs('panel.reels.*') ? 'active' : '' }}">
    <i class="bi bi-collection-play"></i> Reels
  </a>

  <a href="{{ route('panel.market-news.index') }}"
     class="nav-link {{ request()->routeIs('panel.market-news.*') ? 'active' : '' }}">
    <i class="bi bi-newspaper"></i> Market yangiliklari
  </a>
  @endif

  {{-- ══ Buyurtmalar ════════════════════════════════════════════ --}}
  @if(auth('panel')->user()?->hasPermission('orders'))
  <div class="nav-section">Buyurtmalar</div>

  <a href="{{ route('panel.orders.index') }}"
     class="nav-link {{ request()->routeIs('panel.orders.*') ? 'active' : '' }}">
    <i class="bi bi-bag-check"></i> Buyurtmalar
    @php $newOrders = \App\Models\Sold::where('status', 'A')->count(); @endphp
    @if($newOrders > 0)
      <span class="nav-badge success">{{ $newOrders }}</span>
    @endif
  </a>

  <a href="{{ route('panel.seller-orders.index') }}"
     class="nav-link {{ request()->routeIs('panel.seller-orders.*') ? 'active' : '' }}">
    <i class="bi bi-shop"></i> Seller buyurtmalari
  </a>

  <a href="{{ route('panel.courier-orders.index') }}"
     class="nav-link {{ request()->routeIs('panel.courier-orders.*') ? 'active' : '' }}">
    <i class="bi bi-truck"></i> Kuryer buyurtmalari
  </a>
  @endif

  {{-- ══ Biznes ══════════════════════════════════════════════════ --}}
  <div class="nav-section">Biznes</div>

  @if(auth('panel')->user()?->hasPermission('sellers'))
  <a href="{{ route('panel.sellers.index') }}"
     class="nav-link {{ request()->routeIs('panel.sellers.*') ? 'active' : '' }}">
    <i class="bi bi-shop-window"></i> Sotuvchilar
    @php $pendingSellers = \App\Models\Seller::where('status', 'pending')->count(); @endphp
    @if($pendingSellers > 0)
      <span class="nav-badge warning">{{ $pendingSellers }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.seller-transactions.index') }}"
     class="nav-link {{ request()->routeIs('panel.seller-transactions.*') ? 'active' : '' }}">
    <i class="bi bi-arrow-left-right"></i> Tranzaksiyalar
    @php
      try {
        $pendingTx = \App\Models\SellerTransaction::where('status', 'pending')->count()
                   + \App\Models\CourierTransaction::where('status', 'pending')->count();
      } catch(\Exception $e) { $pendingTx = 0; }
    @endphp
    @if($pendingTx > 0)
      <span class="nav-badge warning">{{ $pendingTx }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('couriers'))
  <a href="{{ route('panel.couriers.index') }}"
     class="nav-link {{ request()->routeIs('panel.couriers.*') ? 'active' : '' }}">
    <i class="bi bi-bicycle"></i> Kuryerlar
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('promocodes'))
  <a href="{{ route('panel.promocodes.index') }}"
     class="nav-link {{ request()->routeIs('panel.promocodes.*') ? 'active' : '' }}">
    <i class="bi bi-ticket-perforated"></i> Promokodlar
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.seller-ads.index') }}"
     class="nav-link {{ request()->routeIs('panel.seller-ads.*') ? 'active' : '' }}">
    <i class="bi bi-megaphone"></i> Reklamalar
    @php $pendingAds = \App\Models\SellerAd::where('moderation', 'pending')->count(); @endphp
    @if($pendingAds > 0)
      <span class="nav-badge warning">{{ $pendingAds }}</span>
    @endif
  </a>
  @endif

  {{-- ══ Sovg'alar ═══════════════════════════════════════════════ --}}
  @if(auth('panel')->user()?->hasPermission('settings'))
  <div class="nav-section">Sovg'alar</div>

  <a href="{{ route('panel.gift-certificates.index') }}"
     class="nav-link {{ request()->routeIs('panel.gift-certificates.*') ? 'active' : '' }}">
    <i class="bi bi-gift"></i> Gift sertifikatlar
    @php
      try {
        $pendingGifts = \App\Models\GiftCertificate::where('status','pending_payment')->count();
      } catch(\Exception $e) { $pendingGifts = 0; }
    @endphp
    @if($pendingGifts > 0)
      <span class="nav-badge warning">{{ $pendingGifts }}</span>
    @endif
  </a>

  <a href="{{ route('panel.mystery-box.subscriptions') }}"
     class="nav-link {{ request()->routeIs('panel.mystery-box.*') ? 'active' : '' }}">
    <i class="bi bi-box-seam"></i> Mystery Box
    @php
      try {
        $dueBox = \App\Models\MysteryBoxSubscription::where('status','active')
          ->where('next_delivery_at','<=',now())->count();
      } catch(\Exception $e) { $dueBox = 0; }
    @endphp
    @if($dueBox > 0)
      <span class="nav-badge danger">{{ $dueBox }}</span>
    @endif
  </a>
  @endif

  {{-- ══ Jamiyat ══════════════════════════════════════════════════ --}}
  @if(auth('panel')->user()?->hasPermission('settings'))
  <div class="nav-section">Jamiyat</div>

  <a href="{{ route('panel.book-club.index') }}"
     class="nav-link {{ request()->routeIs('panel.book-club.*') ? 'active' : '' }}">
    <i class="bi bi-chat-quote-fill"></i> Book Club
    @php
      try {
        $newBcPosts = \App\Models\BookClub::where('is_deleted', false)
          ->where('created_at', '>=', now()->subDay())->count();
      } catch(\Exception $e) { $newBcPosts = 0; }
    @endphp
    @if($newBcPosts > 0)
      <span class="nav-badge info">{{ $newBcPosts }}</span>
    @endif
  </a>

  <a href="{{ route('panel.chats.index') }}"
     class="nav-link {{ request()->routeIs('panel.chats.*') ? 'active' : '' }}">
    <i class="bi bi-chat-dots"></i> Chat kuzatuv
  </a>

  <a href="{{ route('panel.reports.index') }}"
     class="nav-link {{ request()->routeIs('panel.reports.*') ? 'active' : '' }}">
    <i class="bi bi-flag-fill"></i> Shikoyatlar
    @php
      try {
        $pendingReports = \App\Models\Report::where('status', 'pending')->count();
      } catch(\Exception $e) { $pendingReports = 0; }
    @endphp
    @if($pendingReports > 0)
      <span class="nav-badge danger">{{ $pendingReports }}</span>
    @endif
  </a>
  @endif

  {{-- ══ Tizim ════════════════════════════════════════════════════ --}}
  <div class="nav-section">Tizim</div>

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.fcm-notifications.index') }}"
     class="nav-link {{ request()->routeIs('panel.fcm-notifications.*') ? 'active' : '' }}">
    <i class="bi bi-bell-fill"></i> Push bildirishnomalar
  </a>

  <a href="{{ route('panel.bot-tickets.index') }}"
     class="nav-link {{ request()->routeIs('panel.bot-tickets.*') ? 'active' : '' }}">
    <i class="bi bi-headset"></i> Support
    @php $queueTickets = \App\Models\BotTicket::where('status', 'queue')->count(); @endphp
    @if($queueTickets > 0)
      <span class="nav-badge danger">{{ $queueTickets }}</span>
    @endif
  </a>

  <a href="{{ route('panel.settings.index') }}"
     class="nav-link {{ request()->routeIs('panel.settings.*') ? 'active' : '' }}">
    <i class="bi bi-gear"></i> Sozlamalar
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('admins'))
  <a href="{{ route('panel.admins.index') }}"
     class="nav-link {{ request()->routeIs('panel.admins.*') ? 'active' : '' }}">
    <i class="bi bi-shield-check"></i> Adminlar
  </a>

  <a href="{{ route('panel.api-clients.index') }}"
     class="nav-link {{ request()->routeIs('panel.api-clients.*') ? 'active' : '' }}">
    <i class="bi bi-key-fill"></i> API Mijozlar
  </a>
  @endif

</nav>

{{-- ── Footer ───────────────────────────────────── --}}
@php $admin = auth('panel')->user(); @endphp
<div class="sidebar-footer">

  <div class="user-popup" id="userPopup">
    <div class="user-popup-head">
      <div class="name">{{ $admin?->name }} {{ $admin?->lastname }}</div>
      <div class="role">{{ $admin?->getRoleLabelAttribute() ?? 'Admin' }}</div>
    </div>
    <a href="{{ route('panel.profile') }}">
      <i class="bi bi-person" style="color:var(--p-accent);font-size:15px"></i>
      Profil
    </a>
    <div class="pop-divider"></div>
    <form method="POST" action="{{ route('panel.logout') }}" style="margin:0">
      @csrf
      <button type="submit" style="color:var(--p-danger)">
        <i class="bi bi-box-arrow-right" style="font-size:15px"></i>
        Chiqish
      </button>
    </form>
  </div>

  <div class="user-pill" id="userPill">
    <div class="user-av">
      @if($admin?->avatar)
        <img src="{{ asset('storage/'.$admin->avatar) }}" alt="{{ $admin?->name }}">
      @else
        {{ strtoupper(substr($admin?->name ?? 'A', 0, 1)) }}
      @endif
    </div>
    <div class="user-info">
      <div class="user-name">{{ $admin?->name ?? 'Admin' }}</div>
      <div class="user-role">{{ $admin?->getRoleLabelAttribute() ?? 'Admin' }}</div>
    </div>
    <form method="POST" action="{{ route('panel.theme') }}"
          onclick="event.stopPropagation()" style="margin:0">
      @csrf
      <input type="hidden" name="theme"
             value="{{ session('theme','dark') === 'dark' ? 'light' : 'dark' }}">
      <button type="submit" class="theme-btn"
              title="{{ session('theme','dark') === 'dark' ? 'Light mode' : 'Dark mode' }}">
        <i class="bi bi-{{ session('theme','dark') === 'dark' ? 'sun' : 'moon-stars' }}"></i>
      </button>
    </form>
  </div>

</div>