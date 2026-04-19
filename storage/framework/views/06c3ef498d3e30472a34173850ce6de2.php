<?php $__env->startSection('title', 'Sotuvchi buyurtmalari'); ?>
<?php $__env->startSection('page-title', 'Sotuvchi buyurtmalari'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Sotuvchi buyurtmalari <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Sotuvchi bo'limlari orqali kelgan buyurtmalar <?php $__env->endSlot(); ?>
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
     class="tab-pill <?php echo e($tab==$key?'active':''); ?>">
    <?php echo e($s['label']); ?> <span class="tab-badge"><?php echo e($counts[$key] ?? 0); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="filter-bar mb-3">
  <form method="GET" class="flex flex-wrap gap-2 items-center">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <input type="search" name="search" class="p-form-control" placeholder="ID, buyurtma ID, sotuvchi..."
           value="<?php echo e(request('search')); ?>" style="width:220px">
    <input type="date" name="date_from" class="p-form-control" value="<?php echo e(request('date_from')); ?>" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="<?php echo e(request('date_to')); ?>"   style="width:145px">
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="<?php echo e(route('panel.seller-orders.index',['tab'=>$tab])); ?>" class="btn-p ghost">
      <i class="bi bi-x"></i>
    </a>
  </form>
</div>


<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table" style="min-width:800px">
      <thead>
        <tr>
          <th>#</th>
          <th>Asosiy #</th>
          <th>Sotuvchi</th>
          <th>Mijoz</th>
          <th>Kuryer</th>
          <th>Summa</th>
          <th>Yetkazish</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $st = $statuses[$order->status] ?? ['label'=>$order->status,'class'=>'ob-p'];
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#<?php echo e($order->id); ?></td>
          <td>
            <?php if($order->order_id): ?>
            <a href="<?php echo e(route('panel.orders.show', $order->order_id)); ?>"
               style="font-family:'JetBrains Mono',monospace;color:var(--p-info);font-size:12px">
              #<?php echo e($order->order_id); ?>

            </a>
            <?php else: ?> —
            <?php endif; ?>
          </td>
          <td>
            <?php if($order->seller): ?>
            <a href="<?php echo e(route('panel.sellers.show', $order->seller_id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              <?php echo e(Str::limit($order->seller->shop_name ?? $order->seller_id, 20)); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)">ID: <?php echo e($order->seller_id); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($order->client): ?>
            <a href="<?php echo e(route('panel.users.show', $order->client_id)); ?>"
               style="font-size:13px;color:var(--p-text)">
              <?php echo e($order->client->name); ?> <?php echo e($order->client->lastname); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)"><?php echo e($order->client_id ?? '—'); ?></span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--p-muted)">
            <?php echo e($order->courierName ?? ($order->courier ? $order->courier->first_name.' '.$order->courier->last_name : '—')); ?>

          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
            <?php echo e(number_format($order->amount)); ?>

            <span style="font-size:10px;color:var(--p-hint)">UZS</span>
          </td>
          <td style="font-size:11px;color:var(--p-hint);max-width:120px">
            <?php echo e(Str::limit($order->delivery_type ?? '—', 18)); ?>

          </td>
          <td>
            <form method="POST" action="<?php echo e(route('panel.seller-orders.status', $order)); ?>">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <select name="status" class="p-form-control" style="width:150px;font-size:12px;padding:5px 8px"
                      onchange="this.form.submit()">
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($k); ?>" <?php echo e($order->status == $k ? 'selected' : ''); ?>>
                  <?php echo e($s['label']); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </form>
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
            <?php echo e($order->created_at?->format('d.m H:i')); ?>

          </td>
          <td>
            <a href="<?php echo e(route('panel.seller-orders.show', $order)); ?>" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-bag-x" style="font-size:32px;display:block;margin-bottom:8px"></i>
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
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/seller-orders/index.blade.php ENDPATH**/ ?>