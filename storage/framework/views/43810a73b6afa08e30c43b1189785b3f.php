<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'icon' => null,
    'filled' => false,
    'badge' => false,
    'raw' => false,
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
    'icon' => null,
    'filled' => false,
    'badge' => false,
    'raw' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<a <?php echo e($attributes->class($raw ? [] : ['btn', 'btn-primary' => $filled])); ?>>
    <?php echo e($icon ?? ''); ?>

    <?php echo e($slot ?? ''); ?>

    <?php if($badge !== false): ?>
        <?php if (isset($component)) { $__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Badge::resolve(['color' => ''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e($badge); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c)): ?>
<?php $attributes = $__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c; ?>
<?php unset($__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c)): ?>
<?php $component = $__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c; ?>
<?php unset($__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c); ?>
<?php endif; ?>
    <?php endif; ?>
</a>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/link-button.blade.php ENDPATH**/ ?>