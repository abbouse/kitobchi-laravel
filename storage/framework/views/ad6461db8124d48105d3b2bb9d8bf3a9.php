<?php $__env->startSection('title', 'Yangiliklar'); ?>
<?php $__env->startSection('page-title', 'Bozor yangiliklari'); ?>

<?php $__env->startSection('content'); ?>
<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Bozor yangiliklari</div>
        <div class="a122-index-header__meta"><?php echo e($news->total()); ?> ta yangilik topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="<?php echo e(request('tab', 'all')); ?>">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Sarlavha, do‘kon yoki mahsulot bo‘yicha qidiring">
        </form>
        <a href="<?php echo e(route('admin.news.create')); ?>" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi yangilik
        </a>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>


<div class="flex items-center gap-2 mb-4 flex-wrap">
    <?php
        $currentTab = request('tab', 'all');
        $tabs = [
            'all'    => ['label' => 'Barchasi',   'count' => $counts['all'] ?? 0],
            'active' => ['label' => 'Faol',        'count' => $counts['active'] ?? 0],
            'news'   => ['label' => 'Yangiliklar', 'count' => $counts['news'] ?? 0],
        ];
    ?>
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a
            href="<?php echo e(request()->fullUrlWithQuery(['tab' => $key])); ?>"
            class="btn <?php echo e($currentTab === $key ? 'btn-primary' : 'btn-secondary'); ?> flex items-center gap-2 text-sm"
        >
            <?php echo e($tab['label']); ?>

            <span class="badge <?php echo e($currentTab === $key ? 'badge-info' : 'badge-muted'); ?>"><?php echo e($tab['count']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sarlavha</th>
                    <th>Harakat</th>
                    <th>Holat</th>
                    <th>Yaratilgan</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $news; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-gray-500 text-sm"><?php echo e($item->id); ?></td>
                        <td class="font-semibold max-w-xs truncate"><?php echo e($item->title); ?></td>
                        <td>
                            <?php if($item->action === 'news'): ?>
                                <span class="badge badge-info">Yangilik</span>
                            <?php elseif($item->action === 'to_shop'): ?>
                                <span class="badge badge-warning">Do'konga</span>
                            <?php elseif($item->action === 'to_product'): ?>
                                <span class="badge badge-muted">Mahsulotga</span>
                            <?php else: ?>
                                <span class="badge badge-muted"><?php echo e($item->action); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($item->status === 'active' || $item->status == 1 || $item->status === true): ?>
                                <span class="badge badge-success">Faol</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Yashirin</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-gray-500">
                            <?php echo e($item->created_at ? $item->created_at->format('d.m.Y H:i') : '—'); ?>

                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?php echo e(route('admin.news.show', $item)); ?>" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="<?php echo e(route('admin.news.edit', $item)); ?>" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('admin.news.toggle', $item)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button
                                        type="submit"
                                        class="btn-ghost p-2 rounded-lg"
                                        title="<?php echo e(($item->status === 'active' || $item->status == 1) ? 'Yashirish' : 'Faollashtirish'); ?>"
                                    >
                                        <?php if($item->status === 'active' || $item->status == 1): ?>
                                            <i data-lucide="eye-off" class="w-4 h-4 text-yellow-500"></i>
                                        <?php else: ?>
                                            <i data-lucide="eye" class="w-4 h-4 text-green-500"></i>
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.news.destroy', $item)); ?>" onsubmit="return confirm('Bu yangilikni o\'chirishga ishonchingiz komilmi?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-ghost p-2 rounded-lg text-red-500 hover:text-red-700" title="O'chirish">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday yangilik topilmadi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($news->hasPages()): ?>
    <div class="mt-4"><?php echo e($news->links('a122.partials.pagination')); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/news/index.blade.php ENDPATH**/ ?>