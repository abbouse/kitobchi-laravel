<?php $__env->startSection('title', 'Live Monitor'); ?>

<?php $__env->startSection('content'); ?>
<div class="live-shell"
     x-data="liveMonitor(<?php echo \Illuminate\Support\Js::from($snapshot)->toHtml() ?>, '<?php echo e(route('admin.dashboard.live.data')); ?>')"
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
      <button type="button" class="btn btn-outline-secondary rounded-pill px-3" @click="toggleFullscreen()"><i class="bi bi-fullscreen me-1"></i>Full screen</button>
      <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-dark rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
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
          <div class="live-row"><div class="live-pill warning">User order</div><div class="live-name">Yangi / Qadoqlanmoqda / Yo'lda</div><div class="live-amount" x-text="`${snapshot.main_counts.new} / ${snapshot.main_counts.packing} / ${snapshot.main_counts.onway}`"></div><div class="live-pill success" x-text="`${snapshot.main_counts.done} qabul qilindi`"></div></div>
          <div class="live-row"><div class="live-pill info">Seller</div><div class="live-name">To'lov / Yangi / Qabul / Kuryerga berdi</div><div class="live-amount" x-text="`${snapshot.seller_counts.payment_pending} / ${snapshot.seller_counts.new} / ${snapshot.seller_counts.accepted} / ${snapshot.seller_counts.handover}`"></div><div class="live-pill danger" x-text="`${snapshot.seller_counts.cancelled} bekor`"></div></div>
          <div class="live-row"><div class="live-pill info">Courier</div><div class="live-name">Kutilmoqda / Yo'lda / Yetib bordi / Mijoz qabul qildi</div><div class="live-amount" x-text="`${snapshot.courier_counts.pending} / ${snapshot.courier_counts.in_delivery} / ${snapshot.courier_counts.delivered} / ${snapshot.courier_counts.customer_received}`"></div><div class="live-pill danger" x-text="`${snapshot.courier_counts.rejected} bekor`"></div></div>
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
              <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" :href="`${userBaseUrl}/${user.id}`">Ochish</a>
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
      <div class="live-stat"><div class="live-stat__label">Mijoz qabul qildi</div><div class="live-stat__value" x-text="snapshot.main_counts.done"></div><div class="live-stat__sub">Yakunlangan</div></div>
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
      <div class="live-stat"><div class="live-stat__label">Mijoz qabul qildi</div><div class="live-stat__value" x-text="snapshot.courier_counts.customer_received"></div><div class="live-stat__sub">Yakunlangan</div></div>
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
      <div class="live-stat"><div class="live-stat__label">Mijoz qabul qilgan</div><div class="live-stat__value" x-text="snapshot.main_counts.done"></div><div class="live-stat__sub">Yakunlangan buyurtmalar</div></div>
    </div>
    <div class="live-card">
      <div class="live-card__head"><div><div class="live-card__title">Online foydalanuvchilar</div><div class="live-card__sub">Real-time kuzatuv oynasi</div></div></div>
      <div class="live-list">
        <template x-for="user in snapshot.online_users" :key="`users-tab-${user.id}`">
          <div class="live-row">
            <div class="live-avatar"><template x-if="user.avatar"><img :src="user.avatar" alt=""></template><template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template></div>
            <div><div class="live-name" x-text="user.name"></div><div class="live-hint" x-text="user.last_seen"></div></div>
            <div class="live-pill success">Online</div>
            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" :href="`${userBaseUrl}/${user.id}`">Profil</a>
          </div>
        </template>
      </div>
    </div>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function liveMonitor(initialSnapshot, endpoint) {
  return {
    tab: localStorage.getItem('a122-live-tab') || 'overview',
    snapshot: initialSnapshot,
    endpoint,
    userBaseUrl: <?php echo \Illuminate\Support\Js::from(url('/a122/users'))->toHtml() ?>,
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.monitor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/dashboard-live.blade.php ENDPATH**/ ?>