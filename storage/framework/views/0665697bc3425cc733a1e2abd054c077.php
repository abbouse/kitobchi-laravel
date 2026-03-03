<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'file' => null,
    'raw' => null,
    'filename' => null,
    'download' => false,
    'removable' => true,
    'removableAttributes' => null,
    'hiddenAttributes' => null,
    'imageable' => true,
    'itemAttributes' => null,
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
    'file' => null,
    'raw' => null,
    'filename' => null,
    'download' => false,
    'removable' => true,
    'removableAttributes' => null,
    'hiddenAttributes' => null,
    'imageable' => true,
    'itemAttributes' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div
    <?php echo e($itemAttributes?->class(['x-removeable dropzone-item zoom-in', 'dropzone-item-file' => !$imageable])); ?>

    <?php if(is_null($itemAttributes)): ?>
    class="x-removeable dropzone-item zoom-in <?php if(!$imageable): ?> dropzone-item-file <?php endif; ?>"
    <?php endif; ?>
>
    <?php if (isset($component)) { $__componentOriginal14a9cb58f632607a286ccbee397ec70f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14a9cb58f632607a286ccbee397ec70f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.form.input','data' => ['type' => 'hidden','attributes' => $hiddenAttributes,'value' => $raw]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'hidden','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hiddenAttributes),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($raw)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $attributes = $__attributesOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__attributesOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14a9cb58f632607a286ccbee397ec70f)): ?>
<?php $component = $__componentOriginal14a9cb58f632607a286ccbee397ec70f; ?>
<?php unset($__componentOriginal14a9cb58f632607a286ccbee397ec70f); ?>
<?php endif; ?>

    <?php if(!$imageable): ?>
        <?php if (isset($component)) { $__componentOriginalb1eae7571f7b9beea214c968152e1d84 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb1eae7571f7b9beea214c968152e1d84 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.file','data' => ['value' => $file,'filename' => $filename ?? $file,'download' => $download]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($file),'filename' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filename ?? $file),'download' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($download)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb1eae7571f7b9beea214c968152e1d84)): ?>
<?php $attributes = $__attributesOriginalb1eae7571f7b9beea214c968152e1d84; ?>
<?php unset($__attributesOriginalb1eae7571f7b9beea214c968152e1d84); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb1eae7571f7b9beea214c968152e1d84)): ?>
<?php $component = $__componentOriginalb1eae7571f7b9beea214c968152e1d84; ?>
<?php unset($__componentOriginalb1eae7571f7b9beea214c968152e1d84); ?>
<?php endif; ?>
    <?php endif; ?>

    <?php if($removable): ?>
        <button
            <?php echo e($removableAttributes?->merge([
                '@click.prevent' => '$event.target.closest(".x-removeable").remove()',
                'type' => 'button',
                'class' => 'dropzone-remove',
            ])); ?>

        >
            <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'x-mark'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
    <?php endif; ?>

    <?php if($imageable): ?>
        <img src="<?php echo e($file); ?>"
             @click.stop="$dispatch('img-popup', {open: true, src: '<?php echo e($file); ?>' })"
             alt="<?php echo e($filename ?? ''); ?>"
        />
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/form/file-item.blade.php ENDPATH**/ ?>