<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '',
    'values' => [],
    'isNullable' => false,
    'isSearchable' => false,
    'asyncUrl' => '',
    'isNative' => false,
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
    'value' => '',
    'values' => [],
    'isNullable' => false,
    'isSearchable' => false,
    'asyncUrl' => '',
    'isNative' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal73c6f62b756759d0cfcff0c734cdf46b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73c6f62b756759d0cfcff0c734cdf46b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.select','data' => ['attributes' => $attributes,'values' => $values,'nullable' => $isNullable,'searchable' => $isSearchable,'asyncRoute' => $asyncUrl,'native' => $isNative]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'values' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values),'nullable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isNullable),'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isSearchable),'asyncRoute' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asyncUrl),'native' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isNative)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73c6f62b756759d0cfcff0c734cdf46b)): ?>
<?php $attributes = $__attributesOriginal73c6f62b756759d0cfcff0c734cdf46b; ?>
<?php unset($__attributesOriginal73c6f62b756759d0cfcff0c734cdf46b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73c6f62b756759d0cfcff0c734cdf46b)): ?>
<?php $component = $__componentOriginal73c6f62b756759d0cfcff0c734cdf46b; ?>
<?php unset($__componentOriginal73c6f62b756759d0cfcff0c734cdf46b); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/select.blade.php ENDPATH**/ ?>