<?php $isEdit = isset($bookCategory); ?>
<?php $__env->startSection('title', $isEdit ? 'Kategoriya tahrirlash' : 'Yangi kategoriya'); ?>
<?php $__env->startSection('page-title', $isEdit ? 'Kategoriya tahrirlash' : 'Yangi kategoriya'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl">
  <div class="card p-6">
    <form method="POST" action="<?php echo e($isEdit ? route('admin.book-categories.update', $bookCategory) : route('admin.book-categories.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if($isEdit): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="mb-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 text-sm">
          <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (O'zbekcha) <span class="text-rose-500">*</span></label>
          <input name="name_uz" value="<?php echo e(old('name_uz', $bookCategory->name_uz ?? '')); ?>" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Ruscha) <span class="text-rose-500">*</span></label>
          <input name="name_ru" value="<?php echo e(old('name_ru', $bookCategory->name_ru ?? '')); ?>" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Inglizcha)</label>
          <input name="name_en" value="<?php echo e(old('name_en', $bookCategory->name_en ?? '')); ?>" class="input">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Nomi (Yaponcha)</label>
          <input name="name_ja" value="<?php echo e(old('name_ja', $bookCategory->name_ja ?? '')); ?>" class="input">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Icon (emoji)</label>
          <input name="icon" value="<?php echo e(old('icon', $bookCategory->icon ?? '')); ?>" class="input" maxlength="10" placeholder="📚">
        </div>
        <div class="flex items-center gap-3 pt-6">
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer"
              <?php echo e(old('is_active', $bookCategory->is_active ?? true) ? 'checked' : ''); ?>>
            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-emerald-500 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            <span class="ml-3 text-sm font-medium">Faol</span>
          </label>
        </div>
      </div>

      <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="<?php echo e($isEdit ? 'save' : 'plus'); ?>" class="w-4 h-4"></i>
          <?php echo e($isEdit ? 'Saqlash' : "Qo'shish"); ?>

        </button>
        <a href="<?php echo e(route('admin.book-categories.index')); ?>" class="btn btn-secondary">Bekor qilish</a>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/book-categories/edit.blade.php ENDPATH**/ ?>