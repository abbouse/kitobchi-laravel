<?php $__env->startSection('title', $book->name); ?>
<?php $__env->startSection('page-title', 'Kitob tafsiloti'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.books.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.books.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($book->name); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($book->author ?: 'Muallif ko‘rsatilmagan'); ?> · <?php echo e($book->category?->name_uz ?: 'Kategoriya yo‘q'); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
      <a href="<?php echo e(route('admin.books.edit', $book)); ?>" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
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
    <div class="xl:col-span-8 space-y-4">
      <section class="card p-5">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5">
          <div class="space-y-3">
            <div class="rounded-[1.4rem] overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] min-h-[320px] flex items-center justify-center">
              <?php if($images->isNotEmpty()): ?>
                <img src="<?php echo e($images->first()); ?>" alt="<?php echo e($book->name); ?>" class="w-full h-full object-cover">
              <?php else: ?>
                <div class="text-center text-[var(--p-hint)]">
                  <i class="bi bi-book text-4xl"></i>
                  <div class="mt-2 text-sm">Rasm biriktirilmagan</div>
                </div>
              <?php endif; ?>
            </div>
            <?php if($images->count() > 1): ?>
              <div class="grid grid-cols-4 gap-2">
                <?php $__currentLoopData = $images->slice(0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <div class="rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] aspect-square">
                    <img src="<?php echo e($image); ?>" alt="<?php echo e($book->name); ?>" class="w-full h-full object-cover">
                  </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="space-y-4">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge <?php echo e($book->is_approved == 1 ? 'badge-success' : ($book->is_approved == 2 ? 'badge-danger' : 'badge-warning')); ?>">
                <?php echo e($book->is_approved == 1 ? 'Tasdiqlangan' : ($book->is_approved == 2 ? 'Rad etilgan' : 'Moderatsiyada')); ?>

              </span>
              <span class="badge <?php echo e($book->status ? 'badge-info' : 'badge-muted'); ?>"><?php echo e($book->status ? 'Faol' : 'Nofaol'); ?></span>
              <?php if($book->recommended): ?>
                <span class="badge badge-warning">Recommended</span>
              <?php endif; ?>
            </div>

            <div class="data-grid two">
              <div class="data-kv"><dt>Sotuvchi</dt><dd><?php echo e($book->seller?->shop_name ?: 'Ichki katalog'); ?></dd></div>
              <div class="data-kv"><dt>Kategoriya</dt><dd><?php echo e($book->category?->name_uz ?: '—'); ?></dd></div>
              <div class="data-kv"><dt>Narx</dt><dd><?php echo e(number_format((float)$book->price, 0, '.', ' ')); ?> UZS</dd></div>
              <div class="data-kv"><dt>Chegirma narxi</dt><dd><?php echo e($book->discountPrice ? number_format((float)$book->discountPrice, 0, '.', ' ') . ' UZS' : '—'); ?></dd></div>
              <div class="data-kv"><dt>Ombor</dt><dd><?php echo e(number_format((int)($book->count ?? 0))); ?> ta</dd></div>
              <div class="data-kv"><dt>Ko‘rishlar</dt><dd><?php echo e(number_format((int)($book->views ?? 0))); ?></dd></div>
            </div>

            <div class="content-prose">
              <?php echo e($book->description ?: 'Kitob uchun batafsil tavsif kiritilmagan.'); ?>

            </div>
          </div>
        </div>
      </section>

      <section class="card p-5">
        <h3 class="text-lg font-black mb-4">Savdo va buyurtma analitikasi</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="kpi-soft"><div class="metric-label">Sotilgan</div><div class="metric-value text-xl"><?php echo e(number_format((int)($book->totalSales ?? 0))); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Mijozlar</div><div class="metric-value text-xl"><?php echo e(number_format((int)($book->totalClients ?? 0))); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Daromad</div><div class="metric-value text-xl"><?php echo e(number_format((float)($book->totalRevenue ?? 0), 0, '.', ' ')); ?></div></div>
          <div class="kpi-soft"><div class="metric-label">Hafta savdosi</div><div class="metric-value text-xl"><?php echo e(number_format((int)($book->totalSalesWeek ?? 0))); ?></div></div>
        </div>

        <div class="table-wrap mt-4">
          <table class="tbl">
            <thead><tr><th>So‘nggi buyurtmalar</th><th>Mijoz</th><th>Summa</th><th>Status</th><th>Sana</th><th></th></tr></thead>
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
                <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Bu kitob ishtirok etgan buyurtmalar topilmadi.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <div class="xl:col-span-4 space-y-4">
      <section class="card p-5">
        <h3 class="text-lg font-black">Texnik ma’lumot</h3>
        <dl class="space-y-3 mt-4">
          <div><dt class="metric-label">Til</dt><dd class="font-semibold mt-1"><?php echo e($book->lang ?: '—'); ?></dd></div>
          <div><dt class="metric-label">Muqova</dt><dd class="font-semibold mt-1"><?php echo e($book->coverType ?: '—'); ?></dd></div>
          <div><dt class="metric-label">Sahifalar</dt><dd class="font-semibold mt-1"><?php echo e($book->pages ?: '—'); ?></dd></div>
          <div><dt class="metric-label">Yil</dt><dd class="font-semibold mt-1"><?php echo e($book->year ?: '—'); ?></dd></div>
          <div><dt class="metric-label">Kangaroo score</dt><dd class="font-semibold mt-1"><?php echo e($book->kangaroo_listing_score ?: '—'); ?></dd></div>
          <div><dt class="metric-label">UGC score</dt><dd class="font-semibold mt-1"><?php echo e($book->ugc_aggregate_score ?: '—'); ?></dd></div>
          <div><dt class="metric-label">Yaratilgan</dt><dd class="font-semibold mt-1"><?php echo e(optional($book->created_at)->format('d.m.Y H:i') ?: '—'); ?></dd></div>
        </dl>
      </section>

      <section class="card p-5">
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/books/show.blade.php ENDPATH**/ ?>