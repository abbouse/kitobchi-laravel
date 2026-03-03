<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '',
    'values' => [],
    'isNullable' => false,
    'isSearchable' => false,
    'isAsyncSearch' => false,
    'asyncSearchUrl' => '',
    'isCreatable' => false,
    'createButton' => '',
    'fragmentUrl' => '',
    'relationName' => '',
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
    'isAsyncSearch' => false,
    'asyncSearchUrl' => '',
    'isCreatable' => false,
    'createButton' => '',
    'fragmentUrl' => '',
    'relationName' => '',
    'isNative' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($isCreatable): ?>
<?php echo $createButton; ?>


<?php if (isset($component)) { $__componentOriginal7bab788aed2a0e8938609bb9914a586d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7bab788aed2a0e8938609bb9914a586d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Divider::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.divider'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Divider::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7bab788aed2a0e8938609bb9914a586d)): ?>
<?php $attributes = $__attributesOriginal7bab788aed2a0e8938609bb9914a586d; ?>
<?php unset($__attributesOriginal7bab788aed2a0e8938609bb9914a586d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7bab788aed2a0e8938609bb9914a586d)): ?>
<?php $component = $__componentOriginal7bab788aed2a0e8938609bb9914a586d; ?>
<?php unset($__componentOriginal7bab788aed2a0e8938609bb9914a586d); ?>
<?php endif; ?>

<?php $__env->startFragment($relationName); ?>
<div
    x-data="fragment('<?php echo e($fragmentUrl); ?>')"
    <?php echo MoonShine\Support\AlpineJs::eventBlade('fragment_updated', $relationName, 'fragmentUpdate'); ?>
>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginal73c6f62b756759d0cfcff0c734cdf46b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73c6f62b756759d0cfcff0c734cdf46b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.select','data' => ['attributes' => $attributes,'nullable' => $isNullable,'searchable' => $isSearchable,'value' => $value,'values' => $values,'asyncRoute' => $isAsyncSearch ? $asyncSearchUrl : null,'native' => $isNative]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'nullable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isNullable),'searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isSearchable),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'values' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($values),'asyncRoute' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isAsyncSearch ? $asyncSearchUrl : null),'native' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isNative)]); ?>
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

<?php if($isCreatable): ?>
</div>
<?php echo $__env->stopFragment(); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/relationships/belongs-to.blade.php ENDPATH**/ ?>