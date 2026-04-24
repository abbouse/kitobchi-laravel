<?php if (isset($component)) { $__componentOriginalaa758e6a82983efcbf593f765e026bd9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa758e6a82983efcbf593f765e026bd9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::message'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::message'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
# Salom, <?php echo e($application->full_name); ?>


<?php echo nl2br(e($bodyText)); ?>


---

<?php if (isset($component)) { $__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::subcopy'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::subcopy'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
Xabar: <?php echo e(config('mail.from.address', 'noreply@kitobchi.com')); ?> (Kitobchi)
<?php if($application->telegram_username): ?>
<br>Telegram: t.me/<?php echo e($application->telegram_username); ?>

<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5)): ?>
<?php $attributes = $__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5; ?>
<?php unset($__attributesOriginala95a089fc4dac0df2b807f0c4d49e8b5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5)): ?>
<?php $component = $__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5; ?>
<?php unset($__componentOriginala95a089fc4dac0df2b807f0c4d49e8b5); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $attributes = $__attributesOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__attributesOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa758e6a82983efcbf593f765e026bd9)): ?>
<?php $component = $__componentOriginalaa758e6a82983efcbf593f765e026bd9; ?>
<?php unset($__componentOriginalaa758e6a82983efcbf593f765e026bd9); ?>
<?php endif; ?>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/mail/career/applicant-reply.blade.php ENDPATH**/ ?>