<?php
  $item = $item ?? null;
  $value = fn ($key, $default = '') => old($key, data_get($item, $key, $default));
?>

<form method="POST" action="<?php echo e($action); ?>" class="space-y-6">
  <?php echo csrf_field(); ?>
  <?php if(($method ?? 'POST') !== 'POST'): ?>
    <?php echo method_field($method); ?>
  <?php endif; ?>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card p-5 xl:col-span-8">
      <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="<?php echo e($value('name')); ?>"></div>
        <div><label class="p-form-label">Material</label><input name="material" class="p-form-control" value="<?php echo e($value('material')); ?>"></div>
        <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($category->id); ?>" <?php if($value('category_id') == $category->id): echo 'selected'; endif; ?>><?php echo e($category->name_uz); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div><label class="p-form-label">Status</label><select name="is_approved" class="p-form-control"><option value="0" <?php if($value('is_approved', 1) == 0): echo 'selected'; endif; ?>>Moderatsiyada</option><option value="1" <?php if($value('is_approved', 1) == 1): echo 'selected'; endif; ?>>Tasdiqlangan</option><option value="2" <?php if($value('is_approved', 1) == 2): echo 'selected'; endif; ?>>Rad etilgan</option></select></div>
        <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7"><?php echo e($value('description')); ?></textarea></div>
      </div>
    </section>

    <section class="card p-5 xl:col-span-4">
      <h3 class="text-lg font-black mb-4">Savdo parametrlari</h3>
      <div class="space-y-4">
        <div><label class="p-form-label">Narx</label><input name="price" type="number" class="p-form-control" required value="<?php echo e($value('price')); ?>"></div>
        <div><label class="p-form-label">Chegirma narxi</label><input name="discount_price" type="number" class="p-form-control" value="<?php echo e($value('discount_price')); ?>"></div>
        <div><label class="p-form-label">Ombor</label><input name="stock" type="number" class="p-form-control" required value="<?php echo e($value('stock', 0)); ?>"></div>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" <?php if($value('status', true)): echo 'checked'; endif; ?>> Mahsulot faol</label>
        <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="recommended" value="1" class="rounded" <?php if($value('recommended', false)): echo 'checked'; endif; ?>> Tavsiya etilgan</label>
      </div>
    </section>
  </div>

  <div class="flex justify-end gap-2">
    <a href="<?php echo e(route('admin.stationery.index')); ?>" class="btn-p ghost">Bekor qilish</a>
    <button class="btn-p primary"><i class="bi bi-check2-circle"></i> Saqlash</button>
  </div>
</form>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/stationery/form.blade.php ENDPATH**/ ?>