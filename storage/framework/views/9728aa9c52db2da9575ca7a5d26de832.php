<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'previewLabel' => '',
    'url' => '#',
    'icon' => '',
    'badge' => false,
    'top' => false,
    'hasComponent' => false,
    'component' => null,
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
    'previewLabel' => '',
    'url' => '#',
    'icon' => '',
    'badge' => false,
    'top' => false,
    'hasComponent' => false,
    'component' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<a
    href="<?php echo e($url); ?>"
    <?php echo e($attributes?->merge(['class' => 'menu-inner-link'])); ?>

>
    <?php if($icon): ?>
        <?php echo $icon; ?>

    <?php elseif(!$top): ?>
        <span class="menu-inner-item-char">
            <?php echo e($previewLabel); ?>

        </span>
    <?php endif; ?>

    <span class="menu-inner-text"><?php echo e($label); ?></span>

    <?php if($badge !== false): ?>
        <span class="menu-inner-counter"><?php echo e($badge); ?></span>
    <?php endif; ?>
</a>

<?php if($hasComponent): ?>
    <template x-teleport="body">
        <?php echo $component; ?>

    </template>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/menu/item-link.blade.php ENDPATH**/ ?>