<?php $__env->startSection('title', 'Live Monitor'); ?>

<?php $__env->startPush('styles'); ?>
<style>
  .lm-page {
    min-height: 100vh;
    padding: 28px;
    background:
      radial-gradient(circle at top left, rgba(32, 107, 196, 0.12), transparent 24%),
      radial-gradient(circle at top right, rgba(16, 185, 129, 0.10), transparent 20%),
      linear-gradient(180deg, #f4f7fb 0%, #eef3f9 100%);
  }

  .lm-shell {
    max-width: 1680px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .lm-panel,
  .lm-card,
  .lm-pill-stat,
  .lm-segment,
  .lm-mini-action,
  .lm-list-row {
    border: 1px solid rgba(15, 23, 42, 0.07);
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
  }

  .lm-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(360px, 0.8fr);
    gap: 18px;
    padding: 22px;
    border-radius: 28px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    background:
      radial-gradient(circle at top left, rgba(32, 107, 196, 0.16), transparent 30%),
      radial-gradient(circle at 85% 10%, rgba(14, 165, 233, 0.12), transparent 20%),
      linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.97));
    box-shadow: 0 26px 60px rgba(15, 23, 42, 0.08);
  }

  .lm-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-height: 36px;
    padding: 0 14px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  .lm-kicker__dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.12);
  }

  .lm-title {
    margin-top: 16px;
    font-size: clamp(1.8rem, 2vw, 2.5rem);
    font-weight: 900;
    line-height: 1.02;
    letter-spacing: -0.05em;
    color: #0f172a;
  }

  .lm-subtitle {
    max-width: 56rem;
    margin-top: 10px;
    color: #64748b;
    font-size: .95rem;
    line-height: 1.7;
  }

  .lm-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
  }

  .lm-chip {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    background: rgba(255, 255, 255, 0.96);
    color: #0f172a;
    font-size: .82rem;
    font-weight: 700;
  }

  .lm-chip i {
    color: #206bc4;
  }

  .lm-hero-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .lm-status-box {
    padding: 18px;
    border-radius: 24px;
    background:
      radial-gradient(circle at top right, rgba(34, 197, 94, 0.18), transparent 30%),
      linear-gradient(180deg, #0f172a 0%, #16243b 100%);
    color: #fff;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
  }

  .lm-status-box__label {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: rgba(255,255,255,.66);
  }

  .lm-status-box__value {
    margin-top: 10px;
    font-size: 2.4rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -.08em;
  }

  .lm-status-box__sub {
    margin-top: 8px;
    color: rgba(255,255,255,.78);
    font-size: .84rem;
    line-height: 1.55;
  }

  .lm-segment {
    display: flex;
    gap: 8px;
    padding: 6px;
    border-radius: 18px;
    overflow: auto;
  }

  .lm-segment button {
    border: 0;
    background: transparent;
    color: #64748b;
    min-height: 42px;
    padding: 0 14px;
    border-radius: 14px;
    font-size: .82rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .lm-segment button.is-active {
    background: #0f172a;
    color: #fff;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.05);
  }

  .lm-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
  }

  .lm-mini-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border-radius: 999px;
    color: #0f172a;
    text-decoration: none;
    font-size: .82rem;
    font-weight: 800;
    transition: transform .16s ease, background-color .16s ease, color .16s ease;
  }

  .lm-mini-action:hover {
    transform: translateY(-1px);
    color: #0f172a;
    background: #fff;
  }

  .lm-mini-action--dark {
    background: #0f172a;
    color: #fff;
  }

  .lm-mini-action--dark:hover {
    color: #fff;
    background: #111827;
  }

  .lm-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .lm-stat-card {
    padding: 18px;
    border-radius: 24px;
    background: rgba(255,255,255,.95);
    border: 1px solid rgba(15, 23, 42, 0.06);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
  }

  .lm-stat-card--accent {
    background:
      linear-gradient(180deg, rgba(32, 107, 196, 0.10), rgba(255,255,255,.96)),
      rgba(255,255,255,.95);
  }

  .lm-stat-card__label {
    color: #94a3b8;
    font-size: .74rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
  }

  .lm-stat-card__value {
    margin-top: 8px;
    font-size: 1.95rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -.06em;
    color: #0f172a;
  }

  .lm-stat-card__sub {
    margin-top: 6px;
    color: #64748b;
    font-size: .8rem;
    line-height: 1.55;
  }

  .lm-board {
    display: none;
    flex-direction: column;
    gap: 16px;
  }

  .lm-board.is-active {
    display: flex;
  }

  .lm-board-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
    gap: 16px;
  }

  .lm-card {
    border-radius: 26px;
    overflow: hidden;
  }

  .lm-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 18px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
  }

  .lm-card__eyebrow {
    color: #94a3b8;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
  }

  .lm-card__title,
  .lm-section__title {
    margin-top: 4px;
    color: #0f172a;
    font-size: 1.05rem;
    font-weight: 900;
    letter-spacing: -.03em;
  }

  .lm-card__sub,
  .lm-section__sub {
    margin-top: 4px;
    color: #64748b;
    font-size: .8rem;
    line-height: 1.55;
  }

  .lm-pulse-list {
    display: grid;
    gap: 12px;
    padding: 14px;
  }

  .lm-pulse-card {
    padding: 16px;
    border-radius: 22px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    background: linear-gradient(180deg, rgba(248,250,252,.98), rgba(255,255,255,.96));
  }

  .lm-pulse-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .lm-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
  }

  .lm-pill--primary { background: rgba(32, 107, 196, 0.12); color: #0d5fd3; }
  .lm-pill--success { background: rgba(25, 135, 84, 0.14); color: #146c43; }
  .lm-pill--warning { background: rgba(255, 193, 7, 0.18); color: #9a6700; }
  .lm-pill--dark { background: rgba(15, 23, 42, 0.08); color: #0f172a; }

  .lm-pulse-card__count {
    font-size: 1.55rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -.05em;
  }

  .lm-pulse-card__title {
    margin-top: 10px;
    color: #0f172a;
    font-size: .9rem;
    font-weight: 800;
  }

  .lm-pulse-card__metrics {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
  }

  .lm-pulse-card__metric {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-height: 30px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    font-size: .74rem;
    font-weight: 700;
  }

  .lm-pulse-card__metric strong,
  .lm-pulse-card__footer strong {
    color: #0f172a;
  }

  .lm-pulse-card__footer {
    margin-top: 10px;
    color: #64748b;
    font-size: .78rem;
  }

  .lm-side-stack {
    display: grid;
    gap: 14px;
    padding: 14px;
  }

  .lm-pill-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .lm-pill-stat {
    padding: 14px;
    border-radius: 18px;
  }

  .lm-pill-stat__label {
    color: #94a3b8;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
  }

  .lm-pill-stat__value {
    margin-top: 8px;
    color: #0f172a;
    font-size: 1.3rem;
    font-weight: 900;
    letter-spacing: -.04em;
  }

  .lm-pill-stat__sub {
    margin-top: 5px;
    color: #64748b;
    font-size: .76rem;
    line-height: 1.45;
  }

  .lm-section {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
  }

  .lm-inline-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .lm-inline-grid .lm-pill-stat {
    min-width: 148px;
  }

  .lm-list {
    padding: 10px;
    display: grid;
    gap: 8px;
  }

  .lm-list-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 12px;
    border-radius: 20px;
    transition: transform .16s ease, background-color .16s ease;
  }

  .lm-list-row:hover {
    transform: translateY(-1px);
    background: #fff;
  }

  .lm-avatar {
    width: 44px;
    height: 44px;
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0f172a, #475569);
    color: #fff;
    font-weight: 900;
  }

  .lm-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .lm-row__title {
    color: #0f172a;
    font-size: .9rem;
    font-weight: 800;
  }

  .lm-row__sub {
    margin-top: 2px;
    color: #64748b;
    font-size: .77rem;
    line-height: 1.45;
  }

  .lm-row__right {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
  }

  .lm-amount {
    color: #0f172a;
    font-size: .82rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .lm-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    color: #0f172a;
    text-decoration: none;
    font-size: .74rem;
    font-weight: 800;
    background: rgba(255,255,255,.96);
  }

  .lm-link:hover {
    color: #0f172a;
    background: #fff;
  }

  .lm-empty {
    padding: 26px 18px 30px;
    color: #64748b;
    text-align: center;
    font-size: .84rem;
  }

  @media (max-width: 1280px) {
    .lm-hero,
    .lm-board-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 960px) {
    .lm-page {
      padding: 16px;
    }

    .lm-stat-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 640px) {
    .lm-page {
      padding: 12px;
    }

    .lm-hero {
      padding: 16px;
      border-radius: 22px;
    }

    .lm-stat-grid,
    .lm-pill-grid {
      grid-template-columns: 1fr;
    }

    .lm-list-row {
      grid-template-columns: auto 1fr;
    }

    .lm-row__right {
      grid-column: 1 / -1;
      justify-content: flex-start;
      padding-left: 56px;
    }
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="lm-page"
     x-data="liveMonitor(<?php echo \Illuminate\Support\Js::from($snapshot)->toHtml() ?>, '<?php echo e(route('admin.dashboard.live.data')); ?>')"
     x-init="init()">
  <div class="lm-shell">
    <section class="lm-hero">
      <div>
        <div class="lm-kicker">
          <span class="lm-kicker__dot"></span>
          Live operations stream
        </div>
        <div class="lm-title">Buyurtmalar, sellerlar, kuryerlar va user oqimi bitta monitor ichida.</div>
        <div class="lm-subtitle">Bu sahifa operatsion nazorat uchun. Sonlar tez o‘qilishi, statuslar bir xil usulda ajralishi va har bir blok kutilayotgan navbatni ko‘rsatishi kerak.</div>

        <div class="lm-chip-row">
          <div class="lm-chip"><i class="bi bi-arrow-repeat"></i><span>Yangilandi: <strong x-text="snapshot.generated_at"></strong></span></div>
          <div class="lm-chip"><i class="bi bi-broadcast-pin"></i><span><strong x-text="snapshot.online_users_count"></strong> online user</span></div>
          <div class="lm-chip"><i class="bi bi-bag-check"></i><span><strong x-text="snapshot.main_counts.all"></strong> jami order</span></div>
        </div>
      </div>

      <div class="lm-hero-side">
        <div class="lm-status-box">
          <div class="lm-status-box__label">Hozir nazoratda</div>
          <div class="lm-status-box__value" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div>
          <div class="lm-status-box__sub">Yangi, qadoqlanayotgan va yo‘ldagi asosiy buyurtmalar soni. Bu blok operatsion navbatning “qizib turgan” qismini ko‘rsatadi.</div>
        </div>

        <div class="lm-segment">
          <button type="button" :class="{ 'is-active': tab === 'overview' }" @click="switchTab('overview')">Umumiy</button>
          <button type="button" :class="{ 'is-active': tab === 'orders' }" @click="switchTab('orders')">Buyurtmalar</button>
          <button type="button" :class="{ 'is-active': tab === 'seller' }" @click="switchTab('seller')">Sellerlar</button>
          <button type="button" :class="{ 'is-active': tab === 'courier' }" @click="switchTab('courier')">Kuryerlar</button>
          <button type="button" :class="{ 'is-active': tab === 'users' }" @click="switchTab('users')">Online userlar</button>
        </div>

        <div class="lm-actions">
          <button type="button" class="lm-mini-action" @click="toggleFullscreen()">
            <i class="bi bi-fullscreen"></i><span>Full screen</span>
          </button>
          <a href="<?php echo e(route('admin.dashboard')); ?>" class="lm-mini-action lm-mini-action--dark">
            <i class="bi bi-arrow-left"></i><span>Asosiy dashboard</span>
          </a>
        </div>
      </div>
    </section>

    <div class="lm-stat-grid">
      <div class="lm-stat-card lm-stat-card--accent">
        <div class="lm-stat-card__label">Asosiy buyurtmalar</div>
        <div class="lm-stat-card__value" x-text="snapshot.main_counts.all"></div>
        <div class="lm-stat-card__sub">Userlar bergan barcha buyurtmalar.</div>
      </div>
      <div class="lm-stat-card">
        <div class="lm-stat-card__label">Seller orderlar</div>
        <div class="lm-stat-card__value" x-text="snapshot.seller_counts.all"></div>
        <div class="lm-stat-card__sub">Do‘kon tomonga tushgan ichki oqim.</div>
      </div>
      <div class="lm-stat-card">
        <div class="lm-stat-card__label">Courier orderlar</div>
        <div class="lm-stat-card__value" x-text="snapshot.courier_counts.all"></div>
        <div class="lm-stat-card__sub">Yetkazish nazoratidagi yozuvlar.</div>
      </div>
      <div class="lm-stat-card">
        <div class="lm-stat-card__label">Mijoz qabul qildi</div>
        <div class="lm-stat-card__value" x-text="snapshot.main_counts.done"></div>
        <div class="lm-stat-card__sub">Yakunlangan buyurtmalar soni.</div>
      </div>
    </div>

    <section class="lm-board" x-cloak :class="{ 'is-active': tab === 'overview' }">
      <div class="lm-board-grid">
        <div class="lm-card">
          <div class="lm-card__head">
            <div>
              <div class="lm-card__eyebrow">Pulse board</div>
              <div class="lm-card__title">Asosiy oqimlar</div>
              <div class="lm-card__sub">Qayerda navbat ko‘p, qayerda oqim tiqilib qolishi mumkinligi shu blokda ko‘rinadi.</div>
            </div>
          </div>
          <div class="lm-pulse-list">
            <div class="lm-pulse-card">
              <div class="lm-pulse-card__top">
                <div class="lm-pill lm-pill--warning">User orders</div>
                <div class="lm-pulse-card__count" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div>
              </div>
              <div class="lm-pulse-card__title">Yangi, qadoqlanmoqda va yo‘ldagi buyurtmalar</div>
              <div class="lm-pulse-card__metrics">
                <span class="lm-pulse-card__metric">Yangi <strong x-text="snapshot.main_counts.new"></strong></span>
                <span class="lm-pulse-card__metric">Qadoqlanmoqda <strong x-text="snapshot.main_counts.packing"></strong></span>
                <span class="lm-pulse-card__metric">Yo‘lda <strong x-text="snapshot.main_counts.onway"></strong></span>
              </div>
              <div class="lm-pulse-card__footer">Yakunlangan: <strong x-text="snapshot.main_counts.done"></strong></div>
            </div>

            <div class="lm-pulse-card">
              <div class="lm-pulse-card__top">
                <div class="lm-pill lm-pill--primary">Seller lane</div>
                <div class="lm-pulse-card__count" x-text="snapshot.seller_counts.new + snapshot.seller_counts.accepted + snapshot.seller_counts.handover"></div>
              </div>
              <div class="lm-pulse-card__title">Seller tayyorlash va topshirish jarayoni</div>
              <div class="lm-pulse-card__metrics">
                <span class="lm-pulse-card__metric">To‘lov <strong x-text="snapshot.seller_counts.payment_pending"></strong></span>
                <span class="lm-pulse-card__metric">Yangi <strong x-text="snapshot.seller_counts.new"></strong></span>
                <span class="lm-pulse-card__metric">Qabul qildi <strong x-text="snapshot.seller_counts.accepted"></strong></span>
              </div>
              <div class="lm-pulse-card__footer">Kuryerga bergan: <strong x-text="snapshot.seller_counts.handover"></strong></div>
            </div>

            <div class="lm-pulse-card">
              <div class="lm-pulse-card__top">
                <div class="lm-pill lm-pill--success">Courier lane</div>
                <div class="lm-pulse-card__count" x-text="snapshot.courier_counts.pending + snapshot.courier_counts.in_delivery"></div>
              </div>
              <div class="lm-pulse-card__title">Kuryer navbati va last-mile holati</div>
              <div class="lm-pulse-card__metrics">
                <span class="lm-pulse-card__metric">Kutilmoqda <strong x-text="snapshot.courier_counts.pending"></strong></span>
                <span class="lm-pulse-card__metric">Yo‘lda <strong x-text="snapshot.courier_counts.in_delivery"></strong></span>
                <span class="lm-pulse-card__metric">Yetib bordi <strong x-text="snapshot.courier_counts.delivered"></strong></span>
              </div>
              <div class="lm-pulse-card__footer">Mijoz qabul qildi: <strong x-text="snapshot.courier_counts.customer_received"></strong></div>
            </div>
          </div>
        </div>

        <div class="lm-card">
          <div class="lm-card__head">
            <div>
              <div class="lm-card__eyebrow">Quick snapshot</div>
              <div class="lm-card__title">Qisqa holat</div>
              <div class="lm-card__sub">Bitta qarashda olish kerak bo‘lgan eng muhim sonlar.</div>
            </div>
          </div>
          <div class="lm-side-stack">
            <div class="lm-pill-grid">
              <div class="lm-pill-stat">
                <div class="lm-pill-stat__label">Kutilayotgan order</div>
                <div class="lm-pill-stat__value" x-text="snapshot.main_counts.new"></div>
                <div class="lm-pill-stat__sub">Admin va ops tez ko‘radigan navbat.</div>
              </div>
              <div class="lm-pill-stat">
                <div class="lm-pill-stat__label">Yo‘ldagi order</div>
                <div class="lm-pill-stat__value" x-text="snapshot.main_counts.onway"></div>
                <div class="lm-pill-stat__sub">Hozir harakatdagi buyurtmalar.</div>
              </div>
              <div class="lm-pill-stat">
                <div class="lm-pill-stat__label">Seller yangi</div>
                <div class="lm-pill-stat__value" x-text="snapshot.seller_counts.new"></div>
                <div class="lm-pill-stat__sub">Do‘kon hali qabul qilmaganlari.</div>
              </div>
              <div class="lm-pill-stat">
                <div class="lm-pill-stat__label">Courier pending</div>
                <div class="lm-pill-stat__value" x-text="snapshot.courier_counts.pending"></div>
                <div class="lm-pill-stat__sub">Kuryer navbatidagi topshiriqlar.</div>
              </div>
            </div>

            <div class="lm-list">
              <template x-for="user in snapshot.online_users.slice(0, 6)" :key="`overview-user-${user.id}`">
                <div class="lm-list-row">
                  <div class="lm-avatar">
                    <template x-if="user.avatar"><img :src="user.avatar" alt=""></template>
                    <template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template>
                  </div>
                  <div>
                    <div class="lm-row__title" x-text="user.name"></div>
                    <div class="lm-row__sub" x-text="user.last_seen"></div>
                  </div>
                  <div class="lm-row__right">
                    <span class="lm-pill lm-pill--success">Online</span>
                    <a class="lm-link" :href="`${userBaseUrl}/${user.id}`">Ochish</a>
                  </div>
                </div>
              </template>
              <div class="lm-empty" x-show="!snapshot.online_users.length">Hozircha online foydalanuvchi topilmadi.</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="lm-board" x-cloak :class="{ 'is-active': tab === 'orders' }">
      <div class="lm-section">
        <div>
          <div class="lm-card__eyebrow">Orders monitor</div>
          <div class="lm-section__title">Asosiy buyurtmalar</div>
          <div class="lm-section__sub">Eng yangi buyurtmalar va ularning hozirgi holati.</div>
        </div>
        <div class="lm-inline-grid">
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Yangi</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.new"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Qadoqlanmoqda</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.packing"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Yo‘lda</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.onway"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Qabul qilindi</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.done"></div></div>
        </div>
      </div>

      <div class="lm-card">
        <div class="lm-list">
          <template x-for="order in snapshot.recent_orders" :key="`order-${order.id}`">
            <div class="lm-list-row">
              <div class="lm-avatar">
                <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
                <template x-if="!order.avatar"><span x-text="order.customer.charAt(0)"></span></template>
              </div>
              <div>
                <div class="lm-row__title" x-text="`#${order.id} · ${order.customer}`"></div>
                <div class="lm-row__sub" x-text="order.updated_at"></div>
              </div>
              <div class="lm-row__right">
                <div class="lm-amount" x-text="`${order.amount} UZS`"></div>
                <span class="lm-pill lm-pill--primary" x-text="order.status"></span>
              </div>
            </div>
          </template>
          <div class="lm-empty" x-show="!snapshot.recent_orders.length">So‘nggi asosiy buyurtmalar topilmadi.</div>
        </div>
      </div>
    </section>

    <section class="lm-board" x-cloak :class="{ 'is-active': tab === 'seller' }">
      <div class="lm-section">
        <div>
          <div class="lm-card__eyebrow">Seller operations</div>
          <div class="lm-section__title">Seller orderlar</div>
          <div class="lm-section__sub">To‘lov kutayotgan, yangi va tayyorlangan seller orderlar oqimi.</div>
        </div>
        <div class="lm-inline-grid">
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">To‘lov</div><div class="lm-pill-stat__value" x-text="snapshot.seller_counts.payment_pending"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Yangi</div><div class="lm-pill-stat__value" x-text="snapshot.seller_counts.new"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Qabul qildi</div><div class="lm-pill-stat__value" x-text="snapshot.seller_counts.accepted"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Kuryerga berdi</div><div class="lm-pill-stat__value" x-text="snapshot.seller_counts.handover"></div></div>
        </div>
      </div>

      <div class="lm-card">
        <div class="lm-list">
          <template x-for="order in snapshot.recent_seller_orders" :key="`seller-${order.id}`">
            <div class="lm-list-row">
              <div class="lm-avatar">
                <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
                <template x-if="!order.avatar"><span x-text="order.seller.charAt(0)"></span></template>
              </div>
              <div>
                <div class="lm-row__title" x-text="`#${order.id} · ${order.seller}`"></div>
                <div class="lm-row__sub" x-text="order.customer"></div>
              </div>
              <div class="lm-row__right">
                <div class="lm-amount" x-text="`${order.amount} UZS`"></div>
                <span class="lm-pill lm-pill--warning" x-text="order.status"></span>
              </div>
            </div>
          </template>
          <div class="lm-empty" x-show="!snapshot.recent_seller_orders.length">Seller orderlari topilmadi.</div>
        </div>
      </div>
    </section>

    <section class="lm-board" x-cloak :class="{ 'is-active': tab === 'courier' }">
      <div class="lm-section">
        <div>
          <div class="lm-card__eyebrow">Courier control</div>
          <div class="lm-section__title">Courier orderlar</div>
          <div class="lm-section__sub">Tayinlangan, yo‘ldagi va yakunlangan yetkazishlar holati.</div>
        </div>
        <div class="lm-inline-grid">
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Pay process</div><div class="lm-pill-stat__value" x-text="snapshot.courier_counts.pay_process"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Pending</div><div class="lm-pill-stat__value" x-text="snapshot.courier_counts.pending"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Yo‘lda</div><div class="lm-pill-stat__value" x-text="snapshot.courier_counts.in_delivery"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Qabul qildi</div><div class="lm-pill-stat__value" x-text="snapshot.courier_counts.customer_received"></div></div>
        </div>
      </div>

      <div class="lm-card">
        <div class="lm-list">
          <template x-for="order in snapshot.recent_courier_orders" :key="`courier-${order.id}`">
            <div class="lm-list-row">
              <div class="lm-avatar">
                <template x-if="order.avatar"><img :src="order.avatar" alt=""></template>
                <template x-if="!order.avatar"><span x-text="order.courier.charAt(0)"></span></template>
              </div>
              <div>
                <div class="lm-row__title" x-text="`#${order.id} · ${order.courier}`"></div>
                <div class="lm-row__sub" x-text="order.customer"></div>
              </div>
              <div class="lm-row__right">
                <div class="lm-amount" x-text="`${order.amount} UZS`"></div>
                <span class="lm-pill lm-pill--success" x-text="order.status"></span>
              </div>
            </div>
          </template>
          <div class="lm-empty" x-show="!snapshot.recent_courier_orders.length">Courier oqimida yozuv topilmadi.</div>
        </div>
      </div>
    </section>

    <section class="lm-board" x-cloak :class="{ 'is-active': tab === 'users' }">
      <div class="lm-section">
        <div>
          <div class="lm-card__eyebrow">Audience window</div>
          <div class="lm-section__title">Online foydalanuvchilar</div>
          <div class="lm-section__sub">Oxirgi 5 daqiqada faol bo‘lgan userlar ro‘yxati.</div>
        </div>
        <div class="lm-inline-grid">
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Online</div><div class="lm-pill-stat__value" x-text="snapshot.online_users_count"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Jami order</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.all"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Faol queue</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.new + snapshot.main_counts.packing + snapshot.main_counts.onway"></div></div>
          <div class="lm-pill-stat"><div class="lm-pill-stat__label">Yakunlangan</div><div class="lm-pill-stat__value" x-text="snapshot.main_counts.done"></div></div>
        </div>
      </div>

      <div class="lm-card">
        <div class="lm-list">
          <template x-for="user in snapshot.online_users" :key="`user-${user.id}`">
            <div class="lm-list-row">
              <div class="lm-avatar">
                <template x-if="user.avatar"><img :src="user.avatar" alt=""></template>
                <template x-if="!user.avatar"><span x-text="user.name.charAt(0)"></span></template>
              </div>
              <div>
                <div class="lm-row__title" x-text="user.name"></div>
                <div class="lm-row__sub" x-text="user.last_seen"></div>
              </div>
              <div class="lm-row__right">
                <span class="lm-pill lm-pill--success">Online</span>
                <a class="lm-link" :href="`${userBaseUrl}/${user.id}`">Profil</a>
              </div>
            </div>
          </template>
          <div class="lm-empty" x-show="!snapshot.online_users.length">Hozircha online user yo‘q.</div>
        </div>
      </div>
    </section>
  </div>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.monitor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/dashboard-live.blade.php ENDPATH**/ ?>