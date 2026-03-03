<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'errors' => []
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'errors' => []
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($errors !== []): ?>
    <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = $error; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalff7f262ea9804a3a03a910ed0fb8040f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Alert::resolve(['removable' => false,'type' => 'error'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Alert::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <?php echo e($message); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f)): ?>
<?php $attributes = $__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f; ?>
<?php unset($__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff7f262ea9804a3a03a910ed0fb8040f)): ?>
<?php $component = $__componentOriginalff7f262ea9804a3a03a910ed0fb8040f; ?>
<?php unset($__componentOriginalff7f262ea9804a3a03a910ed0fb8040f); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/form/all-errors.blade.php ENDPATH**/ ?>