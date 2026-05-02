<?php $__env->startSection('title', 'Tranzaksiya #' . $transaction->id); ?>
<?php $__env->startSection('page-title', 'Tranzaksiya tafsilotlari'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php
    $statusTone = $transaction->status === 'approved' ? 'badge-success' : ($transaction->status === 'rejected' ? 'badge-danger' : 'badge-warning');
    $margin = (float) ($transaction->commissionPrice ?? 0);
    $gross = (float) ($transaction->amount ?? 0);
    $net = (float) ($transaction->netAmount ?? 0);
  ?>
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.transactions.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.transactions.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> #TRX-<?php echo e($transaction->id); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($transaction->seller?->shop_name ?: 'Seller yo‘q'); ?> · <?php echo e(optional($transaction->created_at)->format('d.m.Y H:i')); ?> <?php $__env->endSlot(); ?>
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

  <?php if(session('success')): ?>
    <div class="p-alert success"><?php echo e(session('success')); ?></div>
  <?php endif; ?>
  <?php if(session('error')): ?>
    <div class="p-alert danger"><?php echo e(session('error')); ?></div>
  <?php endif; ?>

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Brutto</div>
          <div class="metric-value text-xl"><?php echo e(number_format($gross, 0, '.', ' ')); ?></div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Komissiya</div>
          <div class="metric-value text-xl"><?php echo e(number_format($margin, 0, '.', ' ')); ?></div>
          <div class="metric-meta"><?php echo e($transaction->commissionPercent ?: 0); ?>%</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Sof summa</div>
          <div class="metric-value text-xl"><?php echo e(number_format($net, 0, '.', ' ')); ?></div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Holat</div>
          <div class="metric-value text-xl"><?php echo e($transaction->status_label); ?></div>
          <div class="metric-meta">Joriy payout bosqichi</div>
        </div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Tranzaksiya ma’lumotlari</div>
          <div class="a122-section-head__meta">Payout yozuvi, seller konteksti va hisob-kitob tarkibi.</div>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="data-grid two">
        <div class="data-kv"><dt>Tranzaksiya ID</dt><dd>#<?php echo e($transaction->id); ?></dd></div>
        <div class="data-kv"><dt>Sana</dt><dd><?php echo e($transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—'); ?></dd></div>
        <div class="data-kv">
          <dt>Sotuvchi</dt>
          <dd>
            <?php if($transaction->seller): ?>
              <a href="<?php echo e(route('admin.sellers.show', $transaction->seller)); ?>" class="font-semibold text-[var(--p-accent)] hover:underline"><?php echo e($transaction->seller->shop_name); ?></a>
            <?php else: ?>
              —
            <?php endif; ?>
          </dd>
        </div>
        <div class="data-kv"><dt>Telefon</dt><dd><?php echo e($transaction->seller?->phone_number ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Brutto miqdor</dt><dd><?php echo e(number_format((float)($transaction->amount ?? 0), 0, '.', ' ')); ?> UZS</dd></div>
        <div class="data-kv"><dt>Holat</dt><dd><span class="badge <?php echo e($statusTone); ?>"><?php echo e($transaction->status_label); ?></span></dd></div>
        <div class="data-kv"><dt>Komissiya %</dt><dd><?php echo e($transaction->commissionPercent ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Komissiya summasi</dt><dd><?php echo e($transaction->commissionPrice ? number_format((float)$transaction->commissionPrice, 0, '.', ' ') . ' UZS' : '—'); ?></dd></div>
        <div class="data-kv"><dt>Sof summa</dt><dd><?php echo e($transaction->netAmount ? number_format((float)$transaction->netAmount, 0, '.', ' ') . ' UZS' : '—'); ?></dd></div>
        <div class="data-kv"><dt>Karta</dt><dd><?php echo e($transaction->card ?: '—'); ?></dd></div>
        <div class="data-kv"><dt>Yangilangan</dt><dd><?php echo e(optional($transaction->updated_at)->format('d.m.Y H:i') ?: '—'); ?></dd></div>
      </div>

      <?php if($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc): ?>
        <div class="mt-5">
          <h4 class="text-sm font-bold mb-2">Izoh</h4>
          <div class="content-prose"><?php echo e($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc); ?></div>
        </div>
      <?php endif; ?>
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Seller summary</div>
          <div class="a122-section-head__meta">Shu seller bo‘yicha payout oqimi va operatsion actionlar.</div>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="grid grid-cols-1 gap-3">
        <div class="kpi-soft"><div class="metric-label">Tasdiqlangan tranzaksiyalar</div><div class="metric-value text-xl"><?php echo e(number_format((int) $sellerTotals['approved_count'])); ?></div></div>
        <div class="kpi-soft"><div class="metric-label">Tasdiqlangan summa</div><div class="metric-value text-xl"><?php echo e(number_format((float) $sellerTotals['approved_sum'], 0, '.', ' ')); ?></div></div>
        <div class="kpi-soft"><div class="metric-label">Pending summa</div><div class="metric-value text-xl"><?php echo e(number_format((float) $sellerTotals['pending_sum'], 0, '.', ' ')); ?></div></div>
      </div>

      <?php if($transaction->status === 'pending'): ?>
        <div class="mt-5 space-y-3">
          <form method="POST" action="<?php echo e(route('admin.transactions.approve', $transaction)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
              <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
            </button>
          </form>

          <form method="POST" action="<?php echo e(route('admin.transactions.reject', $transaction)); ?>" onsubmit="return confirm('Tranzaksiyani rad etishga ishonchingiz komilmi?')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <button type="submit" class="btn btn-danger w-full flex items-center justify-center gap-2">
              <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
            </button>
          </form>
        </div>
      <?php endif; ?>
      </div>
    </section>
  </div>

  <section class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Sellerning yaqin tranzaksiyalari</div>
        <div class="a122-section-head__meta">Shu payout yozuvi qaysi oqim ichida turganini tez ko‘rish uchun.</div>
      </div>
      <div class="a122-section-head__actions">
        <span class="badge badge-info"><?php echo e($sellerTransactions->count()); ?> ta</span>
      </div>
    </div>
    <div class="a122-section-body">
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Miqdor</th><th>Holat</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $sellerTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td>#TRX-<?php echo e($row->id); ?></td>
                <td><?php echo e(number_format((float) $row->amount, 0, '.', ' ')); ?> UZS</td>
                <td><span class="badge badge-<?php echo e($row->status_color); ?>"><?php echo e($row->status_label); ?></span></td>
                <td><?php echo e(optional($row->created_at)->format('d.m.Y H:i')); ?></td>
                <td class="text-right"><a href="<?php echo e(route('admin.transactions.show', $row)); ?>" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" class="text-center text-sm text-gray-500 py-8">Boshqa tranzaksiyalar topilmadi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/transactions/show.blade.php ENDPATH**/ ?>