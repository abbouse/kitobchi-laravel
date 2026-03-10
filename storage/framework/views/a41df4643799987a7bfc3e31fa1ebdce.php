<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal781784ddc1cff9584ff159910cf34f25 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal781784ddc1cff9584ff159910cf34f25 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.page-breadcrumb','data' => ['pageTitle' => ''.e($user->full_name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.page-breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pageTitle' => ''.e($user->full_name).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal781784ddc1cff9584ff159910cf34f25)): ?>
<?php $attributes = $__attributesOriginal781784ddc1cff9584ff159910cf34f25; ?>
<?php unset($__attributesOriginal781784ddc1cff9584ff159910cf34f25); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal781784ddc1cff9584ff159910cf34f25)): ?>
<?php $component = $__componentOriginal781784ddc1cff9584ff159910cf34f25; ?>
<?php unset($__componentOriginal781784ddc1cff9584ff159910cf34f25); ?>
<?php endif; ?>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

    
    <div class="space-y-5 xl:col-span-1">

        
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">

            
            <div class="flex flex-col items-center gap-4 pb-5 border-b border-gray-200 dark:border-gray-800 sm:flex-row sm:items-start">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border border-gray-200 dark:border-gray-800">
                    <?php if($user->avatar): ?>
                        <img src="<?php echo e($user->avatar); ?>" alt="<?php echo e($user->full_name); ?>" class="h-full w-full object-cover" />
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center rounded-full bg-brand-500 text-2xl font-bold text-white">
                            <?php echo e(strtoupper(substr($user->name, 0, 1))); ?><?php echo e(strtoupper(substr($user->lastname, 0, 1))); ?>

                        </div>
                    <?php endif; ?>
                    <?php if($user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->diffInMinutes() < 5): ?>
                        <span class="absolute bottom-1 right-1 h-3 w-3 rounded-full border-2 border-white bg-success-500 dark:border-gray-900"></span>
                    <?php endif; ?>
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        <?php echo e($user->full_name); ?>

                        <?php if($user->is_premium && $user->isPremium()): ?> <span class="text-yellow-400">★</span> <?php endif; ?>
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($user->position ?? "O'quvchi"); ?> · #<?php echo e($user->id); ?></p>
                    <div class="mt-2 flex flex-wrap justify-center gap-1.5 sm:justify-start">
                        <?php if($user->isVerified): ?>
                            <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'success','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'success','size' => 'sm']); ?>Tasdiqlangan <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                        <?php else: ?>
                            <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'warning','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'warning','size' => 'sm']); ?>Tasdiqlanmagan <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                        <?php endif; ?>
                        <?php if($user->is_premium && $user->isPremium()): ?>
                            <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'solid','color' => 'warning','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'solid','color' => 'warning','size' => 'sm']); ?>Premium <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                        <?php endif; ?>
                        <?php if($user->isSupport): ?>
                            <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'info','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'info','size' => 'sm']); ?>Support <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="grid grid-cols-1 gap-4 pt-5 lg:grid-cols-2 lg:gap-5">
                <?php if($user->phone_number): ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Telefon</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($user->phone_number); ?></p>
                </div>
                <?php endif; ?>
                <?php if($user->email): ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Email</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate"><?php echo e($user->email); ?></p>
                </div>
                <?php endif; ?>
                <?php if($user->telegram_id): ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Telegram ID</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($user->telegram_id); ?></p>
                </div>
                <?php endif; ?>
                <?php if($user->locale): ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Til</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e(strtoupper($user->locale)); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Ro'yxatdan o'tgan</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($user->created_at->format('d.m.Y')); ?></p>
                </div>
                <?php if($user->last_seen_at): ?>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Oxirgi faollik</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e(\Carbon\Carbon::parse($user->last_seen_at)->diffForHumans()); ?></p>
                </div>
                <?php endif; ?>
            </div>

            
            <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-800">
                <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>"
                    class="shadow-theme-xs flex w-full items-center justify-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z" fill=""/>
                    </svg>
                    Tahrirlash
                </a>
            </div>
        </div>

        
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <h4 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Moliyaviy ma'lumotlar</h4>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Asosiy balans</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($user->real_balance)); ?> UZS</p>
                </div>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Cashback</p>
                    <p class="text-sm font-semibold text-success-500"><?php echo e(number_format($user->cashback)); ?> UZS</p>
                </div>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">AI so'rovlar limiti</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e($user->ai_limit); ?> / 25</p>
                </div>
                <div>
                    <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Ijtimoiy</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                        <?php echo e($user->followings()->count()); ?> ta obuna · <?php echo e($user->followers()->count()); ?> ta obunachi
                    </p>
                </div>
            </div>
        </div>

        
        <?php if($user->location): ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <h4 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Asosiy manzil</h4>
            <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($user->location->fullAddress); ?></p>
            <?php if($user->location->lat && $user->location->lon): ?>
                <a href="https://maps.yandex.uz/?text=<?php echo e($user->location->lat); ?>+<?php echo e($user->location->lon); ?>&z=16"
                    target="_blank"
                    class="mt-2 inline-flex items-center gap-1 text-xs text-brand-500 hover:underline">
                    Yandex xaritada ochish ↗
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        
        <?php if($user->cards->count() > 0): ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <h4 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Bank kartalar</h4>
            <div class="space-y-3">
                <?php $__currentLoopData = $user->cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                    <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                        **** **** **** <?php echo e(substr($card->card_number, -4)); ?>

                    </span>
                    <?php if($card->is_verified): ?>
                        <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'success','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'success','size' => 'sm']); ?>Faol <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'warning','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'warning','size' => 'sm']); ?>Tasdiqlanmagan <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    
    <div class="space-y-5 xl:col-span-2" x-data="{ activeTab: 'orders' }">

        
        <div class="rounded-2xl border border-gray-200 bg-white p-1.5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex gap-1">
                <button @click="activeTab = 'orders'"
                    :class="activeTab === 'orders' ? 'bg-brand-500 text-white shadow-theme-xs' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/[0.05]'"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                    Buyurtmalar
                    <?php if($orders->count() > 0): ?>
                        <span class="ml-1 opacity-70">(<?php echo e($orders->count()); ?>)</span>
                    <?php endif; ?>
                </button>
                <button @click="activeTab = 'cart'"
                    :class="activeTab === 'cart' ? 'bg-brand-500 text-white shadow-theme-xs' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/[0.05]'"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                    Savat
                    <?php if($user->cart->count() > 0): ?>
                        <span class="ml-1 opacity-70">(<?php echo e($user->cart->count()); ?>)</span>
                    <?php endif; ?>
                </button>
                <button @click="activeTab = 'books'"
                    :class="activeTab === 'books' ? 'bg-brand-500 text-white shadow-theme-xs' : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/[0.05]'"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                    Kutubxona
                </button>
            </div>
        </div>

        
        <div x-show="activeTab === 'orders'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Buyurtmalar tarixi</h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($orders->count()); ?> ta</span>
                </div>
                <?php if($orders->count() > 0): ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-5 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-white/90">#<?php echo e($order->id); ?></span>
                                    <?php
                                        $statusMap = [
                                            'A' => ['color' => 'warning', 'label' => 'Kutilmoqda'],
                                            'P' => ['color' => 'info',    'label' => 'Qadoqlanmoqda'],
                                            'B' => ['color' => 'primary', 'label' => "Yo'lda"],
                                            'C' => ['color' => 'success', 'label' => 'Yetkazildi'],
                                            'F' => ['color' => 'error',   'label' => 'Bekor qilindi'],
                                        ];
                                        $s = $statusMap[$order->status] ?? ['color' => 'light', 'label' => $order->status];
                                    ?>
                                    <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => ''.e($s['color']).'','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => ''.e($s['color']).'','size' => 'sm']); ?><?php echo e($s['label']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($order->created_at->format('d.m.Y, H:i')); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($order->amount)); ?> UZS</p>
                                <?php if($order->promocode): ?>
                                    <p class="text-xs text-success-500 mt-0.5">-<?php echo e(number_format($order->discountAmount)); ?> (<?php echo e($order->promocode); ?>)</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        
                        <?php if($order->items && count($order->items) > 0): ?>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <?php $__currentLoopData = array_slice($order->items, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center gap-2 rounded-xl border border-gray-100 px-3 py-2 dark:border-gray-800">
                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate"><?php echo e(data_get($item, 'name', 'Noma\'lum')); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo e(number_format(data_get($item, 'item_price', 0))); ?> · <?php echo e(data_get($item, 'count_item', 1)); ?> dona</p>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(count($order->items) > 4): ?>
                            <div class="flex items-center justify-center rounded-xl border border-gray-100 px-3 py-2 dark:border-gray-800">
                                <span class="text-xs text-gray-400">+<?php echo e(count($order->items) - 4); ?> ta boshqa</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php else: ?>
                <div class="py-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Buyurtmalar yo'q</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div x-show="activeTab === 'cart'" x-cloak>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Savat</h4>
                    <span class="text-sm font-semibold text-brand-500"><?php echo e(number_format($user->cart_total_price)); ?> UZS</span>
                </div>
                <?php if($user->cart->count() > 0): ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__currentLoopData = $user->cart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                            <?php if($item->product_image): ?>
                                <img src="<?php echo e($item->product_image); ?>" alt="<?php echo e($item->product_name); ?>" class="h-full w-full object-cover" />
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate"><?php echo e($item->product_name); ?></p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => ''.e($item->product_type === 'book' ? 'info' : 'primary').'','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => ''.e($item->product_type === 'book' ? 'info' : 'primary').'','size' => 'sm']); ?>
                                    <?php echo e($item->product_type === 'book' ? 'Kitob' : 'Kanseler'); ?>

                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                                <?php if($item->variant): ?>
                                    <span class="text-xs text-gray-400"><?php echo e($item->variant->name ?? ''); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($item->product_price)); ?> UZS</p>
                            <p class="text-xs text-gray-400 mt-0.5"><?php echo e($item->count_item); ?> dona</p>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Jami:</span>
                    <span class="text-base font-bold text-brand-500"><?php echo e(number_format($user->cart_total_price)); ?> UZS</span>
                </div>
                <?php else: ?>
                <div class="py-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Savat bo'sh</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/pages/users/show.blade.php ENDPATH**/ ?>