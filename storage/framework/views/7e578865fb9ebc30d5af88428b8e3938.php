<?php $__env->startSection('title', 'Buyurtma #' . $order->id); ?>
<?php $__env->startSection('page-title', 'Buyurtma tafsiloti'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php
    $orderStatusLabel = match($order->status) {
      'A' => 'Kutilmoqda',
      'P' => 'Qadoqlanmoqda',
      'B' => "Yo'lda",
      'C' => 'Yetkazildi',
      'F' => 'Bekor qilingan',
      default => $order->status ?: '—',
    };
    $paymentLabel = match ((int) $order->paymentStatus) {
      2 => 'Karta orqali to‘langan',
      1 => 'Karta orqali, tasdiq kutilmoqda',
      0 => 'Naqd to‘lov',
      default => 'Aniqlanmagan',
    };
    $paymentMethodLabel = match ((int) $order->paymentStatus) {
      2, 1 => 'Karta / Payme',
      0 => 'Naqd',
      default => 'Boshqa',
    };
    $deliveryTypeLabel = match ((string) ($order->deliveryType ?? '')) {
      'pickup' => "Do'kondan olib ketish",
      '' => 'Yetkazib berish',
      default => (string) $order->deliveryType,
    };
    $primaryAddress = collect($order->address ?? [])->first() ?? [];
    $fullAddress = $primaryAddress['fullAddress']
      ?? $primaryAddress['branch_address']
      ?? $order->recipient_address
      ?? 'Manzil kiritilmagan';
    $branchLabel = !empty($primaryAddress['location_id'])
      ? ('Filial #'.$primaryAddress['location_id'])
      : null;
    $isGiftToOther = (bool) ($order->is_gift_to_other ?? false);
    $packagingPrice = (int) ($order->packaging_price ?? 0);
    $isInstore = (bool) ($order->is_instore ?? false);
    $cashbackReadyAt = $order->cashback_ready_at;
    $cashbackAwardedAt = $order->cashback_awarded_at;
    $cashbackNotifiedAt = $order->cashback_notified_at;
    $cashbackFlowLabel = $isInstore
      ? "In-store — darhol"
      : 'Oddiy buyurtma — 7 kundan keyin';
  ?>
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.orders.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.orders.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> #ORD-<?php echo e($order->id); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($order->user?->full_name ?: 'Mehmon foydalanuvchi'); ?> · <?php echo e(optional($order->created_at)->format('d.m.Y H:i')); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
      <form method="POST" action="<?php echo e(route('admin.orders.cancel', $order)); ?>" onsubmit="return confirm('Buyurtmani bekor qilasizmi?')">
        <?php echo csrf_field(); ?>
        <button class="btn-p danger"><i class="bi bi-x-circle"></i> Bekor qilish</button>
      </form>
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

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Jami summa</div>
          <div class="metric-value text-xl"><?php echo e(number_format((float)$order->amount, 0, '.', ' ')); ?></div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Mahsulotlar</div>
          <div class="metric-value text-xl"><?php echo e(number_format($summary['items_count'])); ?></div>
          <div class="metric-meta">Buyurtma itemlari</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Status</div>
          <div class="metric-value text-xl"><?php echo e($orderStatusLabel); ?></div>
          <div class="metric-meta">Operatsion bosqich</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">To‘lov</div>
          <div class="metric-value text-xl"><?php echo e($paymentMethodLabel); ?></div>
          <div class="metric-meta"><?php echo e($paymentLabel); ?></div>
        </div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Buyurtma tarkibi</div>
          <div class="a122-section-head__meta">Buyurtmadagi barcha mahsulotlar, soni va narx bo‘yicha tafsilotlar.</div>
        </div>
        <div class="a122-section-head__actions">
          <span class="badge badge-info"><?php echo e($summary['items_count']); ?> ta mahsulot</span>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Mahsulot</th><th>Tip</th><th>Soni</th><th>Narx</th><th>Jami</th></tr></thead>
          <tbody>
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $qty = (int)($it['count_item'] ?? $it['count'] ?? 1);
                $price = (float)($it['item_price'] ?? $it['price'] ?? 0);
              ?>
              <tr>
                <td>
                  <?php if(($it['type'] ?? 'book') === 'stationery' && !empty($it['product'])): ?>
                    <a href="<?php echo e(route('admin.stationery.show', $it['product'])); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">
                      <?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?>

                    </a>
                  <?php elseif(!empty($it['product'])): ?>
                    <a href="<?php echo e(route('admin.books.show', $it['product'])); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">
                      <?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?>

                    </a>
                  <?php else: ?>
                    <?php echo e($it['name'] ?? '—'); ?>

                  <?php endif; ?>
                  <?php if(!empty($it['product']?->seller)): ?>
                    <div class="text-xs text-[var(--p-hint)] mt-1">
                      Do‘kon:
                      <a href="<?php echo e(route('admin.sellers.show', $it['product']->seller)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e($it['product']->seller->shop_name); ?></a>
                    </div>
                  <?php endif; ?>
                </td>
                <td><?php echo e($it['type'] ?? 'book'); ?></td>
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
            <div class="a122-section-head__title">Holat boshqaruvi</div>
            <div class="a122-section-head__meta">Buyurtma statusini shu yerdan yangilash mumkin.</div>
          </div>
        </div>
        <div class="a122-section-body">
        <form method="POST" action="<?php echo e(route('admin.orders.status', $order)); ?>" class="space-y-3">
          <?php echo csrf_field(); ?>
          <?php echo method_field('PATCH'); ?>
          <select name="status" class="p-form-control">
            <option value="A" <?php if($order->status==='A'): echo 'selected'; endif; ?>>Kutilmoqda</option>
            <option value="P" <?php if($order->status==='P'): echo 'selected'; endif; ?>>Qadoqlanmoqda</option>
            <option value="B" <?php if($order->status==='B'): echo 'selected'; endif; ?>>Yo'lda</option>
            <option value="C" <?php if($order->status==='C'): echo 'selected'; endif; ?>>Yetkazildi</option>
            <option value="F" <?php if($order->status==='F'): echo 'selected'; endif; ?>>Bekor</option>
          </select>
          <button class="btn-p primary w-full"><i class="bi bi-arrow-repeat"></i> Statusni yangilash</button>
        </form>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Moliyaviy xulosa</div>
            <div class="a122-section-head__meta">Subtotal, delivery, chegirma va yakuniy summa.</div>
          </div>
        </div>
      <div class="a122-section-body">
        <dl class="space-y-3">
          <div class="flex justify-between gap-3"><dt class="metric-label">Subtotal</dt><dd class="font-semibold"><?php echo e(number_format($summary['subtotal'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Yetkazish</dt><dd class="font-semibold"><?php echo e(number_format($summary['delivery'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Qadoqlash</dt><dd class="font-semibold"><?php echo e(number_format($packagingPrice, 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Chegirma</dt><dd class="font-semibold"><?php echo e(number_format($summary['discount'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback</dt><dd class="font-semibold"><?php echo e(number_format($summary['cashback'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback oqimi</dt><dd class="font-semibold"><?php echo e($cashbackFlowLabel); ?></dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback tayyor vaqti</dt><dd class="font-semibold"><?php echo e($cashbackReadyAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback tushgan vaqt</dt><dd class="font-semibold"><?php echo e($cashbackAwardedAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback push</dt><dd class="font-semibold"><?php echo e($cashbackNotifiedAt?->format('d.m.Y H:i') ?: '—'); ?></dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Promokod</dt><dd class="font-semibold"><?php echo e($order->promocode ?: '—'); ?></dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Gift sertifikat</dt><dd class="font-semibold"><?php echo e($order->gift_certificate_id ? '#'.$order->gift_certificate_id : '—'); ?></dd></div>
          <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-3"><dt class="font-bold">Jami</dt><dd class="font-black"><?php echo e(number_format((float)$order->amount, 0, '.', ' ')); ?> UZS</dd></div>
        </dl>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Yetkazish va manzil</div>
            <div class="a122-section-head__meta">Buyurtmaning fulfillment turi, filial va qabul manzili.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <div class="space-y-3 text-sm">
            <div><span class="metric-label">Fulfillment turi</span><div class="font-semibold mt-1"><?php echo e($deliveryTypeLabel); ?></div></div>
            <div><span class="metric-label">Manzil</span><div class="font-semibold mt-1"><?php echo e($fullAddress); ?></div></div>
            <?php if($branchLabel): ?>
              <div><span class="metric-label">Filial</span><div class="font-semibold mt-1"><?php echo e($branchLabel); ?><?php echo e(!empty($primaryAddress['branch_is_main']) ? ' · asosiy filial' : ''); ?></div></div>
            <?php endif; ?>
            <?php if(!empty($primaryAddress['lat']) && !empty($primaryAddress['lon'])): ?>
              <div><span class="metric-label">Koordinata</span><div class="font-semibold mt-1"><?php echo e($primaryAddress['lat']); ?>, <?php echo e($primaryAddress['lon']); ?></div></div>
            <?php endif; ?>
            <div><span class="metric-label">Recipient address</span><div class="font-semibold mt-1"><?php echo e($order->recipient_address ?: '—'); ?></div></div>
          </div>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Do‘konlar va seller oqimi</div>
            <div class="a122-section-head__meta">Buyurtmada ishtirok etgan sellerlar va ularning seller-order yozuvlari.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <?php if($sellerOrders->isEmpty()): ?>
            <div class="text-sm text-gray-500">Seller orderlar hali yaratilmagan.</div>
          <?php else: ?>
            <div class="space-y-3">
              <?php $__currentLoopData = $sellerOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sellerOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="data-kv">
                  <dt class="flex items-center justify-between gap-3">
                    <span>
                      <a href="<?php echo e(route('admin.seller-orders.show', $sellerOrder)); ?>" class="font-semibold text-[var(--p-accent)] hover:underline">#SELL-<?php echo e($sellerOrder->id); ?></a>
                    </span>
                    <span class="text-xs text-[var(--p-hint)]"><?php echo e($sellerOrder->created_at?->format('d.m.Y H:i')); ?></span>
                  </dt>
                  <dd class="mt-1">
                    <?php if($sellerOrder->seller): ?>
                      <a href="<?php echo e(route('admin.sellers.show', $sellerOrder->seller)); ?>" class="font-semibold text-[var(--p-text)] hover:underline"><?php echo e($sellerOrder->seller->shop_name); ?></a>
                    <?php else: ?>
                      Sotuvchi yo‘q
                    <?php endif; ?>
                  </dd>
                  <div class="mt-2 text-sm text-[var(--p-muted)]">
                    <?php echo e(number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ')); ?> UZS
                    · <?php echo e($sellerOrder->delivery_type ?: 'delivery'); ?>

                    <?php if($sellerOrder->courier): ?>
                      · kuryer: <a href="<?php echo e(route('admin.couriers.show', $sellerOrder->courier)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e(trim(($sellerOrder->courier->first_name ?? '').' '.($sellerOrder->courier->last_name ?? '')) ?: 'Kuryer'); ?></a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Mijoz va to‘lov ma’lumoti</div>
            <div class="a122-section-head__meta">Asosiy aloqa, gift recipient va to‘lov usuli tafsilotlari.</div>
          </div>
        </div>
      <div class="a122-section-body">
        <div class="space-y-2 text-sm">
          <div>
            <span class="metric-label">Ism</span>
            <div class="font-semibold mt-1">
              <?php if($order->user): ?>
                <a href="<?php echo e(route('admin.users.show', $order->user)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e($order->user->full_name ?: 'Foydalanuvchi'); ?></a>
              <?php else: ?>
                Mehmon
              <?php endif; ?>
            </div>
          </div>
          <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1"><?php echo e($order->user?->phone_number ?: '—'); ?></div></div>
          <div><span class="metric-label">To‘lov turi</span><div class="font-semibold mt-1"><?php echo e($paymentMethodLabel); ?></div></div>
          <div><span class="metric-label">To‘lov holati</span><div class="font-semibold mt-1"><?php echo e($paymentLabel); ?></div></div>
          <div><span class="metric-label">In-store buyurtmami</span><div class="font-semibold mt-1"><?php echo e($isInstore ? 'Ha' : "Yo'q"); ?></div></div>
          <div><span class="metric-label">Buyurtma statusi</span><div class="font-semibold mt-1"><?php echo e($orderStatusLabel); ?></div></div>
          <div><span class="metric-label">Gift buyurtmami</span><div class="font-semibold mt-1"><?php echo e($isGiftToOther ? 'Ha' : "Yo'q"); ?></div></div>
          <?php if($isGiftToOther): ?>
            <div><span class="metric-label">Qabul qiluvchi</span><div class="font-semibold mt-1"><?php echo e($order->recipient_name ?: '—'); ?></div></div>
            <div><span class="metric-label">Qabul qiluvchi telefoni</span><div class="font-semibold mt-1"><?php echo e($order->recipient_phone ?: '—'); ?></div></div>
            <div><span class="metric-label">Qabul qiluvchi hududi</span><div class="font-semibold mt-1"><?php echo e($order->recipient_region ?: '—'); ?></div></div>
          <?php endif; ?>
          <div><span class="metric-label">Mijoz istagi</span><div class="font-semibold mt-1"><?php echo e($order->buyerWish ?: '—'); ?></div></div>
        </div>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Kuryer va bajarilish</div>
            <div class="a122-section-head__meta">Courier assignment, pickup/delivery bosqichi va tegishli order yozuvi.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <?php if($courierOrder || $assignedCourier): ?>
            <div class="space-y-3 text-sm">
              <?php if($courierOrder): ?>
                <div>
                  <span class="metric-label">Courier order</span>
                  <div class="font-semibold mt-1">
                    <a href="<?php echo e(route('admin.courier-orders.show', $courierOrder)); ?>" class="text-[var(--p-accent)] hover:underline">#COR-<?php echo e($courierOrder->id); ?></a>
                  </div>
                </div>
              <?php endif; ?>
              <?php if($assignedCourier): ?>
                <div>
                  <span class="metric-label">Biriktirilgan kuryer</span>
                  <div class="font-semibold mt-1">
                    <a href="<?php echo e(route('admin.couriers.show', $assignedCourier)); ?>" class="text-[var(--p-accent)] hover:underline"><?php echo e(trim(($assignedCourier->first_name ?? '').' '.($assignedCourier->last_name ?? '')) ?: 'Kuryer'); ?></a>
                  </div>
                </div>
                <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1"><?php echo e($assignedCourier->phone_number ?: '—'); ?></div></div>
                <div><span class="metric-label">Hudud</span><div class="font-semibold mt-1"><?php echo e($assignedCourier->region ?: '—'); ?></div></div>
              <?php endif; ?>
              <?php if($courierOrder): ?>
                <div><span class="metric-label">Courier order holati</span><div class="font-semibold mt-1"><?php echo e($courierOrder->status ?: '—'); ?></div></div>
                <div><span class="metric-label">Courier narxi</span><div class="font-semibold mt-1"><?php echo e(number_format((float) ($courierOrder->courierPrice ?? 0), 0, '.', ' ')); ?> UZS</div></div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="text-sm text-gray-500">Kuryer hali biriktirilmagan.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/orders/show.blade.php ENDPATH**/ ?>