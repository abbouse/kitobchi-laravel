<?php $__env->startSection('content'); ?>
<div class="grid grid-cols-12 gap-4 md:gap-6">

    
    <?php if(session('cache_cleared')): ?>
    <div class="col-span-12">
        <div class="rounded-2xl border border-success-200 bg-success-50 px-5 py-3 text-success-700 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400 flex items-center gap-2">
            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Kesh muvaffaqiyatli tozalandi.
        </div>
    </div>
    <?php endif; ?>

    
    <div class="col-span-12 flex justify-end">
        <a href="<?php echo e(route('dashboard', ['clear_cache' => 1])); ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181-3.181A1.652 1.652 0 0 1 8.847 10.42M16.023 9.348 12.98 12.381m0 0-3.182 3.182m0-3.182A1.652 1.652 0 0 0 10.42 8.847"/>
            </svg>
            Keshni tozalash
        </a>
    </div>

    
    <div class="col-span-12 space-y-6 xl:col-span-7">

        
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 md:gap-6">
            <?php
                $salesMetrics = [
                    ['label' => 'Jami sotuvlar',        'value' => $soldCount,   'icon' => 'shopping-bag', 'color' => 'brand'],
                    ['label' => 'Yetkazish kerak',      'value' => $soldStatusA, 'icon' => 'paper-plane',  'color' => 'warning'],
                    ['label' => 'Jarayonda',            'value' => $soldStatusB, 'icon' => 'signal',       'color' => 'info'],
                    ['label' => 'Yakunlangan',          'value' => $soldStatusC, 'icon' => 'check-circle', 'color' => 'success'],
                ];
            ?>
            <?php $__currentLoopData = $salesMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginal6a8a67f45eb427493755e52dd279a4d0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a8a67f45eb427493755e52dd279a4d0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.metric-card','data' => ['label' => $m['label'],'value' => $m['value'],'icon' => $m['icon'],'color' => $m['color']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.metric-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['label']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['value']),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['icon']),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['color'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6a8a67f45eb427493755e52dd279a4d0)): ?>
<?php $attributes = $__attributesOriginal6a8a67f45eb427493755e52dd279a4d0; ?>
<?php unset($__attributesOriginal6a8a67f45eb427493755e52dd279a4d0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6a8a67f45eb427493755e52dd279a4d0)): ?>
<?php $component = $__componentOriginal6a8a67f45eb427493755e52dd279a4d0; ?>
<?php unset($__componentOriginal6a8a67f45eb427493755e52dd279a4d0); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <?php if (isset($component)) { $__componentOriginal4664d379b10b97a1beeff3b807d2e69e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4664d379b10b97a1beeff3b807d2e69e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.chart-monthly-sales','data' => ['data' => $monthlyRevenue]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.chart-monthly-sales'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyRevenue)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4664d379b10b97a1beeff3b807d2e69e)): ?>
<?php $attributes = $__attributesOriginal4664d379b10b97a1beeff3b807d2e69e; ?>
<?php unset($__attributesOriginal4664d379b10b97a1beeff3b807d2e69e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4664d379b10b97a1beeff3b807d2e69e)): ?>
<?php $component = $__componentOriginal4664d379b10b97a1beeff3b807d2e69e; ?>
<?php unset($__componentOriginal4664d379b10b97a1beeff3b807d2e69e); ?>
<?php endif; ?>

    </div>

    
    <div class="col-span-12 xl:col-span-5">
        <?php if (isset($component)) { $__componentOriginal957636d633a7faaa6f525f02160454f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal957636d633a7faaa6f525f02160454f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.monthly-target','data' => ['totalRevenue' => $totalRevenue,'booksRevenue' => $booksRevenue,'soldCount' => $soldCount,'soldStatusC' => $soldStatusC]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.monthly-target'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['totalRevenue' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalRevenue),'booksRevenue' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($booksRevenue),'soldCount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($soldCount),'soldStatusC' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($soldStatusC)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal957636d633a7faaa6f525f02160454f5)): ?>
<?php $attributes = $__attributesOriginal957636d633a7faaa6f525f02160454f5; ?>
<?php unset($__attributesOriginal957636d633a7faaa6f525f02160454f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal957636d633a7faaa6f525f02160454f5)): ?>
<?php $component = $__componentOriginal957636d633a7faaa6f525f02160454f5; ?>
<?php unset($__componentOriginal957636d633a7faaa6f525f02160454f5); ?>
<?php endif; ?>
    </div>

    
    <div class="col-span-12">
        <?php if (isset($component)) { $__componentOriginal3e6612bef48c382c0852e4328256e235 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3e6612bef48c382c0852e4328256e235 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.chart-statistics','data' => ['trend' => $soldTrend7]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.chart-statistics'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['trend' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($soldTrend7)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3e6612bef48c382c0852e4328256e235)): ?>
