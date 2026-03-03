<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => '',
    'bodyColor' => '',
    'components' => []
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
    'title' => '',
    'bodyColor' => '',
    'components' => []
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<head <?php echo e($attributes); ?>

>
    <meta charset="utf-8" />

    <title><?php echo $__env->yieldContent('title', $title); ?></title>

    <meta name="description"
          content="<?php echo e($title); ?>"
    />

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"
    />

    <meta name="msapplication-TileColor" content="<?php echo e($bodyColor); ?>">
    <meta name="theme-color" content="<?php echo e($bodyColor); ?>">

    <?php if (isset($component)) { $__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Components::resolve(['components' => $components] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::components'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Components::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055)): ?>
<?php $attributes = $__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055; ?>
<?php unset($__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055)): ?>
<?php $component = $__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055; ?>
<?php unset($__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055); ?>
<?php endif; ?>

    <?php echo e($slot ?? ''); ?>

</head>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/head.blade.php ENDPATH**/ ?>