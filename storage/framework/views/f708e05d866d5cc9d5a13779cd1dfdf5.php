<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'simple' => false,
    'values' => false,
    'columns' => false,
    'notfound' => false,
    'responsive' => true,
    'sticky' => false,
    'thead',
    'tbody',
    'tfoot',
    'headAttributes' => '',
    'bodyAttributes' => '',
    'footAttributes' => '',
    'translates' => [],
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
    'simple' => false,
    'values' => false,
    'columns' => false,
    'notfound' => false,
    'responsive' => true,
    'sticky' => false,
    'thead',
    'tbody',
    'tfoot',
    'headAttributes' => '',
    'bodyAttributes' => '',
    'footAttributes' => '',
    'translates' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if(isset($tbody) || (is_iterable($values) && count($values))): ?>
    <!-- Table -->
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['table-responsive' => $responsive, 'table-sticky' => $sticky]); ?>">
        <table <?php echo e($attributes->merge(['class' => 'table' . (!$simple ? '-list' : '')])); ?>

               x-id="['table-component']" :id="$id('table-component')"
        >
            <thead <?php echo e($headAttributes ??  $thead->attributes ?? ''); ?>>
            <?php echo e($thead ?? ''); ?>

            </thead>
            <tbody  <?php echo e($bodyAttributes ?? $tbody->attributes ?? ''); ?>>
            <?php if(is_iterable($values)): ?>
                <?php $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td>
                                <?php echo isset($data[$name]) && is_scalar($data[$name]) ? $data[$name] : ''; ?>

                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>

            <?php echo e($tbody ?? ''); ?>

            </tbody>

            <?php if($tfoot ?? false): ?>
                <tfoot <?php echo e($footAttributes ?? $tfoot->attributes ?? ''); ?>>
                <?php echo e($tfoot); ?>

                </tfoot>
            <?php endif; ?>
        </table>
    </div>
<?php elseif($notfound): ?>
    <?php if (isset($component)) { $__componentOriginalff7f262ea9804a3a03a910ed0fb8040f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Alert::resolve(['type' => 'default','icon' => 's.no-symbol'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Alert::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'my-4']); ?>
        <?php echo e($translates['notfound'] ?? 'Records not found'); ?>

     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f)): ?>
<?php $attributes = $__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f; ?>
<?php unset($__attributesOriginalff7f262ea9804a3a03a910ed0fb8040f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff7f262ea9804a3a03a910ed0fb8040f)): ?>
<?php $component = $__componentOriginalff7f262ea9804a3a03a910ed0fb8040f; ?>
<?php unset($__componentOriginalff7f262ea9804a3a03a910ed0fb8040f); ?>
<?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/table/index.blade.php ENDPATH**/ ?>