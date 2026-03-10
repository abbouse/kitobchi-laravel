<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal781784ddc1cff9584ff159910cf34f25 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal781784ddc1cff9584ff159910cf34f25 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.page-breadcrumb','data' => ['pageTitle' => 'Foydalanuvchilar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.page-breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pageTitle' => 'Foydalanuvchilar']); ?>
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

<div class="space-y-5">

    
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Jami</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($stats['total'])); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Premium</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($stats['premium'])); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Tasdiqlangan</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($stats['verified'])); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-1 text-xs leading-normal text-gray-500 dark:text-gray-400">Bugun qo'shilgan</p>
            <h4 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format($stats['today'])); ?></h4>
        </div>
    </div>

    
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        
        <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                Foydalanuvchilar ro'yxati
            </h3>
            <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Qidirish..."
                        class="h-9 w-48 rounded-lg border border-gray-300 bg-transparent pl-9 pr-4 text-sm text-gray-700 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                </div>

                <select name="filter" class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Barchasi</option>
                    <option value="premium"  <?php if(request('filter')==='premium'): echo 'selected'; endif; ?>>Premium</option>
                    <option value="verified" <?php if(request('filter')==='verified'): echo 'selected'; endif; ?>>Tasdiqlangan</option>
                    <option value="support"  <?php if(request('filter')==='support'): echo 'selected'; endif; ?>>Support</option>
                    <option value="deleted"  <?php if(request('filter')==='deleted'): echo 'selected'; endif; ?>>O'chirilgan</option>
                </select>

                <select name="sort" class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="latest"  <?php if(request('sort','latest')==='latest'): echo 'selected'; endif; ?>>Yangilari avval</option>
                    <option value="oldest"  <?php if(request('sort')==='oldest'): echo 'selected'; endif; ?>>Eskisi avval</option>
                    <option value="balance" <?php if(request('sort')==='balance'): echo 'selected'; endif; ?>>Balans bo'yicha</option>
                    <option value="name"    <?php if(request('sort')==='name'): echo 'selected'; endif; ?>>Ism bo'yicha</option>
                </select>

                <button type="submit"
                    class="flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Filter
                </button>

                <?php if(request()->hasAny(['search','filter','sort'])): ?>
                <a href="<?php echo e(route('admin.users.index')); ?>"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Tozalash
                </a>
                <?php endif; ?>
            </form>
        </div>

        
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Foydalanuvchi</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Telefon / Email</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Balans</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Qo'shilgan</p>
                        </th>
                        <th class="px-5 py-3 text-right sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amallar</p>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">

                        
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                    <?php if($user->avatar): ?>
                                        <img src="<?php echo e($user->avatar); ?>" alt="<?php echo e($user->full_name); ?>" class="h-full w-full object-cover" />
                                    <?php else: ?>
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                                            <?php echo e(strtoupper(substr($user->name, 0, 1))); ?><?php echo e(strtoupper(substr($user->lastname, 0, 1))); ?>

                                        </div>
                                    <?php endif; ?>
                                    <?php if($user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->diffInMinutes() < 5): ?>
                                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-success-500 dark:border-gray-900"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?php echo e($user->full_name); ?>

                                        <?php if($user->is_premium && $user->isPremium()): ?>
                                            <span class="text-yellow-400">★</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        #<?php echo e($user->id); ?>

                                        <?php if($user->isSupport): ?> · <span class="text-brand-500">Support</span> <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </td>

                        
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm text-gray-700 dark:text-gray-300"><?php echo e($user->phone_number); ?></span>
                            <?php if($user->email): ?>
                                <span class="block text-xs text-gray-400 dark:text-gray-500 max-w-[160px] truncate"><?php echo e($user->email); ?></span>
                            <?php endif; ?>
                        </td>

                        
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e(number_format($user->real_balance)); ?> UZS</span>
                            <?php if($user->cashback > 0): ?>
                                <span class="block text-xs text-success-500">+<?php echo e(number_format($user->cashback)); ?> cashback</span>
                            <?php endif; ?>
                        </td>

                        
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap gap-1">
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
                                <?php if($user->isDeleted === 'yes'): ?>
                                    <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'light','color' => 'error','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'light','color' => 'error','size' => 'sm']); ?>O'chirilgan <?php echo $__env->renderComponent(); ?>
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
                        </td>

                        
                        <td class="px-5 py-4 sm:px-6">
                            <span class="block text-sm text-gray-700 dark:text-gray-300"><?php echo e($user->created_at->format('d.m.Y')); ?></span>
                            <span class="block text-xs text-gray-400 dark:text-gray-500"><?php echo e($user->created_at->diffForHumans()); ?></span>
                        </td>

                        
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('admin.users.show', $user->id)); ?>"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                    Ko'rish
                                </a>
                                <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>"
                                    class="flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white hover:bg-brand-600">
                                    Tahrirlash
                                </a>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Foydalanuvchi topilmadi</p>
                            <?php if(request()->hasAny(['search','filter'])): ?>
                                <a href="<?php echo e(route('admin.users.index')); ?>" class="mt-2 inline-block text-xs text-brand-500 hover:underline">Filtrlarni tozalash</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if($users->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            
        </div>
        <?php endif; ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/pages/users/index.blade.php ENDPATH**/ ?>