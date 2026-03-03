<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'buttons',
    'name' => null,
    'errors' => [],
    'precognitive' => false,
    'raw' => false
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
    'buttons',
    'name' => null,
    'errors' => [],
    'precognitive' => false,
    'raw' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<form
    <?php echo e($attributes->merge(['class' => !$raw ? 'form space-elements' : '', 'method' => 'POST'])); ?>

    <?php if(empty($attributes->get('id'))): ?>
        x-id="['form']" :id="$id('form')"
    <?php endif; ?>
>
    <?php if(strtolower($attributes->get('method', '')) !== 'get'): ?>
        <?php echo csrf_field(); ?>
    <?php endif; ?>

        <?php echo e($slot ?? ''); ?>


    <?php if(!$raw): ?>
        <?php if (isset($component)) { $__componentOriginal92549e3c286b4ce5bd58a0b73ea14813 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92549e3c286b4ce5bd58a0b73ea14813 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Grid::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.grid'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Grid::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <?php if (isset($component)) { $__componentOriginal2e37ae84d4448d28efad53a0aa65ed52 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Column::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.column'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Column::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <div class="mt-3 flex w-full flex-wrap justify-start gap-2">
                    <?php echo e($buttons ?? ''); ?>

                </div>
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

            <?php if($precognitive): ?>
                <?php if (isset($component)) { $__componentOriginal2e37ae84d4448d28efad53a0aa65ed52 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e37ae84d4448d28efad53a0aa65ed52 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Column::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.column'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Column::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <div class="js-precognition-errors mb-6"></div>
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
            <?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92549e3c286b4ce5bd58a0b73ea14813)): ?>
<?php $attributes = $__attributesOriginal92549e3c286b4ce5bd58a0b73ea14813; ?>
<?php unset($__attributesOriginal92549e3c286b4ce5bd58a0b73ea14813); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92549e3c286b4ce5bd58a0b73ea14813)): ?>
<?php $component = $__componentOriginal92549e3c286b4ce5bd58a0b73ea14813; ?>
<?php unset($__componentOriginal92549e3c286b4ce5bd58a0b73ea14813); ?>
<?php endif; ?>
    <?php endif; ?>
</form>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/form/index.blade.php ENDPATH**/ ?>