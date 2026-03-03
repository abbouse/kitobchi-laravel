<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'colors' => '',
    'assets' => '',
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
    'colors' => '',
    'assets' => '',
    'translates' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php echo $__env->yieldPushContent('styles'); ?>

<?php echo $colors; ?>

<?php $__env->startFragment('moonshine-assets'); ?>
<?php echo $assets; ?>

<?php echo $__env->stopFragment(); ?>

<?php echo e($slot ?? ''); ?>


<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    const translates = <?php echo \Illuminate\Support\Js::from($translates)->toHtml() ?>;
</script>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/assets.blade.php ENDPATH**/ ?>