<?php $attributes = $__attributesOriginal3e6612bef48c382c0852e4328256e235; ?>
<?php unset($__attributesOriginal3e6612bef48c382c0852e4328256e235); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3e6612bef48c382c0852e4328256e235)): ?>
<?php $component = $__componentOriginal3e6612bef48c382c0852e4328256e235; ?>
<?php unset($__componentOriginal3e6612bef48c382c0852e4328256e235); ?>
<?php endif; ?>
    </div>

    
    <div class="col-span-12">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 md:gap-6">
            <?php
                $infoMetrics = [
                    ['label' => 'Kitoblar',              'value' => $booksCount,    'icon' => 'book-open'],
                    ['label' => 'Kategoriyalar',         'value' => $catsCount,     'icon' => 'folder'],
                    ['label' => 'Foydalanuvchilar',      'value' => $userCount,     'icon' => 'users'],
                    ['label' => 'Yangi (7 kun)',         'value' => $newUsers,      'icon' => 'user-plus'],
                    ['label' => 'Kuryerlar',             'value' => $couriersCount, 'icon' => 'truck'],
                    ['label' => 'Yetkazish usullari',   'value' => $deliveryCount, 'icon' => 'inbox-stack'],
                    ['label' => 'Faol promokodlar',     'value' => $promosCount,   'icon' => 'receipt-percent'],
                    ['label' => "Sovg'ali buyurtmalar", 'value' => $giftsCount,    'icon' => 'gift'],
                    ['label' => 'Yangiliklar',           'value' => $newsCount,     'icon' => 'rss'],
                    ['label' => 'Stokdagi kitoblar',    'value' => $inStock,       'icon' => 'archive-box'],
                    ['label' => 'Faol foydalanuvchilar','value' => $activeUsers,   'icon' => 'wifi'],
                    ['label' => 'Nofaol',               'value' => $inactiveUsers, 'icon' => 'wifi-off'],
                ];
            ?>
            <?php $__currentLoopData = $infoMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginal6a8a67f45eb427493755e52dd279a4d0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a8a67f45eb427493755e52dd279a4d0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.metric-card','data' => ['label' => $m['label'],'value' => $m['value'],'icon' => $m['icon'],'color' => 'gray','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.metric-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['label']),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['value']),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($m['icon']),'color' => 'gray','compact' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6a8a67f45eb427493755e52dd279a4d0)): ?>
<?php $attributes = $__attributesOriginal6a8a67f45eb427493755e52dd279a4d0; ?>
<?php unset($__attributesOriginal6a8a67f45eb427493755e52dd279a4d0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6a8a67f45eb427493755e52dd279a4d0)): ?>
<?php $component = $__componentOriginal6a8a67f45eb427493755e52dd279a4d0; ?>
<?php unset($__componentOriginal6a8a67f45eb427493755e52dd279a4d0); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <div class="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
        <?php if (isset($component)) { $__componentOriginalf584fde218f2bf93619a786868594039 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf584fde218f2bf93619a786868594039 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.text-metric','data' => ['label' => 'Eng ko\'p sotilgan kitob','value' => $topBook,'icon' => 'star']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.text-metric'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Eng ko\'p sotilgan kitob','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($topBook),'icon' => 'star']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf584fde218f2bf93619a786868594039)): ?>
