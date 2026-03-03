<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => '',
    'formName' => '',
    'onValue' => '',
    'value' => '',
    'offValue' => '',
    'isChecked' => false,
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
    'name' => '',
    'formName' => '',
    'onValue' => '',
    'value' => '',
    'offValue' => '',
    'isChecked' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal907a9c03c99069df6c25a34488f7ce8f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal907a9c03c99069df6c25a34488f7ce8f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.switcher','data' => ['attributes' => $attributes,'name' => $name,'onValue' => $onValue,'offValue' => $offValue,'value' => ($onValue == $value ? $onValue : $offValue),'checked' => $isChecked]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.switcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'onValue' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($onValue),'offValue' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($offValue),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($onValue == $value ? $onValue : $offValue)),'checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isChecked)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal907a9c03c99069df6c25a34488f7ce8f)): ?>
<?php $attributes = $__attributesOriginal907a9c03c99069df6c25a34488f7ce8f; ?>
<?php unset($__attributesOriginal907a9c03c99069df6c25a34488f7ce8f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal907a9c03c99069df6c25a34488f7ce8f)): ?>
<?php $component = $__componentOriginal907a9c03c99069df6c25a34488f7ce8f; ?>
<?php unset($__componentOriginal907a9c03c99069df6c25a34488f7ce8f); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/switch.blade.php ENDPATH**/ ?>