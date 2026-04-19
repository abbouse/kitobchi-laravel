<?php $__env->startSection('title', 'Bot operatorlari'); ?>
<?php $__env->startSection('page-title', 'Bot operatorlari'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.bot-tickets.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.bot-tickets.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Bot operatorlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Telegram bot support operatorlari <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <?php $__empty_1 = true; $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php
    $dotClr = match($op->status) { 'online'=>'success','busy'=>'warning',default=>'muted' };
    $dotLbl = match($op->status) { 'online'=>'Online','busy'=>'Band',default=>'Offline' };
  ?>
  <div class="xl:col-span-4">
    <div class="p-card">
      <div class="dash-card-body">

        
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                        display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0">
              <?php echo e(strtoupper(substr($op->name ?? $op->username ?? 'O', 0, 1))); ?>

            </div>
            <div>
              <div style="font-size:15px;font-weight:600;color:var(--p-text)"><?php echo e($op->name ?: 'Noma\'lum'); ?></div>
              <?php if($op->username): ?>
                <div style="font-size:12px;color:var(--p-hint)">{{ $op->username }}</div>
              <?php endif; ?>
              <div style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                TG: <?php echo e($op->telegram_id); ?>

              </div>
            </div>
          </div>
          <div class="flex flex-col items-end gap-1">
            <span class="s-pill <?php echo e($op->is_active ? 'success' : 'muted'); ?>">
              <?php echo e($op->is_active ? 'Faol' : 'Blok'); ?>

            </span>
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--p-<?php echo e($dotClr); ?>)">
              <div style="width:6px;height:6px;border-radius:50%;background:var(--p-<?php echo e($dotClr); ?>)"></div>
              <?php echo e($dotLbl); ?>

            </div>
          </div>
        </div>

        
        <?php if($op->stats): ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border:1px solid var(--p-border);border-radius:8px;overflow:hidden;margin-bottom:14px">
          <?php $__currentLoopData = [
            ['Bajarildi', $op->stats->handled, 'text'],
            ['Yopildi',   $op->stats->closed,  'success'],
            ['O\'rt. baho', number_format($op->stats->avg_rating,1), 'warning'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl,$val,$clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="text-align:center;padding:10px 6px;border-right:1px solid var(--p-border)">
            <div style="font-size:16px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-<?php echo e($clr); ?>)"><?php echo e($val); ?></div>
            <div style="font-size:10px;color:var(--p-hint)"><?php echo e($lbl); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <form method="POST" action="<?php echo e(route('panel.bot-tickets.operator.toggle', $op)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <button class="btn-p <?php echo e($op->is_active ? 'danger' : ''); ?> ghost sm" style="width:100%">
            <i class="bi bi-<?php echo e($op->is_active ? 'lock' : 'unlock'); ?>"></i>
            <?php echo e($op->is_active ? 'Bloklash' : 'Faollashtirish'); ?>

          </button>
        </form>

      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
  <div class="">
    <div class="p-card" style="text-align:center;padding:50px;color:var(--p-hint)">
      <i class="bi bi-headset" style="font-size:40px;display:block;margin-bottom:12px"></i>
      Operatorlar yo'q
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if($operators->hasPages()): ?>
<div class="p-pagination mt-3"><?php echo e($operators->links('panel.partials.pagination')); ?></div>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/bot-tickets/operators.blade.php ENDPATH**/ ?>