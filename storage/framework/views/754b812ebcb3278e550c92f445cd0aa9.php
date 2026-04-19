<?php $__env->startSection('title', 'Tranzaksiyalar'); ?>
<?php $__env->startSection('page-title', 'Tranzaksiyalar'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Tranzaksiyalar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Yechib olish arizalari <?php $__env->endSlot(); ?>
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



<div class="flex gap-2 mb-4 fade-up">
  <a href="<?php echo e(request()->fullUrlWithQuery(['segment'=>'seller','tab'=>'pending','page'=>1])); ?>"
     class="btn-p <?php echo e($segment==='seller'?'primary':'ghost'); ?>"
     style="gap:7px">
    <i class="bi bi-shop-window"></i> Seller
    <?php if($segment==='courier' && $otherPending > 0): ?>
      <span class="nav-badge warning" style="position:relative;inset:auto"><?php echo e($otherPending); ?></span>
    <?php elseif($segment==='seller' && $counts['pending'] > 0): ?>
      <span style="background:rgba(255,255,255,.2);border-radius:10px;
                   padding:1px 7px;font-size:11px;font-weight:600">
        <?php echo e($counts['pending']); ?>

      </span>
    <?php endif; ?>
  </a>
  <a href="<?php echo e(request()->fullUrlWithQuery(['segment'=>'courier','tab'=>'pending','page'=>1])); ?>"
     class="btn-p <?php echo e($segment==='courier'?'primary':'ghost'); ?>"
     style="gap:7px">
    <i class="bi bi-bicycle"></i> Kuryer
    <?php if($segment==='seller' && $otherPending > 0): ?>
      <span class="nav-badge warning" style="position:relative;inset:auto"><?php echo e($otherPending); ?></span>
    <?php elseif($segment==='courier' && $counts['pending'] > 0): ?>
      <span style="background:rgba(255,255,255,.2);border-radius:10px;
                   padding:1px 7px;font-size:11px;font-weight:600">
        <?php echo e($counts['pending']); ?>

      </span>
    <?php endif; ?>
  </a>
</div>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3 fade-up">
  <?php $__currentLoopData = [
    ['pending',  'Kutilmoqda',    'warning', 'bi-hourglass-split', 'pending_amount'],
    ['approved', 'Tasdiqlangan',  'success', 'bi-check-circle',    'approved_amount'],
    ['rejected', 'Rad etilgan',   'danger',  'bi-x-circle',        null],
    ['all',      'Jami',          'muted',   'bi-list-ul',         null],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key, $lbl, $clr, $icon, $amountKey]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:38px;height:38px;border-radius:9px;flex-shrink:0;font-size:17px;
                  background:var(--p-<?php echo e($clr); ?>-d,var(--p-elevated));
                  color:var(--p-<?php echo e($clr); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($icon); ?>"></i>
      </div>
      <div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          <?php echo e($lbl); ?>

        </div>
        <div style="font-size:18px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-<?php echo e($clr); ?>)">
          <?php echo e($counts[$key]); ?>

        </div>
        <?php if($amountKey): ?>
        <div style="font-size:10px;color:var(--p-hint)">
          <?php echo e(number_format($stats[$amountKey]/1000)); ?>K UZS
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'pending'  => ['Kutilmoqda',   $counts['pending']],
    'approved' => ['Tasdiqlangan', $counts['approved']],
    'rejected' => ['Rad etilgan',  $counts['rejected']],
    'all'      => ['Barchasi',     $counts['all']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$lbl, $cnt]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$key,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$key?'active':''); ?>">
    <?php echo e($lbl); ?> <span class="tab-count"><?php echo e($cnt); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="segment" value="<?php echo e($segment); ?>">
  <input type="hidden" name="tab"     value="<?php echo e($tab); ?>">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search"
           placeholder="<?php echo e($segment==='courier'?'Kuryer ismi, telefon...':'Do\'kon nomi, telefon...'); ?>"
           value="<?php echo e(request('search')); ?>">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
  <?php if(request('search')): ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['search'=>null])); ?>"
       class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
  <?php endif; ?>
