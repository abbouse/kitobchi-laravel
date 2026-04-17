{{-- ══ Brand ══════════════════════════════════════════════════ --}}
<div class="sidebar-brand">
  <div class="brand-icon">K</div>
  <div class="brand-text">
    <div class="brand-name">kitobchi.</div>
    <div class="brand-badge">
      {{ auth('panel')->user()?->getRoleLabelAttribute() ?? 'admin' }} · v1.0
    </div>
  </div>
</div>

{{-- ══ Navigation ═════════════════════════════════════════════ --}}
<nav class="sidebar-nav">

  {{-- ─── Asosiy ───────────────────────────────────────────── --}}
  <div class="nav-section">Asosiy</div>

  <a href="{{ route('panel.dashboard') }}" title="Dashboard"
     class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}">
    <i class="bi bi-grid-1x2-fill"></i>
    <span class="nav-link-text">Dashboard</span>
  </a>

  @if(auth('panel')->user()?->hasPermission('users'))
  <a href="{{ route('panel.users.index') }}" title="Foydalanuvchilar"
     class="nav-link {{ request()->routeIs('panel.users.*') ? 'active' : '' }}">
    <i class="bi bi-people-fill"></i>
    <span class="nav-link-text">Foydalanuvchilar</span>
  </a>
  @endif

  {{-- ─── Mahsulotlar ──────────────────────────────────────── --}}
  <div class="nav-section">Mahsulotlar</div>

  @if(auth('panel')->user()?->hasPermission('books'))
  <a href="{{ route('panel.books.index') }}" title="Kitoblar"
     class="nav-link {{ request()->routeIs('panel.books.*') ? 'active' : '' }}">
    <i class="bi bi-book-fill"></i>
    <span class="nav-link-text">Kitoblar</span>
    @php $pendingBooks = \App\Models\Books::where('is_approved', 0)->count(); @endphp
    @if($pendingBooks > 0)
      <span class="nav-badge warning">{{ $pendingBooks }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('stationery'))
  <a href="{{ route('panel.stationery.index') }}" title="Kanstovar"
     class="nav-link {{ request()->routeIs('panel.stationery.*') ? 'active' : '' }}">
    <i class="bi bi-pencil-fill"></i>
    <span class="nav-link-text">Kanstovar</span>
    @php $pendingStat = \App\Models\Stationery::where('is_approved', 0)->count(); @endphp
    @if($pendingStat > 0)
      <span class="nav-badge warning">{{ $pendingStat }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.book-categories.index') }}" title="Kitob kategoriyalari"
     class="nav-link {{ request()->routeIs('panel.book-categories.*') ? 'active' : '' }}">
    <i class="bi bi-tags-fill"></i>
    <span class="nav-link-text">Kitob kategoriyalari</span>
  </a>

  <a href="{{ route('panel.stationery-categories.index') }}" title="Kanstovar kategoriyalari"
     class="nav-link {{ request()->routeIs('panel.stationery-categories.*') ? 'active' : '' }}">
    <i class="bi bi-tag-fill"></i>
    <span class="nav-link-text">Kanstovar kategoriyalari</span>
  </a>

  <a href="{{ route('panel.reels.index') }}" title="Reels"
     class="nav-link {{ request()->routeIs('panel.reels.*') ? 'active' : '' }}">
    <i class="bi bi-play-circle-fill"></i>
    <span class="nav-link-text">Reels</span>
  </a>

  <a href="{{ route('panel.market-news.index') }}" title="Market yangiliklari"
     class="nav-link {{ request()->routeIs('panel.market-news.*') ? 'active' : '' }}">
    <i class="bi bi-newspaper"></i>
    <span class="nav-link-text">Market yangiliklari</span>
  </a>
  @endif

  {{-- ─── Buyurtmalar ───────────────────────────────────────── --}}
  @if(auth('panel')->user()?->hasPermission('orders'))
  <div class="nav-section">Buyurtmalar</div>

  <a href="{{ route('panel.orders.index') }}" title="Buyurtmalar"
     class="nav-link {{ request()->routeIs('panel.orders.*') ? 'active' : '' }}">
    <i class="bi bi-bag-check-fill"></i>
    <span class="nav-link-text">Buyurtmalar</span>
    @php $newOrders = \App\Models\Sold::where('status', 'A')->count(); @endphp
    @if($newOrders > 0)
      <span class="nav-badge success">{{ $newOrders }}</span>
    @endif
  </a>

  <a href="{{ route('panel.seller-orders.index') }}" title="Seller buyurtmalari"
     class="nav-link {{ request()->routeIs('panel.seller-orders.*') ? 'active' : '' }}">
    <i class="bi bi-shop"></i>
    <span class="nav-link-text">Seller buyurtmalari</span>
  </a>

  <a href="{{ route('panel.courier-orders.index') }}" title="Kuryer buyurtmalari"
     class="nav-link {{ request()->routeIs('panel.courier-orders.*') ? 'active' : '' }}">
    <i class="bi bi-truck"></i>
    <span class="nav-link-text">Kuryer buyurtmalari</span>
  </a>
  @endif

  {{-- ─── Biznes ─────────────────────────────────────────────── --}}
  <div class="nav-section">Biznes</div>

  @if(auth('panel')->user()?->hasPermission('sellers'))
  <a href="{{ route('panel.sellers.index') }}" title="Sotuvchilar"
     class="nav-link {{ request()->routeIs('panel.sellers.*') ? 'active' : '' }}">
    <i class="bi bi-shop-window"></i>
    <span class="nav-link-text">Sotuvchilar</span>
    @php $pendingSellers = \App\Models\Seller::where('status', 'pending')->count(); @endphp
    @if($pendingSellers > 0)
      <span class="nav-badge warning">{{ $pendingSellers }}</span>
    @endif
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.seller-transactions.index') }}" title="Tranzaksiyalar"
     class="nav-link {{ request()->routeIs('panel.seller-transactions.*') ? 'active' : '' }}">
    <i class="bi bi-arrow-left-right"></i>
    <span class="nav-link-text">Tranzaksiyalar</span>
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
  <a href="{{ route('panel.couriers.index') }}" title="Kuryerlar"
     class="nav-link {{ request()->routeIs('panel.couriers.*') ? 'active' : '' }}">
    <i class="bi bi-bicycle"></i>
    <span class="nav-link-text">Kuryerlar</span>
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('promocodes'))
  <a href="{{ route('panel.promocodes.index') }}" title="Promokodlar"
     class="nav-link {{ request()->routeIs('panel.promocodes.*') ? 'active' : '' }}">
    <i class="bi bi-ticket-perforated-fill"></i>
    <span class="nav-link-text">Promokodlar</span>
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.seller-ads.index') }}" title="Reklamalar"
     class="nav-link {{ request()->routeIs('panel.seller-ads.*') ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>
    <span class="nav-link-text">Reklamalar</span>
    @php $pendingAds = \App\Models\SellerAd::where('moderation', 'pending')->count(); @endphp
    @if($pendingAds > 0)
      <span class="nav-badge warning">{{ $pendingAds }}</span>
    @endif
  </a>
  @endif

  {{-- ─── Sovg'alar ───────────────────────────────────────── --}}
  @if(auth('panel')->user()?->hasPermission('settings'))
  <div class="nav-section">Sovg'alar</div>

  <a href="{{ route('panel.gift-certificates.index') }}" title="Gift sertifikatlar"
     class="nav-link {{ request()->routeIs('panel.gift-certificates.*') ? 'active' : '' }}">
    <i class="bi bi-gift-fill"></i>
    <span class="nav-link-text">Gift sertifikatlar</span>
    @php
      try { $pendingGifts = \App\Models\GiftCertificate::where('status','pending_payment')->count(); }
      catch(\Exception $e) { $pendingGifts = 0; }
    @endphp
    @if($pendingGifts > 0)
      <span class="nav-badge warning">{{ $pendingGifts }}</span>
    @endif
  </a>

  <a href="{{ route('panel.mystery-box.subscriptions') }}" title="Mystery Box"
     class="nav-link {{ request()->routeIs('panel.mystery-box.*') ? 'active' : '' }}">
    <i class="bi bi-box-seam-fill"></i>
    <span class="nav-link-text">Mystery Box</span>
    @php
      try { $dueBox = \App\Models\MysteryBoxSubscription::where('status','active')->where('next_delivery_at','<=',now())->count(); }
      catch(\Exception $e) { $dueBox = 0; }
    @endphp
    @if($dueBox > 0)
      <span class="nav-badge danger">{{ $dueBox }}</span>
    @endif
  </a>
  @endif

  {{-- ─── Jamiyat ─────────────────────────────────────────── --}}
  @if(auth('panel')->user()?->hasPermission('settings'))
  <div class="nav-section">Jamiyat</div>

  <a href="{{ route('panel.book-club.index') }}" title="Book Club"
     class="nav-link {{ request()->routeIs('panel.book-club.*') ? 'active' : '' }}">
    <i class="bi bi-chat-quote-fill"></i>
    <span class="nav-link-text">Book Club</span>
    @php
      try { $newBcPosts = \App\Models\BookClub::where('is_deleted', false)->where('created_at', '>=', now()->subDay())->count(); }
      catch(\Exception $e) { $newBcPosts = 0; }
    @endphp
    @if($newBcPosts > 0)
      <span class="nav-badge info">{{ $newBcPosts }}</span>
    @endif
  </a>

  <a href="{{ route('panel.chats.index') }}" title="Chat kuzatuv"
     class="nav-link {{ request()->routeIs('panel.chats.*') ? 'active' : '' }}">
    <i class="bi bi-chat-dots-fill"></i>
    <span class="nav-link-text">Chat kuzatuv</span>
  </a>

  <a href="{{ route('panel.reports.index') }}" title="Shikoyatlar"
     class="nav-link {{ request()->routeIs('panel.reports.*') ? 'active' : '' }}">
    <i class="bi bi-flag-fill"></i>
    <span class="nav-link-text">Shikoyatlar</span>
    @php
      try { $pendingReports = \App\Models\Report::where('status', 'pending')->count(); }
      catch(\Exception $e) { $pendingReports = 0; }
    @endphp
    @if($pendingReports > 0)
      <span class="nav-badge danger">{{ $pendingReports }}</span>
    @endif
  </a>
  @endif

  {{-- ─── Tizim ───────────────────────────────────────────── --}}
  <div class="nav-section">Tizim</div>

  @if(auth('panel')->user()?->hasPermission('settings'))
  <a href="{{ route('panel.fcm-notifications.index') }}" title="Push bildirishnomalar"
     class="nav-link {{ request()->routeIs('panel.fcm-notifications.*') ? 'active' : '' }}">
    <i class="bi bi-bell-fill"></i>
    <span class="nav-link-text">Push bildirishnomalar</span>
  </a>

  <a href="{{ route('panel.bot-tickets.index') }}" title="Support"
     class="nav-link {{ request()->routeIs('panel.bot-tickets.*') ? 'active' : '' }}">
    <i class="bi bi-headset"></i>
    <span class="nav-link-text">Support</span>
    @php $queueTickets = \App\Models\BotTicket::where('status', 'queue')->count(); @endphp
    @if($queueTickets > 0)
      <span class="nav-badge danger">{{ $queueTickets }}</span>
    @endif
  </a>

  <a href="{{ route('panel.settings.index') }}" title="Sozlamalar"
     class="nav-link {{ request()->routeIs('panel.settings.*') ? 'active' : '' }}">
    <i class="bi bi-gear-fill"></i>
    <span class="nav-link-text">Sozlamalar</span>
  </a>
  @endif

  @if(auth('panel')->user()?->hasPermission('admins'))
  <a href="{{ route('panel.admins.index') }}" title="Adminlar"
     class="nav-link {{ request()->routeIs('panel.admins.*') ? 'active' : '' }}">
    <i class="bi bi-shield-fill-check"></i>
    <span class="nav-link-text">Adminlar</span>
  </a>

  <a href="{{ route('panel.api-clients.index') }}" title="API Mijozlar"
     class="nav-link {{ request()->routeIs('panel.api-clients.*') ? 'active' : '' }}">
    <i class="bi bi-key-fill"></i>
    <span class="nav-link-text">API Mijozlar</span>
  </a>
  @endif

</nav>

{{-- ══ Footer ══════════════════════════════════════════════ --}}
@php $admin = auth('panel')->user(); @endphp
<div class="sidebar-footer">

  <div class="user-popup" id="userPopup">
    <div class="user-popup-head">
      <div class="name">{{ $admin?->name }} {{ $admin?->lastname }}</div>
      <div class="role">{{ $admin?->getRoleLabelAttribute() ?? 'Admin' }}</div>
    </div>
    <a href="{{ route('panel.profile') }}">
      <i class="bi bi-person-fill" style="color:var(--p-accent);font-size:15px"></i>
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
