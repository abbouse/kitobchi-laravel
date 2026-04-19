<?php $__env->startSection('title', 'Support murojaat'); ?>
<?php $__env->startSection('page-title', 'Support murojaatlar'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Support murojaatlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Telegram bot orqali kelgan yordam so'rovlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.bot-tickets.operators')); ?>" class="btn-p ghost">
        <i class="bi bi-headset"></i> Operatorlar
      </a>
   <?php $__env->endSlot(); ?>
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


<div class="tab-pills fade-up mb-3">
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>'all','page'=>1])); ?>"
     class="tab-pill <?php echo e($tab==='all'?'active':''); ?>">
    Barchasi <span class="tab-badge"><?php echo e($counts['all']); ?></span>
  </a>
  <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$key,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$key?'active':''); ?>">
    <?php echo e($s['label']); ?> <span class="tab-badge"><?php echo e($counts[$key] ?? 0); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="filter-bar mb-3">
  <form method="GET" class="flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control" placeholder="ID, ism, username..."
           value="<?php echo e(request('search')); ?>" style="width:200px">
    <select name="operator_id" class="p-form-control" style="width:180px">
      <option value="">Barcha operatorlar</option>
      <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($op->id); ?>" <?php echo e(request('operator_id')==$op->id?'selected':''); ?>>
        <?php echo e($op->name ?? $op->username); ?>

      </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <input type="date" name="date_from" class="p-form-control" value="<?php echo e(request('date_from')); ?>" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="<?php echo e(request('date_to')); ?>"   style="width:145px">
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i></button>
    <a href="<?php echo e(route('panel.bot-tickets.index',['tab'=>$tab])); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>


<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
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
            <a href="<?php echo e(route('panel.bot-tickets.show', $ticket)); ?>" class="btn-p ghost sm">
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
  <div class="p-pagination"><?php echo e($tickets->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/bot-tickets/index.blade.php ENDPATH**/ ?>