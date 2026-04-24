<?php $__env->startSection('title', 'Yangi kuryer'); ?>
<?php $__env->startSection('page-title', 'Yangi kuryer qo\'shish'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn btn-secondary flex items-center gap-2 w-fit">
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

<div class="card p-5">
    <form method="POST" action="<?php echo e(route('admin.couriers.store')); ?>" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php echo csrf_field(); ?>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Ism <span class="text-red-500">*</span></label>
            <input
                name="first_name"
                class="input"
                required
                placeholder="Ism"
                value="<?php echo e(old('first_name')); ?>"
            >
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Familiya <span class="text-red-500">*</span></label>
            <input
                name="last_name"
                class="input"
                required
                placeholder="Familiya"
                value="<?php echo e(old('last_name')); ?>"
            >
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Telefon raqam <span class="text-red-500">*</span></label>
            <input
                name="phone_number"
                class="input"
                required
                placeholder="+998901234567"
                value="<?php echo e(old('phone_number')); ?>"
            >
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Viloyat</label>
            <input
                name="region"
                class="input"
                placeholder="Viloyat nomi"
                value="<?php echo e(old('region')); ?>"
            >
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Parol <span class="text-red-500">*</span></label>
            <input
                name="password"
                type="password"
                class="input"
                required
                placeholder="Parol kiriting"
                autocomplete="new-password"
            >
        </div>

        
        <div>
            <label class="text-xs text-gray-500 mb-1 block">Holat</label>
            <select name="status" class="select">
                <option value="pending"  <?php if(old('status', 'pending') === 'pending'): echo 'selected'; endif; ?>>Kutilmoqda</option>
                <option value="approved" <?php if(old('status') === 'approved'): echo 'selected'; endif; ?>>Tasdiqlangan</option>
                <option value="rejected" <?php if(old('status') === 'rejected'): echo 'selected'; endif; ?>>Rad etilgan</option>
            </select>
        </div>

        
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 mb-1 block">Profil rasmi <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="photo" type="file" class="input" accept="image/*">
        </div>

        
        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/10">
            <a href="<?php echo e(route('admin.couriers.index')); ?>" class="btn btn-secondary">Bekor</a>
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Saqlash
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/couriers/create.blade.php ENDPATH**/ ?>