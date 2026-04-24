<?php $__env->startSection('title', 'Support murojaat'); ?>
<?php $__env->startSection('page-title', 'Support murojaatlar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Support murojaatlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Telegram bot orqali kelgan yordam so'rovlari <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5 mb-4">
  <?php $__currentLoopData = [
    [$counts['all'] ?? 0, 'Jami murojaat', 'accent', 'bi-headset'],
    [$counts['queue'] ?? 0, 'Navbatda', 'warning', 'bi-hourglass-split'],
    [$counts['active'] ?? 0, 'Aktiv', 'info', 'bi-lightning-charge'],
    [$counts['closed'] ?? 0, 'Yopilgan', 'muted', 'bi-check2-circle'],
    [$counts['rated'] ?? 0, 'Baholangan', 'success', 'bi-star'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$value, $label, $tone, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-<?php echo e($tone); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($tone); ?>)">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)"><?php echo e($value); ?></div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)"><?php echo e($label); ?></div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Murojaatlar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($tickets->total()); ?> ta support ticket yuklandi</div>
  </div>
</div>


<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Birinchi xabar</th>
          <th>Operator</th>
          <th>Status</th>
          <th>Baho</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $st = $statuses[$ticket->status] ?? ['label'=>$ticket->status,'class'=>'ob-p']; ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#<?php echo e($ticket->id); ?></td>
          <td>
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                <?php echo e($ticket->name ?: 'Noma\'lum'); ?>

              </div>
              <?php if($ticket->username): ?>
                <div style="font-size:11px;color:var(--p-hint)">t.me/<?php echo e($ticket->username); ?></div>
              <?php endif; ?>
              <?php if($ticket->user_id): ?>
                <div style="font-size:10px;color:var(--p-accent);font-family:'JetBrains Mono',monospace">
                  user #<?php echo e($ticket->user_id); ?>

                </div>
              <?php endif; ?>
            </div>
          </td>
          <td style="max-width:200px">
            <div style="font-size:12px;color:var(--p-muted);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
              <?php echo e($ticket->first_msg ?: '—'); ?>

            </div>
          </td>
          <td>
            <?php if($ticket->operator): ?>
              <div style="display:flex;align-items:center;gap:6px">
                <div style="width:6px;height:6px;border-radius:50%;background:var(--p-<?php echo e($ticket->operator->status==='online'?'success':($ticket->operator->status==='busy'?'warning':'muted')); ?>)"></div>
                <span style="font-size:13px;color:var(--p-text)"><?php echo e($ticket->operator->name ?? $ticket->operator->username); ?></span>
              </div>
            <?php elseif($ticket->status === 'queue'): ?>
              <span style="font-size:12px;color:var(--p-warning)">Tayinlanmagan</span>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>
          <td><span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span></td>
          <td>
            <?php if($ticket->rating): ?>
              <div style="display:flex;align-items:center;gap:2px">
                <?php for($i=1;$i<=5;$i++): ?>
                  <i class="bi bi-star<?php echo e($i<=$ticket->rating?'-fill':''); ?>"
                     style="font-size:12px;color:<?php echo e($i<=$ticket->rating?'var(--p-warning)':'var(--p-border)'); ?>"></i>
                <?php endfor; ?>
              </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
            <?php echo e($ticket->created_at?->format('d.m H:i')); ?>

          </td>
          <td>
            <a href="<?php echo e(route('admin.support.show', $ticket)); ?>" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-chat-square-text" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Murojaatlar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($tickets->hasPages()): ?>
  <?php echo e($tickets->links('a122.partials.pagination')); ?>

  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/support/index.blade.php ENDPATH**/ ?>