<?php $__env->startSection('title', 'Kantselyariya mahsulotlari'); ?>
<?php $__env->startSection('page-title', 'Kantselyariya'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
  <?php $__currentLoopData = [
    ['pending',  'Kutilmoqda',   'warning', 'hourglass-split'],
    ['approved', 'Tasdiqlangan', 'success', 'check-circle'],
    ['rejected', 'Rad etilgan',  'danger',  'x-circle'],
    ['hidden',   'Yashirilgan',  'muted',   'eye-slash'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key, $lbl, $clr, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3">
      <div style="width:40px;height:40px;border-radius:10px;background:var(--p-<?php echo e($clr); ?>-d ?? var(--p-elevated));display:flex;align-items:center;justify-content:center;color:var(--p-<?php echo e($clr); ?>);font-size:18px;flex-shrink:0">
        <i class="bi bi-<?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text)"><?php echo e(number_format($counts[$key])); ?></div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em"><?php echo e($lbl); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="tab-pills mb-3">
  <?php $__currentLoopData = [
    ['pending','Kutilmoqda','warning'],
    ['approved','Tasdiqlangan','success'],
    ['rejected','Rad etilgan','danger'],
    ['hidden','Yashirilgan','muted'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key,$lbl,$clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$key,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$key?'active':''); ?>">
    <?php echo e($lbl); ?>

    <span class="tab-badge"><?php echo e($counts[$key]); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control" placeholder="ID, nomi, seller ID..."
           value="<?php echo e(request('search')); ?>" style="width:220px">
    <select name="category_id" class="p-form-control" style="width:180px">
      <option value="">Barcha kategoriya</option>
      <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category_id')==$cat->id?'selected':''); ?>>
          <?php echo e($cat->icon); ?> <?php echo e($cat->name_uz); ?>

        </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="<?php echo e(route('panel.stationery.index', ['tab'=>$tab])); ?>" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="<?php echo e(route('panel.stationery.export')); ?>" class="btn-p ghost">
        <i class="bi bi-download"></i> Export
      </a>
    </div>
  </form>
</div>


<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th style="width:50px">#</th>
          <th>Mahsulot</th>
          <th>Kategoriya</th>
          <th>Sotuvchi</th>
          <th>Narx</th>
          <th>Ombor</th>
          <th>Sotildi</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $stationeries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $img = is_array($item->images) ? ($item->images[0] ?? null) : null;
          $approvedClass = match($item->is_approved) {
            1 => 's-pill success', 2 => 's-pill danger', default => 's-pill warning'
          };
          $approvedLabel = match($item->is_approved) {
            1 => 'Tasdiqlangan', 2 => 'Rad etildi', default => 'Kutilmoqda'
          };
        ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent);font-weight:600"><?php echo e($item->id); ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:36px;height:36px;border-radius:8px;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center">
                <?php if($img): ?>
                  <img src="<?php echo e(asset('storage/' . $img)); ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                <?php else: ?>
                  <i class="bi bi-box" style="color:var(--p-hint)"></i>
                <?php endif; ?>
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)"><?php echo e(Str::limit($item->name,30)); ?></div>
                <?php if($item->material): ?>
                  <div style="font-size:11px;color:var(--p-hint)"><?php echo e($item->material); ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:12px;color:var(--p-muted)"><?php echo e($item->category?->name_uz ?? '—'); ?></td>
          <td>
            <a href="<?php echo e(route('panel.sellers.show', $item->seller_id)); ?>"
               style="font-size:12px;color:var(--p-accent)">
              <?php echo e($item->seller?->shop_name ?? $item->seller_id); ?>

            </a>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:13px;font-weight:600;color:var(--p-text)">
            <?php echo e(number_format($item->price)); ?>

            <?php if($item->discount_price): ?>
              <div style="font-size:11px;color:var(--p-danger);text-decoration:line-through"><?php echo e(number_format($item->discount_price)); ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="s-pill <?php echo e($item->stock > 0 ? 'success' : 'danger'); ?>">
              <?php echo e($item->stock); ?> ta
            </span>
          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-muted)"><?php echo e(number_format($item->totalSales)); ?></td>
          <td>
            <div class="d-flex flex-column gap-1">
              <span class="<?php echo e($approvedClass); ?>"><?php echo e($approvedLabel); ?></span>
              <?php if($item->is_hidden): ?>
                <span class="s-pill muted" style="font-size:10px">Yashirin</span>
              <?php endif; ?>
            </div>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap"><?php echo e($item->created_at?->format('d.m.Y')); ?></td>
          <td>
            <div class="d-flex gap-1">
              <?php if($item->is_approved == 0): ?>
                <form method="POST" action="<?php echo e(route('panel.stationery.moderate', $item)); ?>">
                  <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                  <input type="hidden" name="action" value="approve">
                  <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
                </form>
                <form method="POST" action="<?php echo e(route('panel.stationery.moderate', $item)); ?>">
                  <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                  <input type="hidden" name="action" value="reject">
                  <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
                </form>
              <?php endif; ?>
              <a href="<?php echo e(route('panel.stationery.show', $item)); ?>" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="<?php echo e(route('panel.stationery.edit', $item)); ?>" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-box" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Mahsulotlar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($stationeries->hasPages()): ?>
  <div class="p-pagination"><?php echo e($stationeries->links('partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/stationeries/index.blade.php ENDPATH**/ ?>