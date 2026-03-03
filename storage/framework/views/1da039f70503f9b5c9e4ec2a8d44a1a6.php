<div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Monthly Sales
        </h3>

        <!-- Dropdown Menu -->
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
        <!-- End Dropdown Menu -->
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="chartOne" class="-ml-5 h-full min-w-[690px] pl-2 xl:min-w-full"></div>
    </div>
</div>


<?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/monthly-sale.blade.php ENDPATH**/ ?>