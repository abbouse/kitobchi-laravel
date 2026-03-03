<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'formName' => '',
    'fieldErrors' => [],
    'beforeLabel' => false,
    'insideLabel' => false,
    'before',
    'after',
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
    'fieldErrors' => [],
    'beforeLabel' => false,
    'insideLabel' => false,
    'before',
    'after',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div <?php echo e($attributes->merge(['class' => 'form-group moonshine-field'])->except('required')); ?>

     x-id="['input-wrapper', 'field-<?php echo e($formName); ?>']"
     :id="$id('input-wrapper')"
     data-validation-wrapper
>
    <?php echo e($beforeLabel && !$insideLabel ? $slot : ''); ?>


    <?php if($label): ?>
        <?php if (isset($component)) { $__componentOriginalb1ab301d22b9d6823ac125a050b59817 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb1ab301d22b9d6823ac125a050b59817 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.label','data' => ['required' => $attributes->get('required', false),':for' => '$id(\'field-'.e($formName).'\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->get('required', false)),':for' => '$id(\'field-'.e($formName).'\')']); ?>
            <?php echo e($beforeLabel && $insideLabel ? $slot : ''); ?>

            <?php echo $label; ?>

            <?php echo e(!$beforeLabel && $insideLabel ? $slot : ''); ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb1ab301d22b9d6823ac125a050b59817)): ?>
<?php $attributes = $__attributesOriginalb1ab301d22b9d6823ac125a050b59817; ?>
<?php unset($__attributesOriginalb1ab301d22b9d6823ac125a050b59817); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb1ab301d22b9d6823ac125a050b59817)): ?>
<?php $component = $__componentOriginalb1ab301d22b9d6823ac125a050b59817; ?>
<?php unset($__componentOriginalb1ab301d22b9d6823ac125a050b59817); ?>
<?php endif; ?>
    <?php endif; ?>

    <div data-validation-wrapper>
        <?php echo e($before ?? ''); ?>


        <?php echo e(!$beforeLabel && !$insideLabel ? $slot : ''); ?>


        <?php echo e($after ?? ''); ?>


        <?php $__currentLoopData = $fieldErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginal8c392c2543387e7715c9099e7b7baca4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c392c2543387e7715c9099e7b7baca4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.input-error','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <?php echo e($error); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c392c2543387e7715c9099e7b7baca4)): ?>
<?php $attributes = $__attributesOriginal8c392c2543387e7715c9099e7b7baca4; ?>
<?php unset($__attributesOriginal8c392c2543387e7715c9099e7b7baca4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c392c2543387e7715c9099e7b7baca4)): ?>
<?php $component = $__componentOriginal8c392c2543387e7715c9099e7b7baca4; ?>
<?php unset($__componentOriginal8c392c2543387e7715c9099e7b7baca4); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/form/wrapper.blade.php ENDPATH**/ ?>