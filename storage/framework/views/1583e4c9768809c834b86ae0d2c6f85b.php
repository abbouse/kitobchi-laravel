<?php $__env->startSection('title', 'Tranzaksiya #'.$tx->id); ?>
<?php $__env->startSection('page-title', 'Tranzaksiya #'.$tx->id); ?>

<?php $__env->startSection('content'); ?>

<?php
  $isCourier = $segment === 'courier';
  $entity    = $isCourier ? $tx->courier : $tx->seller;
  $entityName = $isCourier
    ? (($entity->first_name??'').' '.($entity->last_name??''))
    : ($entity->shop_name ?? '—');
  $entityRoute = $isCourier
    ? route('panel.couriers.show', $tx->courier_id ?? 0)
    : route('panel.sellers.show',  $tx->seller_id  ?? 0);
  $entityBalance = $entity->balance ?? 0;
  $stCls = match($tx->status){'approved'=>'success','rejected'=>'danger',default=>'warning'};
  $stLbl = match($tx->status){'approved'=>'Tasdiqlangan','rejected'=>'Rad etildi',default=>'Kutilmoqda'};
?>


<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.seller-transactions.index')).'?segment='.e($segment).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.seller-transactions.index')).'?segment='.e($segment).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Tranzaksiya #<?php echo e($tx->id); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <div class="flex gap-2">
        <?php if($tx->status === 'pending'): ?>
        <form method="POST"
              action="<?php echo e(route('panel.seller-transactions.approve', $tx->id)); ?>"
              onsubmit="return confirm('Tasdiqlashni xohlaysizmi?\n<?php echo e(number_format($tx->amount)); ?> UZS yechildi.')">
          <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
          <input type="hidden" name="segment" value="<?php echo e($segment); ?>">
          <button class="btn-p success">
            <i class="bi bi-check-lg"></i> Tasdiqlash
          </button>
        </form>
        <?php endif; ?>
    
        <?php if($tx->status !== 'rejected'): ?>
        <button class="btn-p danger"
                onclick="openReject(<?php echo e($tx->id); ?>,
                  '<?php echo e(addslashes($entityName)); ?>',
                  <?php echo e($tx->amount); ?>,
                  '<?php echo e($tx->status); ?>',
                  '<?php echo e($segment); ?>')">
          <i class="bi bi-x-lg"></i>
          <?php echo e($tx->status==='approved' ? 'Bekor qilish (qaytarish)' : 'Rad etish'); ?>

        </button>
        <?php endif; ?>
      </div>
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


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  
  <div class="xl:col-span-4">

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <?php echo e($isCourier ? 'Kuryer' : 'Sotuvchi'); ?>

        </div>
      </div>
      <div style="padding:14px 18px">
        <?php if($entity): ?>
        <div class="flex items-center gap-3 mb-3">
          <div style="width:46px;height:46px;
                      border-radius:<?php echo e($isCourier?'50%':'10px'); ?>;
                      overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,
                        <?php echo e($isCourier?'var(--p-info),#0ea5e9':'var(--p-warning),#f97316'); ?>);
                      display:flex;align-items:center;justify-content:center;
                      font-size:18px;font-weight:700;color:#fff">
            <?php if($entity->photo): ?>
              <img src="<?php echo e(asset('storage/'.$entity->photo)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($entityName,0,1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              <?php echo e($entityName); ?>

            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($entity->phone_number ?? '—'); ?>

            </div>
          </div>
        </div>
        <a href="<?php echo e($entityRoute); ?>"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-<?php echo e($isCourier?'bicycle':'shop-window'); ?>"></i>
          <?php echo e($isCourier?'Kuryer profiliga o\'tish':'Seller profiliga o\'tish'); ?>

        </a>
        <?php else: ?>
          <div style="color:var(--p-hint)">
            #<?php echo e($isCourier ? $tx->courier_id : $tx->seller_id); ?>

          </div>
        <?php endif; ?>
      </div>
    </div>

    
    <?php if($entity): ?>
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Hozirgi holat</div></div>
      <div style="padding:14px 18px">
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">Joriy balans</span>
          <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-text)">
            <?php echo e(number_format($entityBalance)); ?> UZS
          </span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0">
          <span style="font-size:12px;color:var(--p-hint)">So'ralgan miqdor</span>
          <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-warning)">
            <?php echo e(number_format($tx->amount)); ?> UZS
          </span>
        </div>
        <?php if($tx->status === 'pending'): ?>
        <div style="padding:10px 12px;background:var(--p-warning-d);border-radius:8px;
                    border:1px solid rgba(245,166,35,.2);font-size:12px;color:var(--p-warning);
                    display:flex;gap:7px;margin-top:4px">
          <i class="bi bi-info-circle" style="flex-shrink:0;margin-top:1px"></i>
          <span>Tasdiqlansa balansdan
            <strong><?php echo e(number_format($tx->amount)); ?> UZS</strong> yechildi.</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($tx->status === 'rejected' && $tx->rejected_desc): ?>
    <div class="p-card fade-up"
         style="background:var(--p-danger-d);border-color:rgba(255,92,106,.2)">
      <div class="p-card-header">
        <div class="p-card-title" style="color:var(--p-danger)">
          <i class="bi bi-x-circle-fill mr-1"></i> Rad etish sababi
        </div>
      </div>
      <div style="padding:0 18px 14px;font-size:13px;color:var(--p-muted);line-height:1.6">
        <?php echo e($tx->rejected_desc); ?>

      </div>
    </div>
    <?php endif; ?>

  </div>

  
  <div class="xl:col-span-8">

    
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Tranzaksiya tafsilotlari</div>
        <span class="s-pill <?php echo e($stCls); ?>"><?php echo e($stLbl); ?></span>
      </div>
      <div style="padding:0 18px 18px">

        <div style="background:var(--p-elevated);border-radius:12px;
                    padding:20px;margin-bottom:18px;
                    display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center">
          <div style="padding:10px 0;border-right:1px solid var(--p-border)">
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em;margin-bottom:6px">So'ralgan miqdor</div>
            <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;
                        color:var(--p-text)">
              <?php echo e(number_format($tx->amount)); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint);margin-top:2px">UZS</div>
          </div>

          <div style="padding:10px 0;border-right:1px solid var(--p-border)">
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em;margin-bottom:6px">Komissiya</div>
            <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;
                        color:var(--p-danger)">
              <?php echo e($tx->commissionPercent ?? 0); ?>%
            </div>
            <div style="font-size:10px;color:var(--p-hint);margin-top:2px">
              <?php echo e($tx->commissionPrice ? number_format($tx->commissionPrice).' UZS' : '—'); ?>

            </div>
          </div>

          <div style="padding:10px 0">
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em;margin-bottom:6px">Toza summa</div>
            <div style="font-size:22px;font-weight:700;font-family:'JetBrains Mono',monospace;
                        color:var(--p-success)">
              <?php echo e(number_format($tx->netAmount ?? $tx->amount)); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint);margin-top:2px">UZS</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Karta raqami</div>
            <div style="font-size:14px;font-weight:600;font-family:'JetBrains Mono',monospace;
                        color:var(--p-text);letter-spacing:.08em">
              <?php if($tx->card): ?>
                <?php echo e(implode(' ', str_split(
                  str_pad('', max(0,strlen($tx->card)-4),'•') . substr($tx->card,-4),
                  4
                ))); ?>

              <?php else: ?>
                <span style="color:var(--p-hint)">Ko'rsatilmagan</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="">
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Ariza vaqti</div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">
              <?php echo e($tx->created_at?->format('d.m.Y H:i:s')); ?>

            </div>
            <div style="font-size:11px;color:var(--p-hint)">
              <?php echo e($tx->created_at?->diffForHumans()); ?>

            </div>
          </div>

          <?php if($tx->updated_at && $tx->status !== 'pending'): ?>
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Ko'rib chiqildi</div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">
              <?php echo e($tx->updated_at?->format('d.m.Y H:i:s')); ?>

            </div>
            <div style="font-size:11px;color:var(--p-hint)">
              <?php echo e($tx->updated_at?->diffForHumans()); ?>

            </div>
          </div>
          <?php endif; ?>

          <div class="">
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">ID</div>
            <div style="font-size:14px;font-weight:600;font-family:'JetBrains Mono',monospace;
                        color:var(--p-accent)">
              #<?php echo e($tx->id); ?>

            </div>
          </div>
        </div>
      </div>
    </div>

    
    <?php if($tx->status === 'pending'): ?>
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Qaror qabul qilish</div>
      </div>
      <div style="padding:0 18px 18px">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <div style="background:var(--p-success-d);border:1px solid rgba(34,201,142,.2);
                        border-radius:12px;padding:18px">
              <div style="font-size:14px;font-weight:600;color:var(--p-success);margin-bottom:6px">
                <i class="bi bi-check-circle-fill mr-1"></i> Tasdiqlash
              </div>
              <div style="font-size:12px;color:var(--p-muted);margin-bottom:14px;line-height:1.6">
                Balansdan <strong style="color:var(--p-text)"><?php echo e(number_format($tx->amount)); ?> UZS</strong>
                yechildi. Toza:
                <strong style="color:var(--p-success)">
                  <?php echo e(number_format($tx->netAmount ?? $tx->amount)); ?> UZS
                </strong>
              </div>
              <form method="POST"
                    action="<?php echo e(route('panel.seller-transactions.approve', $tx->id)); ?>"
                    onsubmit="return confirm('Tasdiqlansinmi?')">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="segment" value="<?php echo e($segment); ?>">
                <button class="btn-p success" style="width:100%;justify-content:center">
                  <i class="bi bi-check-lg"></i> Tasdiqlash
                </button>
              </form>
            </div>
          </div>
          <div class="">
            <div style="background:var(--p-danger-d);border:1px solid rgba(255,92,106,.2);
                        border-radius:12px;padding:18px">
              <div style="font-size:14px;font-weight:600;color:var(--p-danger);margin-bottom:6px">
                <i class="bi bi-x-circle-fill mr-1"></i> Rad etish
              </div>
              <div style="font-size:12px;color:var(--p-muted);margin-bottom:14px;line-height:1.6">
                Balans <strong style="color:var(--p-text)">o'zgarmaydi</strong>.
                Rad etish sababi ko'rsatish shart.
              </div>
              <button class="btn-p danger" style="width:100%;justify-content:center"
                      onclick="openReject(<?php echo e($tx->id); ?>,'<?php echo e(addslashes($entityName)); ?>',
                               <?php echo e($tx->amount); ?>,'<?php echo e($tx->status); ?>','<?php echo e($segment); ?>')">
                <i class="bi bi-x-lg"></i> Rad etish
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($tx->status === 'approved'): ?>
    <div class="p-card fade-up"
         style="background:var(--p-success-d);border-color:rgba(34,201,142,.2)">
      <div style="padding:16px 18px;display:flex;align-items:center;gap:14px">
        <i class="bi bi-check-circle-fill"
           style="font-size:28px;color:var(--p-success);flex-shrink:0"></i>
        <div style="flex:1">
          <div style="font-size:14px;font-weight:600;color:var(--p-success)">
            Tasdiqlangan
          </div>
          <div style="font-size:12px;color:var(--p-muted);margin-top:3px">
            <?php echo e(number_format($tx->amount)); ?> UZS yechildi ·
            Toza: <?php echo e(number_format($tx->netAmount ?? $tx->amount)); ?> UZS
          </div>
        </div>
        <button class="btn-p danger ghost sm"
                onclick="openReject(<?php echo e($tx->id); ?>,'<?php echo e(addslashes($entityName)); ?>',
                         <?php echo e($tx->amount); ?>,'<?php echo e($tx->status); ?>','<?php echo e($segment); ?>')">
          <i class="bi bi-arrow-counterclockwise"></i> Bekor qilish
        </button>
      </div>
    </div>
    <?php endif; ?>

    
    <?php if($tx->status === 'rejected'): ?>
    <div class="p-card fade-up"
         style="background:var(--p-danger-d);border-color:rgba(255,92,106,.2)">
      <div style="padding:16px 18px;display:flex;align-items:center;gap:14px">
        <i class="bi bi-x-circle-fill"
           style="font-size:28px;color:var(--p-danger);flex-shrink:0"></i>
        <div>
          <div style="font-size:14px;font-weight:600;color:var(--p-danger)">
            Rad etilgan
          </div>
          <div style="font-size:12px;color:var(--p-muted);margin-top:3px">
            <?php echo e($tx->rejected_desc); ?>

          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>


