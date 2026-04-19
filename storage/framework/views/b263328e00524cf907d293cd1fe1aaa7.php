<?php $__env->startSection('title', 'Book Club'); ?>
<?php $__env->startSection('page-title', 'Book Club postlari'); ?>

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
   <?php $__env->slot('heading', null, []); ?> Book Club <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilar postlari va repostlari <?php $__env->endSlot(); ?>
   <?php $__env->slot('actions', null, []); ?> 
    <a href="<?php echo e(route('panel.book-club.moderation-queue')); ?>" class="btn-p ghost">
      <i class="bi bi-shield-exclamation"></i> UGC navbati
    </a>
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


<div class="tab-pills fade-up mb-3">
  <?php $__currentLoopData = ['all'=>'Barchasi','posts'=>'Postlar','reposts'=>'Repostlar']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <a href="<?php echo e(request()->fullUrlWithQuery(['tab'=>$k,'page'=>1])); ?>"
     class="tab-pill <?php echo e($tab===$k?'active':''); ?>">
    <?php echo e($l); ?> <span class="tab-badge"><?php echo e($counts[$k]); ?></span>
  </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="filter-bar mb-3 fade-up">
  <form method="GET" class="flex flex-wrap gap-2">
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
  <?php if($loop->first): ?><div class="grid grid-cols-1 md:grid-cols-2 gap-3"><?php endif; ?>

  <div class="xl:col-span-6 fade-up">
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
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/book-club/index.blade.php ENDPATH**/ ?>