<?php $attributes = $__attributesOriginalf584fde218f2bf93619a786868594039; ?>
<?php unset($__attributesOriginalf584fde218f2bf93619a786868594039); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf584fde218f2bf93619a786868594039)): ?>
<?php $component = $__componentOriginalf584fde218f2bf93619a786868594039; ?>
<?php unset($__componentOriginalf584fde218f2bf93619a786868594039); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalf584fde218f2bf93619a786868594039 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf584fde218f2bf93619a786868594039 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.text-metric','data' => ['label' => 'Eng ko\'p ishlatilgan promokod','value' => $topPromo,'icon' => 'receipt-percent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.text-metric'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Eng ko\'p ishlatilgan promokod','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($topPromo),'icon' => 'receipt-percent']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf584fde218f2bf93619a786868594039)): ?>
<?php $attributes = $__attributesOriginalf584fde218f2bf93619a786868594039; ?>
<?php unset($__attributesOriginalf584fde218f2bf93619a786868594039); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf584fde218f2bf93619a786868594039)): ?>
<?php $component = $__componentOriginalf584fde218f2bf93619a786868594039; ?>
<?php unset($__componentOriginalf584fde218f2bf93619a786868594039); ?>
<?php endif; ?>
    </div>

    
    <div class="col-span-12 xl:col-span-5">
        <?php if (isset($component)) { $__componentOriginala2f3fe56005d5b519452d4c5c24a151f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.chart-donut','data' => ['title' => 'Kategoriyalar bo\'yicha kitoblar','items' => $categoryDist,'colors' => ['#6366F1','#8B5CF6','#EC4899','#14B8A6','#F59E0B','#10B981','#3B82F6','#EF4444']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.chart-donut'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kategoriyalar bo\'yicha kitoblar','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($categoryDist),'colors' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['#6366F1','#8B5CF6','#EC4899','#14B8A6','#F59E0B','#10B981','#3B82F6','#EF4444'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $attributes = $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $component = $__componentOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
    </div>

    <div class="col-span-12 xl:col-span-7">
        <?php if (isset($component)) { $__componentOriginala2f3fe56005d5b519452d4c5c24a151f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.chart-donut','data' => ['title' => 'Top 5 kategoriyalar (sotuv bo\'yicha)','items' => $topCatsSold,'colors' => ['#FF5733','#33FF57','#3357FF','#FF33A1','#33FFF5']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.chart-donut'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Top 5 kategoriyalar (sotuv bo\'yicha)','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($topCatsSold),'colors' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['#FF5733','#33FF57','#3357FF','#FF33A1','#33FFF5'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $attributes = $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $component = $__componentOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
    </div>

    <div class="col-span-12 xl:col-span-5">
        <?php if (isset($component)) { $__componentOriginala2f3fe56005d5b519452d4c5c24a151f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.chart-donut','data' => ['title' => 'Foydalanuvchilar statusi','items' => $userStatus,'colors' => ['#10B981','#F43F5E']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.chart-donut'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Foydalanuvchilar statusi','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($userStatus),'colors' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['#10B981','#F43F5E'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $attributes = $__attributesOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__attributesOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f)): ?>
<?php $component = $__componentOriginala2f3fe56005d5b519452d4c5c24a151f; ?>
<?php unset($__componentOriginala2f3fe56005d5b519452d4c5c24a151f); ?>
<?php endif; ?>
    </div>

    
    <div class="col-span-12 xl:col-span-5">
        <?php if (isset($component)) { $__componentOriginal3e9ac53279f1446e096b630991b3923d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3e9ac53279f1446e096b630991b3923d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.customer-demographic','data' => ['activeUsers' => $activeUsers,'inactiveUsers' => $inactiveUsers,'newUsers' => $newUsers,'userCount' => $userCount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.customer-demographic'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activeUsers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeUsers),'inactiveUsers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inactiveUsers),'newUsers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($newUsers),'userCount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($userCount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3e9ac53279f1446e096b630991b3923d)): ?>
<?php $attributes = $__attributesOriginal3e9ac53279f1446e096b630991b3923d; ?>
<?php unset($__attributesOriginal3e9ac53279f1446e096b630991b3923d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3e9ac53279f1446e096b630991b3923d)): ?>
<?php $component = $__componentOriginal3e9ac53279f1446e096b630991b3923d; ?>
<?php unset($__componentOriginal3e9ac53279f1446e096b630991b3923d); ?>
<?php endif; ?>
    </div>

    <div class="col-span-12 xl:col-span-7">
        <?php if (isset($component)) { $__componentOriginalcddb22c315ffd50021df116c9609f9ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcddb22c315ffd50021df116c9609f9ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.recent-orders','data' => ['orders' => $recentOrders]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.recent-orders'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['orders' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($recentOrders)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcddb22c315ffd50021df116c9609f9ca)): ?>
<?php $attributes = $__attributesOriginalcddb22c315ffd50021df116c9609f9ca; ?>
<?php unset($__attributesOriginalcddb22c315ffd50021df116c9609f9ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcddb22c315ffd50021df116c9609f9ca)): ?>
<?php $component = $__componentOriginalcddb22c315ffd50021df116c9609f9ca; ?>
<?php unset($__componentOriginalcddb22c315ffd50021df116c9609f9ca); ?>
<?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/pages/dashboard/ecommerce.blade.php ENDPATH**/ ?>