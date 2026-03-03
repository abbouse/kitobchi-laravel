<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'items' => null,
    'placement' => 'bottom-start',
    'toggler',
    'title',
    'searchable' => false,
    'searchPlaceholder' => '',
    'footer' => null,
    'strategy' => 'fixed',
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
    'items' => null,
    'placement' => 'bottom-start',
    'toggler',
    'title',
    'searchable' => false,
    'searchPlaceholder' => '',
    'footer' => null,
    'strategy' => 'fixed',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div x-data="dropdown"
     @click.outside="closeDropdown"
     data-dropdown-strategy="<?php echo e($strategy); ?>"
     data-dropdown-placement="<?php echo e($placement); ?>"
     data-searchable="<?php echo e($searchable); ?>"
     class="dropdown"
>
    <button type="button" @click.prevent="toggleDropdown" <?php echo e($toggler->attributes->merge(['class' => 'dropdown-btn'])); ?>>
        <?php echo e($toggler); ?>

    </button>

    <div <?php echo e($attributes->merge(['class' => 'dropdown-body'])); ?>>
        <?php if($title ?? false): ?>
            <h5 class="dropdown-heading"><?php echo e($title); ?></h5>
        <?php endif; ?>

        <div class="dropdown-content">
            <?php echo e($slot ?? ''); ?>


            <?php if(!empty($items)): ?>
                <?php if($searchable): ?>
                    <?php if (isset($component)) { $__componentOriginal14a9cb58f632607a286ccbee397ec70f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14a9cb58f632607a286ccbee397ec70f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.input','data' => ['xModel.debounce.500ms' => 'dropdownSearch','class' => 'dropdown-input-search','placeholder' => ''.e($searchPlaceholder).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-model.debounce.500ms' => 'dropdownSearch','class' => 'dropdown-input-search','placeholder' => ''.e($searchPlaceholder).'']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $attributes = $__attributesOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $component = $__componentOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__componentOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>
                <?php endif; ?>

                <ul class="dropdown-menu">
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key =>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li
                            class="dropdown-menu-item p-2"
                            <?php if($searchable): ?> x-ref="dropdown_<?php echo e($key); ?>" <?php endif; ?>
                        >
                            <?php echo $item; ?>

                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if($footer ?? false): ?>
            <div class="dropdown-footer">
                <?php echo e($footer ?? ''); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/dropdown.blade.php ENDPATH**/ ?>