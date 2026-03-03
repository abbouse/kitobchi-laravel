
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'formName' => '',
    'errors' => [],
    'isBeforeLabel' => false,
    'isInsideLabel' => false,
    'before' => null,
    'after' => null,
    'beforeInner' => null,
    'afterInner' => null,
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
    'formName' => '',
    'errors' => [],
    'isBeforeLabel' => false,
    'isInsideLabel' => false,
    'before' => null,
    'after' => null,
    'beforeInner' => null,
    'afterInner' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php echo $before; ?>


<?php if (isset($component)) { $__componentOriginal68fb945a6b3d2c679dec66aef706f1f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68fb945a6b3d2c679dec66aef706f1f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.wrapper','data' => ['label' => $label,'formName' => $formName,'attributes' => $attributes,'beforeLabel' => $isBeforeLabel,'insideLabel' => $isInsideLabel,'fieldErrors' => $errors]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.wrapper'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'form-name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($formName),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'beforeLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isBeforeLabel),'insideLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isInsideLabel),'fieldErrors' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors)]); ?>
    <?php if($beforeInner ?? false): ?>
     <?php $__env->slot('before', null, []); ?> 
        <?php echo $beforeInner; ?>

     <?php $__env->endSlot(); ?>
    <?php endif; ?>

    <?php echo $slot; ?>


    <?php if($afterInner ?? false): ?>
     <?php $__env->slot('after', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginal590e607e39a00c9ea660dbfd829f3bd1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal590e607e39a00c9ea660dbfd829f3bd1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.hint','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.hint'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <?php echo $afterInner; ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal590e607e39a00c9ea660dbfd829f3bd1)): ?>
<?php $attributes = $__attributesOriginal590e607e39a00c9ea660dbfd829f3bd1; ?>
<?php unset($__attributesOriginal590e607e39a00c9ea660dbfd829f3bd1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal590e607e39a00c9ea660dbfd829f3bd1)): ?>
<?php $component = $__componentOriginal590e607e39a00c9ea660dbfd829f3bd1; ?>
<?php unset($__componentOriginal590e607e39a00c9ea660dbfd829f3bd1); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68fb945a6b3d2c679dec66aef706f1f8)): ?>
<?php $attributes = $__attributesOriginal68fb945a6b3d2c679dec66aef706f1f8; ?>
<?php unset($__attributesOriginal68fb945a6b3d2c679dec66aef706f1f8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68fb945a6b3d2c679dec66aef706f1f8)): ?>
<?php $component = $__componentOriginal68fb945a6b3d2c679dec66aef706f1f8; ?>
<?php unset($__componentOriginal68fb945a6b3d2c679dec66aef706f1f8); ?>
<?php endif; ?>

<?php echo $after; ?>

<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/field-container.blade.php ENDPATH**/ ?>