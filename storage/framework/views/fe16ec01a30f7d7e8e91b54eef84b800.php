<?php $__env->startSection('title', 'Mystery Box'); ?>
<?php $__env->startSection('page-title', 'Mystery Box obunalari'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Mystery Box <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Oylik kitob qutisi obunalari <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('admin.mystery-box.plans')); ?>" class="btn-p ghost">
        <i class="bi bi-list-ul"></i> Tariflar
      </a>
   <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>



<?php if($dueToday > 0): ?>
<div class="p-alert warning fade-up">
  <i class="bi bi-clock-fill"></i>
  Bugun <strong><?php echo e($dueToday); ?> ta</strong> obunachi uchun jo'natish navbati keldi!
  <?php if($dueWeek > $dueToday): ?>
    Bu hafta jami: <?php echo e($dueWeek); ?> ta.
  <?php endif; ?>
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


<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="<?php echo e(request('search')); ?>"
           placeholder="Ism, telefon...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
</form>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
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

              <?php if($isOverdue): ?> ⚠️ Kechikdi <?php endif; ?>
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

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/mystery-box/subscriptions.blade.php ENDPATH**/ ?>