<?php $__env->startSection('title', 'Foydalanuvchi'); ?>
<?php $__env->startSection('page-title', 'Foydalanuvchi profili'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $fullName = data_get($user, 'full_name');
  $spentSeconds = (int) (data_get($user, 'total_seconds_spent') ?? data_get($user, 'total_seconds_spend') ?? 0);
  $spentHours = $spentSeconds > 0 ? number_format($spentSeconds / 3600, 1) : '0';
  $mainAddressId = (int) data_get($user, 'mainAddressID', 0);
  $mainAddress = $addresses->firstWhere('id', $mainAddressId) ?? $addresses->first();
  $verifyToken = data_get($user, 'verifyCode');
  $isActivated = blank($verifyToken);
  $staffRoleLabel = match ((string) data_get($user, 'staff_role')) {
    'administrator' => 'Administrator',
    'moderator' => 'Moderator',
    default => '—',
  };
?>

<div class="space-y-6">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.users.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.users.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($fullName); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> ID #<?php echo e($user->id); ?> · <?php echo e(data_get($user,'phone_number') ?: 'Telefon yo‘q'); ?> · <?php echo e(optional($user->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q'); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
      <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
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

  <section class="a122-section overflow-hidden">
    <div class="px-6 py-6 border-b border-[var(--p-border)] bg-[var(--p-surface)]">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          <?php if($user->avatar): ?>
            <img src="<?php echo e(asset('storage/'.$user->avatar)); ?>" class="w-24 h-24 rounded-[28px] object-cover ring-4 ring-white/50 dark:ring-white/10 shadow-xl" alt="<?php echo e($fullName); ?>">
          <?php else: ?>
            <div class="w-24 h-24 rounded-[28px] flex items-center justify-center text-3xl font-black text-white shadow-xl bg-[linear-gradient(135deg,#10b981,#2563eb)]">
              <?php echo e(strtoupper(substr((string) data_get($user,'name','U'), 0, 1))); ?>

            </div>
          <?php endif; ?>

          <div class="space-y-3">
            <div>
              <h2 class="text-2xl font-black tracking-tight"><?php echo e($fullName); ?></h2>
              <div class="text-sm text-[var(--p-muted)] mt-1">
                <?php echo e(data_get($user,'email') ?: 'Email ko‘rsatilmagan'); ?>

              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="badge badge-info"><?php echo e(data_get($user,'position','reader')); ?></span>
              <?php if(data_get($user,'staff_role')): ?>
                <span class="badge badge-warning"><?php echo e(data_get($user,'staff_role') === 'administrator' ? 'Administrator' : 'Moderator'); ?></span>
              <?php endif; ?>
              <span class="badge <?php echo e($user->isVerified ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($user->isVerified ? 'Tasdiqlangan' : 'Tasdiqlanmagan'); ?></span>
              <span class="badge <?php echo e($user->is_premium ? 'badge-warning' : 'badge-muted'); ?>"><?php echo e($user->is_premium ? 'Premium' : 'Standard'); ?></span>
              <?php if($user->isBlocked()): ?>
                <span class="badge badge-danger">Bloklangan</span>
              <?php endif; ?>
              <?php if($user->isSupport): ?>
                <span class="badge badge-info">Support</span>
              <?php endif; ?>
              <?php if(data_get($user,'role_title')): ?>
                <span class="badge badge-muted"><?php echo e(trim((data_get($user,'role_emoji') ?: '').' '.data_get($user,'role_title'))); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 min-[520px]:grid-cols-4 lg:min-w-[420px]">
          <div class="kpi-soft">
            <div class="metric-label">Buyurtmalar</div>
            <div class="metric-value text-2xl"><?php echo e($stats['orders_count']); ?></div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">To‘langan</div>
            <div class="metric-value text-2xl"><?php echo e($stats['paid_orders_count']); ?></div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">Balans</div>
            <div class="metric-value text-xl"><?php echo e(number_format((float) data_get($user,'real_balance',0), 0, '.', ' ')); ?></div>
          </div>
          <div class="kpi-soft">
            <div class="metric-label">Cashback</div>
            <div class="metric-value text-xl"><?php echo e(number_format((float) data_get($user,'cashback',0), 0, '.', ' ')); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-px bg-[var(--p-border)] md:grid-cols-4 xl:grid-cols-8">
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Kartalar</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['cards_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Qurilmalar</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['devices_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Manzillar</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['addresses_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Followers</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['followers_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Following</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['following_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Gift sertifikat</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['gift_certificates_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Mystery Box</div>
        <div class="metric-value text-xl mt-2"><?php echo e($stats['mystery_subscriptions_count']); ?></div>
      </div>
      <div class="bg-[var(--p-surface)] p-4">
        <div class="metric-label">Faollik</div>
        <div class="metric-value text-xl mt-2"><?php echo e($spentHours); ?> soat</div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-4">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Profil ma’lumotlari</div>
          <div class="a122-section-head__meta">Aloqa, premium va asosiy akkaunt atributlari.</div>
        </div>
        <div class="a122-section-head__actions">
          <span class="badge badge-muted">Asosiy</span>
        </div>
      </div>
      <div class="a122-section-body">

      <div class="data-grid two">
        <div class="data-kv"><dt>Telefon</dt><dd><?php echo e(data_get($user,'phone_number') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Email</dt><dd><?php echo e(data_get($user,'email') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Username</dt><dd><?php echo e(data_get($user,'username') ? '@'.data_get($user,'username') : '—'); ?></dd></div>
        <div class="data-kv"><dt>Telegram ID</dt><dd><?php echo e(data_get($user,'telegram_id') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Til</dt><dd><?php echo e(data_get($user,'locale') ?: 'uz'); ?></dd></div>
        <div class="data-kv"><dt>Staff roli</dt><dd><?php echo e($staffRoleLabel); ?></dd></div>
        <div class="data-kv"><dt>Aktivlashtirish holati</dt><dd><?php echo e($isActivated ? 'Profilga kirgan / aktivlashtirilgan' : 'Tasdiqlash kutilmoqda'); ?></dd></div>
        <div class="data-kv"><dt>verifyToken</dt><dd><?php echo e($verifyToken ?: 'null'); ?></dd></div>
        <div class="data-kv"><dt>Premium</dt><dd><?php echo e($user->is_premium ? 'Faol' : 'Yo‘q'); ?></dd></div>
        <div class="data-kv"><dt>Premium muddati</dt><dd><?php echo e(optional(data_get($user,'premium_until'))->format('d.m.Y H:i') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>AI limiti</dt><dd><?php echo e(number_format((int) data_get($user,'ai_limit', 0))); ?></dd></div>
        <div class="data-kv"><dt>Oxirgi faollik</dt><dd><?php echo e(optional(data_get($user,'last_seen_at'))->format('d.m.Y H:i') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Ro‘yxatdan o‘tgan</dt><dd><?php echo e(optional(data_get($user,'created_at'))->format('d.m.Y H:i') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Spent time</dt><dd><?php echo e(number_format($spentSeconds)); ?> sec</dd></div>
        <div class="data-kv"><dt>Balans</dt><dd><?php echo e(number_format((float) data_get($user,'real_balance',0), 0, '.', ' ')); ?> UZS</dd></div>
        <div class="data-kv"><dt>Cashback</dt><dd><?php echo e(number_format((float) data_get($user,'cashback',0), 0, '.', ' ')); ?> UZS</dd></div>
        <div class="data-kv"><dt>Asosiy manzil</dt><dd><?php echo e(data_get($mainAddress, 'fullAddress') ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Blok holati</dt><dd><?php echo e($user->isBlocked() ? $user->activeBlockLabel() : 'Faol'); ?></dd></div>
      </div>

      <?php if(data_get($user,'bio')): ?>
        <div class="mt-5 p-4 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)]">
          <div class="metric-label mb-2">Bio</div>
          <div class="content-prose"><?php echo e(data_get($user,'bio')); ?></div>
        </div>
      <?php endif; ?>

      <div class="mt-5 flex justify-end">
        <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" onsubmit="return confirm('Foydalanuvchini o‘chirishga ishonchingiz komilmi?')">
          <?php echo csrf_field(); ?>
          <?php echo method_field('DELETE'); ?>
          <button class="btn-p danger"><i class="bi bi-trash3"></i> Foydalanuvchini o‘chirish</button>
        </form>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-8">
      <div class="a122-section-body">
      <div class="mb-5 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-5">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg font-black">Akkaunt boshqaruvi</h3>
            <p class="text-sm text-[var(--p-muted)] mt-1">Bloklangan foydalanuvchining barcha tokenlari o‘chiriladi va keyingi kirish rad etiladi.</p>
          </div>
          <?php if($user->isBlocked()): ?>
            <form method="POST" action="<?php echo e(route('admin.users.unblock', $user)); ?>">
              <?php echo csrf_field(); ?>
              <button class="btn-p success"><i class="bi bi-unlock"></i> Blokdan chiqarish</button>
            </form>
          <?php endif; ?>
        </div>

        <?php if($user->isBlocked()): ?>
          <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <div><strong>Muddat:</strong> <?php echo e($user->activeBlockLabel()); ?></div>
            <div class="mt-2"><strong>Sabab:</strong> <?php echo e($user->block_reason ?: '—'); ?></div>
          </div>
        <?php else: ?>
          <form method="POST" action="<?php echo e(route('admin.users.block', $user)); ?>" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <?php echo csrf_field(); ?>
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Blok muddati</label>
              <select name="block_period" class="input" required>
                <option value="10_days">10 kun</option>
                <option value="1_month">1 oy</option>
                <option value="1_year">1 yil</option>
                <option value="3_years">3 yil</option>
                <option value="forever">Abadiy</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="text-xs font-medium text-gray-500 mb-1 block">Blok sababi</label>
              <textarea name="block_reason" rows="4" class="input" required placeholder="Nega bloklanayotganini yozing"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
              <button class="btn-p danger"><i class="bi bi-ban"></i> Akkauntni bloklash</button>
            </div>
          </form>
        <?php endif; ?>
      </div>

      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">So‘nggi buyurtmalar</h3>
        <span class="badge badge-info"><?php echo e($stats['orders_count']); ?> ta</span>
      </div>

      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Status</th><th>To‘lov</th><th>Yetkazish</th><th>Summa</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php
                $statusLabel = match ((string) $order->status) {
                  'A', 'P' => 'Kutilmoqda',
                  'B' => 'Yo‘lda',
                  'C' => 'Yetkazildi',
                  'F' => 'Bekor',
                  default => 'Noma’lum',
                };
                $statusColor = match ((string) $order->status) {
                  'A', 'P' => 'warning',
                  'B' => 'info',
                  'C' => 'success',
                  'F' => 'danger',
                  default => 'muted',
                };
                $paymentLabel = (int) $order->paymentStatus === 2 ? 'To‘langan' : 'Kutilmoqda';
                $deliveryLabel = match ((int) $order->deliveryType) {
                  1 => 'Kuryer',
                  2 => 'Olib ketish',
                  default => 'Standart',
                };
              ?>
              <tr>
                <td class="font-semibold">#ORD-<?php echo e($order->id); ?></td>
                <td><span class="badge badge-<?php echo e($statusColor); ?>"><?php echo e($statusLabel); ?></span></td>
                <td><?php echo e($paymentLabel); ?></td>
                <td><?php echo e($deliveryLabel); ?></td>
                <td><?php echo e(number_format((float) $order->amount, 0, '.', ' ')); ?> UZS</td>
                <td><?php echo e(optional($order->created_at)->format('d.m.Y H:i')); ?></td>
                <td class="text-right"><a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="7" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Kartalar</h3>
        <span class="badge badge-info"><?php echo e($cards->count()); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-semibold tracking-[0.18em] uppercase text-[var(--p-muted)]">Card</div>
                <div class="mt-2 text-lg font-black">**** **** **** <?php echo e(substr((string) $card->card_number, -4) ?: '****'); ?></div>
              </div>
              <span class="badge <?php echo e($card->is_verified ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($card->is_verified ? 'Tasdiqlangan' : 'Kutilmoqda'); ?></span>
            </div>
            <div class="text-xs text-[var(--p-muted)] mt-3"><?php echo e(optional($card->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q'); ?></div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Karta topilmadi.</div>
        <?php endif; ?>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Qurilmalar</h3>
        <span class="badge badge-info"><?php echo e($devices->count()); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold"><?php echo e($device->device_name ?: 'Noma’lum qurilma'); ?></div>
                <div class="text-sm text-[var(--p-muted)] mt-1"><?php echo e(strtoupper($device->platform ?: 'platform yo‘q')); ?> · <?php echo e($device->device_id ?: 'ID yo‘q'); ?></div>
              </div>
              <span class="badge <?php echo e($device->fcm_token ? 'badge-success' : 'badge-muted'); ?>">Push</span>
            </div>
            <div class="text-xs text-[var(--p-muted)] mt-3"><?php echo e(optional($device->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q'); ?></div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Qurilmalar topilmadi.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Manzillar</h3>
        <span class="badge badge-info"><?php echo e($addresses->count()); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="text-sm leading-6"><?php echo e($address->fullAddress ?: 'Manzil kiritilmagan'); ?></div>
              <?php if((int) $address->id === $mainAddressId): ?>
                <span class="badge badge-success">Asosiy</span>
              <?php endif; ?>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-[var(--p-muted)]">
              <span><?php echo e(optional($address->created_at)->format('d.m.Y H:i') ?: 'Sana yo‘q'); ?></span>
              <?php if($address->lat && $address->lon): ?>
                <a href="https://maps.yandex.uz/?text=<?php echo e($address->lat); ?>+<?php echo e($address->lon); ?>&z=16" target="_blank" class="text-[var(--p-accent)] font-semibold">Xaritada ko‘rish</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Manzillar topilmadi.</div>
        <?php endif; ?>
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-6">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Followers</h3>
        <span class="badge badge-info"><?php echo e($stats['followers_count']); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $followers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $person): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('admin.users.show', $person->id)); ?>" class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-3 hover:border-[var(--p-accent)] transition">
            <?php if($person->avatar): ?>
              <img src="<?php echo e(asset('storage/'.$person->avatar)); ?>" class="w-11 h-11 rounded-2xl object-cover" alt="<?php echo e(trim(($person->name ?? '').' '.($person->lastname ?? ''))); ?>">
            <?php else: ?>
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-black text-white bg-[linear-gradient(135deg,#10b981,#2563eb)]">
                <?php echo e(strtoupper(substr((string) ($person->name ?? 'U'), 0, 1))); ?>

              </div>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="font-semibold truncate"><?php echo e(trim(($person->name ?? '').' '.($person->lastname ?? '')) ?: 'Noma’lum foydalanuvchi'); ?></div>
              <div class="text-sm text-[var(--p-muted)] truncate"><?php echo e($person->phone_number ?: 'Telefon yo‘q'); ?></div>
            </div>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Followers topilmadi.</div>
        <?php endif; ?>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-6">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Following</h3>
        <span class="badge badge-info"><?php echo e($stats['following_count']); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $following; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $person): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('admin.users.show', $person->id)); ?>" class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-3 hover:border-[var(--p-accent)] transition">
            <?php if($person->avatar): ?>
              <img src="<?php echo e(asset('storage/'.$person->avatar)); ?>" class="w-11 h-11 rounded-2xl object-cover" alt="<?php echo e(trim(($person->name ?? '').' '.($person->lastname ?? ''))); ?>">
            <?php else: ?>
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-black text-white bg-[linear-gradient(135deg,#f59e0b,#ef4444)]">
                <?php echo e(strtoupper(substr((string) ($person->name ?? 'U'), 0, 1))); ?>

              </div>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="font-semibold truncate"><?php echo e(trim(($person->name ?? '').' '.($person->lastname ?? '')) ?: 'Noma’lum foydalanuvchi'); ?></div>
              <div class="text-sm text-[var(--p-muted)] truncate"><?php echo e($person->phone_number ?: 'Telefon yo‘q'); ?></div>
            </div>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Following topilmadi.</div>
        <?php endif; ?>
      </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
    <section class="a122-section xl:col-span-7">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Gift sertifikatlar</h3>
        <span class="badge badge-info"><?php echo e($stats['gift_certificates_count']); ?> ta</span>
      </div>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Kod</th><th>Rol</th><th>Status</th><th>Miqdor</th><th>Muddat</th><th></th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $giftCertificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td class="font-semibold"><?php echo e($certificate->code); ?></td>
                <td><?php echo e((int) $certificate->buyer_user_id === (int) $user->id ? 'Sotib olgan' : 'Qabul qilgan'); ?></td>
                <td><span class="badge badge-<?php echo e($certificate->status_color); ?>"><?php echo e($certificate->status_label); ?></span></td>
                <td><?php echo e(number_format((int) $certificate->nominal_uzs, 0, '.', ' ')); ?> UZS</td>
                <td><?php echo e(optional($certificate->expires_at)->format('d.m.Y') ?: '—'); ?></td>
                <td class="text-right"><a href="<?php echo e(route('admin.gift-certificates.show', $certificate)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Gift sertifikatlar topilmadi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-5">
      <div class="a122-section-body">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black">Mystery Box obunalari</h3>
        <span class="badge badge-info"><?php echo e($mysterySubscriptions->count()); ?> ta</span>
      </div>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $mysterySubscriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('admin.mystery-box.show', $subscription)); ?>" class="block rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 hover:border-[var(--p-accent)] transition">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold"><?php echo e($subscription->plan?->name_uz ?: 'Mystery Box obuna'); ?></div>
                <div class="text-sm text-[var(--p-muted)] mt-1">
                  <?php echo e($subscription->books_per_month); ?> ta kitob / <?php echo e($subscription->total_months); ?> oy
                </div>
              </div>
              <span class="badge badge-<?php echo e($subscription->status_color); ?>"><?php echo e($subscription->status_label); ?></span>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
              <div class="rounded-xl bg-[var(--p-surface)] px-3 py-2">
                <div class="metric-label">Narx</div>
                <div class="font-semibold mt-1"><?php echo e(number_format((int) $subscription->price_uzs, 0, '.', ' ')); ?> UZS</div>
              </div>
              <div class="rounded-xl bg-[var(--p-surface)] px-3 py-2">
                <div class="metric-label">Progress</div>
                <div class="font-semibold mt-1"><?php echo e($subscription->delivered_months); ?>/<?php echo e($subscription->total_months); ?></div>
              </div>
            </div>

            <div class="mt-4 h-2 rounded-full bg-[var(--p-surface)] overflow-hidden">
              <div class="h-full rounded-full bg-[linear-gradient(90deg,#10b981,#2563eb)]" style="width: <?php echo e(min(100, max(0, $subscription->progress_pct))); ?>%"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-[var(--p-muted)]">
              <span>Keyingi yetkazish: <?php echo e(optional($subscription->next_delivery_at)->format('d.m.Y') ?: '—'); ?></span>
              <span><?php echo e($subscription->deliveries->count()); ?> ta delivery</span>
            </div>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Mystery Box obunalari topilmadi.</div>
        <?php endif; ?>
      </div>
      </div>
    </section>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/show.blade.php ENDPATH**/ ?>