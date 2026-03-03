<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'notifications',
    'readAllRoute' => '',
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
    'notifications',
    'readAllRoute' => '',
    'translates' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($notifications->isNotEmpty()): ?>
    <!-- Notifications -->
    <div <?php echo e($attributes->merge(['class' => 'notifications'])); ?>>
        <?php if (isset($component)) { $__componentOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Dropdown::resolve(['placement' => 'bottom-end','title' => $translates['title']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Dropdown::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-[264px] xs:w-80']); ?>
             <?php $__env->slot('toggler', null, ['class' => 'notifications-icon']); ?> 
                <span class="absolute top-0 right-1 h-2 w-2 rounded-full bg-red-500"></span>
                <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'bell','color' => 'gray','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
             <?php $__env->endSlot(); ?>

            <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="notifications-item">
                    <a href="<?php echo e($notification->getReadRoute()); ?>"
                       class="notifications-remove"
                       title="<?php echo e($translates['mark_as_read']); ?>"
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
                    </a>

                    <div class="notifications-category badge-<?php echo e($notification->getColor()); ?>">
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => $notification->getIcon()] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                    </div>

                    <div class="notifications-content">
                        <h5 class="notifications-title"></h5>
                        <p class="notifications-text"><?php echo e($notification->getMessage()); ?></p>

                        <?php if(!is_null($notification->getButton())): ?>
                            <div class="notifications-more">
                                <a href="<?php echo e($notification->getButton()->getLink()); ?>" <?php echo e($notification->getButton()->getAttributes()); ?>>
                                    <?php echo e($notification->getButton()->getLabel()); ?>

                                </a>
                            </div>
                        <?php endif; ?>

                        <span class="notifications-time"><?php echo e($notification->getDate()->format('d.m.Y H:i')); ?></span>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

             <?php $__env->slot('footer', null, []); ?> 
                <a href="<?php echo e($readAllRoute); ?>" class="notifications-read">
                    <?php echo e($translates['mark_as_read_all']); ?>

                </a>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3)): ?>
<?php $attributes = $__attributesOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3; ?>
<?php unset($__attributesOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3)): ?>
<?php $component = $__componentOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3; ?>
<?php unset($__componentOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3); ?>
<?php endif; ?>
    </div>
    <!-- END: Notifications-->
<?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/notifications.blade.php ENDPATH**/ ?>