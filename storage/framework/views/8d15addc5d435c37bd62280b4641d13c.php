<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'default',
    'open' => $isOpen ?? false,
    'left' => $isLeft ?? false,
    'wide' => $isWide ?? false,
    'full' => $isFull ?? false,
    'title' => '',
    'async' => false,
    'asyncUrl' => '',
    'toggler' => null,
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
    'name' => 'default',
    'open' => $isOpen ?? false,
    'left' => $isLeft ?? false,
    'wide' => $isWide ?? false,
    'full' => $isFull ?? false,
    'title' => '',
    'async' => false,
    'asyncUrl' => '',
    'toggler' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div x-data="offCanvas(
    `<?php echo e($open); ?>`,
    `<?php echo e($async ? str_replace('&amp;', '&', $asyncUrl) : ''); ?>`
)"
    <?php echo e($attributes); ?>

>
    <?php if($toggler?->isNotEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginalec670fa434a76aa5cbcd93ca765df912 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec670fa434a76aa5cbcd93ca765df912 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.link-button','data' => ['attributes' => $toggler->attributes?->merge([
                '@click.prevent' => 'toggleCanvas',
            ])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::link-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toggler->attributes?->merge([
                '@click.prevent' => 'toggleCanvas',
            ]))]); ?>
            <?php echo e($toggler ?? ''); ?>

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

    <template x-teleport="body">
        <div class="offcanvas-template"
             <?php echo MoonShine\Support\AlpineJs::eventBlade('off_canvas_toggled', $name, 'toggleCanvas'); ?>
        >
            <div
                x-show="open"
                x-bind="dismissCanvas"
                <?php if($left): ?>
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-x-full"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-full"
                <?php else: ?>
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-full"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-full"
                <?php endif; ?>
                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'offcanvas',
                    'offcanvas-left' => $left,
                    'offcanvas-right' => !$left,
                    'w-full max-w-none' => $full,
                    'w-4/5 max-w-none' => $wide && !$full,
                ]); ?>"
                aria-modal="true"
                role="dialog"
            >
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title"><?php echo e($title); ?></h5>
                    <button type="button" class="btn btn-close" @click.prevent="toggleCanvas" aria-label="Close">
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'x-mark','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                </div>
                <div class="offcanvas-body">
                    <?php if($async): ?>
                        <div :id="id">
                            <?php if (isset($component)) { $__componentOriginalfafd141889f3559e22f375cbbe7394f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfafd141889f3559e22f375cbbe7394f5 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Loader::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::loader'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Loader::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfafd141889f3559e22f375cbbe7394f5)): ?>
<?php $attributes = $__attributesOriginalfafd141889f3559e22f375cbbe7394f5; ?>
<?php unset($__attributesOriginalfafd141889f3559e22f375cbbe7394f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfafd141889f3559e22f375cbbe7394f5)): ?>
<?php $component = $__componentOriginalfafd141889f3559e22f375cbbe7394f5; ?>
<?php unset($__componentOriginalfafd141889f3559e22f375cbbe7394f5); ?>
<?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php echo e($slot ?? ''); ?>

                </div>
            </div>
            <div x-show="open" x-transition.opacity class="offcanvas-backdrop"></div>
        </div>
    </template>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/off-canvas.blade.php ENDPATH**/ ?>