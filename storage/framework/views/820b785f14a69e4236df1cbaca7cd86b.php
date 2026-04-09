<?php $__env->startSection('title', 'Promokodlar'); ?>
<?php $__env->startSection('page-title', 'Promokodlar'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div class="tab-pills">
    <?php $__currentLoopData = [['all','Barchasi'],['active','Aktiv'],['expired','Muddati o\'tgan']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$l]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
       class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
      <?php echo e($l); ?> <span class="tab-badge"><?php echo e($counts[$k]); ?></span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <a href="<?php echo e(route('panel.promocodes.create')); ?>" class="btn-p">
    <i class="bi bi-plus-lg"></i> Yangi promokod
  </a>
</div>

<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control" placeholder="Kod bo'yicha..."
           value="<?php echo e(request('search')); ?>" style="width:200px">
    <select name="type" class="p-form-control" style="width:150px">
      <option value="">Barcha tur</option>
      <option value="percent" <?php echo e(request('type')==='percent'?'selected':''); ?>>Foiz (%)</option>
      <option value="fixed"   <?php echo e(request('type')==='fixed'?'selected':''); ?>>Miqdor (UZS)</option>
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="<?php echo e(route('panel.promocodes.index')); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Kod</th>
          <th>Tur</th>
          <th>Miqdor</th>
          <th>Min. buyurtma</th>
          <th>Limit / Ishlatildi</th>
          <th>Muddat</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $promocodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $expired = $p->expires_at <= now();
          $full    = $p->usesLimit > 0 && $p->usedCount >= $p->usesLimit;
        ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent)"><?php echo e($p->id); ?></td>
          <td>
            <code style="font-family:'DM Mono',monospace;font-size:14px;font-weight:700;
                         color:var(--p-text);background:var(--p-elevated);
                         padding:3px 10px;border-radius:6px;letter-spacing:.05em">
              <?php echo e($p->code); ?>

            </code>
          </td>
          <td>
            <span class="s-pill <?php echo e($p->type==='percent'?'info':'accent'); ?>">
              <?php echo e($p->type==='percent' ? 'Foiz (%)' : 'Miqdor (UZS)'); ?>

            </span>
          </td>
          <td style="font-family:'DM Mono',monospace;font-weight:700;font-size:14px;color:var(--p-text)">
            <?php echo e($p->type==='percent' ? $p->amount.'%' : number_format($p->amount).' UZS'); ?>

          </td>
          <td style="font-size:12px;color:var(--p-muted)">
            <?php echo e($p->min_order_amount > 0 ? number_format($p->min_order_amount).' UZS' : '—'); ?>

          </td>
          <td>
            <?php if($p->usesLimit > 0): ?>
              <div style="font-family:'DM Mono',monospace;font-size:12px">
                <span style="color:<?php echo e($full?'var(--p-danger)':'var(--p-text)'); ?>"><?php echo e($p->usedCount); ?></span>
                / <?php echo e($p->usesLimit); ?>

              </div>
              <div class="dash-prog-track" style="margin-top:4px">
                <div class="dash-prog-fill" style="width:<?php echo e(min(round($p->usedCount/$p->usesLimit*100),100)); ?>%;
                     background:<?php echo e($full?'var(--p-danger)':'var(--p-accent)'); ?>"></div>
              </div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--p-hint)"><?php echo e($p->usedCount); ?> / ∞</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;white-space:nowrap">
            <span style="color:<?php echo e($expired?'var(--p-danger)':'var(--p-muted)'); ?>">
              <?php echo e(\Carbon\Carbon::parse($p->expires_at)->format('d.m.Y H:i')); ?>

            </span>
            <?php if($expired): ?>
              <div style="font-size:10px;color:var(--p-danger)">Muddati o'tgan</div>
            <?php endif; ?>
          </td>
          <td>
            <span class="s-pill <?php echo e($p->status && !$expired && !$full ? 'success' : 'muted'); ?>">
              <?php echo e($p->status && !$expired && !$full ? 'Aktiv' : 'Nofaol'); ?>

            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?php echo e(route('panel.promocodes.show', $p)); ?>" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="<?php echo e(route('panel.promocodes.edit', $p)); ?>" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
              <form method="POST" action="<?php echo e(route('panel.promocodes.destroy', $p)); ?>"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-ticket-perforated" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Promokodlar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($promocodes->hasPages()): ?>
  <div class="p-pagination"><?php echo e($promocodes->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/promocodes/index.blade.php ENDPATH**/ ?>