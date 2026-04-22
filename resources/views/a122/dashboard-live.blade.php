@extends('a122.layouts.monitor')
@section('title', 'Live Monitor')

@push('styles')
<style>
  .live-shell { padding: 24px; max-width: 1680px; margin: 0 auto; }
  .live-header { display:flex; align-items:center; gap:16px; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; }
  .live-title { font-size: 1.35rem; font-weight: 900; color: var(--p-text); }
  .live-sub { font-size: .82rem; color: var(--p-hint); margin-top: 4px; }
  .live-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .live-seg { display:flex; align-items:center; gap:8px; padding:6px; border-radius:18px; background:var(--p-elevated); overflow:auto; }
  .live-seg button { border:0; background:transparent; color:var(--p-hint); min-height:38px; padding:0 14px; border-radius:12px; font-size:.82rem; font-weight:800; white-space:nowrap; }
  .live-seg button.active { background:var(--p-text); color:var(--p-surface); }
  .dark .live-seg button.active { background:#fff; color:#111827; }
  .live-meta-chip { display:inline-flex; align-items:center; gap:8px; min-height:38px; padding:0 14px; border-radius:12px; background:var(--p-surface-strong); border:1px solid var(--p-border); font-size:.8rem; font-weight:700; color:var(--p-text); }
  .live-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
  .live-stat { padding:18px; border-radius:22px; border:1px solid var(--p-border); background:var(--p-surface-strong); box-shadow:var(--p-shadow); }
  .live-stat__label { font-size:.8rem; color:var(--p-hint); }
  .live-stat__value { margin-top:8px; font-size:1.65rem; font-weight:900; color:var(--p-text); }
  .live-stat__sub { margin-top:4px; font-size:.77rem; color:var(--p-hint); }
  .live-panels { display:grid; grid-template-columns:1.1fr .9fr; gap:16px; }
  .live-card { border:1px solid var(--p-border); background:var(--p-surface-strong); border-radius:24px; box-shadow:var(--p-shadow); overflow:hidden; }
  .live-card__head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 18px 14px; border-bottom:1px solid var(--p-border); }
  .live-card__title { font-size:1rem; font-weight:900; color:var(--p-text); }
  .live-card__sub { font-size:.77rem; color:var(--p-hint); margin-top:2px; }
  .live-list { padding:8px 10px 12px; }
  .live-row { display:grid; grid-template-columns:auto 1fr auto auto; gap:12px; align-items:center; padding:12px 10px; border-radius:18px; }
  .live-row + .live-row { margin-top:4px; }
  .live-row:hover { background:var(--p-elevated); }
  .live-avatar { width:40px; height:40px; border-radius:14px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#111827,#475569); color:#fff; font-weight:900; }
  .live-avatar img { width:100%; height:100%; object-fit:cover; }
  .live-name { font-size:.88rem; font-weight:800; color:var(--p-text); }
  .live-hint { font-size:.76rem; color:var(--p-hint); }
  .live-pill { display:inline-flex; align-items:center; justify-content:center; min-height:28px; padding:0 10px; border-radius:999px; background:var(--p-elevated); color:var(--p-text); font-size:.72rem; font-weight:800; white-space:nowrap; }
  .live-pill.success { color:var(--p-success); background:var(--p-success-d); }
  .live-pill.warning { color:var(--p-warning); background:var(--p-warning-d); }
  .live-pill.info { color:var(--p-info); background:var(--p-info-d); }
  .live-pill.danger { color:var(--p-danger); background:var(--p-danger-d); }
  .live-amount { font-size:.82rem; font-weight:800; color:var(--p-text); }
  .live-empty { padding:28px 18px; text-align:center; color:var(--p-hint); font-size:.84rem; }
  .live-section { display:none; }
  .live-section.is-active { display:block; }
  @media (max-width: 1200px) { .live-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .live-panels { grid-template-columns:1fr; } }
  @media (max-width: 720px) { .live-shell { padding:16px; } .live-grid { grid-template-columns:1fr; } .live-row { grid-template-columns:auto 1fr; } .live-row > :nth-child(3), .live-row > :nth-child(4) { grid-column:2; } }
</style>
@endpush

@section('content')
<div class="live-shell"
     x-data="liveMonitor(@js($snapshot), '{{ route('admin.dashboard.live.data') }}')"
     x-init="init()">
  <div class="live-header">
    <div>
      <div class="live-title">Real-time Monitor</div>
      <div class="live-sub">Buyurtmalar, seller orderlar, courier orderlar va online foydalanuvchilar bitta oynada</div>
    </div>
    <div class="live-actions">
      <div class="live-seg">
        <button type="button" :class="{ active: tab === 'overview' }" @click="switchTab('overview')">Umumiy</button>
        <button type="button" :class="{ active: tab === 'orders' }" @click="switchTab('orders')">Buyurtmalar</button>
        <button type="button" :class="{ active: tab === 'seller' }" @click="switchTab('seller')">Seller orderlar</button>
        <button type="button" :class="{ active: tab === 'courier' }" @click="switchTab('courier')">Courier orderlar</button>
        <button type="button" :class="{ active: tab === 'users' }" @click="switchTab('users')">Online userlar</button>
      </div>
      <div class="live-meta-chip">Yangilandi: <span x-text="snapshot.generated_at"></span></div>
      <button type="button" class="btn-p ghost" @click="toggleFullscreen()"><i class="bi bi-fullscreen"></i> Full screen</button>
      <a href="{{ route('admin.dashboard') }}" class="btn-p ghost"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
  </div>

  <section class="live-section" :class="{ 'is-active': tab === 'overview' }">
    <div class="live-grid">
      <div class="live-stat"><div class="live-stat__label">Asosiy buyurtmalar</div><div class="live-stat__value" x-text="snapshot.main_counts.all"></div><div class="live-stat__sub">Userlar bergan jami buyurtmalar</div></div>
      <div class="live-stat"><div class="live-stat__label">Seller orderlar</div><div class="live-stat__value" x-text="snapshot.seller_counts.all"></div><div class="live-stat__sub">Sellerlar olgan buyurtmalar</div></div>
      <div class="live-stat"><div class="live-stat__label">Courier orderlar</div><div class="live-stat__value" x-text="snapshot.courier_counts.all"></div><div class="live-stat__sub">Kuryer oqimiga tushgan buyurtmalar</div></div>
      <div class="live-stat"><div class="live-stat__label">Online userlar</div><div class="live-stat__value" x-text="snapshot.online_users_count"></div><div class="live-stat__sub">Oxirgi 5 daqiqa ichida faol</div></div>
    </div>
    <div class="live-panels">
      <div class="live-card">
        <div class="live-card__head"><div><div class="live-card__title">Status pulse</div><div class="live-card__sub">Har bir oqim bo'yicha jonli statuslar</div></div></div>
        <div class="live-list">
          <div class="live-row"><div class="live-pill warning">User order</div><div class="live-name">Yangi / Qadoqlanmoqda / Yo'lda</div><div class="live-amount" x-text="`${snapshot.main_counts.new} / ${snapshot.main_counts.packing} / ${snapshot.main_counts.onway}`"></div><div class="live-pill success" x-text="`${snapshot.main_counts.done} yetkazildi`"></div></div>
          <div class="live-row"><div class="live-pill info">Seller</div><div class="live-name">To'lov / Yangi / Qabul / Kuryerga berdi</div><div class="live-amount" x-text="`${snapshot.seller_counts.payment_pending} / ${snapshot.seller_counts.new} / ${snapshot.seller_counts.accepted} / ${snapshot.seller_counts.handover}`"></div><div class="live-pill danger" x-text="`${snapshot.seller_counts.cancelled} bekor`"></div></div>
          <div class="live-row"><div class="live-pill info">Courier</div><div class="live-name">Kutilmoqda / Yo'lda / Yetkazildi</div><div class="live-amount" x-text="`${snapshot.courier_counts.pending} / ${snapshot.courier_counts.in_delivery} / ${snapshot.courier_counts.delivered}`"></div><div class="live-pill danger" x-text="`${snapshot.courier_counts.rejected} bekor`"></div></div>
        </div>
      </div>
      <div class="live-card">
        <div class="live-card__head"><div><div class="live-card__title">Hozir online</div><div class="live-card__sub">Jonli user monitoring</div></div></div>
        <div class="live-list">
          <template x-for="user in snapshot.online_users" :key="`overview-user-${user.id}`">
            <div class="live-row">
              <div class="live-avatar"><template x-if="user.avatar"><img :src="user.avatar" alt=""></template><template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template></div>
              <div><div class="live-name" x-text="user.name"></div><div class="live-hint" x-text="user.last_seen"></div></div>
              <div class="live-pill success">Online</div>
              <a class="btn-p ghost sm" :href="`${userBaseUrl}/${user.id}`">Ochish</a>
            </div>
          </template>
          <div class="live-empty" x-show="!snapshot.online_users.length">Hozir online user topilmadi.</div>
        </div>
      </div>
    </div>
  </section>

  <section class="live-section" :class="{ 'is-active': tab === 'orders' }">
    <div class="live-grid">
      <div class="live-stat"><div class="live-stat__label">Yangi</div><div class="live-stat__value" x-text="snapshot.main_counts.new"></div><div class="live-stat__sub">Admin nazoratida</div></div>
      <div class="live-stat"><div class="live-stat__label">Qadoqlanmoqda</div><div class="live-stat__value" x-text="snapshot.main_counts.packing"></div><div class="live-stat__sub">Seller tayyorlayapti</div></div>
      <div class="live-stat"><div class="live-stat__label">Yo'lda</div><div class="live-stat__value" x-text="snapshot.main_counts.onway"></div><div class="live-stat__sub">Courier oqimida</div></div>
      <div class="live-stat"><div class="live-stat__label">Yetkazildi</div><div class="live-stat__value" x-text="snapshot.main_counts.done"></div><div class="live-stat__sub">Yakunlangan</div></div>
    </div>
    <div class="live-card">
      <div class="live-card__head"><div><div class="live-card__title">So'nggi user buyurtmalari</div><div class="live-card__sub">Status o'zgarishini kuzatish uchun</div></div></div>
      <div class="live-list">
        <template x-for="order in snapshot.recent_orders" :key="`order-${order.id}`">
          <div class="live-row">
            <div class="live-avatar"><template x-if="order.avatar"><img :src="order.avatar" alt=""></template><template x-if="!order.avatar"><span x-text="order.customer.charAt(0)"></span></template></div>
            <div><div class="live-name" x-text="`#${order.id} · ${order.customer}`"></div><div class="live-hint" x-text="order.updated_at"></div></div>
            <div class="live-amount" x-text="`${order.amount} UZS`"></div>
            <div class="live-pill info" x-text="order.status"></div>
          </div>
        </template>
      </div>
    </div>
  </section>

  <section class="live-section" :class="{ 'is-active': tab === 'seller' }">
    <div class="live-grid">
      <div class="live-stat"><div class="live-stat__label">To'lov jarayoni</div><div class="live-stat__value" x-text="snapshot.seller_counts.payment_pending"></div><div class="live-stat__sub">Online to'lov hali tushmagan</div></div>
      <div class="live-stat"><div class="live-stat__label">Yangi seller order</div><div class="live-stat__value" x-text="snapshot.seller_counts.new"></div><div class="live-stat__sub">Sellerga tushgan</div></div>
      <div class="live-stat"><div class="live-stat__label">Do'kon qabul qildi</div><div class="live-stat__value" x-text="snapshot.seller_counts.accepted"></div><div class="live-stat__sub">Tayyorlab qo'yilgan</div></div>
      <div class="live-stat"><div class="live-stat__label">Kuryerga berilgan</div><div class="live-stat__value" x-text="snapshot.seller_counts.handover"></div><div class="live-stat__sub">Topshirilgan</div></div>
      <div class="live-stat"><div class="live-stat__label">Bekor qilingan</div><div class="live-stat__value" x-text="snapshot.seller_counts.cancelled"></div><div class="live-stat__sub">To'xtagan oqim</div></div>
    </div>
    <div class="live-card">
      <div class="live-card__head"><div><div class="live-card__title">So'nggi seller orderlar</div><div class="live-card__sub">Sellerlar olgan buyurtmalar</div></div></div>
      <div class="live-list">
        <template x-for="order in snapshot.recent_seller_orders" :key="`seller-${order.id}`">
          <div class="live-row">
            <div class="live-avatar"><template x-if="order.avatar"><img :src="order.avatar" alt=""></template><template x-if="!order.avatar"><span x-text="order.seller.charAt(0)"></span></template></div>
            <div><div class="live-name" x-text="`#${order.id} · ${order.seller}`"></div><div class="live-hint" x-text="order.customer"></div></div>
            <div class="live-amount" x-text="`${order.amount} UZS`"></div>
            <div class="live-pill warning" x-text="order.status"></div>
          </div>
        </template>
      </div>
    </div>
  </section>

  <section class="live-section" :class="{ 'is-active': tab === 'courier' }">
    <div class="live-grid">
      <div class="live-stat"><div class="live-stat__label">To'lov jarayoni</div><div class="live-stat__value" x-text="snapshot.courier_counts.pay_process"></div><div class="live-stat__sub">Hali dispatch bo'lmagan</div></div>
      <div class="live-stat"><div class="live-stat__label">Kutilmoqda</div><div class="live-stat__value" x-text="snapshot.courier_counts.pending"></div><div class="live-stat__sub">Kuryer hali olmagan</div></div>
      <div class="live-stat"><div class="live-stat__label">Yo'lda</div><div class="live-stat__value" x-text="snapshot.courier_counts.in_delivery"></div><div class="live-stat__sub">Courier oqimida</div></div>
      <div class="live-stat"><div class="live-stat__label">Yetkazgan</div><div class="live-stat__value" x-text="snapshot.courier_counts.delivered"></div><div class="live-stat__sub">Yakunlangan</div></div>
    </div>
    <div class="live-card">
      <div class="live-card__head"><div><div class="live-card__title">So'nggi courier orderlar</div><div class="live-card__sub">Kuryer qabul qilgan va yetkazgan oqim</div></div></div>
      <div class="live-list">
        <template x-for="order in snapshot.recent_courier_orders" :key="`courier-${order.id}`">
          <div class="live-row">
            <div class="live-avatar"><template x-if="order.avatar"><img :src="order.avatar" alt=""></template><template x-if="!order.avatar"><span x-text="order.courier.charAt(0)"></span></template></div>
            <div><div class="live-name" x-text="`#${order.id} · ${order.courier}`"></div><div class="live-hint" x-text="order.customer"></div></div>
            <div class="live-amount" x-text="`${order.amount} UZS`"></div>
            <div class="live-pill success" x-text="order.status"></div>
          </div>
        </template>
      </div>
    </div>
  </section>

  <section class="live-section" :class="{ 'is-active': tab === 'users' }">
    <div class="live-grid">
      <div class="live-stat"><div class="live-stat__label">Online userlar</div><div class="live-stat__value" x-text="snapshot.online_users_count"></div><div class="live-stat__sub">Jonli ko'rinayotganlar</div></div>
      <div class="live-stat"><div class="live-stat__label">User buyurtmalar</div><div class="live-stat__value" x-text="snapshot.main_counts.all"></div><div class="live-stat__sub">Umumiy xaridlar</div></div>
      <div class="live-stat"><div class="live-stat__label">Faol user order</div><div class="live-stat__value" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div><div class="live-stat__sub">Hozir jarayonda</div></div>
      <div class="live-stat"><div class="live-stat__label">Yetkazilgan</div><div class="live-stat__value" x-text="snapshot.main_counts.done"></div><div class="live-stat__sub">Mijozga yetgan</div></div>
    </div>
    <div class="live-card">
      <div class="live-card__head"><div><div class="live-card__title">Online foydalanuvchilar</div><div class="live-card__sub">Real-time kuzatuv oynasi</div></div></div>
      <div class="live-list">
        <template x-for="user in snapshot.online_users" :key="`users-tab-${user.id}`">
          <div class="live-row">
            <div class="live-avatar"><template x-if="user.avatar"><img :src="user.avatar" alt=""></template><template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template></div>
            <div><div class="live-name" x-text="user.name"></div><div class="live-hint" x-text="user.last_seen"></div></div>
            <div class="live-pill success">Online</div>
            <a class="btn-p ghost sm" :href="`${userBaseUrl}/${user.id}`">Profil</a>
          </div>
        </template>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script>
function liveMonitor(initialSnapshot, endpoint) {
  return {
    tab: localStorage.getItem('a122-live-tab') || 'overview',
    snapshot: initialSnapshot,
    endpoint,
    userBaseUrl: @js(url('/a122/users')),
    timer: null,
    init() {
      this.refresh();
      this.timer = setInterval(() => this.refresh(), 10000);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) this.refresh();
      });
    },
    switchTab(tab) {
      this.tab = tab;
      localStorage.setItem('a122-live-tab', tab);
    },
    async refresh() {
      try {
        const response = await fetch(this.endpoint, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          cache: 'no-store',
        });
        if (!response.ok) return;
        this.snapshot = await response.json();
      } catch (error) {
        console.error('Live monitor refresh error:', error);
      }
    },
    async toggleFullscreen() {
      if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen?.();
        return;
      }
      await document.exitFullscreen?.();
    },
  };
}
</script>
@endpush
