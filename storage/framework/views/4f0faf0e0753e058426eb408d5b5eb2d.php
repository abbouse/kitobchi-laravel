<?php $__env->startSection('title', 'Yangi yangilik'); ?>
<?php $__env->startSection('page-title', 'Yangi yangilik'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <a href="<?php echo e(route('admin.news.index')); ?>" class="btn btn-secondary flex items-center gap-2 w-fit">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
</div>

<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card-panel p-5">
    <form method="POST" action="<?php echo e(route('admin.news.store')); ?>" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php echo csrf_field(); ?>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Sarlavha <span class="text-red-500">*</span></label>
            <input
                name="title"
                class="input"
                required
                placeholder="Yangilik sarlavhasini kiriting"
                value="<?php echo e(old('title')); ?>"
            >
        </div>

        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Tavsif</label>
            <textarea
                name="description"
                class="textarea"
                rows="4"
                placeholder="Yangilik haqida tavsif"
            ><?php echo e(old('description')); ?></textarea>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Rasm (imgUrl)</label>
            <input name="imgUrl" type="file" class="input" accept="image/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Joylashuv (align)</label>
            <select name="align" class="select">
                <option value="top" <?php if(old('align') === 'top'): echo 'selected'; endif; ?>>Yuqori (top)</option>
                <option value="center" <?php if(old('align') === 'center'): echo 'selected'; endif; ?>>Markaz (center)</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Harakat turi (action)</label>
            <select name="action" class="select" id="action-select">
                <option value="news" <?php if(old('action') === 'news'): echo 'selected'; endif; ?>>Yangilik (news)</option>
                <option value="to_shop" <?php if(old('action') === 'to_shop'): echo 'selected'; endif; ?>>Do'konga (to_shop)</option>
                <option value="to_product" <?php if(old('action') === 'to_product'): echo 'selected'; endif; ?>>Mahsulotga (to_product)</option>
            </select>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Harakat ID <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input
                name="action_id"
                type="number"
                class="input"
                placeholder="Do'kon yoki mahsulot IDsi"
                value="<?php echo e(old('action_id')); ?>"
                min="1"
            >
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    name="status"
                    type="checkbox"
                    value="1"
                    class="w-4 h-4 rounded accent-emerald-500"
                    <?php if(old('status', true)): echo 'checked'; endif; ?>
                >
                <span class="text-sm font-medium">Faol holat</span>
            </label>
            <span class="text-xs text-gray-400">Belgilanmasa, yangilik yashirin bo'ladi</span>
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="<?php echo e(route('admin.news.index')); ?>" class="btn btn-secondary">Bekor</a>
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/news/create.blade.php ENDPATH**/ ?>