<?php $__env->startSection('title', 'Murojaat #'.$botTicket->id); ?>
<?php $__env->startSection('page-title', 'Murojaat #'.$botTicket->id); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.support.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.support.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Murojaat #<?php echo e($botTicket->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e($botTicket->created_at?->format('d.m.Y H:i')); ?> <?php $__env->endSlot(); ?>
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

  <div class="xl:col-span-8">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Murojaat mazmuni</div></div>
      <div class="dash-card-body">
        <div style="background:var(--p-elevated);border-radius:10px;padding:16px;font-size:14px;
                    color:var(--p-text);line-height:1.7;border-left:3px solid var(--p-accent)">
          <?php echo e($botTicket->first_msg ?: 'Xabar yo\'q'); ?>

        </div>
      </div>
    </div>

    
    <?php if($botTicket->attachments && $botTicket->attachments->count()): ?>
    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Ilovalar</div>
        <div class="dash-card-sub"><?php echo e($botTicket->attachments->count()); ?> ta fayl</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>Fayl nomi</th><th>Tur</th><th>Hajm</th><th>Yuboruvchi</th></tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $botTicket->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td style="font-size:13px;color:var(--p-text)"><?php echo e($att->file_name ?: $att->file_id); ?></td>
                <td><span class="s-pill muted"><?php echo e($att->file_type); ?></span></td>
                <td style="font-size:12px;color:var(--p-hint)">
                  <?php echo e($att->file_size ? number_format($att->file_size / 1024, 1).' KB' : '—'); ?>

                </td>
                <td>
                  <span class="s-pill <?php echo e($att->sent_by==='operator'?'accent':'muted'); ?>">
                    <?php echo e($att->sent_by==='operator'?'Operator':'Foydalanuvchi'); ?>

                  </span>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if(in_array($botTicket->status, ['queue','active'])): ?>
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Murojaatni yopish</div></div>
      <div class="dash-card-body">
        <form method="POST" action="<?php echo e(route('admin.support.close', $botTicket)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <label class="p-form-label">Yopish sababi (ixtiyoriy)</label>
          <div class="flex gap-2 mt-1">
            <input type="text" name="close_reason" class="p-form-control flex-fill"
                   placeholder="Muammo hal qilindi..." maxlength="100">
            <button class="btn-p danger">
              <i class="bi bi-x-circle"></i> Yopish
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if($botTicket->close_reason): ?>
    <div class="p-card" style="background:var(--p-danger-d);border-color:rgba(255,92,106,.2)">
      <div class="dash-card-body" style="padding:14px 20px">
        <div style="font-size:12px;color:var(--p-danger);margin-bottom:4px">YOPISH SABABI</div>
        <div style="font-size:13px;color:var(--p-text)"><?php echo e($botTicket->close_reason); ?></div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="xl:col-span-4">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Holat</div></div>
      <div class="dash-card-body">
        <?php $st = $statuses[$botTicket->status] ?? ['label'=>$botTicket->status,'class'=>'ob-p']; ?>
        <span class="o-badge <?php echo e($st['class']); ?>" style="font-size:13px;padding:6px 14px"><?php echo e($st['label']); ?></span>

        <?php if($botTicket->rating): ?>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">FOYDALANUVCHI BAHOSI</div>
          <div style="display:flex;gap:4px">
            <?php for($i=1;$i<=5;$i++): ?>
              <i class="bi bi-star<?php echo e($i<=$botTicket->rating?'-fill':''); ?>"
                 style="font-size:20px;color:<?php echo e($i<=$botTicket->rating?'var(--p-warning)':'var(--p-border)'); ?>"></i>
            <?php endfor; ?>
          </div>
          <div style="font-size:14px;font-weight:700;color:var(--p-warning);margin-top:4px">
            <?php echo e($botTicket->rating); ?> / 5
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Murojaat egasi</div></div>
      <div class="dash-card-body">
        <div style="margin-bottom:12px">
          <div style="font-size:15px;font-weight:600;color:var(--p-text)"><?php echo e($botTicket->name ?: 'Noma\'lum'); ?></div>
          <?php if($botTicket->username): ?>
            <div style="font-size:13px;color:var(--p-hint)">t.me/<?php echo e($botTicket->username); ?></div>
          <?php endif; ?>
          <?php if($botTicket->user_id): ?>
            <div style="font-size:12px;color:var(--p-accent);font-family:'JetBrains Mono',monospace;margin-top:4px">
              Telegram ID: <?php echo e($botTicket->user_id); ?>

            </div>
          <?php endif; ?>
        </div>
        <?php if($botTicket->user_id): ?>
        <a href="<?php echo e(route('admin.users.index', ['search'=>$botTicket->user_id])); ?>"
           class="btn-p ghost sm">
          Foydalanuvchini izlash <i class="bi bi-search"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Operator</div></div>
      <div class="dash-card-body">
        <?php if($botTicket->operator): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--p-<?php echo e($botTicket->operator->status==='online'?'success':($botTicket->operator->status==='busy'?'warning':'muted')); ?>);flex-shrink:0"></div>
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                <?php echo e($botTicket->operator->name ?? 'Noma\'lum'); ?>

              </div>
              <?php if($botTicket->operator->username): ?>
                <div style="font-size:12px;color:var(--p-hint)">{{ $botTicket->operator->username }}</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if(in_array($botTicket->status, ['queue','active'])): ?>
        <form method="POST" action="<?php echo e(route('admin.support.assign', $botTicket)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <label class="p-form-label"><?php echo e($botTicket->operator ? 'Operatorni o\'zgartirish' : 'Operator tayinlash'); ?></label>
          <div class="flex gap-2 mt-1">
            <select name="operator_id" class="p-form-control flex-fill">
              <option value="">Tanlang...</option>
              <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($op->id); ?>" <?php echo e($botTicket->operator_id===$op->id?'selected':''); ?>>
                <?php echo e($op->name ?? $op->username); ?>

                (<?php echo e($op->status); ?>)
              </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['ID',         '#'.$botTicket->id],
          ['Yaratildi',  $botTicket->created_at?->format('d.m.Y H:i')],
          ['Yangilandi', $botTicket->updated_at?->format('d.m.Y H:i')],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($k); ?></span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/support/show.blade.php ENDPATH**/ ?>