</form>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo e($segment==='courier'?'Kuryer':'Sotuvchi'); ?></th>
          <th>Karta</th>
          <th style="text-align:right">Miqdor</th>
          <th>Komissiya</th>
          <th style="text-align:right">Toza summa</th>
          <th>Status</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $clr = match($tx->status){
            'approved'=>'success','rejected'=>'danger',default=>'warning'
          };
          // Segment ga mos entity
          $entity     = $segment==='courier' ? $tx->courier : $tx->seller;
          $entityName = $segment==='courier'
            ? (($entity->first_name??'').' '.($entity->last_name??''))
            : ($entity->shop_name ?? '—');
          $entityRoute = $segment==='courier'
            ? route('panel.couriers.show', $tx->courier_id ?? 0)
            : route('panel.sellers.show',  $tx->seller_id  ?? 0);
          $entityPhoto = $entity->photo ?? null;
          $entityPhone = $entity->phone_number ?? '—';
          $entityBal   = $entity->balance ?? 0;
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($tx->id); ?>

          </td>

          <td>
            <?php if($entity): ?>
            <div class="flex items-center gap-2">
              <div style="width:30px;height:30px;border-radius:<?php echo e($segment==='courier'?'50%':'8px'); ?>;
                          overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,
                            <?php echo e($segment==='courier'?'var(--p-info),#0ea5e9':'var(--p-warning),#f97316'); ?>);
                          display:flex;align-items:center;justify-content:center;
                          font-size:12px;font-weight:700;color:#fff">
                <?php if($entityPhoto): ?>
                  <img src="<?php echo e(asset('storage/'.$entityPhoto)); ?>"
                       style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?php echo e(strtoupper(substr($entityName,0,1))); ?>

                <?php endif; ?>
              </div>
              <div>
                <a href="<?php echo e($entityRoute); ?>"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  <?php echo e($entityName); ?>

                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  Balans: <?php echo e(number_format($entityBal)); ?> UZS
                </div>
              </div>
            </div>
            <?php else: ?>
              <span style="color:var(--p-hint);font-size:12px">
                #<?php echo e($segment==='courier' ? $tx->courier_id : $tx->seller_id); ?>

              </span>
            <?php endif; ?>
          </td>

          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            <?php echo e($tx->card ? '****'.substr($tx->card,-4) : '—'); ?>

          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:600;color:var(--p-text);font-size:13px">
            <?php echo e(number_format($tx->amount)); ?>

          </td>

          <td>
            <?php if($tx->commissionPercent): ?>
            <span class="s-pill danger" style="font-size:10px">
              <?php echo e($tx->commissionPercent); ?>%
              <?php if($tx->commissionPrice): ?>
                <span style="opacity:.7">· <?php echo e(number_format($tx->commissionPrice)); ?></span>
              <?php endif; ?>
            </span>
            <?php else: ?>
              <span style="color:var(--p-hint);font-size:12px">—</span>
            <?php endif; ?>
          </td>

          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                     font-weight:700;color:var(--p-success);font-size:13px">
            <?php echo e(number_format($tx->netAmount ?? $tx->amount)); ?>

          </td>

          <td>
            <span class="s-pill <?php echo e($clr); ?>" style="font-size:10px">
              <?php echo e(match($tx->status){
                'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda'
              }); ?>

            </span>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            <?php echo e($tx->created_at?->format('d.m.Y H:i')); ?>

          </td>

          <td>
            <div class="flex gap-1 items-center">

              
              <?php if($tx->status === 'pending'): ?>
              <form method="POST"
                    action="<?php echo e(route('panel.seller-transactions.approve', $tx->id)); ?>"
                    onsubmit="return confirm('Tasdiqlashni xohlaysizmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="segment" value="<?php echo e($segment); ?>">
                <button class="btn-p success sm" title="Tasdiqlash">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              <?php endif; ?>

              
              <?php if($tx->status !== 'rejected'): ?>
              <button class="btn-p danger sm"
                      onclick="openReject(
                        <?php echo e($tx->id); ?>,
                        '<?php echo e(addslashes($entityName)); ?>',
                        <?php echo e($tx->amount); ?>,
                        '<?php echo e($tx->status); ?>',
                        '<?php echo e($segment); ?>'
                      )" title="Rad etish">
                <i class="bi bi-x-lg"></i>
              </button>
              <?php endif; ?>

              
              <a href="<?php echo e(route('panel.seller-transactions.show', $tx->id)); ?>?segment=<?php echo e($segment); ?>"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:30px;display:block;margin-bottom:8px"></i>
            Tranzaksiyalar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($transactions->hasPages()): ?>
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($transactions->firstItem()); ?>–<?php echo e($transactions->lastItem()); ?>

      / <?php echo e($transactions->total()); ?>

    </div>
    <?php echo e($transactions->links('panel.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>


<div id="reject-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
            z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border-radius:14px;padding:24px;
              width:100%;max-width:460px;border:1px solid var(--p-border);
              box-shadow:0 20px 60px rgba(0,0,0,.4)">
    <div class="flex items-start justify-between mb-3">
      <div>
        <div style="font-size:16px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-x-circle-fill mr-1" style="color:var(--p-danger)"></i>
          Rad etish
        </div>
        <div id="reject-subtitle" style="font-size:12px;color:var(--p-hint);margin-top:3px"></div>
      </div>
      <button onclick="closeReject()"
              style="background:none;border:none;cursor:pointer;
                     color:var(--p-muted);font-size:20px;line-height:1">×</button>
    </div>

    <div id="reject-warn-approved"
         style="display:none;padding:10px 12px;background:var(--p-warning-d);
                border-radius:8px;border:1px solid rgba(245,166,35,.2);
                margin-bottom:14px;font-size:12px;color:var(--p-warning)">
      <i class="bi bi-exclamation-triangle-fill mr-1"></i>
      Bu tranzaksiya oldin <strong>tasdiqlangan</strong> edi.
      Rad etilsa <strong><span id="reject-amount"></span> UZS qaytariladi</strong>.
    </div>

    <form id="reject-form" method="POST">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <input type="hidden" name="segment" id="reject-segment" value="seller">
      <label class="p-form-label">
        Rad etish sababi <span style="color:var(--p-danger)">*</span>
      </label>
      <textarea name="rejected_desc" class="p-form-control" rows="3" required
                style="margin-bottom:14px"
                placeholder="Karta ma'lumotlari noto'g'ri, hujjat taqdim etilmadi...">
      </textarea>
      <div class="flex gap-2 justify-end">
        <button type="button" onclick="closeReject()" class="btn-p ghost">Bekor</button>
        <button type="submit" class="btn-p danger">
          <i class="bi bi-x-circle"></i> Rad etish
        </button>
      </div>
    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function openReject(id, name, amount, status, segment) {
  document.getElementById('reject-form').action =
    `/panel/seller-transactions/${id}/reject`;
  document.getElementById('reject-segment').value  = segment;
  document.getElementById('reject-subtitle').textContent =
    name + ' · ' + amount.toLocaleString() + ' UZS';
  document.getElementById('reject-amount').textContent =
    amount.toLocaleString();

  document.getElementById('reject-warn-approved').style.display =
    status === 'approved' ? 'block' : 'none';

  const modal = document.getElementById('reject-modal');
  modal.style.display = 'flex';
  modal.querySelector('textarea').value = '';
  setTimeout(() => modal.querySelector('textarea').focus(), 80);
}

function closeReject() {
  document.getElementById('reject-modal').style.display = 'none';
}

document.getElementById('reject-modal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeReject();
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeReject();
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/seller-transactions/index.blade.php ENDPATH**/ ?>