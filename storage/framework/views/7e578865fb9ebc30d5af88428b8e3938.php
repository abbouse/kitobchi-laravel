<?php $__env->startSection('title', 'Buyurtma #' . $order->id); ?>
<?php $__env->startSection('page-title', 'Buyurtma tafsiloti'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
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
                <td><?php echo e($it['name'] ?? ($it['product']->name ?? '—')); ?></td>
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
          <div class="flex justify-between gap-3"><dt class="metric-label">Chegirma</dt><dd class="font-semibold"><?php echo e(number_format($summary['discount'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback</dt><dd class="font-semibold"><?php echo e(number_format($summary['cashback'], 0, '.', ' ')); ?> UZS</dd></div>
          <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-3"><dt class="font-bold">Jami</dt><dd class="font-black"><?php echo e(number_format((float)$order->amount, 0, '.', ' ')); ?> UZS</dd></div>
        </dl>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Mijoz ma’lumoti</div>
            <div class="a122-section-head__meta">Asosiy aloqa ma’lumotlari va to‘lov holati.</div>
          </div>
        </div>
        <div class="a122-section-body">
        <div class="space-y-2 text-sm">
          <div><span class="metric-label">Ism</span><div class="font-semibold mt-1"><?php echo e($order->user?->full_name ?: 'Mehmon'); ?></div></div>
          <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1"><?php echo e($order->user?->phone_number ?: '—'); ?></div></div>
          <div><span class="metric-label">To‘lov holati</span><div class="font-semibold mt-1"><?php echo e((int)$order->paymentStatus === 2 ? 'To‘langan' : 'Kutilmoqda'); ?></div></div>
        </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/orders/show.blade.php ENDPATH**/ ?>