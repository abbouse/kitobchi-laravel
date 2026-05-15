<?php $__env->startSection('title', 'Buyurtma #' . $order->id); ?>
<?php $__env->startSection('page-title', 'Buyurtma tafsiloti'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-column gap-4">
  <?php
    $currentOrderStatus = $order->status_code ?? $order->status;
    $currentPaymentStatus = $order->payment_status_code ?? $order->paymentStatus;
    $normalizedDeliveryType = (string) ($order->deliveryType ?? 'delivery');
    $isPostalDelivery = $normalizedDeliveryType === 'postal';

    $orderStatusLabel = match ($currentOrderStatus) {
      'pending', 'A' => 'Kutilmoqda',
      'packing', 'P' => 'Qadoqlanmoqda',
      'in_delivery', 'B' => "Yo'lda",
      'delivered', 'C' => 'Yetkazildi',
      'returned' => 'Pochta qaytargan',
      'cancelled', 'F' => 'Bekor qilingan',
      default => $currentOrderStatus ?: '—',
    };
    $orderStatusBadge = match ($currentOrderStatus) {
      'delivered', 'C' => 'badge badge-success',
      'returned', 'cancelled', 'F' => 'badge badge-danger',
      'packing', 'P' => 'badge badge-warning',
      default => 'badge badge-info',
    };

    $paymentLabel = match ($currentPaymentStatus) {
      'paid', 2 => 'Karta orqali to‘langan',
      'card_pending', 1 => 'Karta orqali, tasdiq kutilmoqda',
      'cash_pending', 0 => 'Naqd to‘lov',
      'cancelled', 3 => 'To‘lov bekor qilingan',
      default => 'Aniqlanmagan',
    };
    $paymentBadge = match ($currentPaymentStatus) {
      'paid', 2 => 'badge badge-success',
      'cancelled', 3 => 'badge badge-danger',
      default => 'badge badge-warning',
    };
    $paymentMethodLabel = match ($currentPaymentStatus) {
      'paid', 'card_pending', 2, 1 => 'Karta / Payme',
      'cash_pending', 0 => 'Naqd',
      default => 'Noma’lum to‘lov turi',
    };

    $deliveryTypeLabel = match ($normalizedDeliveryType) {
      'pickup' => "Do'kondan olib ketish",
      'postal' => 'Pochta orqali',
      default => 'Yetkazib berish',
    };

    $primaryAddress = collect($order->address ?? [])->first() ?? [];
    $fullAddress = $primaryAddress['fullAddress']
      ?? $primaryAddress['branch_address']
      ?? $order->recipient_address
      ?? 'Manzil kiritilmagan';
    $addressLat = $primaryAddress['lat'] ?? null;
    $addressLon = $primaryAddress['lon'] ?? null;
    $encodedAddress = rawurlencode($fullAddress);
    $googleMapsUrl = ($addressLat !== null && $addressLon !== null)
      ? ('https://www.google.com/maps?q=' . $addressLat . ',' . $addressLon)
      : ('https://www.google.com/maps/search/?api=1&query=' . $encodedAddress);
    $yandexMapsUrl = ($addressLat !== null && $addressLon !== null)
      ? ('https://yandex.uz/maps/?pt=' . $addressLon . ',' . $addressLat . '&z=16&l=map')
      : ('https://yandex.uz/maps/?text=' . $encodedAddress);
    $branchLabel = !empty($primaryAddress['location_id'])
      ? ('Filial #' . $primaryAddress['location_id'])
      : null;

    $isGiftToOther = (bool) ($order->is_gift_to_other ?? false);
    $withPackaging = (bool) ($order->with_packaging ?? false);
    $packagingPrice = (int) ($order->packaging_price ?? 0);
    $isInstore = (bool) ($order->is_instore ?? false);
    $cashbackReadyAt = $order->cashback_ready_at;
    $cashbackAwardedAt = $order->cashback_awarded_at;
    $cashbackNotifiedAt = $order->cashback_notified_at;
    $fulfillment = $order->fulfillment;
    $fulfillmentModeLabel = match ($fulfillment?->fulfillment_mode) {
      'direct_courier' => "Kuryer: seller → mijoz",
      'postal_only_via_hub' => 'Hub → pochta oqimi',
      'pickup_only' => "Olib ketish / ichki pickup",
      'hub_based' => 'Hub-based fulfillment',
      default => 'Legacy / hali biriktirilmagan',
    };
    $fulfillmentStatusLabel = match ($fulfillment?->status_code) {
      'awaiting_seller_prep' => 'Seller tayyorlamoqda',
      'ready_for_pickup' => 'Olib ketishga tayyor',
      'picked_from_seller' => 'Sellerdan olingan',
      'arrived_at_hub' => 'Hubga yetib kelgan',
      'qc_checked' => 'QC tekshirildi',
      'packed' => 'Qadoqlandi',
      'labeled' => 'Etiketka yopildi',
      'dispatched_to_post' => 'Pochtaga topshirilgan',
      'assigned_last_mile' => 'Last-mile biriktirilgan',
      'out_for_delivery' => 'Yetkazib berishga chiqqan',
      'delivered' => 'Fulfillment yakunlangan',
      'returned' => 'Qaytgan',
      'cancelled' => 'Bekor qilingan',
      default => 'Hali ochilmagan',
    };
    $lastModeSwitch = collect(data_get($fulfillment?->meta ?? [], 'mode_switch_log', []))->last();
    $lastHubReroute = collect(data_get($fulfillment?->meta ?? [], 'hub_reroute_log', []))->last();

    $postalReturnStatus = $order->postal_return_status ?? 'none';
    $postalReturnLabel = match ($postalReturnStatus) {
      'returned_to_sender' => 'Pochta qaytargan',
      'resend_pending_payment' => 'Qayta yuborish to‘lovi kutilmoqda',
      'resent' => 'Qayta yuborilgan',
      default => 'Pochta oqimi yo‘q',
    };
    $postalReturnBadge = match ($postalReturnStatus) {
      'resent' => 'badge badge-success',
      'returned_to_sender', 'resend_pending_payment' => 'badge badge-warning',
      default => 'badge badge-muted',
    };

    $cashbackFlowLabel = $isInstore
      ? "Do‘konda — darhol"
      : 'Oddiy buyurtma — 7 kundan keyin';

    $sellerStatusLabel = function ($status) {
      return match ($status) {
        'new', 1, '1' => 'Yangi',
        'accepted', 2, '2' => 'Qabul qilingan',
        'handed_to_courier', 3, '3' => 'Kuryerga topshirilgan',
        'cancelled', 4, '4' => 'Bekor qilingan',
        default => $status ?: '—',
      };
    };

    $courierStatusLabel = function ($status) {
      return match ($status) {
        'payment_pending', 'pay_process' => 'To‘lov kutilmoqda',
        'pending' => 'Kutilmoqda',
        'accepted' => 'Qabul qilingan',
        'in_delivery' => "Yo'lda",
        'delivered' => 'Yetkazilgan',
        'returned' => 'Qaytgan',
        'cancelled', 'rejected' => 'Bekor qilingan',
        default => $status ?: '—',
      };
    };
  ?>

  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Order detail','title' => '#ORD-'.e($order->id).'','subtitle' => ''.e($order->user?->full_name ?: 'Mehmon foydalanuvchi').' · '.e(optional($order->created_at)->format('d.m.Y H:i')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Order detail','title' => '#ORD-'.e($order->id).'','subtitle' => ''.e($order->user?->full_name ?: 'Mehmon foydalanuvchi').' · '.e(optional($order->created_at)->format('d.m.Y H:i')).'']); ?>
    <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn btn-outline-secondary rounded-pill px-4">
      <i class="bi bi-arrow-left me-2"></i>Ro‘yxatga qaytish
    </a>
    <form method="POST" action="<?php echo e(route('admin.orders.cancel', $order)); ?>" onsubmit="return confirm('Buyurtmani bekor qilasizmi?')">
      <?php echo csrf_field(); ?>
      <button class="btn btn-danger rounded-pill px-4"><i class="bi bi-x-circle me-2"></i>Bekor qilish</button>
    </form>
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

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="<?php echo e($orderStatusBadge); ?>"><?php echo e($orderStatusLabel); ?></span>
        <span class="<?php echo e($paymentBadge); ?>"><?php echo e($paymentLabel); ?></span>
        <span class="badge badge-info"><?php echo e($deliveryTypeLabel); ?></span>
        <?php if($isPostalDelivery): ?>
          <span class="<?php echo e($postalReturnBadge); ?>"><?php echo e($postalReturnLabel); ?></span>
        <?php endif; ?>
        <?php if($isGiftToOther): ?>
          <span class="badge badge-info">Sovg‘a buyurtma</span>
        <?php endif; ?>
        <?php if($withPackaging): ?>
          <span class="badge badge-warning">Qadoqlash xizmati olingan</span>
        <?php endif; ?>
        <?php if($isInstore): ?>
          <span class="badge badge-warning">Do‘kon ichida rasmiylashtirilgan</span>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Jami summa</div>
          <div class="metric-value text-xl"><?php echo e(number_format((float) $order->amount, 0, '.', ' ')); ?></div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Mahsulotlar</div>
          <div class="metric-value text-xl"><?php echo e(number_format($summary['items_count'])); ?></div>
          <div class="metric-meta">Buyurtma itemlari</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Buyurtma holati</div>
          <div class="metric-value text-xl"><?php echo e($orderStatusLabel); ?></div>
          <div class="metric-meta">Joriy bosqich</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">To‘lov</div>
          <div class="metric-value text-xl"><?php echo e($paymentMethodLabel); ?></div>
          <div class="metric-meta"><?php echo e($paymentLabel); ?></div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
        <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
          <div class="metric-label">Mijoz</div>
          <div class="font-semibold mt-1"><?php echo e($order->user?->full_name ?: 'Mehmon foydalanuvchi'); ?></div>
          <div class="text-sm text-[var(--p-hint)] mt-1"><?php echo e($order->user?->phone_number ?: 'Telefon yo‘q'); ?></div>
        </div>
        <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
          <div class="metric-label">Buyurtma vaqti</div>
          <div class="font-semibold mt-1"><?php echo e($order->created_at?->format('d.m.Y H:i') ?: '—'); ?></div>
          <div class="text-sm text-[var(--p-hint)] mt-1">ID: #ORD-<?php echo e($order->id); ?></div>
        </div>
        <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
          <div class="metric-label">Yakunlangan vaqt</div>
          <div class="font-semibold mt-1"><?php echo e($order->completed_at?->format('d.m.Y H:i') ?: 'Hali yakunlanmagan'); ?></div>
          <div class="text-sm text-[var(--p-hint)] mt-1"><?php echo e($cashbackFlowLabel); ?></div>
        </div>
        <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
          <div class="metric-label">Qayta yuborish holati</div>
          <div class="font-semibold mt-1"><?php echo e($postalReturnLabel); ?></div>
          <div class="text-sm text-[var(--p-hint)] mt-1">
            <?php if(!empty($order->resend_replacement_order_id)): ?>
              Yangi buyurtma: #ORD-<?php echo e($order->resend_replacement_order_id); ?>

            <?php elseif($isPostalDelivery): ?>
              Qayta yuborish hali ochilmagan
            <?php else: ?>
              Pochta oqimi yo‘q
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="mt-4 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
        <div class="flex flex-wrap items-center gap-2">
          <div class="text-sm font-semibold">Fulfillment routing</div>
          <button type="button" class="order-help-trigger inline-flex h-5 w-5 items-center justify-center rounded-full border border-[var(--p-border)] text-[11px] font-bold text-[var(--p-muted)]" data-help-target="fulfillment-routing-help">?</button>
          <div id="fulfillment-routing-help" class="order-help-popover hidden max-w-xs rounded-2xl border border-[var(--p-border)] bg-white p-3 text-xs leading-5 text-[var(--p-text)] shadow-xl">
            Order yaratilganda tizim qaysi mode bilan ishlashini shu yerda qotiradi: hub-based, direct courier yoki postal oqim. Hub, seller va courier app’lar keyingi bosqichlarda aynan shu routing qarorga qarab ishlaydi.
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-3 text-sm">
          <div>
            <div class="metric-label">Mode</div>
            <div class="font-semibold mt-1"><?php echo e($fulfillmentModeLabel); ?></div>
          </div>
          <div>
            <div class="metric-label">Fulfillment holati</div>
            <div class="font-semibold mt-1"><?php echo e($fulfillmentStatusLabel); ?></div>
          </div>
          <div>
            <div class="metric-label">Mas’ul hub</div>
            <div class="font-semibold mt-1"><?php echo e($fulfillment?->hub?->name ?: 'Hub yo‘q / direct'); ?></div>
          </div>
          <div>
            <div class="metric-label">COD</div>
            <div class="font-semibold mt-1">
              <?php if($fulfillment?->is_cod): ?>
                Ha · <?php echo e(number_format((int) ($fulfillment->cash_collect_amount ?? 0), 0, '.', ' ')); ?> UZS
              <?php else: ?>
                Yo‘q
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php if($fulfillment): ?>
          <div class="mt-4 flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.orders.print.label', $order)); ?>" target="_blank" class="btn-p ghost">
              <i class="bi bi-printer"></i> 48x80 label
            </a>
            <a href="<?php echo e(route('admin.orders.print.receipt', $order)); ?>" target="_blank" class="btn-p ghost">
              <i class="bi bi-receipt"></i> Receipt / packing slip
            </a>
          </div>
        <?php endif; ?>
        <?php if($lastModeSwitch || $lastHubReroute): ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 text-xs">
            <?php if($lastModeSwitch): ?>
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] p-3 text-[var(--p-hint)]">
                <div class="font-semibold text-[var(--p-text)]">Oxirgi mode switch</div>
                <div class="mt-1">
                  <?php echo e(data_get($lastModeSwitch, 'admin_name') ?: 'Admin'); ?> ·
                  <?php echo e(\Illuminate\Support\Carbon::parse(data_get($lastModeSwitch, 'at'))->format('d.m.Y H:i')); ?>

                </div>
                <div class="mt-1">Mode: <?php echo e(data_get($lastModeSwitch, 'target_mode')); ?></div>
                <?php if(data_get($lastModeSwitch, 'hub_name')): ?>
                  <div class="mt-1">Hub: <?php echo e(data_get($lastModeSwitch, 'hub_name')); ?></div>
                <?php endif; ?>
                <?php if(data_get($lastModeSwitch, 'note')): ?>
                  <div class="mt-1"><?php echo e(data_get($lastModeSwitch, 'note')); ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if($lastHubReroute): ?>
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] p-3 text-[var(--p-hint)]">
                <div class="font-semibold text-[var(--p-text)]">Oxirgi hub reroute</div>
                <div class="mt-1">
                  <?php echo e(data_get($lastHubReroute, 'admin_name') ?: 'Admin'); ?> ·
                  <?php echo e(\Illuminate\Support\Carbon::parse(data_get($lastHubReroute, 'at'))->format('d.m.Y H:i')); ?>

                </div>
                <div class="mt-1">Hub: <?php echo e(data_get($lastHubReroute, 'hub_name') ?: '—'); ?></div>
                <?php if(data_get($lastHubReroute, 'note')): ?>
                  <div class="mt-1"><?php echo e(data_get($lastHubReroute, 'note')); ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mt-4">
          <div class="rounded-3xl border border-[var(--p-border)] bg-white p-4">
            <div class="flex flex-wrap items-center gap-2">
              <div class="text-sm font-semibold">Fulfillment mode almashtirish</div>
              <button type="button" class="order-help-trigger inline-flex h-5 w-5 items-center justify-center rounded-full border border-[var(--p-border)] text-[11px] font-bold text-[var(--p-muted)]" data-help-target="mode-switch-help">?</button>
              <div id="mode-switch-help" class="order-help-popover hidden max-w-xs rounded-2xl border border-[var(--p-border)] bg-white p-3 text-xs leading-5 text-[var(--p-text)] shadow-xl">
                Bu action orderni hub-based oqimdan direct courier oqimiga yoki aksincha o‘tkazadi. Tizim kech bosqichlarga o‘tgan, kuryer biriktirilgan yoki qadoqlash boshlangan orderlarda bu amaliyotni bloklaydi.
              </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.orders.switch-mode', $order)); ?>" class="space-y-3 mt-3">
              <?php echo csrf_field(); ?>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="space-y-1">
                  <span class="metric-label">Yangi mode</span>
                  <select name="target_mode" class="w-full rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] px-3 py-2 text-sm js-fulfillment-mode-select">
                    <?php $__currentLoopData = $fulfillmentModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modeOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($modeOption['value']); ?>" <?php if(($fulfillment?->fulfillment_mode ?? old('target_mode')) === $modeOption['value']): echo 'selected'; endif; ?>>
                        <?php echo e($modeOption['label']); ?>

                      </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </select>
                </label>
                <label class="space-y-1 js-fulfillment-hub-wrap">
                  <span class="metric-label">Target hub</span>
                  <select name="hub_id" class="w-full rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] px-3 py-2 text-sm">
                    <option value="">Auto tanlash</option>
                    <?php $__currentLoopData = $activeHubs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($hub->id); ?>" <?php if(($fulfillment?->hub_id ?? old('hub_id')) === $hub->id): echo 'selected'; endif; ?>>
                        <?php echo e($hub->name); ?> <?php if($hub->code): ?>(<?php echo e($hub->code); ?>)<?php endif; ?> <?php if($hub->city_name): ?> · <?php echo e($hub->city_name); ?> <?php endif; ?>
                      </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </select>
                </label>
              </div>
              <label class="space-y-1 block">
                <span class="metric-label">Izoh</span>
                <textarea name="override_note" rows="2" class="w-full rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] px-3 py-2 text-sm" placeholder="Nega mode almashtirilayotgani haqida qisqa izoh"><?php echo e(old('override_note')); ?></textarea>
              </label>
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-xs text-[var(--p-hint)]">Hub kerak bo‘lgan mode’larda target hub bo‘sh qolsa tizim o‘zi mos hub tanlaydi.</div>
                <button type="submit" class="btn-p primary">Mode’ni yangilash</button>
              </div>
            </form>
          </div>

          <div class="rounded-3xl border border-[var(--p-border)] bg-white p-4">
            <div class="flex flex-wrap items-center gap-2">
              <div class="text-sm font-semibold">Mas’ul hubni reroute qilish</div>
              <button type="button" class="order-help-trigger inline-flex h-5 w-5 items-center justify-center rounded-full border border-[var(--p-border)] text-[11px] font-bold text-[var(--p-muted)]" data-help-target="hub-reroute-help">?</button>
              <div id="hub-reroute-help" class="order-help-popover hidden max-w-xs rounded-2xl border border-[var(--p-border)] bg-white p-3 text-xs leading-5 text-[var(--p-text)] shadow-xl">
                Reroute faqat hub orqali yuradigan orderlarda va sellerdan olib ketish tugamagan bosqichlarda ishlaydi. Shu bilan noto‘g‘ri hubga ketayotgan orderni xavfsiz boshqa markazga burish mumkin.
              </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.orders.reroute-hub', $order)); ?>" class="space-y-3 mt-3">
              <?php echo csrf_field(); ?>
              <label class="space-y-1 block">
                <span class="metric-label">Yangi mas’ul hub</span>
                <select name="hub_id" class="w-full rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] px-3 py-2 text-sm">
                  <?php $__currentLoopData = $activeHubs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($hub->id); ?>" <?php if(($fulfillment?->hub_id ?? old('hub_id')) === $hub->id): echo 'selected'; endif; ?>>
                      <?php echo e($hub->name); ?> <?php if($hub->code): ?>(<?php echo e($hub->code); ?>)<?php endif; ?> <?php if($hub->city_name): ?> · <?php echo e($hub->city_name); ?> <?php endif; ?>
                    </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </label>
              <label class="space-y-1 block">
                <span class="metric-label">Reroute izohi</span>
                <textarea name="reroute_note" rows="2" class="w-full rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] px-3 py-2 text-sm" placeholder="Masalan: hub yuklamasi yuqori, mijozga yaqin hub tanlandi"><?php echo e(old('reroute_note')); ?></textarea>
              </label>
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-xs text-[var(--p-hint)]">Direct courier orderlarda bu forma ishlamaydi, backend guard xatoni to‘xtatadi.</div>
                <button type="submit" class="btn-p ghost">Hub’ni yangilash</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Buyurtma tarkibi</div>
          <div class="a122-section-head__meta">Buyurtmadagi mahsulotlar, ularning soni va summasi bir joyda.</div>
        </div>
        <div class="a122-section-head__actions">
          <span class="badge badge-info"><?php echo e($summary['items_count']); ?> ta mahsulot</span>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th>Mahsulot</th><th>Turi</th><th>Soni</th><th>Narxi</th><th>Jami</th></tr></thead>
            <tbody>
              <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $qty = (int) ($it['count_item'] ?? $it['count'] ?? 1);
                  $price = (float) ($it['item_price'] ?? $it['price'] ?? 0);
                  $imageValue = trim((string) ($it['image'] ?? ''));
                  $imageUrl = $imageValue === '' ? null : (
                    str_starts_with($imageValue, 'http://')
                    || str_starts_with($imageValue, 'https://')
                    || str_starts_with($imageValue, 'data:')
                    || str_starts_with($imageValue, '/storage/')
                    || str_starts_with($imageValue, '/')
                      ? $imageValue
                      : asset('storage/' . ltrim($imageValue, '/'))
                  );
                ?>
                <tr>
                  <td>
                    <div class="flex items-start gap-3">
                      <div class="w-12 h-12 rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] shrink-0">
                        <?php if($imageUrl): ?>
                          <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($it['name'] ?? ($it['product']->name ?? 'Mahsulot')); ?>" class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                          <div class="w-full h-full flex items-center justify-center text-[var(--p-muted)]">
                            <i class="bi bi-box-seam"></i>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="min-w-0">
                        <?php if(($it['type'] ?? 'book') === 'stationery' && !empty($it['product'])): ?>
                          <a href="<?php echo e(route('admin.stationery.show', $it['product'])); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">
                            <?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?>

                          </a>
                        <?php elseif(($it['type'] ?? 'book') === 'gift'): ?>
                          <span class="font-semibold text-[var(--p-text)]">
                            <?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?>

                          </span>
                        <?php elseif(!empty($it['product'])): ?>
                          <a href="<?php echo e(route('admin.books.show', $it['product'])); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">
                            <?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?>

                          </a>
                        <?php else: ?>
                          <?php echo e($it['name'] ?? '—'); ?>

                        <?php endif; ?>
                        <?php if(!empty($it['seller'])): ?>
                          <div class="text-xs text-[var(--p-hint)] mt-1">
                            Do‘kon:
                            <a href="<?php echo e(route('admin.sellers.show', $it['seller'])); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e($it['seller']->shop_name); ?></a>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td><?php echo e($it['type_label'] ?? ($it['type'] ?? 'kitob')); ?></td>
                  <td><?php echo e($qty); ?></td>
                  <td><?php echo e(number_format($price, 0, '.', ' ')); ?> UZS</td>
                  <td><?php echo e(number_format($qty * $price, 0, '.', ' ')); ?> UZS</td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="xl:col-span-4 space-y-4">
      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Boshqaruv</div>
            <div class="a122-section-head__meta">Buyurtma holatini yangilash va pochta qaytimi amallari.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 mb-4">
            <div class="text-sm font-semibold">Joriy holat</div>
            <div class="flex flex-wrap gap-2 mt-3">
              <span class="<?php echo e($orderStatusBadge); ?>"><?php echo e($orderStatusLabel); ?></span>
              <span class="<?php echo e($paymentBadge); ?>"><?php echo e($paymentLabel); ?></span>
              <?php if($isPostalDelivery): ?>
                <span class="<?php echo e($postalReturnBadge); ?>"><?php echo e($postalReturnLabel); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <form method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <select name="status" class="p-form-control">
              <option value="A" <?php if(in_array($currentOrderStatus, ['pending', 'A'], true)): echo 'selected'; endif; ?>>Kutilmoqda</option>
              <option value="P" <?php if(in_array($currentOrderStatus, ['packing', 'P'], true)): echo 'selected'; endif; ?>>Qadoqlanmoqda</option>
              <option value="B" <?php if(in_array($currentOrderStatus, ['in_delivery', 'B'], true)): echo 'selected'; endif; ?>>Yo‘lda</option>
              <option value="C" <?php if(in_array($currentOrderStatus, ['delivered', 'C'], true)): echo 'selected'; endif; ?>>Yetkazildi</option>
              <option value="F" <?php if(in_array($currentOrderStatus, ['cancelled', 'returned', 'F'], true)): echo 'selected'; endif; ?>>Bekor qilingan</option>
            </select>
            <button class="btn-p primary w-full"><i class="bi bi-arrow-repeat"></i> Holatni yangilash</button>
          </form>

          <?php if($isPostalDelivery): ?>
            <form method="POST" action="<?php echo e(route('admin.orders.postal-return', $order)); ?>" class="space-y-3 pt-4 mt-4 border-t border-[var(--p-border)]">
              <?php echo csrf_field(); ?>
              <?php echo method_field('PATCH'); ?>
              <div class="text-sm font-semibold">Pochta qaytimi va qayta yuborish</div>
              <div class="text-xs text-[var(--p-hint)]">
                Bu amal faqat pochta orqali yuborilgan va mijoz olmagan buyurtmalar uchun ishlatiladi.
              </div>
              <input type="number" min="0" name="postal_return_fee" value="<?php echo e(old('postal_return_fee', (int) ($order->postal_return_fee ?? 0))); ?>" class="p-form-control" placeholder="Qayta yuborish jarimasi (UZS)">
              <textarea name="postal_return_note" rows="3" class="p-form-control" placeholder="Qisqa izoh"><?php echo e(old('postal_return_note', $order->postal_return_note)); ?></textarea>
              <button class="btn-p w-full">
                <i class="bi bi-arrow-counterclockwise"></i>
                Pochta qaytgan deb belgilash
              </button>
              <div class="text-xs text-[var(--p-hint)]">
                Holat: <?php echo e($postalReturnLabel); ?>

                <?php if(!empty($order->resend_replacement_order_id)): ?>
                  · yangi buyurtma #<?php echo e($order->resend_replacement_order_id); ?>

                <?php endif; ?>
              </div>
            </form>
          <?php else: ?>
            <div class="pt-4 mt-4 border-t border-[var(--p-border)] text-sm text-[var(--p-hint)]">
              Bu buyurtma pochta orqali yuborilmagan. Shuning uchun “qaytib keldi” amali bu yerda chiqmaydi.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Mijoz va manzil</div>
            <div class="a122-section-head__meta">Aloqa ma’lumotlari, qabul qiluvchi va yetkazish manzili.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <div class="space-y-4 text-sm">
            <div>
              <span class="metric-label">Mijoz</span>
              <div class="font-semibold mt-2 flex items-center gap-3">
                <?php if($order->user): ?>
                  <?php echo $__env->make('a122.partials.avatar', [
                    'name' => $order->user->full_name ?: 'Foydalanuvchi',
                    'image' => $order->user->avatar,
                    'class' => 'w-10 h-10 rounded-2xl text-xs',
                  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                  <a href="<?php echo e(route('admin.users.show', $order->user)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e($order->user->full_name ?: 'Foydalanuvchi'); ?></a>
                <?php else: ?>
                  <span>Mehmon</span>
                <?php endif; ?>
              </div>
            </div>
            <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1"><?php echo e($order->user?->phone_number ?: '—'); ?></div></div>
            <div><span class="metric-label">Yetkazish turi</span><div class="font-semibold mt-1"><?php echo e($deliveryTypeLabel); ?></div></div>
            <div><span class="metric-label">Asosiy manzil</span><div class="font-semibold mt-1"><?php echo e($fullAddress); ?></div></div>
            <div>
              <span class="metric-label">Xaritada ochish</span>
              <div class="mt-2 flex flex-wrap gap-2">
                <a href="<?php echo e($yandexMapsUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">
                  <i class="bi bi-geo-alt"></i> Yandex Maps
                </a>
                <a href="<?php echo e($googleMapsUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-ghost rounded-xl px-3 py-2 text-xs font-semibold">
                  <i class="bi bi-map"></i> Google Maps
                </a>
              </div>
            </div>
            <?php if($branchLabel): ?>
              <div><span class="metric-label">Filial</span><div class="font-semibold mt-1"><?php echo e($branchLabel); ?><?php echo e(!empty($primaryAddress['branch_is_main']) ? ' · asosiy filial' : ''); ?></div></div>
            <?php endif; ?>
            <?php if(!empty($primaryAddress['lat']) && !empty($primaryAddress['lon'])): ?>
              <div><span class="metric-label">Koordinata</span><div class="font-semibold mt-1"><?php echo e($primaryAddress['lat']); ?>, <?php echo e($primaryAddress['lon']); ?></div></div>
            <?php endif; ?>
            <div><span class="metric-label">Qabul manzili</span><div class="font-semibold mt-1"><?php echo e($order->recipient_address ?: '—'); ?></div></div>
            <div><span class="metric-label">Mijoz istagi</span><div class="font-semibold mt-1"><?php echo e($order->buyerWish ?: '—'); ?></div></div>
            <div><span class="metric-label">To‘lov turi</span><div class="font-semibold mt-1"><?php echo e($paymentMethodLabel); ?></div></div>
            <div><span class="metric-label">To‘lov holati</span><div class="font-semibold mt-1"><?php echo e($paymentLabel); ?></div></div>
            <div><span class="metric-label">Qadoqlash xizmati</span><div class="font-semibold mt-1"><?php echo e($withPackaging ? 'Ha' : 'Yo‘q'); ?></div></div>
            <?php if($withPackaging): ?>
              <div><span class="metric-label">Qadoqlash narxi</span><div class="font-semibold mt-1"><?php echo e(number_format($packagingPrice, 0, '.', ' ')); ?> UZS</div></div>
            <?php endif; ?>

            <?php if($isGiftToOther): ?>
              <div class="pt-3 border-t border-[var(--p-border)] space-y-3">
                <div class="text-sm font-semibold">Sovg‘a qabul qiluvchi</div>
                <div><span class="metric-label">Ism</span><div class="font-semibold mt-1"><?php echo e($order->recipient_name ?: '—'); ?></div></div>
                <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1"><?php echo e($order->recipient_phone ?: '—'); ?></div></div>
                <div><span class="metric-label">Hudud</span><div class="font-semibold mt-1"><?php echo e($order->recipient_region ?: '—'); ?></div></div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Hisob-kitob</div>
            <div class="a122-section-head__meta">Buyurtma summalari, cashback va sellerga tushadigan hisob-kitob.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <?php
            $settlementBadge = match($settlementOverview['status']) {
              'settled' => 'badge badge-success',
              'reversed' => 'badge badge-danger',
              default => 'badge badge-info',
            };
          ?>

          <dl class="space-y-3">
            <div class="flex justify-between gap-3"><dt class="metric-label">Mahsulotlar summasi</dt><dd class="font-semibold"><?php echo e(number_format($summary['subtotal'], 0, '.', ' ')); ?> UZS</dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Yetkazish narxi</dt><dd class="font-semibold"><?php echo e(number_format($summary['delivery'], 0, '.', ' ')); ?> UZS</dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Qadoqlash</dt><dd class="font-semibold"><?php echo e(number_format($packagingPrice, 0, '.', ' ')); ?> UZS</dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Chegirma</dt><dd class="font-semibold"><?php echo e(number_format($summary['discount'], 0, '.', ' ')); ?> UZS</dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Cashback</dt><dd class="font-semibold"><?php echo e(number_format($summary['cashback'], 0, '.', ' ')); ?> UZS</dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Cashback tartibi</dt><dd class="font-semibold"><?php echo e($cashbackFlowLabel); ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Cashback tayyor vaqti</dt><dd class="font-semibold"><?php echo e($cashbackReadyAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Cashback tushgan vaqt</dt><dd class="font-semibold"><?php echo e($cashbackAwardedAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Cashback xabari</dt><dd class="font-semibold"><?php echo e($cashbackNotifiedAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Promokod</dt><dd class="font-semibold"><?php echo e($order->promocode ?: '—'); ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="metric-label">Sertifikat</dt><dd class="font-semibold"><?php echo e($order->gift_certificate_id ? '#'.$order->gift_certificate_id : '—'); ?></dd></div>
            <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-3"><dt class="font-bold">Yakuniy summa</dt><dd class="font-black"><?php echo e(number_format((float) $order->amount, 0, '.', ' ')); ?> UZS</dd></div>
          </dl>

          <div class="mt-4 rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="font-semibold">Seller hisob-kitobi</div>
                <div class="text-xs text-[var(--p-hint)]">Komissiya va sellerga tushgan sof summa.</div>
              </div>
              <span class="<?php echo e($settlementBadge); ?>"><?php echo e($settlementOverview['label']); ?></span>
            </div>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between gap-3"><dt class="metric-label">Brutto tushum</dt><dd class="font-semibold"><?php echo e(number_format($settlementOverview['gross'], 0, '.', ' ')); ?> UZS</dd></div>
              <div class="flex justify-between gap-3"><dt class="metric-label">Komissiya</dt><dd class="font-semibold"><?php echo e(number_format($settlementOverview['commission'], 0, '.', ' ')); ?> UZS</dd></div>
              <div class="flex justify-between gap-3"><dt class="metric-label">Sof tushum</dt><dd class="font-semibold"><?php echo e(number_format($settlementOverview['net'], 0, '.', ' ')); ?> UZS</dd></div>
              <div class="flex justify-between gap-3"><dt class="metric-label">Qaytarilgan summa</dt><dd class="font-semibold"><?php echo e(number_format($settlementOverview['reversed_net'], 0, '.', ' ')); ?> UZS</dd></div>
              <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-2"><dt class="font-semibold">Hozirgi sof summa</dt><dd class="font-black"><?php echo e(number_format($settlementOverview['current_net'], 0, '.', ' ')); ?> UZS</dd></div>
            </dl>
          </div>
        </div>
      </div>
    </section>

    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Do‘konlar va bajarilish</div>
          <div class="a122-section-head__meta">Sellerlar, ularga tegishli ichki buyurtmalar va kuryer oqimi bir joyda.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <?php if($sellerOrders->isEmpty()): ?>
          <div class="text-sm text-gray-500">Seller buyurtmalari hali yaratilmagan.</div>
        <?php else: ?>
          <div class="space-y-3">
            <?php $__currentLoopData = $sellerOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sellerOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $sellerSettlement = $sellerSettlements[$sellerOrder->id] ?? null;
                $sellerSettlementBadge = match($sellerSettlement['status'] ?? 'pending') {
                  'settled' => 'badge badge-success',
                  'reversed' => 'badge badge-danger',
                  default => 'badge badge-info',
                };
              ?>
              <div class="rounded-3xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <a href="<?php echo e(route('admin.seller-orders.show', $sellerOrder)); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">#SELL-<?php echo e($sellerOrder->id); ?></a>
                      <span class="badge badge-info"><?php echo e($sellerStatusLabel($sellerOrder->status_code ?? $sellerOrder->status)); ?></span>
                      <?php if($sellerSettlement): ?>
                        <span class="<?php echo e($sellerSettlementBadge); ?>"><?php echo e($sellerSettlement['label']); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="mt-2 text-sm text-[var(--p-muted)]">
                      <?php if($sellerOrder->seller): ?>
                        <a href="<?php echo e(route('admin.sellers.show', $sellerOrder->seller)); ?>" class="font-semibold text-[var(--p-text)] hover:underline"><?php echo e($sellerOrder->seller->shop_name); ?></a>
                      <?php else: ?>
                        Sotuvchi ko‘rsatilmagan
                      <?php endif; ?>
                      · <?php echo e(number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ')); ?> UZS
                      · <?php echo e($sellerOrder->delivery_type ?: 'yetkazib berish'); ?>

                    </div>
                  </div>
                  <div class="text-sm text-[var(--p-hint)]">
                    <?php echo e($sellerOrder->created_at?->format('d.m.Y H:i') ?: '—'); ?>

                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
                  <div>
                    <div class="metric-label">Kuryer</div>
                    <div class="font-semibold mt-1">
                      <?php if($sellerOrder->courier): ?>
                        <a href="<?php echo e(route('admin.couriers.show', $sellerOrder->courier)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e(trim(($sellerOrder->courier->first_name ?? '') . ' ' . ($sellerOrder->courier->last_name ?? '')) ?: 'Kuryer'); ?></a>
                      <?php else: ?>
                        Hali biriktirilmagan
                      <?php endif; ?>
                    </div>
                  </div>
                  <div>
                    <div class="metric-label">Seller holati</div>
                    <div class="font-semibold mt-1"><?php echo e($sellerStatusLabel($sellerOrder->status_code ?? $sellerOrder->status)); ?></div>
                  </div>
                  <div>
                    <div class="metric-label">Sof tushum</div>
                    <div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['current_net'] ?? 0), 0, '.', ' ')); ?> UZS</div>
                  </div>
                  <div>
                    <div class="metric-label">Komissiya</div>
                    <div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['commission'] ?? 0), 0, '.', ' ')); ?> UZS</div>
                  </div>
                </div>

                <?php if(!empty($sellerSettlement)): ?>
                  <div class="mt-4 rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] p-3">
                    <div class="text-sm font-semibold mb-2">Seller hisob-kitobi tafsiloti</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                      <div><span class="metric-label">Brutto</span><div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['gross'] ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                      <div><span class="metric-label">Komissiya</span><div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['commission'] ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                      <div><span class="metric-label">Sof</span><div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['net'] ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                      <div><span class="metric-label">Qoldiq sof</span><div class="font-semibold mt-1"><?php echo e(number_format((int) ($sellerSettlement['current_net'] ?? 0), 0, '.', ' ')); ?> UZS</div></div>
                    </div>
                    <?php if(!empty($sellerSettlement['latest_sale_at']) || !empty($sellerSettlement['latest_reversal_at'])): ?>
                      <div class="mt-2 text-xs text-[var(--p-hint)]">
                        <?php if(!empty($sellerSettlement['latest_sale_at'])): ?>
                          Tushgan vaqt: <?php echo e($sellerSettlement['latest_sale_at']->format('d.m.Y H:i')); ?>

                        <?php endif; ?>
                        <?php if(!empty($sellerSettlement['latest_reversal_at'])): ?>
                          <?php if(!empty($sellerSettlement['latest_sale_at'])): ?> · <?php endif; ?>
                          Qaytarilgan vaqt: <?php echo e($sellerSettlement['latest_reversal_at']->format('d.m.Y H:i')); ?>

                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>

        <div class="mt-4 pt-4 border-t border-[var(--p-border)]">
          <div class="text-sm font-semibold mb-3">Kuryer oqimi</div>
          <?php if($courierOrder || $assignedCourier): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 text-sm">
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
                <div class="metric-label">Kuryer buyurtmasi</div>
                <div class="font-semibold mt-1">
                  <?php if($courierOrder): ?>
                    <a href="<?php echo e(route('admin.courier-orders.show', $courierOrder)); ?>" class="text-[var(--p-accent)] hover:underline">#COR-<?php echo e($courierOrder->id); ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </div>
              </div>
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
                <div class="metric-label">Kuryer</div>
                <div class="font-semibold mt-1">
                  <?php if($assignedCourier): ?>
                    <a href="<?php echo e(route('admin.couriers.show', $assignedCourier)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e(trim(($assignedCourier->first_name ?? '') . ' ' . ($assignedCourier->last_name ?? '')) ?: 'Kuryer'); ?></a>
                  <?php else: ?>
                    Hali biriktirilmagan
                  <?php endif; ?>
                </div>
                <?php if($assignedCourier?->phone_number): ?>
                  <div class="text-xs text-[var(--p-hint)] mt-1"><?php echo e($assignedCourier->phone_number); ?></div>
                <?php endif; ?>
              </div>
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
                <div class="metric-label">Kuryer holati</div>
                <div class="font-semibold mt-1"><?php echo e($courierStatusLabel($courierOrder->status_code ?? $courierOrder->status ?? null)); ?></div>
              </div>
              <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
                <div class="metric-label">Kuryer narxi</div>
                <div class="font-semibold mt-1"><?php echo e(number_format((float) ($courierOrder->courierPrice ?? 0), 0, '.', ' ')); ?> UZS</div>
              </div>
            </div>
          <?php else: ?>
            <div class="text-sm text-gray-500">Kuryer hali biriktirilmagan.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