<div id="reject-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
            z-index:9999;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--p-surface);border-radius:14px;padding:24px;
              width:100%;max-width:460px;border:1px solid var(--p-border);
              box-shadow:0 20px 60px rgba(0,0,0,.4)">
    <div class="flex items-start justify-between mb-3">
      <div>
        <div style="font-size:16px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-x-circle-fill mr-1" style="color:var(--p-danger)"></i>
          Rad etish
        </div>
        <div id="reject-subtitle"
             style="font-size:12px;color:var(--p-hint);margin-top:3px"></div>
      </div>
      <button onclick="closeReject()"
              style="background:none;border:none;cursor:pointer;
                     color:var(--p-muted);font-size:20px;line-height:1">×</button>
    </div>

    <div id="reject-warn-approved"
         style="display:none;padding:11px 13px;background:var(--p-warning-d);
                border-radius:9px;border:1px solid rgba(245,166,35,.2);
                margin-bottom:14px;font-size:12px;color:var(--p-warning)">
      <i class="bi bi-exclamation-triangle-fill mr-1"></i>
      Oldin <strong>tasdiqlangan</strong> edi.
      Rad etilsa <strong><span id="reject-amount"></span> UZS qaytariladi</strong>.
    </div>

    <form id="reject-form" method="POST">
      <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
      <input type="hidden" name="segment" id="reject-segment" value="<?php echo e($segment); ?>">
      <label class="p-form-label">
        Rad etish sababi <span style="color:var(--p-danger)">*</span>
      </label>
      <textarea name="rejected_desc" class="p-form-control" rows="3" required
                style="margin-bottom:14px"
                placeholder="Karta ma'lumotlari noto'g'ri..."></textarea>
      <div class="flex gap-2 justify-end">
        <button type="button" onclick="closeReject()" class="btn-p ghost">
          Bekor
        </button>
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
  document.getElementById('reject-segment').value = segment;
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

document.getElementById('reject-modal')
  .addEventListener('click', e => { if (e.target===e.currentTarget) closeReject(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeReject(); });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/seller-transactions/show.blade.php ENDPATH**/ ?>