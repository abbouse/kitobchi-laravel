<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'route' => '',
    'logOutRoute' => '',
    'avatar' => '',
    'nameOfUser' => '',
    'username' => '',
    'withBorder' => false,
    'menu' => null,
    'translates' => [],
    'before',
    'after',
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
    'route' => '',
    'logOutRoute' => '',
    'avatar' => '',
    'nameOfUser' => '',
    'username' => '',
    'withBorder' => false,
    'menu' => null,
    'translates' => [],
    'before',
    'after',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($withBorder): ?> <div <?php echo e($attributes->merge(['class' => 'mt-2 border-t border-dark-200'])); ?>> <?php endif; ?>
    <?php echo e($before ?? ''); ?>


    <?php if(isset($slot) && $slot->isNotEmpty()): ?>
        <?php echo e($slot); ?>

    <?php else: ?>
        <div class="profile">
            <?php if($route): ?>
            <a href="<?php echo e($route); ?>"
               class="profile-main"
            >
            <?php endif; ?>
                <?php if($avatar): ?>
                    <div class="profile-photo">
                        <img class="h-full w-full object-cover"
                             src="<?php echo e($avatar); ?>"
                             alt="<?php echo e($nameOfUser); ?>"
                        />
                    </div>
                <?php endif; ?>

                <div class="profile-info">
                    <h5 class="name"><?php echo e($nameOfUser); ?></h5>
                    <div class="email"><?php echo e($username); ?></div>
                </div>
            <?php if($route): ?>
            </a>
            <?php endif; ?>

            <?php if($menu === null): ?>
                <?php if($logOutRoute): ?>
                    <a href="<?php echo e($logOutRoute); ?>"
                       class="profile-exit"
                       title="Logout"
                    >
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'power','color' => 'gray','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                <?php endif; ?>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f1ffb27451a1bf8bbfbb41fb0a08cc3 = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Dropdown::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Dropdown::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                     <?php $__env->slot('title', null, []); ?> 
                        <div class="profile-main">
                            <?php if($avatar): ?>
                                <div class="profile-photo">
                                    <img class="h-full w-full object-cover"
                                         src="<?php echo e($avatar); ?>"
                                         alt="<?php echo e($nameOfUser); ?>"
                                    />
                                </div>
                            <?php endif; ?>

                            <div class="profile-info">
                                <h5 class="text-purple"><?php echo e($nameOfUser); ?></h5>
                                <div class="email"><?php echo e($username); ?></div>
                            </div>
                        </div>
                     <?php $__env->endSlot(); ?>
                     <?php $__env->slot('toggler', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'chevron-up-down','color' => 'white'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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

                    <?php if($logOutRoute): ?>
                         <?php $__env->slot('footer', null, []); ?> 
                            <?php if (isset($component)) { $__componentOriginal1713db97e40aee9fd53e09cb830715f7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1713db97e40aee9fd53e09cb830715f7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.link-native','data' => ['href' => ''.e($logOutRoute).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::link-native'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e($logOutRoute).'']); ?>
                                <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'power'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                                <?php echo e($translates['logout'] ?? 'Log out'); ?>

                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1713db97e40aee9fd53e09cb830715f7)): ?>
<?php $attributes = $__attributesOriginal1713db97e40aee9fd53e09cb830715f7; ?>
<?php unset($__attributesOriginal1713db97e40aee9fd53e09cb830715f7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1713db97e40aee9fd53e09cb830715f7)): ?>
<?php $component = $__componentOriginal1713db97e40aee9fd53e09cb830715f7; ?>
<?php unset($__componentOriginal1713db97e40aee9fd53e09cb830715f7); ?>
<?php endif; ?>
                         <?php $__env->endSlot(); ?>
                    <?php endif; ?>

                    <?php if(is_iterable($menu)): ?>
                        <ul class="dropdown-menu">
                            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="dropdown-menu-item p-2">
                                    <?php echo $link; ?>

                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php else: ?>
                        <?php echo e($menu); ?>

                    <?php endif; ?>
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
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php echo e($after ?? ''); ?>

<?php if($withBorder): ?> </div> <?php endif; ?>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/layout/profile.blade.php ENDPATH**/ ?>