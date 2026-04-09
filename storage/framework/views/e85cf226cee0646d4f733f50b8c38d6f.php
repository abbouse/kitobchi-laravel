<?php $__env->startSection('title', 'Kuryer buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Kuryer buyurtmalari'); ?>

<?php $__env->startSection('content'); ?>


<div class="tab-pills mb-3">
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
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control" placeholder="ID, buyurtma, kuryer..."
           value="<?php echo e(request('search')); ?>" style="width:200px">
    <select name="courier_id" class="p-form-control" style="width:180px">
      <option value="">Barcha kuryerlar</option>
      <?php $__currentLoopData = $couriers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($c->id); ?>" <?php echo e(request('courier_id')==$c->id?'selected':''); ?>>
        <?php echo e($c->first_name); ?> <?php echo e($c->last_name); ?>

      </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <input type="date" name="date_from" class="p-form-control" value="<?php echo e(request('date_from')); ?>" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="<?php echo e(request('date_to')); ?>"   style="width:145px">
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="<?php echo e(route('panel.courier-orders.index',['tab'=>$tab])); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>


<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table" style="min-width:780px">
      <thead>
        <tr>
          <th>#</th>
          <th>Asosiy #</th>
          <th>Kuryer</th>
          <th>Mijoz</th>
          <th>Summa</th>
          <th>Kuryer haq</th>
          <th>Bonus</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $st = $statuses[$order->status] ?? ['label'=>$order->status,'class'=>'ob-p']; ?>
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent);font-weight:600">#<?php echo e($order->id); ?></td>
          <td>
            <?php if($order->order_id): ?>
            <a href="<?php echo e(route('panel.orders.show', $order->order_id)); ?>"
               style="font-family:'DM Mono',monospace;color:var(--p-info);font-size:12px">
              #<?php echo e($order->order_id); ?>

            </a>
            <?php else: ?> —
            <?php endif; ?>
          </td>
          <td>
            <?php if($order->courier): ?>
            <a href="<?php echo e(route('panel.couriers.show', $order->courier_id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              <?php echo e($order->courier->first_name); ?> <?php echo e($order->courier->last_name); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)"><?php echo e($order->courier_id ?? '—'); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($order->user): ?>
            <a href="<?php echo e(route('panel.users.show', $order->user_id)); ?>"
               style="font-size:13px;color:var(--p-text)">
              <?php echo e($order->user->name); ?> <?php echo e($order->user->lastname); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)"><?php echo e($order->user_id ?? '—'); ?></span>
            <?php endif; ?>
          </td>
          <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--p-text)">
            <?php echo e(number_format($order->amount)); ?>

          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-success)">
            <?php echo e(number_format($order->courierPrice)); ?>

          </td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--p-warning)">
            <?php echo e($order->courierBonus > 0 ? '+'.number_format($order->courierBonus) : '—'); ?>

          </td>
          <td><span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span></td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap"><?php echo e($order->created_at?->format('d.m H:i')); ?></td>
          <td>
            <a href="<?php echo e(route('panel.courier-orders.show', $order)); ?>" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-bicycle" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Buyurtmalar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($orders->hasPages()): ?>
  <div class="p-pagination"><?php echo e($orders->links('panel.partials.pagination')); ?></div>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/courier-orders/index.blade.php ENDPATH**/ ?>