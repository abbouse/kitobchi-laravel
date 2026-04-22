<?php $__env->startSection('title', 'Promokod: '.$promocode->code); ?>
<?php $__env->startSection('page-title', 'Promokod: '.$promocode->code); ?>

<?php $__env->startSection('content'); ?>

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


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="xl:col-span-4">
    <div class="p-card mb-3">
      <div class="dash-card-body" style="text-align:center;padding:30px">
        <code style="font-family:'JetBrains Mono',monospace;font-size:26px;font-weight:700;
                     color:var(--p-accent);background:var(--p-elevated);
                     padding:12px 24px;border-radius:10px;letter-spacing:.1em;display:inline-block">
          <?php echo e($promocode->code); ?>

        </code>
        <div class="mt-3">
          <span class="s-pill <?php echo e($promocode->status && $promocode->expires_at > now() ? 'success' : 'danger'); ?>" style="font-size:13px;padding:5px 14px">
            <?php echo e($promocode->status && $promocode->expires_at > now() ? 'Aktiv' : 'Nofaol'); ?>

          </span>
        </div>
      </div>
    </div>

    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlar</div></div>
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['Tur',       $promocode->type === 'percent' ? 'Foiz (%)' : 'Miqdor (UZS)'],
          ['Chegirma',  $promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS'],
          ['Min. buyurtma', $promocode->min_order_amount > 0 ? number_format($promocode->min_order_amount).' UZS' : '—'],
          ['Limit',     $promocode->usesLimit ?: 'Cheksiz'],
          ['Ishlatildi', $promocode->usedCount.' marta'],
          ['Muddat',    \Carbon\Carbon::parse($promocode->expires_at)->format('d.m.Y H:i')],
          ["Qo'shildi", $promocode->created_at?->format('d.m.Y H:i')],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    <?php if($promocode->usesLimit > 0): ?>
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Foydalanish</div></div>
      <div class="dash-card-body">
        <?php $pct = min(round($promocode->usedCount / $promocode->usesLimit * 100), 100); ?>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($promocode->usedCount); ?> / <?php echo e($promocode->usesLimit); ?></span>
          <span style="font-size:12px;font-weight:600;color:var(--p-text)"><?php echo e($pct); ?>%</span>
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
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Foydalanish tarixi</div>
        <div class="dash-card-sub"><?php echo e($histories->total()); ?> ta foydalanuvchi</div>
      </div>
      <div class="dash-card-body">
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
                  <a href="<?php echo e(route('admin.users.show', $h->user_id)); ?>" style="color:var(--p-text);font-weight:500">
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
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/promocodes/show.blade.php ENDPATH**/ ?>