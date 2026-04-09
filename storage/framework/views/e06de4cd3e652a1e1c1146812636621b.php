<?php $__env->startSection('title', 'Book Club'); ?>
<?php $__env->startSection('page-title', 'Book Club postlari'); ?>

<?php $__env->startSection('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-3 fade-up">
  <div class="tab-pills">
    <?php $__currentLoopData = ['all'=>'Barchasi','posts'=>'Postlar','reposts'=>'Repostlar']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
       class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
      <?php echo e($l); ?> <span class="tab-badge"><?php echo e($counts[$k]); ?></span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</div>

<div class="filter-bar mb-3 fade-up">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
    <div class="search-box" style="width:240px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="search" name="search"
             placeholder="Matn, foydalanuvchi..."
             value="<?php echo e(request('search')); ?>">
    </div>
    <select name="product_type" class="p-form-control" style="width:150px">
      <option value="">Barcha tur</option>
      <option value="book"       <?php echo e(request('product_type')==='book'?'selected':''); ?>>📚 Kitob</option>
      <option value="stationery" <?php echo e(request('product_type')==='stationery'?'selected':''); ?>>✏️ Kanstovar</option>
    </select>
    <button class="btn-p primary" type="submit">
      <i class="bi bi-funnel"></i> Filter
    </button>
    <?php if(request('search') || request('product_type')): ?>
    <a href="<?php echo e(route('panel.book-club.index',['tab'=>$tab])); ?>" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    <?php endif; ?>
  </form>
</div>


<?php $__empty_1 = true; $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php if($loop->first): ?><div class="row g-3"><?php endif; ?>

  <div class="col-12 col-xl-6 fade-up">
    <?php echo $__env->make('panel.book-club._post-card', ['post' => $post, 'showUser' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </div>

  <?php if($loop->last): ?></div><?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="p-card fade-up" style="text-align:center;padding:50px;color:var(--p-hint)">
  <i class="bi bi-chat-square-text" style="font-size:36px;display:block;margin-bottom:12px"></i>
  Postlar topilmadi
</div>
<?php endif; ?>

<div class="mt-3">
  <?php echo e($posts->links('panel.partials.pagination')); ?>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/book-club/index.blade.php ENDPATH**/ ?>