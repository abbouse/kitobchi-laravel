<?php $__env->startSection('title', isset($vacancy) ? 'Vakansiya tahriri' : 'Yangi vakansiya'); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.jobs.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.jobs.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($vacancy->title ?? 'Yangi vakansiya'); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($vacancy) ? 'Vakansiyani tahrirlash va ko‘rinishini boshqarish' : 'Marketplace jamoasi uchun yangi pozitsiya ochish'); ?> <?php $__env->endSlot(); ?>
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

<form method="POST" action="<?php echo e(isset($vacancy) ? route('admin.jobs.update', $vacancy) : route('admin.jobs.store')); ?>">
    <?php echo csrf_field(); ?>
    <?php if(isset($vacancy)): ?>
    <?php echo method_field('PUT'); ?>
    <?php endif; ?>
    <?php echo $__env->make('a122.jobs._form', ['vacancy' => $vacancy ?? null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/jobs/edit.blade.php ENDPATH**/ ?>