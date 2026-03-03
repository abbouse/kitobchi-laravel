<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['activeUsers'=>0,'inactiveUsers'=>0,'newUsers'=>0,'userCount'=>0]));

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

foreach (array_filter((['activeUsers'=>0,'inactiveUsers'=>0,'newUsers'=>0,'userCount'=>0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $activeRate = $userCount > 0 ? round(($activeUsers/$userCount)*100) : 0;
    $newRate    = $userCount > 0 ? min(100, round(($newUsers/$userCount)*100)) : 0;
?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="flex justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Foydalanuvchilar</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Holat bo'yicha</p>
        </div>
        <?php if (isset($component)) { $__componentOriginala50c193cb6f2974616f14721445453d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala50c193cb6f2974616f14721445453d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.dropdown-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.dropdown-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala50c193cb6f2974616f14721445453d4)): ?>
<?php $attributes = $__attributesOriginala50c193cb6f2974616f14721445453d4; ?>
<?php unset($__attributesOriginala50c193cb6f2974616f14721445453d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala50c193cb6f2974616f14721445453d4)): ?>
<?php $component = $__componentOriginala50c193cb6f2974616f14721445453d4; ?>
<?php unset($__componentOriginala50c193cb6f2974616f14721445453d4); ?>
<?php endif; ?>
    </div>
    <div class="space-y-5">
        <?php $__currentLoopData = [
            ['label'=>'Faol','sub'=>'FCM token mavjud','count'=>$activeUsers,'rate'=>$activeRate,'bg'=>'bg-success-50 dark:bg-success-500/10','ic'=>'fill-success-500','bar'=>'bg-success-500'],
            ['label'=>'Nofaol','sub'=>'Token yoq','count'=>$inactiveUsers,'rate'=>100-$activeRate,'bg'=>'bg-error-50 dark:bg-error-500/10','ic'=>'fill-error-500','bar'=>'bg-error-500'],
            ['label'=>'Yangi (7 kun)','sub'=>"Haftada royxatdan otgan",'count'=>$newUsers,'rate'=>$newRate,'bg'=>'bg-brand-50 dark:bg-brand-500/10','ic'=>'fill-brand-500','bar'=>'bg-brand-500'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-9 h-9 <?php echo e($row['bg']); ?> rounded-lg">
                    <svg class="w-4 h-4 <?php echo e($row['ic']); ?>" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($row['label']); ?></p>
                    <span class="text-xs text-gray-500"><?php echo e($row['sub']); ?></span>
                </div>
            </div>
            <div class="flex items-center gap-3 w-40">
                <div class="relative h-2 w-full rounded-sm bg-gray-200 dark:bg-gray-800">
                    <div class="absolute left-0 top-0 h-full rounded-sm <?php echo e($row['bar']); ?>" style="width:<?php echo e($row['rate']); ?>%"></div>
                </div>
                <p class="text-sm font-medium text-gray-800 dark:text-white/90 w-10 text-right"><?php echo e($row['count']); ?></p>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 grid grid-cols-3 gap-3 text-center">
        <div><p class="text-xs text-gray-500 mb-1">Jami</p><p class="text-xl font-bold text-gray-800 dark:text-white/90"><?php echo e(number_format($userCount)); ?></p></div>
        <div><p class="text-xs text-gray-500 mb-1">Faol</p><p class="text-xl font-bold text-success-600"><?php echo e(number_format($activeUsers)); ?></p></div>
        <div><p class="text-xs text-gray-500 mb-1">Yangi</p><p class="text-xl font-bold text-brand-500"><?php echo e(number_format($newUsers)); ?></p></div>
    </div>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/customer-demographic.blade.php ENDPATH**/ ?>