<script>
document.addEventListener('click', function (event) {
  const trigger = event.target.closest('.order-help-trigger');
  const popovers = document.querySelectorAll('.order-help-popover');

  if (!trigger) {
    popovers.forEach((popover) => popover.classList.add('hidden'));
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  const targetId = trigger.getAttribute('data-help-target');
  const popover = document.getElementById(targetId);
  if (!popover) return;

  const shouldOpen = popover.classList.contains('hidden');
  popovers.forEach((item) => item.classList.add('hidden'));
  if (!shouldOpen) return;

  const rect = trigger.getBoundingClientRect();
  popover.classList.remove('hidden');
  popover.style.position = 'fixed';
  popover.style.left = Math.min(window.innerWidth - popover.offsetWidth - 16, rect.left - 8) + 'px';
  popover.style.top = (rect.bottom + 8) + 'px';
  popover.style.zIndex = '60';
});

function syncFulfillmentHubVisibility() {
  const modeSelect = document.querySelector('.js-fulfillment-mode-select');
  const hubWrap = document.querySelector('.js-fulfillment-hub-wrap');
  if (!modeSelect || !hubWrap) return;

  const requiresHub = ['hub_based', 'postal_only_via_hub'].includes(modeSelect.value);
  hubWrap.style.display = requiresHub ? '' : 'none';
}

document.addEventListener('change', function (event) {
  if (event.target.matches('.js-fulfillment-mode-select')) {
    syncFulfillmentHubVisibility();
  }
});

syncFulfillmentHubVisibility();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/orders/show.blade.php ENDPATH**/ ?>