<?php $__env->startSection('title', 'Mystery Box'); ?>
<?php $__env->startSection('page-title', 'Mystery Box obunalari'); ?>

<?php $__env->startSection('content'); ?>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Mystery Box obunalari</div>
    <div class="a122-index-header__meta"><?php echo e($subs->total()); ?> ta obuna topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Ism yoki telefon bo'yicha qidiring">
    </form>
  </div>
</div>


<?php if($dueToday > 0): ?>
<div class="p-alert warning fade-up">
  <i class="bi bi-clock-fill"></i>
  Bugun <strong><?php echo e($dueToday); ?> ta</strong> obunachi uchun jo'natish navbati keldi!
  <?php if($dueWeek > $dueToday): ?>
    Bu hafta jami: <?php echo e($dueWeek); ?> ta.
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if(isset($opsDueNow) && $opsDueNow->count()): ?>
<div class="p-card fade-up mb-3">
  <div class="p-card-header">
    <div class="p-card-title">
      <i class="bi bi-box-seam" style="color:var(--p-danger)"></i>
      Jo'natish navbati
    </div>
    <span class="s-pill danger" style="font-size:10px"><?php echo e($opsDueNow->count()); ?> ta</span>
  </div>
  <div style="padding:14px 18px;display:grid;gap:10px">
    <?php $__currentLoopData = $opsDueNow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('admin.mystery-box.show', $delivery->subscription_id)); ?>"
       style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--p-border);border-radius:14px;background:var(--p-elevated);text-decoration:none">
      <div style="min-width:0">
        <div style="font-size:12px;font-weight:700;color:var(--p-text)">
          #<?php echo e($delivery->subscription_id); ?> · <?php echo e($delivery->month_number); ?>-oy
        </div>
        <div style="font-size:11px;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?php echo e($delivery->subscription?->user?->name); ?> <?php echo e($delivery->subscription?->user?->lastname); ?>

        </div>
        <div style="font-size:10px;color:var(--p-hint)">
          <?php echo e($delivery->dispatch_type_label); ?> · <?php echo e(optional($delivery->planned_for_date)->format('d.m.Y') ?? '—'); ?>

        </div>
      </div>
      <span class="s-pill <?php echo e($delivery->status_color); ?>" style="font-size:10px;white-space:nowrap">
        <?php echo e($delivery->status_label); ?>

      </span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</div>
<?php endif; ?>


<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  <?php $__currentLoopData = [
    ['Faol',        $counts['active'],          'success', 'bi-check-circle'],
    ['Kutilmoqda',  $counts['pending_payment'],  'warning', 'bi-hourglass'],
    ['To\'xtatilgan',$counts['paused'],          'muted',   'bi-pause-circle'],
    ['Yakunlandi',  $counts['completed'],        'info',    'bi-flag'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$l,$v,$c,$i]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-<?php echo e($c); ?>-d,var(--p-elevated));color:var(--p-<?php echo e($c); ?>);
                  display:flex;align-items:center;justify-content:center">
        <i class="bi <?php echo e($i); ?>"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-<?php echo e($c); ?>)"><?php echo e($v); ?></div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em"><?php echo e($l); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    'active'          => ['Faol',         $counts['active']],
    'all'             => ['Barchasi',     $counts['all']],
    'pending_payment' => ['Kutilmoqda',   $counts['pending_payment']],
    'paused'          => ["To'xtatilgan", $counts['paused']],
    'completed'       => ['Yakunlangan',  $counts['completed']],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => [$l, $c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-count"><?php echo e($c); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Tarif</th>
          <th>Manzil</th>
          <th>Progress</th>
          <th>Keyingi yetkazish</th>
          <th>Holat</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $addr = is_array($sub->address) ? $sub->address : [];
          $isOverdue = $sub->next_delivery_at && $sub->next_delivery_at->isPast()
            && $sub->status === 'active';
        ?>
        <tr style="<?php echo e($isOverdue ? 'background:var(--p-warning-d)' : ''); ?>">
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #<?php echo e($sub->id); ?>

          </td>

          <td>
            <?php if($sub->user): ?>
            <a href="<?php echo e(route('admin.users.show',$sub->user_id)); ?>"
               style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
              <?php echo e($sub->user->name); ?> <?php echo e($sub->user->lastname); ?>

            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($sub->user->phone_number); ?>

            </div>
            <?php endif; ?>
          </td>

          <td>
            <?php if($sub->plan): ?>
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)">
              <?php echo e($sub->plan->name_uz); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint)">
              <?php echo e($sub->plan->months); ?> oy · <?php echo e($sub->books_per_month); ?> kitob/oy
            </div>
            <?php endif; ?>
          </td>

          <td style="max-width:150px">
            <?php if($addr['fullAddress'] ?? null): ?>
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis"
                 title="<?php echo e($addr['fullAddress']); ?>">
              <?php echo e($addr['fullAddress']); ?>

            </div>
            <?php if($addr['phoneNumber'] ?? null): ?>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              <?php echo e($addr['phoneNumber']); ?>

            </div>
            <?php endif; ?>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td style="min-width:120px">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:6px;background:var(--p-elevated);
                          border-radius:3px;overflow:hidden">
                <div style="height:100%;background:var(--p-success);
                            border-radius:3px;width:<?php echo e($sub->progress_pct); ?>%">
                </div>
              </div>
              <span style="font-size:11px;color:var(--p-muted);font-family:'JetBrains Mono',monospace;
                           white-space:nowrap">
                <?php echo e($sub->delivered_months); ?>/<?php echo e($sub->total_months); ?>

              </span>
            </div>
          </td>

          <td style="white-space:nowrap">
            <?php if($sub->next_delivery_at): ?>
            <div style="font-size:12px;font-weight:600;
                        color:<?php echo e($isOverdue ? 'var(--p-danger)' : 'var(--p-text)'); ?>;
                        font-family:'JetBrains Mono',monospace">
              <?php echo e($sub->next_delivery_at->format('d.m.Y')); ?>

            </div>
            <div style="font-size:10px;color:<?php echo e($isOverdue ? 'var(--p-danger)' : 'var(--p-hint)'); ?>">
              <?php echo e($sub->next_delivery_at->diffForHumans()); ?>

              <?php if($isOverdue): ?> | Kechikdi <?php endif; ?>
            </div>
            <?php else: ?>
              <span style="color:var(--p-hint)">—</span>
            <?php endif; ?>
          </td>

          <td>
            <span class="s-pill <?php echo e($sub->status_color); ?>" style="font-size:10px">
              <?php echo e($sub->status_label); ?>

            </span>
          </td>

          <td>
            <a href="<?php echo e(route('admin.mystery-box.show', $sub)); ?>"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Obunalar topilmadi
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($subs->hasPages()): ?>
  <div class="flex justify-between items-center px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      <?php echo e($subs->firstItem()); ?>–<?php echo e($subs->lastItem()); ?> / <?php echo e($subs->total()); ?>

    </div>
    <?php echo e($subs->links('a122.partials.pagination')); ?>

  </div>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/mystery-box/index.blade.php ENDPATH**/ ?>