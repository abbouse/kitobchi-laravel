<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'components' => [],
    'collapsed' => false,
    'collapseAttributes',
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
    'components' => [],
    'collapsed' => false,
    'collapseAttributes',
    'translates' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<aside <?php echo e($attributes->merge(['class' => 'layout-menu'])); ?>

       :class="minimizedMenu && '_is-minimized'"
>
    <?php if (isset($component)) { $__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Components::resolve(['components' => $components] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::components'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Components::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055)): ?>
<?php $attributes = $__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055; ?>
<?php unset($__attributesOriginal19b4fc714625cdcf69d1dc3ea40c6055); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055)): ?>
<?php $component = $__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055; ?>
<?php unset($__componentOriginal19b4fc714625cdcf69d1dc3ea40c6055); ?>
<?php endif; ?>

    <?php echo e($slot ?? ''); ?>


    <?php if($collapsed): ?>
        <div <?php echo e($collapseAttributes->merge(['class' => 'menu-bottom'])); ?>>
            <ul class="menu-inner mt-2">
                <li class="menu-inner-item hidden xl:block">
                    <button
                        type="button"
                        x-data="navTooltip"
                        @mouseenter="toggleTooltip()"
                        @click.prevent="minimizedMenu = ! minimizedMenu"
                        class="menu-inner-button outline-none"
                    >
                        <svg x-show="!minimizedMenu"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke-width="1.5"
                             stroke="currentColor"
                             class="h-6 w-6"
                        >
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>

                        <svg x-show="minimizedMenu"
                             style="display: none"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke-width="1.5"
                             stroke="currentColor"
                             class="h-6 w-6"
                        >
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M12.75 15l3-3m0 0l-3-3m3 3h-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>

                        <span class="menu-inner-text" x-show="!minimizedMenu">
                            <?php echo e($translates['collapse_menu'] ?? ''); ?>

                        </span>
                    </button>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</aside>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/sidebar.blade.php ENDPATH**/ ?>