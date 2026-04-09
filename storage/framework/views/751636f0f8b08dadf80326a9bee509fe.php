<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'onlineCount' => 0,
    'dau'         => 0,
    'mau'         => 0,
    'users'       => [],
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
    'onlineCount' => 0,
    'dau'         => 0,
    'mau'         => 0,
    'users'       => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <!-- Header -->
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            🟢 Faol Foydalanuvchilar
        </h3>
        <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
            Jonli
        </span>
    </div>

    <!-- Stats row -->
    <div class="mb-5 grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-800">
        <div class="pr-4 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo e($onlineCount); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Onlayn (5 min)</p>
        </div>
        <div class="px-4 text-center">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo e(number_format($dau)); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Bugungi (DAU)</p>
        </div>
        <div class="pl-4 text-center">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo e(number_format($mau)); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Oylik (MAU)</p>
        </div>
    </div>

    <!-- Recent online users list -->
    <?php if(empty($users)): ?>
        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-3">Hozir onlayn foydalanuvchilar yo'q</p>
    <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 py-2">
                    <?php if($user['avatar']): ?>
                        <img src="<?php echo e($user['avatar']); ?>" alt="<?php echo e($user['name']); ?>"
                             class="h-8 w-8 flex-shrink-0 rounded-full object-cover" />
                    <?php else: ?>
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-purple-500 text-sm font-bold text-white">
                            <?php echo e(strtoupper(substr($user['name'] ?? '?', 0, 1))); ?>

                        </div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">
                            <?php echo e($user['name']); ?>

                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500"><?php echo e($user['phone']); ?></p>
                    </div>
                    <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                        <?php echo e($user['last_seen_at']); ?>

                    </span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/components/ecommerce/online-users.blade.php ENDPATH**/ ?>