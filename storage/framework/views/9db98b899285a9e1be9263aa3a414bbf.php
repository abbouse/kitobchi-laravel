<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label'=>'','value'=>'','icon'=>'star']));

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

foreach (array_filter((['label'=>'','value'=>'','icon'=>'star']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] flex items-center gap-4">
    <div class="flex items-center justify-center w-12 h-12 bg-brand-50 rounded-xl dark:bg-brand-500/10 shrink-0">
        <?php if (isset($component)) { $__componentOriginala29ef3201a1822a0f1ba1263b331ca88 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29ef3201a1822a0f1ba1263b331ca88 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.icon','data' => ['name' => ''.e($icon).'','class' => 'w-6 h-6 text-brand-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => ''.e($icon).'','class' => 'w-6 h-6 text-brand-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala29ef3201a1822a0f1ba1263b331ca88)): ?>
<?php $attributes = $__attributesOriginala29ef3201a1822a0f1ba1263b331ca88; ?>
<?php unset($__attributesOriginala29ef3201a1822a0f1ba1263b331ca88); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala29ef3201a1822a0f1ba1263b331ca88)): ?>
<?php $component = $__componentOriginala29ef3201a1822a0f1ba1263b331ca88; ?>
<?php unset($__componentOriginala29ef3201a1822a0f1ba1263b331ca88); ?>
<?php endif; ?>
    </div>
    <div class="min-w-0">
        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($label); ?></span>
        <p class="mt-1 font-semibold text-gray-800 dark:text-white/90 truncate"><?php echo e($value); ?></p>
    </div>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/text-metric.blade.php ENDPATH**/ ?>