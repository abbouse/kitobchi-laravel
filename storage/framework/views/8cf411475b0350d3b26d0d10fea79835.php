<?php $__env->startSection('title', 'Kuryer buyurtmasi #'.$courierOrder->id); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmasi #'.$courierOrder->id); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">

  <div class="col-xl-8">

    
    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Mahsulotlar</div>
        <div class="dash-card-sub"><?php echo e($courierOrder->items?->count() ?? 0); ?> ta</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr><th>Mahsulot ID</th><th>Tur</th><th>Miqdor</th><th>Narx</th><th>Sotuvchi</th></tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $courierOrder->items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td style="font-family:'DM Mono',monospace;color:var(--p-accent)"><?php echo e($item->product_id); ?></td>
                <td>
                  <span class="s-pill <?php echo e($item->type==='book'?'accent':'info'); ?>">
                    <?php echo e($item->type ?? 'book'); ?>

                  </span>
                </td>
                <td style="font-family:'DM Mono',monospace"><?php echo e($item->quantity); ?></td>
                <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-text)">
                  <?php echo e(number_format($item->price)); ?> UZS
                </td>
                <td>
                  <?php if($item->seller_id): ?>
                  <a href="<?php echo e(route('panel.sellers.show', $item->seller_id)); ?>"
                     style="font-size:12px;color:var(--p-accent)">
                    #<?php echo e($item->seller_id); ?>

                  </a>
                  <?php else: ?> —
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">Ma'lumot yo'q</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Moliyaviy tafsilot</div></div>
      <div class="dash-card-body">
        <div class="row g-3">
          <?php $__currentLoopData = [
            ['Buyurtma summasi',  number_format($courierOrder->amount).' UZS',        'text',    'bi-cash'],
            ['Kuryer haqi',       number_format($courierOrder->courierPrice).' UZS',   'success', 'bi-person-check'],
            ['Kuryer bonusi',     $courierOrder->courierBonus > 0 ? '+'.number_format($courierOrder->courierBonus).' UZS' : '—', 'warning', 'bi-gift'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl, $val, $clr, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-sm-4">
            <div style="background:var(--p-elevated);border-radius:10px;padding:16px;text-align:center">
              <i class="bi <?php echo e($icon); ?>" style="font-size:22px;color:var(--p-<?php echo e($clr); ?>);margin-bottom:8px;display:block"></i>
              <div style="font-size:18px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-<?php echo e($clr); ?>)"><?php echo e($val); ?></div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px"><?php echo e($lbl); ?></div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>

  </div>

  <div class="col-xl-4">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Status</div></div>
      <div class="dash-card-body">
        <?php $st = $statuses[$courierOrder->status] ?? ['label'=>$courierOrder->status,'class'=>'ob-p']; ?>
        <div class="mb-3">
          <span class="o-badge <?php echo e($st['class']); ?>" style="font-size:13px;padding:6px 14px"><?php echo e($st['label']); ?></span>
        </div>
        <form method="POST" action="<?php echo e(route('panel.courier-orders.status', $courierOrder)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <label class="p-label">Statusni o'zgartirish</label>
          <div class="d-flex gap-2 mt-1">
            <select name="status" class="p-form-control flex-fill">
              <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($k); ?>" <?php echo e($courierOrder->status === $k ? 'selected' : ''); ?>><?php echo e($s['label']); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Kuryer</div></div>
      <div class="dash-card-body">
        <?php if($courierOrder->courier): ?>
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;background:var(--p-elevated);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
            <?php if($courierOrder->courier->photo): ?>
              <img src="<?php echo e($courierOrder->courier->photo); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <i class="bi bi-bicycle" style="color:var(--p-hint)"></i>
            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($courierOrder->courier->first_name); ?> <?php echo e($courierOrder->courier->last_name); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($courierOrder->courier->phone_number); ?></div>
            <div style="font-size:11px;color:var(--p-hint)"><?php echo e($courierOrder->courier->region); ?></div>
          </div>
        </div>
        <a href="<?php echo e(route('panel.couriers.show', $courierOrder->courier_id)); ?>" class="btn-p ghost sm">
          Kuryerni ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        <?php else: ?>
        <form method="POST" action="<?php echo e(route('panel.courier-orders.assign', $courierOrder)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <label class="p-label">Kuryer tayinlash</label>
          <div class="d-flex gap-2 mt-1">
            <select name="courier_id" class="p-form-control flex-fill">
              <option value="">Kuryer tanlang</option>
              <?php $__currentLoopData = \App\Models\Courier::where('status',1)->orderBy('first_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($c->id); ?>"><?php echo e($c->first_name); ?> <?php echo e($c->last_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mijoz</div></div>
      <div class="dash-card-body">
        <?php if($courierOrder->user): ?>
        <div class="d-flex align-items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;overflow:hidden">
            <?php if($courierOrder->user->avatar): ?>
              <img src="<?php echo e($courierOrder->user->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($courierOrder->user->name??'U',0,1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($courierOrder->user->name); ?> <?php echo e($courierOrder->user->lastname); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($courierOrder->user->phone_number); ?></div>
          </div>
        </div>
        <a href="<?php echo e(route('panel.users.show', $courierOrder->user_id)); ?>" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        <?php else: ?>
          <span style="color:var(--p-hint)">ID: <?php echo e($courierOrder->user_id ?? '—'); ?></span>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['ID',         '#'.$courierOrder->id],
          ['Buyurtma #', $courierOrder->order_id ? '#'.$courierOrder->order_id : '—'],
          ['Yaratildi',  $courierOrder->created_at?->format('d.m.Y H:i')],
          ['Yangilandi', $courierOrder->updated_at?->format('d.m.Y H:i')],
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
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/courier-orders/show.blade.php ENDPATH**/ ?>