<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'assets' => [],
    'bodyColor' => ''
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
    'assets' => [],
    'bodyColor' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if(isset($assets['apple-touch'])): ?>
<link rel="apple-touch-icon"
      sizes="180x180"
      href="<?php echo e($assets['apple-touch']); ?>"
>
<?php endif; ?>

<?php if(isset($assets['32'])): ?>
<link rel="icon" type="image/png"
      sizes="32x32"
      href="<?php echo e($assets['32']); ?>"
>
<?php endif; ?>

<?php if(isset($assets['16'])): ?>
<link rel="icon" type="image/png" sizes="16x16"
      href="<?php echo e($assets['16']); ?>"
>
<?php endif; ?>

<?php if(isset($assets['web-manifest'])): ?>
<link rel="manifest"
      href="<?php echo e($assets['web-manifest']); ?>"
>
<?php endif; ?>

<?php if(isset($assets['safari-pinned-tab'])): ?>
<link rel="mask-icon"
      href="<?php echo e($assets['safari-pinned-tab']); ?>"
      color="<?php echo e($bodyColor); ?>"
>
<?php endif; ?>

<?php echo e($slot ?? ''); ?>

<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/favicon.blade.php ENDPATH**/ ?>