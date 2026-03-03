<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'href',
    'logo',
    'logoAttributes',
    'logoSmall',
    'logoSmallAttributes',
    'darkLogo' => null,
    'darkLogoSmall' => null,
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
    'href',
    'logo',
    'logoAttributes',
    'logoSmall',
    'logoSmallAttributes',
    'darkLogo' => null,
    'darkLogoSmall' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<a <?php echo e($attributes->merge(['class' => 'block', 'rel' => 'home', 'href' => $href])); ?>>
    <img src="<?php echo e($logo); ?>"
        <?php echo e($logoAttributes?->merge([
            'class' => 'hidden h-14 xl:block',
        ])); ?>

         alt="<?php echo e($title); ?>"
        <?php if($darkLogo): ?> x-show="!$store.darkMode.on" <?php endif; ?>
    />

    <?php if($darkLogo): ?>
        <img x-show="$store.darkMode.on" src="<?php echo e($darkLogo); ?>"
             <?php echo e($logoAttributes?->merge([
                 'class' => 'hidden h-14 xl:block',
             ])); ?>

             alt="<?php echo e($title); ?>"
        />
    <?php endif; ?>

    <?php if($logoSmall): ?>
        <img src="<?php echo e($logoSmall); ?>"
            <?php echo e($logoSmallAttributes?->merge(['class' => 'block h-8 lg:h-10 xl:hidden'])); ?>

             alt="<?php echo e($title); ?>"
             <?php if($darkLogoSmall): ?> :style="$store.darkMode.on ? 'display: none!important' : ''" <?php endif; ?>
        />
    <?php endif; ?>

    <?php if($logoSmall && $darkLogoSmall): ?>
        <img src="<?php echo e($darkLogoSmall); ?>"
             :style="!$store.darkMode.on ? 'display: none!important' : ''"
             <?php echo e($logoSmallAttributes?->merge(['class' => 'block h-8 lg:h-10 xl:hidden'])); ?>

             alt="<?php echo e($title); ?>"
        />
    <?php endif; ?>
</a>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/logo.blade.php ENDPATH**/ ?>