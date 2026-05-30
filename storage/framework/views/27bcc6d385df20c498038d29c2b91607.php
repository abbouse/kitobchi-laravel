<?php $__env->startSection('title', $title ?? 'Sahifa'); ?>
<?php $__env->startSection('page-title', $title ?? 'Sahifa'); ?>

<?php $__env->startSection('content'); ?>
<div class="card-panel p-5 text-center mx-auto" style="max-width:640px;">
  <div class="d-inline-grid place-items-center rounded-3 text-white mb-4"
       style="width:64px;height:64px;background:linear-gradient(135deg,#4f46e5,#7c3aed);">
    <i class="bi bi-grid-1x2-fill fs-3"></i>
  </div>
  <h1 class="page-title mb-2"><?php echo e($title ?? 'Sahifa'); ?></h1>
  <p class="page-subtitle mx-auto" style="max-width:420px;">Ushbu modul uchun boshqaruv oynasi tayyorlanmoqda.</p>
  <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-primary-gradient d-inline-flex align-items-center gap-2 mt-4 text-decoration-none">
    <i class="bi bi-arrow-left"></i>
    Dashboard
  </a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/placeholder.blade.php ENDPATH**/ ?>