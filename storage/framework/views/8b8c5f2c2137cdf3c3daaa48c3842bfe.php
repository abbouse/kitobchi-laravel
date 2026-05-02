<?php $__env->startSection('title', 'Promokod: '.$promocode->code); ?>
<?php $__env->startSection('page-title', 'Promokod: '.$promocode->code); ?>

<?php $__env->startSection('content'); ?>
<?php
  $isActive = $promocode->status && $promocode->expires_at > now();
  $usesLimit = (int) ($promocode->usesLimit ?? 0);
  $usedCount = (int) ($promocode->usedCount ?? 0);
  $remaining = $usesLimit > 0 ? max($usesLimit - $usedCount, 0) : null;
  $pct = $usesLimit > 0 ? min(round($usedCount / $usesLimit * 100), 100) : null;
?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.promocodes.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.promocodes.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Promokod: <?php echo e($promocode->code); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> ID: #<?php echo e($promocode->id); ?> · <?php echo e($promocode->created_at?->format('d.m.Y')); ?> <?php $__env->endSlot(); ?>
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

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Chegirma turi</div>
        <div class="metric-value text-xl"><?php echo e($promocode->type === 'percent' ? 'Foiz' : 'Miqdor'); ?></div>
        <div class="metric-meta"><?php echo e($promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS'); ?></div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Ishlatilgan</div>
        <div class="metric-value text-xl"><?php echo e(number_format($usedCount)); ?></div>
        <div class="metric-meta">Jami foydalanish</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Qolgan limit</div>
        <div class="metric-value text-xl"><?php echo e($remaining !== null ? number_format($remaining) : '∞'); ?></div>
        <div class="metric-meta"><?php echo e($usesLimit > 0 ? 'Cheklangan' : 'Cheksiz'); ?></div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl"><?php echo e($isActive ? 'Aktiv' : 'Nofaol'); ?></div>
        <div class="metric-meta"><?php echo e(optional($promocode->expires_at)->format('d.m.Y H:i') ?: 'Muddat yo‘q'); ?></div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="xl:col-span-4">
    <div class="a122-section mb-3">
      <div class="a122-section-body text-center py-8">
        <code class="inline-block rounded-2xl bg-[var(--p-elevated)] px-6 py-4 text-[26px] font-black tracking-[0.12em] text-[var(--p-accent)]">
          <?php echo e($promocode->code); ?>

        </code>
        <div class="mt-3">
          <span class="badge <?php echo e($isActive ? 'badge-success' : 'badge-danger'); ?>">
            <?php echo e($isActive ? 'Aktiv' : 'Nofaol'); ?>

          </span>
        </div>
      </div>
    </div>

    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Qo‘llanish qoidalari</div>
          <div class="a122-section-head__meta">Promokodning turi, limitlari va minimal buyurtma sharti.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <?php $__currentLoopData = [
          ['Tur',       $promocode->type === 'percent' ? 'Foiz (%)' : 'Miqdor (UZS)'],
          ['Chegirma',  $promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS'],
          ['Min. buyurtma', $promocode->min_order_amount > 0 ? number_format($promocode->min_order_amount).' UZS' : '—'],
          ['Limit',     $promocode->usesLimit ?: 'Cheksiz'],
          ['Ishlatildi', $promocode->usedCount.' marta'],
          ['Muddat',    \Carbon\Carbon::parse($promocode->expires_at)->format('d.m.Y H:i')],
          ["Qo'shildi", $promocode->created_at?->format('d.m.Y H:i')],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex justify-between gap-4 py-2 border-b border-[var(--p-border)]">
          <span class="text-xs text-[var(--p-hint)]"><?php echo e($k); ?></span>
          <span class="text-sm font-medium text-[var(--p-text)] text-right"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    <?php if($promocode->usesLimit > 0): ?>
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Foydalanish progressi</div>
          <div class="a122-section-head__meta">Limitli promokod uchun ishlatilish darajasi.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="flex justify-between items-center gap-4 mb-2">
          <span class="text-xs text-[var(--p-hint)]"><?php echo e($usedCount); ?> / <?php echo e($usesLimit); ?></span>
          <span class="text-xs font-semibold text-[var(--p-text)]"><?php echo e($pct); ?>%</span>
        </div>
        <div class="dash-prog-track" style="height:8px">
          <div class="dash-prog-fill" style="width:<?php echo e($pct); ?>%;background:<?php echo e($pct>=100?'var(--p-danger)':'var(--p-accent)'); ?>"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <a href="<?php echo e(route('admin.promocodes.edit', $promocode)); ?>" class="btn-p primary" style="width:100%;justify-content:center;margin-bottom:8px">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>

  <div class="xl:col-span-8">
    <div class="a122-section">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Foydalanish tarixi</div>
          <div class="a122-section-head__meta"><?php echo e($histories->total()); ?> ta foydalanuvchi promokoddan foydalangan.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Foydalanuvchi</th><th>Telefon</th><th>Sana</th></tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)"><?php echo e($h->id); ?></td>
                <td>
                  <?php if($h->user): ?>
                  <a href="<?php echo e(route('admin.users.show', $h->user_id)); ?>" style="color:var(--p-text);font-weight:600">
                    <?php echo e($h->user->name); ?> <?php echo e($h->user->lastname); ?>

                  </a>
                  <?php else: ?>
                    <span style="color:var(--p-hint)">ID: <?php echo e($h->user_id); ?></span>
                  <?php endif; ?>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)"><?php echo e($h->user?->phone_number ?? '—'); ?></td>
                <td style="font-size:11px;color:var(--p-hint)"><?php echo e(\Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i')); ?></td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--p-hint)">Hali ishlatilmagan</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if($histories->hasPages()): ?>
        <?php echo e($histories->links('a122.partials.pagination')); ?>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/promocodes/show.blade.php ENDPATH**/ ?>