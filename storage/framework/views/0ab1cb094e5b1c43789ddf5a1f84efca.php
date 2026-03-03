<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '',
    'extensions' => [],
    'extensionsAttributes' => null,
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
    'extensions' => [],
    'extensionsAttributes' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal2cd4af85b2579be8f040b85fc30903c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2cd4af85b2579be8f040b85fc30903c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.input-extensions','data' => ['extensions' => $extensions,'attributes' => $extensionsAttributes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.input-extensions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['extensions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extensions),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extensionsAttributes)]); ?>
    <?php if (isset($component)) { $__componentOriginal14a9cb58f632607a286ccbee397ec70f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14a9cb58f632607a286ccbee397ec70f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.input','data' => ['attributes' => $attributes->merge([
            'value' => $value
        ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->merge([
            'value' => $value
        ]))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $attributes = $__attributesOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $component = $__componentOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__componentOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2cd4af85b2579be8f040b85fc30903c3)): ?>
<?php $attributes = $__attributesOriginal2cd4af85b2579be8f040b85fc30903c3; ?>
<?php unset($__attributesOriginal2cd4af85b2579be8f040b85fc30903c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2cd4af85b2579be8f040b85fc30903c3)): ?>
<?php $component = $__componentOriginal2cd4af85b2579be8f040b85fc30903c3; ?>
<?php unset($__componentOriginal2cd4af85b2579be8f040b85fc30903c3); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/input.blade.php ENDPATH**/ ?>