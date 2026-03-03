<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'icon' => '',
    'config' => [],
    'events' => '{}',
    'decimals' => 3,
    'columnSpanValue' => 12,
    'adaptiveColumnSpanValue' => 12,
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
    'columnSpanValue' => 12,
    'adaptiveColumnSpanValue' => 12,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal2e37ae84d4448d28efad53a0aa65ed52 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Column::resolve(['colSpan' => $columnSpanValue,'adaptiveColSpan' => $adaptiveColumnSpanValue] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.column'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Column::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Box::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Box::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <?php if (isset($component)) { $__componentOriginal370d26995da629180756983654c1ce31 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal370d26995da629180756983654c1ce31 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine-apexcharts::components.metrics.donut','data' => ['attributes' => $attributes,'label' => $label,'icon' => $icon,'config' => $config,'events' => $events,'decimals' => $decimals]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine-apexcharts::metrics.donut'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'config' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($config),'events' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($events),'decimals' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($decimals)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal370d26995da629180756983654c1ce31)): ?>
<?php $attributes = $__attributesOriginal370d26995da629180756983654c1ce31; ?>
<?php unset($__attributesOriginal370d26995da629180756983654c1ce31); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal370d26995da629180756983654c1ce31)): ?>
<?php $component = $__componentOriginal370d26995da629180756983654c1ce31; ?>
<?php unset($__componentOriginal370d26995da629180756983654c1ce31); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51)): ?>
<?php $attributes = $__attributesOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51; ?>
<?php unset($__attributesOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51)): ?>
<?php $component = $__componentOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51; ?>
<?php unset($__componentOriginalf93841d0ea7d6b884dd3dbc2a3cc5a51); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52)): ?>
<?php $attributes = $__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52; ?>
<?php unset($__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e37ae84d4448d28efad53a0aa65ed52)): ?>
<?php $component = $__componentOriginal2e37ae84d4448d28efad53a0aa65ed52; ?>
<?php unset($__componentOriginal2e37ae84d4448d28efad53a0aa65ed52); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/apexcharts/src/Providers/../../resources/views/components/metrics/wrapped/donut-chart.blade.php ENDPATH**/ ?>