<?php $__env->startSection('title', $title ?? 'Sahifa'); ?>
<?php $__env->startSection('page-title', $title ?? 'Sahifa'); ?>

<?php $__env->startSection('content'); ?>
<div class="card p-10 text-center">
  <div class="w-16 h-16 rounded-2xl brand-gradient flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/20">
    <i data-lucide="construction" class="w-7 h-7 text-white"></i>
  </div>
  <h2 class="text-2xl font-bold"><?php echo e($title ?? 'Sahifa'); ?></h2>
  <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Bu modul keyingi bosqichda to'liq ko'chiriladi.</p>
  <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-primary mt-6 inline-flex">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> Dashboardga qaytish
  </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/placeholder.blade.php ENDPATH**/ ?>