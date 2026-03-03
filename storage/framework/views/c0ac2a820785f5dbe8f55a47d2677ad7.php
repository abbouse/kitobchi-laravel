<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '',
    'files' => [],
    'isRemovable' => false,
    'canDownload' => false,
    'removableAttributes' => null,
    'hiddenAttributes' => null,
    'dropzoneAttributes' => null,
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
    'files' => [],
    'isRemovable' => false,
    'canDownload' => false,
    'removableAttributes' => null,
    'hiddenAttributes' => null,
    'dropzoneAttributes' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.file','data' => ['attributes' => $attributes,'files' => $files,'removable' => $isRemovable,'removableAttributes' => $removableAttributes,'hiddenAttributes' => $hiddenAttributes,'dropzoneAttributes' => $dropzoneAttributes,'imageable' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'files' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($files),'removable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isRemovable),'removableAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($removableAttributes),'hiddenAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hiddenAttributes),'dropzoneAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dropzoneAttributes),'imageable' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7)): ?>
<?php $attributes = $__attributesOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7; ?>
<?php unset($__attributesOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7)): ?>
<?php $component = $__componentOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7; ?>
<?php unset($__componentOriginal1f062e0eb6b2ffa8e08ae7cfd90ffbf7); ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/fields/image.blade.php ENDPATH**/ ?>