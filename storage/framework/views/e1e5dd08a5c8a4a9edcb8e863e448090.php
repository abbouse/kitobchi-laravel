<?php $__env->startSection('title', 'Sotuvchi buyurtmasi #'.$sellerOrder->id); ?>
<?php $__env->startSection('page-title', 'Buyurtma #'.$sellerOrder->id); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.seller-orders.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.seller-orders.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Buyurtma #<?php echo e($sellerOrder->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e($sellerOrder->created_at?->format('d.m.Y H:i')); ?> <?php $__env->endSlot(); ?>
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

  
  <div class="xl:col-span-8">

    
    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Buyurtma mahsulotlari</div>
        <div class="dash-card-sub"><?php echo e($sellerOrder->items?->count() ?? 0); ?> ta pozitsiya</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>Mahsulot</th><th>Tur</th><th>Narx</th><th>Miqdor</th><th>Jami</th></tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $sellerOrder->items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td>
                  <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                    <?php echo e($item->product?->name ?? 'ID: '.$item->product_id); ?>

                  </div>
                  <?php if($item->variant_id): ?>
                    <div style="font-size:11px;color:var(--p-hint)">Variant #<?php echo e($item->variant_id); ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="s-pill <?php echo e($item->type==='book'?'accent':'info'); ?>">
                    <?php echo e($item->type === 'book' ? 'Kitob' : 'Kantselyariya'); ?>

                  </span>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--p-muted)">
                  <?php echo e(number_format($item->price)); ?> UZS
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px"><?php echo e($item->quantity); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                  <?php echo e(number_format($item->price * $item->quantity)); ?> UZS
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--p-hint)">Mahsulotlar yo'q</td></tr>
              <?php endif; ?>
            </tbody>
            <?php if($sellerOrder->items?->count()): ?>
            <tfoot>
              <tr style="border-top:2px solid var(--p-border2)">
                <td colspan="4" style="text-align:right;font-weight:600;color:var(--p-muted);font-size:13px">Jami:</td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;color:var(--p-success)">
                  <?php echo e(number_format($sellerOrder->amount)); ?> UZS
                </td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    
    <?php if($sellerOrder->address): ?>
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Yetkazish manzili</div></div>
      <div class="dash-card-body">
        <?php $addr = is_array($sellerOrder->address) ? $sellerOrder->address : json_decode($sellerOrder->address, true); ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php $__currentLoopData = [
            ['To\'liq manzil', data_get($addr,'fullAddress') ?? data_get($addr,'address')],
            ['Viloyat/Shahar', data_get($addr,'city') ?? data_get($addr,'region')],
            ['Koordinat',     data_get($addr,'lat') && data_get($addr,'lon') ? data_get($addr,'lat').', '.data_get($addr,'lon') : null],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if($v): ?>
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px"><?php echo e($k); ?></div>
            <div style="font-size:13px;color:var(--p-text)"><?php echo e($v); ?></div>
          </div>
          <?php endif; ?>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="xl:col-span-4">

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Status</div></div>
      <div class="dash-card-body">
        <?php $st = $statuses[$sellerOrder->status] ?? ['label'=>$sellerOrder->status,'class'=>'ob-p']; ?>
        <div class="mb-3">
          <span class="o-badge <?php echo e($st['class']); ?>" style="font-size:13px;padding:6px 14px">
            <?php echo e($st['label']); ?>

          </span>
        </div>
        <form method="POST" action="<?php echo e(route('panel.seller-orders.status', $sellerOrder)); ?>">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <label class="p-form-label">Statusni o'zgartirish</label>
          <div class="flex gap-2 mt-1">
            <select name="status" class="p-form-control flex-fill">
              <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($k); ?>" <?php echo e($sellerOrder->status == $k ? 'selected' : ''); ?>>
                <?php echo e($s['label']); ?>

              </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Sotuvchi</div></div>
      <div class="dash-card-body">
        <?php if($sellerOrder->seller): ?>
        <div class="flex items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:var(--p-elevated);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
            <?php if($sellerOrder->seller->photo): ?>
              <img src="<?php echo e($sellerOrder->seller->photo); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <i class="bi bi-shop" style="color:var(--p-hint)"></i>
            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)"><?php echo e($sellerOrder->seller->shop_name); ?></div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($sellerOrder->seller->phone_number); ?></div>
          </div>
        </div>
        <a href="<?php echo e(route('panel.sellers.show', $sellerOrder->seller_id)); ?>" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        <?php else: ?>
          <span style="color:var(--p-hint)">ID: <?php echo e($sellerOrder->seller_id); ?></span>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Mijoz</div></div>
      <div class="dash-card-body">
        <?php if($sellerOrder->client): ?>
        <div class="flex items-center gap-3">
          <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden">
            <?php if($sellerOrder->client->avatar): ?>
              <img src="<?php echo e($sellerOrder->client->avatar); ?>" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($sellerOrder->client->name ?? 'U', 0, 1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($sellerOrder->client->name); ?> <?php echo e($sellerOrder->client->lastname); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint)"><?php echo e($sellerOrder->client->phone_number); ?></div>
          </div>
        </div>
        <a href="<?php echo e(route('panel.users.show', $sellerOrder->client_id)); ?>" class="btn-p ghost sm mt-3">
          Ko'rish <i class="bi bi-arrow-right"></i>
        </a>
        <?php else: ?>
          <span style="color:var(--p-hint)">ID: <?php echo e($sellerOrder->client_id ?? '—'); ?></span>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Kuryer</div></div>
      <div class="dash-card-body">
        <?php if($sellerOrder->courier): ?>
          <div style="font-size:14px;font-weight:600;color:var(--p-text)">
            <?php echo e($sellerOrder->courier->first_name); ?> <?php echo e($sellerOrder->courier->last_name); ?>

          </div>
          <div style="font-size:12px;color:var(--p-hint)"><?php echo e($sellerOrder->courier->phone_number); ?></div>
        <?php elseif($sellerOrder->courierName): ?>
          <div style="font-size:13px;color:var(--p-text)"><?php echo e($sellerOrder->courierName); ?></div>
        <?php else: ?>
          <span style="color:var(--p-hint)">Tayinlanmagan</span>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="p-card">
      <div class="dash-card-body">
        <?php $__currentLoopData = [
          ['Buyurtma #',  '#'.$sellerOrder->id],
          ['Asosiy #',    $sellerOrder->order_id ? '#'.$sellerOrder->order_id : '—'],
          ['Yetkazish',   $sellerOrder->delivery_type ?? '—'],
          ['Jami summa',  number_format($sellerOrder->amount).' UZS'],
          ['Sana',        $sellerOrder->created_at?->format('d.m.Y H:i')],
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
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/seller-orders/show.blade.php ENDPATH**/ ?>