<?php $__env->startSection('title', 'Reklama moderatsiyasi'); ?>
<?php $__env->startSection('page-title', 'Reklamalar'); ?>

<?php $__env->startSection('content'); ?>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Reklamalar ro'yxati</div>
    <div class="a122-index-header__meta"><?php echo e($ads->total()); ?> ta reklama topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
      <?php if(request('type')): ?>
        <input type="hidden" name="type" value="<?php echo e(request('type')); ?>">
      <?php endif; ?>
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="ID yoki seller ID bo'yicha qidiring">
    </form>
  </div>
</div>

<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = [
    ['pending','Kutilmoqda','warning'],
    ['approved','Tasdiqlangan','success'],
    ['rejected','Rad etilgan','danger'],
    ['active','Faol','info'],
    ['expired','Muddati o\'tgan','muted'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$l,$c]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-badge"><?php echo e($counts[$k]); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="filter-bar mb-3">
  <form method="GET" class="a122-index-search-form flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <i class="bi bi-funnel"></i>
    <select name="type" class="p-form-control" style="width:160px">
      <option value="">Barcha tur</option>
      <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($t); ?>" <?php echo e(request('type')===$t?'selected':''); ?>><?php echo e($t); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="btn-p primary" type="submit"><i class="bi bi-funnel"></i></button>
    <a href="<?php echo e(route('admin.ads.index',['tab'=>$tab])); ?>" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid style="min-width:780px">
      <thead>
        <tr>
          <th>#</th>
          <th>Banner</th>
          <th>Sotuvchi</th>
          <th>Tur</th>
          <th>Harakat</th>
          <th>Summa</th>
          <th>Muddat</th>
          <th>Moderatsiya</th>
          <th>To'lov</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $ads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $modCls = match($ad->moderation) {
            'approved'=>'s-pill success','rejected'=>'s-pill danger',default=>'s-pill warning'
          };
          $modLbl = match($ad->moderation) {
            'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda'
          };
          $payCls = match($ad->paymentStatus) {
            'paid'=>'s-pill success','failed'=>'s-pill danger',default=>'s-pill warning'
          };
          $payLbl = match($ad->paymentStatus) {
            'paid'=>'To\'langan','failed'=>'Muvaffaqiyatsiz',default=>'Kutilmoqda'
          };
          $expired = $ad->expire_at && $ad->expire_at <= now();
        ?>
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#<?php echo e($ad->id); ?></td>
          <td>
            <?php if($ad->banner_img): ?>
              <a href="<?php echo e($ad->banner_img); ?>" target="_blank">
                <img src="<?php echo e($ad->banner_img); ?>" style="width:72px;height:36px;border-radius:5px;object-fit:cover;border:1px solid var(--p-border)">
              </a>
            <?php else: ?>
              <div style="width:72px;height:36px;border-radius:5px;background:var(--p-elevated);display:flex;align-items:center;justify-content:center">
                <i class="bi bi-image" style="color:var(--p-hint)"></i>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if($ad->seller): ?>
            <a href="<?php echo e(route('admin.sellers.show', $ad->seller_id)); ?>"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              <?php echo e(Str::limit($ad->seller->shop_name ?? $ad->seller_id, 18)); ?>

            </a>
            <?php else: ?>
              <span style="color:var(--p-hint)">#<?php echo e($ad->seller_id); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="s-pill muted" style="font-size:11px"><?php echo e($ad->type); ?></span>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            <?php echo e($ad->action); ?> → <?php echo e($ad->product_type); ?> #<?php echo e($ad->product_id); ?>

          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
            <?php echo e(number_format($ad->amount)); ?>

          </td>
          <td style="font-size:11px;white-space:nowrap">
            <span style="color:<?php echo e($expired?'var(--p-danger)':'var(--p-muted)'); ?>">
              <?php echo e($ad->expire_at ? \Carbon\Carbon::parse($ad->expire_at)->format('d.m.Y') : '—'); ?>

            </span>
            <?php if($expired): ?>
              <div style="font-size:10px;color:var(--p-danger)">Muddati o'tgan</div>
            <?php endif; ?>
          </td>
          <td><span class="<?php echo e($modCls); ?>"><?php echo e($modLbl); ?></span></td>
          <td><span class="<?php echo e($payCls); ?>"><?php echo e($payLbl); ?></span></td>
          <td>
            <div class="flex gap-1">
              <?php if($ad->moderation === 'pending'): ?>
              <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="action" value="approve">
                <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
              </form>
              <form method="POST" action="<?php echo e(route('admin.ads.moderate', $ad)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="action" value="reject">
                <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
              </form>
              <?php endif; ?>
              <a href="<?php echo e(route('admin.ads.show', $ad)); ?>" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-megaphone" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Reklamalar topilmadi
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($ads->hasPages()): ?>
  <?php echo e($ads->links('a122.partials.pagination')); ?>

  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/ads/index.blade.php ENDPATH**/ ?>