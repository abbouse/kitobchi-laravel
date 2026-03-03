<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label'=>'','value'=>0,'icon'=>'chart-bar','color'=>'gray','compact'=>false]));

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

foreach (array_filter((['label'=>'','value'=>0,'icon'=>'chart-bar','color'=>'gray','compact'=>false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $bg   = ['brand'=>'bg-brand-50 dark:bg-brand-500/10','success'=>'bg-success-50 dark:bg-success-500/10','warning'=>'bg-warning-50 dark:bg-warning-500/10','info'=>'bg-blue-50 dark:bg-blue-500/10','gray'=>'bg-gray-100 dark:bg-gray-800'][$color] ?? 'bg-gray-100';
    $ic   = ['brand'=>'text-brand-500','success'=>'text-success-500','warning'=>'text-warning-500','info'=>'text-blue-500','gray'=>'text-gray-600 dark:text-gray-300'][$color] ?? 'text-gray-600';
?>
<div class="rounded-2xl border border-gray-200 bg-white <?php echo e($compact ? 'p-4' : 'p-5 md:p-6'); ?> dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-center <?php echo e($compact ? 'w-9 h-9' : 'w-12 h-12'); ?> <?php echo e($bg); ?> rounded-xl">
        <?php if (isset($component)) { $__componentOriginala29ef3201a1822a0f1ba1263b331ca88 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29ef3201a1822a0f1ba1263b331ca88 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.icon','data' => ['name' => ''.e($icon).'','class' => 'w-5 h-5 '.e($ic).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => ''.e($icon).'','class' => 'w-5 h-5 '.e($ic).'']); ?>
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
    <div class="<?php echo e($compact ? 'mt-3' : 'mt-5'); ?>">
        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($label); ?></span>
        <h4 class="<?php echo e($compact ? 'mt-1 text-xl' : 'mt-2 text-2xl'); ?> font-bold text-gray-800 dark:text-white/90"><?php echo e($value); ?></h4>
    </div>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/metric-card.blade.php ENDPATH**/ ?>