<div <?php echo e($attributes->merge(['class' => 'text-center'])); ?>>
    <?php if (isset($component)) { $__componentOriginal985250747768bf6e9eda28185ae7cf0c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal985250747768bf6e9eda28185ae7cf0c = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Spinner::resolve(['color' => 'primary','size' => 'lg'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::spinner'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Spinner::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal985250747768bf6e9eda28185ae7cf0c)): ?>
<?php $attributes = $__attributesOriginal985250747768bf6e9eda28185ae7cf0c; ?>
<?php unset($__attributesOriginal985250747768bf6e9eda28185ae7cf0c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal985250747768bf6e9eda28185ae7cf0c)): ?>
<?php $component = $__componentOriginal985250747768bf6e9eda28185ae7cf0c; ?>
<?php unset($__componentOriginal985250747768bf6e9eda28185ae7cf0c); ?>
<?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/loader.blade.php ENDPATH**/ ?>