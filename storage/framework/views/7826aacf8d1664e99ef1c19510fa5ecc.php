<?php $__env->startSection('title', $item->name); ?>
<?php $__env->startSection('page-title', 'Kanstovar tafsiloti'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.stationery.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.stationery.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($item->name); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($item->category?->name_uz ?: 'Kategoriya yo‘q'); ?> · <?php echo e($item->material ?: 'Material ko‘rsatilmagan'); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
      <a href="<?php echo e(route('admin.stationery.edit', $item->id)); ?>" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
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
    <section class="card p-5 xl:col-span-8">
      <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5">
        <div class="space-y-3">
          <div class="rounded-[1.4rem] overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] min-h-[320px] flex items-center justify-center">
            <?php if($images->isNotEmpty()): ?>
              <img src="<?php echo e($images->first()); ?>" alt="<?php echo e($item->name); ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="text-center text-[var(--p-hint)]">
                <i class="bi bi-pencil-square text-4xl"></i>
                <div class="mt-2 text-sm">Rasm biriktirilmagan</div>
              </div>
            <?php endif; ?>
          </div>
          <?php if($images->count() > 1): ?>
            <div class="grid grid-cols-4 gap-2">
              <?php $__currentLoopData = $images->slice(0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] aspect-square">
                  <img src="<?php echo e($image); ?>" alt="<?php echo e($item->name); ?>" class="w-full h-full object-cover">
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="space-y-4">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="badge <?php echo e($item->is_approved == 1 ? 'badge-success' : ($item->is_approved == 2 ? 'badge-danger' : 'badge-warning')); ?>">
              <?php echo e($item->is_approved == 1 ? 'Tasdiqlangan' : ($item->is_approved == 2 ? 'Rad etilgan' : 'Moderatsiyada')); ?>

            </span>
            <span class="badge <?php echo e($item->status ? 'badge-info' : 'badge-muted'); ?>"><?php echo e($item->status ? 'Faol' : 'Nofaol'); ?></span>
            <?php if($item->recommended): ?>
              <span class="badge badge-warning">Recommended</span>
            <?php endif; ?>
          </div>

          <div class="data-grid two">
            <div class="data-kv"><dt>Narx</dt><dd><?php echo e(number_format((float)$item->price, 0, '.', ' ')); ?> UZS</dd></div>
            <div class="data-kv"><dt>Chegirma</dt><dd><?php echo e($item->discount_price ? number_format((float)$item->discount_price, 0, '.', ' ') . ' UZS' : '—'); ?></dd></div>
            <div class="data-kv"><dt>Discount %</dt><dd><?php echo e($item->discount_percent ?: 0); ?>%</dd></div>
            <div class="data-kv"><dt>Ombor</dt><dd><?php echo e(number_format((int)($item->stock ?? 0))); ?></dd></div>
            <div class="data-kv"><dt>Ko‘rishlar</dt><dd><?php echo e(number_format((int)($item->views ?? 0))); ?></dd></div>
            <div class="data-kv"><dt>Sotuvchi</dt><dd><?php echo e($item->seller?->shop_name ?: 'Ichki katalog'); ?></dd></div>
          </div>

          <div class="content-prose"><?php echo e($item->description ?: 'Mahsulot uchun tavsif kiritilmagan.'); ?></div>
        </div>
      </div>
    </section>

    <section class="xl:col-span-4 space-y-4">
      <div class="card p-5">
        <h3 class="text-lg font-black mb-4">KPI</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="kpi-soft"><div class="metric-label">Sotilgan</div><div class="metric-value text-xl"><?php echo e(number_format((int)($item->totalSales ?? 0))); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Mijozlar</div><div class="metric-value text-xl"><?php echo e(number_format((int)($item->totalClients ?? 0))); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Daromad</div><div class="metric-value text-xl"><?php echo e(number_format((float)($item->totalRevenue ?? 0), 0, '.', ' ')); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Variantlar</div><div class="metric-value text-xl"><?php echo e($item->variants?->count() ?? 0); ?></div></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-lg font-black mb-4">Variantlar</h3>
        <div class="space-y-3">
          <?php $__empty_1 = true; $__currentLoopData = $item->variants ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="data-kv">
              <dt><?php echo e($variant->name ?? ('Variant #'.$variant->id)); ?></dt>
              <dd><?php echo e(number_format((float) ($variant->price ?? 0), 0, '.', ' ')); ?> UZS · stock <?php echo e(number_format((int) ($variant->stock ?? 0))); ?></dd>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-sm text-gray-500">Variantlar mavjud emas.</div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card p-5 xl:col-span-6">
      <h3 class="text-lg font-black mb-4">So‘nggi buyurtmalar</h3>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Mijoz</th><th>Summa</th><th>To‘lov</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td>#ORD-<?php echo e($order->id); ?></td>
                <td><?php echo e($order->user?->full_name ?: 'Mehmon'); ?></td>
                <td><?php echo e(number_format((float) $order->amount, 0, '.', ' ')); ?> UZS</td>
                <td><?php echo e((int) $order->paymentStatus === 2 ? 'To‘langan' : 'Jarayonda'); ?></td>
                <td><?php echo e(optional($order->created_at)->format('d.m.Y H:i')); ?></td>
                <td class="text-right"><a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card p-5 xl:col-span-6">
      <h3 class="text-lg font-black mb-4">Seller oqimi</h3>
      <div class="space-y-3">
        <?php $__empty_1 = true; $__currentLoopData = $sellerOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sellerOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="data-kv">
            <dt>#SELL-<?php echo e($sellerOrder->id); ?> · <?php echo e(optional($sellerOrder->created_at)->format('d.m.Y')); ?></dt>
            <dd><?php echo e($sellerOrder->client?->full_name ?: 'Mijoz yo‘q'); ?></dd>
            <div class="mt-2 text-sm text-[var(--p-muted)]"><?php echo e(number_format((float) $sellerOrder->amount, 0, '.', ' ')); ?> UZS · <?php echo e($sellerOrder->status ?: 'status yo‘q'); ?></div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="text-sm text-gray-500">Seller order oqimi topilmadi.</div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/stationery/show.blade.php ENDPATH**/ ?>