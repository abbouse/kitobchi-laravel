<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'searchable' => false,
    'nullable' => false,
    'values' => [],
    'options' => false,
    'asyncRoute' => null,
    'native' => false,
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
    'searchable' => false,
    'nullable' => false,
    'values' => [],
    'options' => false,
    'asyncRoute' => null,
    'native' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<select
        <?php echo e($attributes->merge([
            'class' => 'form-select',
            'data-search-enabled' => $searchable,
            'data-remove-item-button' => $attributes->get('multiple', false) || $nullable
        ])->when(!$native, fn($a) => $a->merge([
            'x-data' => 'select(\''. $asyncRoute .'\')',
        ]))->when($nullable && !$native && $attributes->get('placeholder') === null, fn($a) => $a->merge(['placeholder' => '-']))); ?>

>
    <?php if($options ?? false): ?>
        <?php echo e($options); ?>

    <?php else: ?>
        <?php if($nullable && !$attributes->has('multiple')): ?>
            <option value=""><?php echo e($attributes->get('placeholder', '-')); ?></option>
        <?php endif; ?>

        <?php $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(isset($optionValue['values'])): ?>
                <optgroup label="<?php echo e($optionValue['label']); ?>">
                    <?php $__currentLoopData = $optionValue['values']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $oValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option <?php if($oValue['selected'] || $attributes->get('value', '') == $oValue['value']): echo 'selected'; endif; ?>
                                value="<?php echo e($oValue['value']); ?>"
                                data-custom-properties='<?php echo json_encode($oValue['properties'], 15, 512) ?>'
                        >
                            <?php echo e($oValue['label']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </optgroup>
            <?php else: ?>
                <option <?php if($optionValue['selected'] || $attributes->get('value', '') == $optionValue['value']): echo 'selected'; endif; ?>
                        value="<?php echo e($optionValue['value']); ?>"
                        data-custom-properties='<?php echo json_encode($optionValue['properties'], 15, 512) ?>'
                >
                    <?php echo e($optionValue['label']); ?>

                </option>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</select>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/form/select.blade.php ENDPATH**/ ?>