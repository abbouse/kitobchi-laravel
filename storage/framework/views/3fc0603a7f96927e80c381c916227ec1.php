<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '',
    'label' => '',
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
    'label' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.textarea','data' => ['attributes' => $attributes->merge([
        'aria-label' => $label ?? '',
    ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->merge([
        'aria-label' => $label ?? '',
    ]))]); ?><?php echo $value ?? ''; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7)): ?>
<?php $attributes = $__attributesOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7; ?>
<?php unset($__attributesOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7)): ?>
<?php $component = $__componentOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7; ?>
<?php unset($__componentOriginalbbd5cf330a72c1a161c8ccdf43d2a1d7); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/textarea.blade.php ENDPATH**/ ?>