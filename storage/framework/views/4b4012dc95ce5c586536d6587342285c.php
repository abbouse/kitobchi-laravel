<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'enabled' => $isEnabled ?? true,
    'form' => '',
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
    'enabled' => $isEnabled ?? true,
    'form' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($enabled): ?>
    <div
        x-data="{
            isPopover: false,
            observer: null,
            init() {
                this.observer = new ResizeObserver(entries => {
                    for (let entry of entries) {
                        const width = entry.contentRect.width;
                        this.isPopover = width < 100;
                    }
                });
                this.observer.observe(this.$el.parentElement);
            },
            destroy() {
                this.observer.disconnect();
            }
        }"
        x-init="init"
        x-on:destroy.window="destroy"
        class="search-wrapper"
    >
        <?php if (isset($component)) { $__componentOriginal346f493014d1f33dba1f6de5be97306c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal346f493014d1f33dba1f6de5be97306c = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Popover::resolve(['title' => '','placement' => 'auto'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::popover'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Popover::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-show' => 'isPopover']); ?>
             <?php $__env->slot('trigger', null, []); ?> 
                <button class="flex justify-center w-full search-form-show">
                    <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'magnifying-glass','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                </button>
             <?php $__env->endSlot(); ?>

            <div <?php echo e($attributes->class(['search'])); ?>>
                <?php echo $form ?? $slot; ?>

            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal346f493014d1f33dba1f6de5be97306c)): ?>
<?php $attributes = $__attributesOriginal346f493014d1f33dba1f6de5be97306c; ?>
<?php unset($__attributesOriginal346f493014d1f33dba1f6de5be97306c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal346f493014d1f33dba1f6de5be97306c)): ?>
<?php $component = $__componentOriginal346f493014d1f33dba1f6de5be97306c; ?>
<?php unset($__componentOriginal346f493014d1f33dba1f6de5be97306c); ?>
<?php endif; ?>

        <div x-show="!isPopover">
            <div <?php echo e($attributes->class(['search'])); ?>>
                <?php echo $form ?? $slot; ?>

            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/search.blade.php ENDPATH**/ ?>