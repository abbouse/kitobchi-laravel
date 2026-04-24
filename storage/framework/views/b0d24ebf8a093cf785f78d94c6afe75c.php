<?php $__env->startSection('title', $reel->title); ?>
<?php $__env->startSection('page-title', 'Reel: ' . $reel->title); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4 flex items-center justify-between">
    <a href="<?php echo e(route('admin.reels.index')); ?>" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="<?php echo e(route('admin.reels.edit', $reel)); ?>" class="btn btn-primary flex items-center gap-2">
        <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
    </a>
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


<div class="card p-5 mb-6">
    <h2 class="text-lg font-bold mb-3">Reel ma'lumotlari</h2>
    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <dt class="text-xs text-gray-500">Sarlavha</dt>
            <dd class="font-semibold mt-1"><?php echo e($reel->title); ?></dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Tartib</dt>
            <dd class="font-semibold mt-1"><?php echo e($reel->order); ?></dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Videolar soni</dt>
            <dd class="font-semibold mt-1">
                <span class="badge badge-info"><?php echo e($reel->items->count()); ?></span>
            </dd>
        </div>
        <?php if($reel->description): ?>
            <div class="sm:col-span-3">
                <dt class="text-xs text-gray-500">Tavsif</dt>
                <dd class="mt-1 text-gray-700 dark:text-gray-300"><?php echo e($reel->description); ?></dd>
            </div>
        <?php endif; ?>
    </dl>
</div>


<div class="card p-5 mb-6">
    <h2 class="text-lg font-bold mb-4">Video elementlar</h2>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tartib</th>
                        <th>Video 720p</th>
                        <th>Video 480p</th>
                        <th>Video 360p</th>
                        <th class="text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $reel->items->sortBy('order'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-gray-500 text-sm"><?php echo e($item->id); ?></td>
                            <td><?php echo e($item->order); ?></td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="<?php echo e($item->video_720p); ?>">
                                <?php echo e($item->video_720p ?: '—'); ?>

                            </td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="<?php echo e($item->video_480p); ?>">
                                <?php echo e($item->video_480p ?: '—'); ?>

                            </td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="<?php echo e($item->video_360p); ?>">
                                <?php echo e($item->video_360p ?: '—'); ?>

                            </td>
                            <td>
                                <div class="flex items-center justify-end">
                                    <form method="POST" action="<?php echo e(route('admin.reels.items.destroy', [$reel, $item])); ?>" onsubmit="return confirm('Bu video elementni o\'chirishga ishonchingiz komilmi?')">
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
                            <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday video element qo'shilmagan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="card p-5">
    <h2 class="text-lg font-bold mb-4">Yangi video qo'shish</h2>

    <?php if($errors->any()): ?>
        <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.reels.items.store', $reel)); ?>" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php echo csrf_field(); ?>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 720p <span class="text-red-500">*</span></label>
            <input name="video_720p" type="file" class="input" accept="video/*" required>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 480p <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="video_480p" type="file" class="input" accept="video/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 360p <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="video_360p" type="file" class="input" accept="video/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tartib raqami</label>
            <input name="order" type="number" class="input" min="0" value="<?php echo e(old('order', $reel->items->count() + 1)); ?>" placeholder="Tartib raqami">
        </div>

        <div class="md:col-span-2 flex justify-end pt-2 border-t border-gray-100 dark:border-white/10">
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="upload" class="w-4 h-4"></i> Yuklash va qo'shish
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/reels/show.blade.php ENDPATH**/ ?>