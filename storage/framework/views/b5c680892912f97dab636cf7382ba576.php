<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => null,
    'values' => null,
    'alt' => '',
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
    'value' => null,
    'values' => null,
    'alt' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($value): ?>
    <div class="flex">
        <div
            <?php echo e($value['attributes']?->class(['zoom-in h-10 w-10 overflow-hidden rounded-md bg-white dark:bg-dark-700 cursor-pointer'])); ?>

        >
            <img class="h-full w-full object-cover"
                 src="<?php echo e($value['full_path']); ?>"
                 alt="<?php echo e($value['name'] ?? $alt); ?>"
                 @click.stop="$dispatch('img-popup', {
                    open: true,
                    src: '<?php echo e($value['full_path']); ?>',
                    wide: <?php echo e(isset($value['extra']['wide']) && $value['extra']['wide'] ? 'true' : 'false'); ?>,
                    auto: <?php echo e(isset($value['extra']['auto']) && $value['extra']['auto'] ? 'true' : 'false'); ?>,
                    styles: '<?php echo e($value['extra']['content_styles'] ?? ''); ?>'
                 })"
            >
        </div>
    </div>
<?php elseif($values !== []): ?>
    <div class="images-row">
        <?php $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div
                <?php echo e($value['attributes']?->class(['zoom-in images-row-item bg-white dark:bg-dark-700 cursor-pointer'])); ?>

            >
                <img
                    class="h-full w-full object-cover"
                    src="<?php echo e($value['full_path']); ?>"
                    alt="<?php echo e($value['name'] ?? $alt); ?>"
                    @click.stop="$dispatch('img-popup', {
                        open: true,
                        src: '<?php echo e($value['full_path']); ?>',
                        wide: <?php echo e(isset($value['extra']['wide']) && $value['extra']['wide'] ? 'true' : 'false'); ?>,
                        auto: <?php echo e(isset($value['extra']['auto']) && $value['extra']['auto'] ? 'true' : 'false'); ?>,
                        styles: '<?php echo e($value['extra']['content_styles'] ?? ''); ?>'
                    })"
                />
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/thumbnails.blade.php ENDPATH**/ ?>