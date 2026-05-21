@extends('a122.layouts.monitor')
@section('title', 'Live Monitor')

@section('content')
<div class="live-shell"
     x-data="liveMonitor(@js($snapshot), '{{ route('admin.dashboard.live.data') }}')"
     x-init="init()">
  <div class="live-hero">
    <div class="live-hero__copy">
      <div class="live-kicker">
        <span class="live-kicker__dot"></span>
        A122 Live Pulse
      </div>
      <div class="live-title">Real-time Dashboard</div>
      <div class="live-sub">Buyurtmalar, seller oqimi, courier harakati va online foydalanuvchilarni bitta kuchli monitor oynasida kuzatib boring.</div>
      <div class="live-hero__meta">
        <div class="live-meta-chip"><i class="bi bi-arrow-repeat"></i><span>Oxirgi yangilanish: <strong x-text="snapshot.generated_at"></strong></span></div>
        <div class="live-meta-chip"><i class="bi bi-people"></i><span><strong x-text="snapshot.online_users_count"></strong> online foydalanuvchi</span></div>
        <div class="live-meta-chip"><i class="bi bi-box-seam"></i><span><strong x-text="snapshot.main_counts.all"></strong> jami buyurtma</span></div>
      </div>
    </div>

    <div class="live-hero__side">
      <div class="live-hero__status">
        <div class="live-hero__status-label">Hozir nazoratda</div>
        <div class="live-hero__status-value" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div>
        <div class="live-hero__status-sub">Yangi, qadoqlanayotgan va yo'ldagi buyurtmalar</div>
      </div>
      <div class="live-actions">
        <div class="live-seg">
          <button type="button" :class="{ active: tab === 'overview' }" @click="switchTab('overview')">Umumiy</button>
          <button type="button" :class="{ active: tab === 'orders' }" @click="switchTab('orders')">Buyurtmalar</button>
          <button type="button" :class="{ active: tab === 'seller' }" @click="switchTab('seller')">Sellerlar</button>
          <button type="button" :class="{ active: tab === 'courier' }" @click="switchTab('courier')">Kuryerlar</button>
          <button type="button" :class="{ active: tab === 'users' }" @click="switchTab('users')">Online userlar</button>
        </div>
        <div class="live-actions__buttons">
          <button type="button" class="live-action-btn live-action-btn--ghost" @click="toggleFullscreen()"><i class="bi bi-fullscreen"></i><span>Full screen</span></button>
          <a href="{{ route('admin.dashboard') }}" class="live-action-btn live-action-btn--dark"><i class="bi bi-arrow-left"></i><span>Dashboard</span></a>
        </div>
      </div>
    </div>
  </div>

  <section class="live-section" x-cloak :class="{ 'is-active': tab === 'overview' }">
    <div class="live-grid live-grid--hero">
      <div class="live-stat live-stat--accent">
        <div class="live-stat__label">Asosiy buyurtmalar</div>
        <div class="live-stat__value" x-text="snapshot.main_counts.all"></div>
        <div class="live-stat__sub">Userlar bergan jami buyurtmalar</div>
      </div>
      <div class="live-stat">
        <div class="live-stat__label">Seller orderlar</div>
        <div class="live-stat__value" x-text="snapshot.seller_counts.all"></div>
        <div class="live-stat__sub">Seller tomoniga tushgan oqim</div>
      </div>
      <div class="live-stat">
        <div class="live-stat__label">Courier orderlar</div>
        <div class="live-stat__value" x-text="snapshot.courier_counts.all"></div>
        <div class="live-stat__sub">Yetkazish oqimiga tushgan buyurtmalar</div>
      </div>
      <div class="live-stat">
        <div class="live-stat__label">Mijoz qabul qildi</div>
        <div class="live-stat__value" x-text="snapshot.main_counts.done"></div>
        <div class="live-stat__sub">Yakunlangan buyurtmalar</div>
      </div>
    </div>

    <div class="live-overview-grid">
      <div class="live-card live-card--flush">
        <div class="live-card__head">
          <div>
            <div class="live-card__eyebrow">Status overview</div>
            <div class="live-card__title">Oqim pulse</div>
            <div class="live-card__sub">Har bir asosiy oqimning jonli holati va yuklamasi.</div>
          </div>
        </div>
        <div class="live-flow">
          <div class="live-flow-card">
            <div class="live-flow-card__head">
              <div class="live-pill warning">User orders</div>
              <div class="live-flow-card__count" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div>
            </div>
            <div class="live-flow-card__title">Yangi, qadoqlanmoqda va yo'ldagi buyurtmalar</div>
            <div class="live-flow-card__rail">
              <span class="live-flow-card__metric">Yangi: <strong x-text="snapshot.main_counts.new"></strong></span>
              <span class="live-flow-card__metric">Qadoqlanmoqda: <strong x-text="snapshot.main_counts.packing"></strong></span>
              <span class="live-flow-card__metric">Yo'lda: <strong x-text="snapshot.main_counts.onway"></strong></span>
            </div>
            <div class="live-flow-card__footer">Yakunlangan: <strong x-text="snapshot.main_counts.done"></strong></div>
          </div>

          <div class="live-flow-card">
            <div class="live-flow-card__head">
              <div class="live-pill info">Seller</div>
              <div class="live-flow-card__count" x-text="snapshot.seller_counts.new + snapshot.seller_counts.accepted + snapshot.seller_counts.handover"></div>
            </div>
            <div class="live-flow-card__title">Seller topshiriqlari va tayyorlash jarayoni</div>
            <div class="live-flow-card__rail">
              <span class="live-flow-card__metric">To'lov: <strong x-text="snapshot.seller_counts.payment_pending"></strong></span>
              <span class="live-flow-card__metric">Yangi: <strong x-text="snapshot.seller_counts.new"></strong></span>
              <span class="live-flow-card__metric">Qabul qilgan: <strong x-text="snapshot.seller_counts.accepted"></strong></span>
            </div>
            <div class="live-flow-card__footer">Kuryerga berilgan: <strong x-text="snapshot.seller_counts.handover"></strong></div>
          </div>

          <div class="live-flow-card">
            <div class="live-flow-card__head">
              <div class="live-pill success">Courier</div>
              <div class="live-flow-card__count" x-text="snapshot.courier_counts.pending + snapshot.courier_counts.in_delivery"></div>
            </div>
            <div class="live-flow-card__title">Last-mile va yetkazish bo'yicha jonli harakat</div>
            <div class="live-flow-card__rail">
              <span class="live-flow-card__metric">Kutilmoqda: <strong x-text="snapshot.courier_counts.pending"></strong></span>
              <span class="live-flow-card__metric">Yo'lda: <strong x-text="snapshot.courier_counts.in_delivery"></strong></span>
              <span class="live-flow-card__metric">Yetib bordi: <strong x-text="snapshot.courier_counts.delivered"></strong></span>
            </div>
            <div class="live-flow-card__footer">Mijoz qabul qildi: <strong x-text="snapshot.courier_counts.customer_received"></strong></div>
          </div>
        </div>
      </div>

      <div class="live-card live-card--flush">
        <div class="live-card__head">
          <div>
            <div class="live-card__eyebrow">Realtime users</div>
            <div class="live-card__title">Hozir online</div>
            <div class="live-card__sub">Oxirgi 5 daqiqada faol bo'lgan foydalanuvchilar.</div>
          </div>
        </div>
        <div class="live-list">
          <template x-for="user in snapshot.online_users" :key="`overview-user-${user.id}`">
            <div class="live-row live-row--soft">
              <div class="live-avatar">
                <template x-if="user.avatar"><img :src="user.avatar" alt=""></template>
                <template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template>
              </div>
              <div class="live-row__main">
                <div class="live-name" x-text="user.name"></div>
                <div class="live-hint" x-text="user.last_seen"></div>
              </div>
              <div class="live-row__aside">
                <div class="live-pill success">Online</div>
                <a class="live-mini-btn" :href="`${userBaseUrl}/${user.id}`">Ochish</a>
              </div>
            </div>
          </template>
          <div class="live-empty" x-show="!snapshot.online_users.length">Hozircha online user topilmadi.</div>
        </div>
      </div>
    </div>
  </section>

  <section class="live-section" x-cloak :class="{ 'is-active': tab === 'orders' }">
    <div class="live-section-head">
      <div>
        <div class="live-card__eyebrow">Orders monitor</div>
        <div class="live-section-title">Asosiy buyurtmalar</div>
        <div class="live-section-sub">User buyurtmalarining eng muhim holatlari va eng so'nggi o'zgarishlar.</div>
      </div>
      <div class="live-inline-stats">
        <div class="live-inline-stat"><span>Yangi</span><strong x-text="snapshot.main_counts.new"></strong></div>
        <div class="live-inline-stat"><span>Qadoqlanmoqda</span><strong x-text="snapshot.main_counts.packing"></strong></div>
        <div class="live-inline-stat"><span>Yo'lda</span><strong x-text="snapshot.main_counts.onway"></strong></div>
        <div class="live-inline-stat"><span>Qabul qilingan</span><strong x-text="snapshot.main_counts.done"></strong></div>
      </div>
    </div>

    <div class="live-card live-card--flush">
      <div class="live-list">
        <template x-for="order in snapshot.recent_orders" :key="`order-${order.id}`">
          <div class="live-row live-row--sheet">
            <div class="live-avatar">
              <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
              <template x-if="!order.avatar"><span x-text="order.customer.charAt(0)"></span></template>
            </div>
            <div class="live-row__main">
              <div class="live-name" x-text="`#${order.id} · ${order.customer}`"></div>
              <div class="live-hint" x-text="order.updated_at"></div>
            </div>
            <div class="live-row__aside">
              <div class="live-amount" x-text="`${order.amount} UZS`"></div>
              <div class="live-pill info" x-text="order.status"></div>
            </div>
          </div>
        </template>
        <div class="live-empty" x-show="!snapshot.recent_orders.length">So'nggi user buyurtmalari hozircha topilmadi.</div>
      </div>
    </div>
  </section>

  <section class="live-section" x-cloak :class="{ 'is-active': tab === 'seller' }">
    <div class="live-section-head">
      <div>
        <div class="live-card__eyebrow">Seller operations</div>
        <div class="live-section-title">Seller orderlar</div>
        <div class="live-section-sub">Seller qabul qilgan, tayyorlayotgan va topshirgan buyurtmalar bo'yicha tez monitoring.</div>
      </div>
      <div class="live-inline-stats">
        <div class="live-inline-stat"><span>To'lov</span><strong x-text="snapshot.seller_counts.payment_pending"></strong></div>
        <div class="live-inline-stat"><span>Yangi</span><strong x-text="snapshot.seller_counts.new"></strong></div>
        <div class="live-inline-stat"><span>Qabul qilgan</span><strong x-text="snapshot.seller_counts.accepted"></strong></div>
        <div class="live-inline-stat"><span>Kuryerga bergan</span><strong x-text="snapshot.seller_counts.handover"></strong></div>
      </div>
    </div>

    <div class="live-card live-card--flush">
      <div class="live-list">
        <template x-for="order in snapshot.recent_seller_orders" :key="`seller-${order.id}`">
          <div class="live-row live-row--sheet">
            <div class="live-avatar">
              <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
              <template x-if="!order.avatar"><span x-text="order.seller.charAt(0)"></span></template>
            </div>
            <div class="live-row__main">
              <div class="live-name" x-text="`#${order.id} · ${order.seller}`"></div>
              <div class="live-hint" x-text="order.customer"></div>
            </div>
            <div class="live-row__aside">
              <div class="live-amount" x-text="`${order.amount} UZS`"></div>
              <div class="live-pill warning" x-text="order.status"></div>
            </div>
          </div>
        </template>
        <div class="live-empty" x-show="!snapshot.recent_seller_orders.length">Seller oqimida so'nggi orderlar topilmadi.</div>
      </div>
    </div>
  </section>

  <section class="live-section" x-cloak :class="{ 'is-active': tab === 'courier' }">
    <div class="live-section-head">
      <div>
        <div class="live-card__eyebrow">Courier control</div>
        <div class="live-section-title">Courier orderlar</div>
        <div class="live-section-sub">Kutilayotgan, yo'ldagi va mijozga topshirilgan yetkazishlar holati.</div>
      </div>
      <div class="live-inline-stats">
        <div class="live-inline-stat"><span>Pay process</span><strong x-text="snapshot.courier_counts.pay_process"></strong></div>
        <div class="live-inline-stat"><span>Kutilmoqda</span><strong x-text="snapshot.courier_counts.pending"></strong></div>
        <div class="live-inline-stat"><span>Yo'lda</span><strong x-text="snapshot.courier_counts.in_delivery"></strong></div>
        <div class="live-inline-stat"><span>Qabul qildi</span><strong x-text="snapshot.courier_counts.customer_received"></strong></div>
      </div>
    </div>

    <div class="live-card live-card--flush">
      <div class="live-list">
        <template x-for="order in snapshot.recent_courier_orders" :key="`courier-${order.id}`">
          <div class="live-row live-row--sheet">
            <div class="live-avatar">
              <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
              <template x-if="!order.avatar"><span x-text="order.courier.charAt(0)"></span></template>
            </div>
            <div class="live-row__main">
              <div class="live-name" x-text="`#${order.id} · ${order.courier}`"></div>
              <div class="live-hint" x-text="order.customer"></div>
            </div>
            <div class="live-row__aside">
              <div class="live-amount" x-text="`${order.amount} UZS`"></div>
              <div class="live-pill success" x-text="order.status"></div>
            </div>
          </div>
        </template>
        <div class="live-empty" x-show="!snapshot.recent_courier_orders.length">Courier oqimida yangi yozuv topilmadi.</div>
      </div>
    </div>
  </section>

  <section class="live-section" x-cloak :class="{ 'is-active': tab === 'users' }">
    <div class="live-section-head">
      <div>
        <div class="live-card__eyebrow">Online audience</div>
        <div class="live-section-title">Online foydalanuvchilar</div>
        <div class="live-section-sub">Kim hozir ilovada va qaysi paytda oxirgi marta faol bo'lganini ko'rish oynasi.</div>
      </div>
      <div class="live-inline-stats">
        <div class="live-inline-stat"><span>Online</span><strong x-text="snapshot.online_users_count"></strong></div>
        <div class="live-inline-stat"><span>Jami buyurtma</span><strong x-text="snapshot.main_counts.all"></strong></div>
        <div class="live-inline-stat"><span>Faol order</span><strong x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></strong></div>
        <div class="live-inline-stat"><span>Yakunlangan</span><strong x-text="snapshot.main_counts.done"></strong></div>
      </div>
    </div>

    <div class="live-card live-card--flush">
      <div class="live-list">
        <template x-for="user in snapshot.online_users" :key="`users-tab-${user.id}`">
          <div class="live-row live-row--sheet">
            <div class="live-avatar">
              <template x-if="user.avatar"><img :src="user.avatar" alt=""></template>
              <template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template>
            </div>
            <div class="live-row__main">
              <div class="live-name" x-text="user.name"></div>
              <div class="live-hint" x-text="user.last_seen"></div>
            </div>
            <div class="live-row__aside">
              <div class="live-pill success">Online</div>
              <a class="live-mini-btn" :href="`${userBaseUrl}/${user.id}`">Profil</a>
            </div>
          </div>
        </template>
        <div class="live-empty" x-show="!snapshot.online_users.length">Online foydalanuvchi hozircha yo'q.</div>
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
    visibilityHandler: null,
    init() {
      this.refresh();
      this.timer = setInterval(() => this.refresh(), 10000);
      this.visibilityHandler = () => {
        if (!document.hidden) this.refresh();
      };
      document.addEventListener('visibilitychange', this.visibilityHandler);
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
    destroy() {
      if (this.timer) clearInterval(this.timer);
      if (this.visibilityHandler) {
        document.removeEventListener('visibilitychange', this.visibilityHandler);
      }
    },
  };
}
</script>
@endpush
