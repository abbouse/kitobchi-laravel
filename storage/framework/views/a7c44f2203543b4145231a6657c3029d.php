<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'path' => '',
    'icon' => '',
    'size' => 5,
    'color' => '',
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
    'path' => '',
    'icon' => '',
    'size' => 5,
    'color' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div <?php echo e($attributes->class([
    'w-' . ($size ?? 5),
    'h-' . ($size ?? 5),
    'text-current' => empty($color),
    "text-$color" => !empty($color),
])); ?>>
    <?php if($slot?->isNotEmpty()): ?>
        <?php echo $slot; ?>

    <?php else: ?>
        <?php echo $__env->renderWhen($icon, "$path.$icon", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/icon.blade.php ENDPATH**/ ?>