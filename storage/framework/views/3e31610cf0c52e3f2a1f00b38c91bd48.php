<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label' => '',
    'previewLabel' => '',
    'icon' => '',
    'items' => [],
    'isActive' => false,
    'top' => false,
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
    'icon' => '',
    'items' => [],
    'isActive' => false,
    'top' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<li <?php echo e($attributes->class(['menu-inner-item'])); ?>

    <?php if($top): ?>
        x-data="{ dropdown: false }"
        @click.outside="dropdown = false"
        data-dropdown-placement="bottom-start"
    <?php else: ?>
        x-data="{ dropdown: <?php echo e($isActive ? 'true' : 'false'); ?> }"
    <?php endif; ?>
    x-ref="dropdownMenu"
>
    <button
        <?php if(!$top): ?>
            x-data="navTooltip"
            @mouseenter="toggleTooltip()"
        <?php endif; ?>
        @click.prevent="dropdown = ! dropdown; $nextTick(() => { if (dropdown && $refs.dropdownMenu) $refs.dropdownMenu.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); })"
        class="menu-inner-button"
        :class="dropdown && '_is-active'"
        type="button"
    >
        <?php if($icon): ?>
            <?php echo $icon; ?>

        <?php elseif(!$top): ?>
            <span class="menu-inner-item-char">
                <?php echo e($previewLabel); ?>

            </span>
        <?php endif; ?>

        <span class="menu-inner-text"><?php echo e($label); ?></span>
        <span class="menu-inner-arrow">
            <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'chevron-down','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $attributes = $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $component = $__componentOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
        </span>
    </button>

    <?php if($items): ?>
        <?php if (isset($component)) { $__componentOriginal0b2726cb82aebce89d04730d647eb6a4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0b2726cb82aebce89d04730d647eb6a4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.menu.index','data' => ['dropdown' => true,'items' => $items,'xTransition.top' => '','style' => 'display: none','xShow' => 'dropdown']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['dropdown' => true,'items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($items),'x-transition.top' => '','style' => 'display: none','x-show' => 'dropdown']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0b2726cb82aebce89d04730d647eb6a4)): ?>
<?php $attributes = $__attributesOriginal0b2726cb82aebce89d04730d647eb6a4; ?>
<?php unset($__attributesOriginal0b2726cb82aebce89d04730d647eb6a4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0b2726cb82aebce89d04730d647eb6a4)): ?>
<?php $component = $__componentOriginal0b2726cb82aebce89d04730d647eb6a4; ?>
<?php unset($__componentOriginal0b2726cb82aebce89d04730d647eb6a4); ?>
<?php endif; ?>
    <?php endif; ?>
</li>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/menu/group.blade.php ENDPATH**/ ?>