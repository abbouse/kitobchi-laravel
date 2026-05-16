<?php $__env->startSection('title', 'Foydalanuvchini tahrirlash'); ?>
<?php $__env->startSection('page-title', 'Tahrirlash'); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'User management','title' => ''.e(data_get($user,'full_name')).'','subtitle' => 'Profil, aloqa, daraja va account holatini yangilang. O‘zgarishlar darhol foydalanuvchi profiliga ta’sir qiladi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'User management','title' => ''.e(data_get($user,'full_name')).'','subtitle' => 'Profil, aloqa, daraja va account holatini yangilang. O‘zgarishlar darhol foydalanuvchi profiliga ta’sir qiladi.']); ?>
  <a href="<?php echo e(route('admin.users.show', data_get($user,'id'))); ?>" class="btn btn-outline-secondary rounded-pill px-4">
    <i class="bi bi-arrow-left me-2"></i>Profilga qaytish
  </a>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php echo $__env->make('a122.users._form', ['user' => $user, 'action' => route('admin.users.update', data_get($user,'id')), 'method' => 'PUT'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/edit.blade.php ENDPATH**/ ?>