<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'inDropdown' => false,
    'hasComponent' => false,
    'url' => '#',
    'icon' => '',
    'label' => '',
    'component' => null,
    'badge' => false,
    'raw' => false,
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
    'inDropdown' => false,
    'hasComponent' => false,
    'url' => '#',
    'icon' => '',
    'label' => '',
    'component' => null,
    'badge' => false,
    'raw' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($attributes->has('type')): ?>
    <?php if (isset($component)) { $__componentOriginaldafd5965086f19145b581eeb5ef12cdb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldafd5965086f19145b581eeb5ef12cdb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.button','data' => ['attributes' => $attributes,'raw' => $raw]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'raw' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($raw)]); ?>
        <?php echo $slot; ?>


         <?php $__env->slot('icon', null, []); ?> <?php echo $icon; ?> <?php $__env->endSlot(); ?>

        <?php echo $label; ?>


        <?php if($badge !== false): ?>
            <?php if (isset($component)) { $__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Badge::resolve(['color' => ''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e($badge); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c)): ?>
<?php $attributes = $__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c; ?>
<?php unset($__attributesOriginal5a046a6b8d3de70e5cda3471f3a32e6c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c)): ?>
<?php $component = $__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c; ?>
<?php unset($__componentOriginal5a046a6b8d3de70e5cda3471f3a32e6c); ?>
<?php endif; ?>
        <?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldafd5965086f19145b581eeb5ef12cdb)): ?>
<?php $attributes = $__attributesOriginaldafd5965086f19145b581eeb5ef12cdb; ?>
<?php unset($__attributesOriginaldafd5965086f19145b581eeb5ef12cdb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldafd5965086f19145b581eeb5ef12cdb)): ?>
<?php $component = $__componentOriginaldafd5965086f19145b581eeb5ef12cdb; ?>
<?php unset($__componentOriginaldafd5965086f19145b581eeb5ef12cdb); ?>
<?php endif; ?>
<?php else: ?>
    <?php if (isset($component)) { $__componentOriginalec670fa434a76aa5cbcd93ca765df912 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec670fa434a76aa5cbcd93ca765df912 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.link-button','data' => ['attributes' => $attributes,'href' => $url,'badge' => $badge,'raw' => $raw]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::link-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($url),'badge' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($badge),'raw' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($raw)]); ?>
        <?php echo $slot; ?>


         <?php $__env->slot('icon', null, []); ?> <?php echo $icon; ?> <?php $__env->endSlot(); ?>

        <?php echo $label; ?>

     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalec670fa434a76aa5cbcd93ca765df912)): ?>
<?php $attributes = $__attributesOriginalec670fa434a76aa5cbcd93ca765df912; ?>
<?php unset($__attributesOriginalec670fa434a76aa5cbcd93ca765df912); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalec670fa434a76aa5cbcd93ca765df912)): ?>
<?php $component = $__componentOriginalec670fa434a76aa5cbcd93ca765df912; ?>
<?php unset($__componentOriginalec670fa434a76aa5cbcd93ca765df912); ?>
<?php endif; ?>
<?php endif; ?>

<?php if($hasComponent): ?>
    <template x-teleport="body">
        <?php echo $component; ?>

    </template>
<?php endif; ?>

<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/action-button.blade.php ENDPATH**/ ?>