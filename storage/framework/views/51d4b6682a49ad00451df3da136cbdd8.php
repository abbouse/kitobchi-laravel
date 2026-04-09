<?php $__env->startSection('title', $stationery->name); ?>
<?php $__env->startSection('page-title', $stationery->name); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">

  
  <div class="col-xl-8">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Rasmlar</div></div>
      <div class="dash-card-body">
        <?php $imgs = is_array($stationery->images) ? $stationery->images : []; ?>
        <?php if(count($imgs)): ?>
        <div class="d-flex flex-wrap gap-2">
          <?php $__currentLoopData = $imgs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a href="<?php echo e(asset('storage/' . $img)); ?>" target="_blank"
             style="display:block;width:110px;height:110px;border-radius:10px;overflow:hidden;background:var(--p-elevated)">
            <img src="<?php echo e(asset('storage/' . $img)); ?>" style="width:100%;height:100%;object-fit:cover" alt="">
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
          <p style="color:var(--p-hint)">Rasm yo'q</p>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mahsulot ma'lumotlari</div></div>
      <div class="dash-card-body">
        <div class="row g-3">
          <?php $__currentLoopData = [
            ['ID',          $stationery->id],
            ['Nomi',        $stationery->name],
            ['Kategoriya',  $stationery->category?->name_uz ?? '—'],
            ['Material',    $stationery->material ?? '—'],
            ['Narx',        number_format($stationery->price).' UZS'],
            ['Chegirma',    $stationery->discount_price ? number_format($stationery->discount_price).' UZS' : '—'],
            ['Ombor',       $stationery->stock.' ta'],
            ['Status',      $stationery->status ? 'Aktiv' : 'Nofaol'],
            ['Qo\'shildi',  $stationery->created_at?->format('d.m.Y H:i')],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k, $v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-sm-6">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px"><?php echo e($k); ?></div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)"><?php echo e($v); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">TAVSIF</div>
          <p style="font-size:13px;color:var(--p-muted);line-height:1.7"><?php echo e($stationery->description); ?></p>
        </div>
      </div>
    </div>

    
    <?php if($stationery->variants && $stationery->variants->count()): ?>
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Variantlar (ranglar)</div></div>
      <div class="dash-card-body">
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Rang nomi</th><th>Ombor</th><th>Rasm</th></tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $stationery->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td style="font-family:'DM Mono',monospace;color:var(--p-accent)"><?php echo e($v->id); ?></td>
                <td style="font-weight:500"><?php echo e($v->color_name); ?></td>
                <td><span class="s-pill <?php echo e($v->stock>0?'success':'danger'); ?>"><?php echo e($v->stock); ?> ta</span></td>
                <td>
                  <?php if($v->image_path): ?>
                    <a href="<?php echo e($v->image_path); ?>" target="_blank">
                      <img src="<?php echo e($v->image_path); ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover" alt="">
                    </a>
                  <?php else: ?>
                    <span style="color:var(--p-hint)">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="col-xl-4">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Moderatsiya</div></div>
      <div class="dash-card-body">
        <?php
          $cls = match($stationery->is_approved) { 1=>'success', 2=>'danger', default=>'warning' };
          $lbl = match($stationery->is_approved) { 1=>'Tasdiqlangan', 2=>'Rad etilgan', default=>'Kutilmoqda' };
        ?>
        <div class="mb-3">
          <span class="s-pill <?php echo e($cls); ?>" style="font-size:13px;padding:6px 14px"><?php echo e($lbl); ?></span>
          <?php if($stationery->is_hidden): ?>
            <span class="s-pill muted ms-2">Yashirin</span>
          <?php endif; ?>
        </div>
        <?php if($stationery->is_approved == 0): ?>
        <div class="d-flex gap-2">
          <form method="POST" action="<?php echo e(route('panel.stationery.moderate', $stationery)); ?>" class="flex-fill">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <input type="hidden" name="action" value="approve">
            <button class="btn-p success" style="width:100%"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
          </form>
          <form method="POST" action="<?php echo e(route('panel.stationery.moderate', $stationery)); ?>" class="flex-fill">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <input type="hidden" name="action" value="reject">
            <button class="btn-p danger" style="width:100%"><i class="bi bi-x-lg"></i> Rad etish</button>
          </form>
        </div>
        <?php endif; ?>
        <div class="mt-3">
          <a href="<?php echo e(route('panel.stationery.edit', $stationery)); ?>" class="btn-p ghost" style="width:100%">
            <i class="bi bi-pencil"></i> Tahrirlash
          </a>
        </div>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Sotuvchi</div></div>
      <div class="dash-card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center">
            <?php if($stationery->seller?->photo): ?>
              <img src="<?php echo e(asset('storage/' . $stationery->seller->photo)); ?>" style="width:100%;height:100%;object-fit:cover" alt="">
            <?php else: ?>
              <i class="bi bi-shop" style="color:var(--p-hint)"></i>
            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)"><?php echo e($stationery->seller?->shop_name ?? 'Noma\'lum'); ?></div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($stationery->seller?->phone_number); ?></div>
          </div>
        </div>
        <?php if($stationery->seller): ?>
        <a href="<?php echo e(route('panel.sellers.show', $stationery->seller_id)); ?>" class="btn-p ghost sm">
          Sotuvchini ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Statistika</div></div>
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['Jami sotildi',    $stationery->totalSales,        'success'],
          ['Jami daromad',    number_format($stationery->totalRevenue).' UZS', 'accent'],
          ['Jami mijozlar',   $stationery->totalClients,      'info'],
          ["Bu hafta sotildi", $stationery->totalSalesWeek,   'warning'],
          ['Haftalik daromad', number_format($stationery->totalRevenueWeek).' UZS', 'warning'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl, $val, $clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)"><?php echo e($lbl); ?></span>
          <span style="font-size:13px;font-weight:600;font-family:'DM Mono',monospace;color:var(--p-<?php echo e($clr); ?>)"><?php echo e($val); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/stationeries/show.blade.php ENDPATH**/ ?>