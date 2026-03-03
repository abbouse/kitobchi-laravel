<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'icon' => '',
    'config' => [],
    'events' => '{}',
    'decimals' => 3,
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
    'label' => '',
    'icon' => '',
    'config' => [],
    'events' => '{}',
    'decimals' => 3,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="flex gap-3">
    <?php if($icon): ?>
        <div><?php echo $icon; ?></div>
    <?php endif; ?>

    <?php if($label): ?>
        <h5><?php echo $label; ?></h5>
    <?php endif; ?>
</div>

<div
    <?php echo e($attributes->merge(['class' => 'chart'])); ?>

    x-data="donutChart({
        config: <?php echo \Illuminate\Support\Js::from($config)->toHtml() ?>,
        events: <?php echo $events; ?>,
        decimals: <?php echo e($decimals); ?>

    })"
></div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/apexcharts/src/Providers/../../resources/views/components/metrics/donut.blade.php ENDPATH**/ ?>