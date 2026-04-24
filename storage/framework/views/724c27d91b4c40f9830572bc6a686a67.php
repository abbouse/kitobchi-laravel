<?php $__env->startSection('title', 'Kitob tahriri'); ?>
<?php $__env->startSection('page-title', 'Kitob tahriri'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.books.show', $book)).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.books.show', $book)).'']); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e($book->name); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Katalog kartasi, narx va moderatsiya sozlamalari <?php $__env->endSlot(); ?>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>

  <form method="POST" action="<?php echo e(route('admin.books.update', $book)); ?>" class="space-y-6">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <section class="card p-5 xl:col-span-8">
        <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="p-form-label">Nomi</label>
            <input name="name" class="p-form-control" required value="<?php echo e(old('name', $book->name)); ?>">
          </div>
          <div>
            <label class="p-form-label">Muallif</label>
            <input name="author" class="p-form-control" required value="<?php echo e(old('author', $book->author)); ?>">
          </div>
          <div>
            <label class="p-form-label">Kategoriya</label>
            <select name="category_id" class="p-form-control" required>
              <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c->id); ?>" <?php if(old('category_id', $book->category_id) == $c->id): echo 'selected'; endif; ?>><?php echo e($c->name_uz); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div>
            <label class="p-form-label">Status</label>
            <select name="is_approved" class="p-form-control">
              <option value="0" <?php if(old('is_approved', $book->is_approved) == 0): echo 'selected'; endif; ?>>Moderatsiyada</option>
              <option value="1" <?php if(old('is_approved', $book->is_approved) == 1): echo 'selected'; endif; ?>>Tasdiqlangan</option>
              <option value="2" <?php if(old('is_approved', $book->is_approved) == 2): echo 'selected'; endif; ?>>Rad etilgan</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="p-form-label">Tavsif</label>
            <textarea name="description" class="p-form-control" rows="7"><?php echo e(old('description', $book->description)); ?></textarea>
          </div>
        </div>
      </section>

      <section class="card p-5 xl:col-span-4">
        <h3 class="text-lg font-black mb-4">Savdo sozlamalari</h3>
        <div class="space-y-4">
          <div>
            <label class="p-form-label">Narx</label>
            <input name="price" type="number" class="p-form-control" required value="<?php echo e(old('price', $book->price)); ?>">
          </div>
          <div>
            <label class="p-form-label">Ombor soni</label>
            <input name="count" type="number" class="p-form-control" required value="<?php echo e(old('count', $book->count)); ?>">
          </div>
          <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]">
            <input type="checkbox" name="status" value="1" class="rounded" <?php if(old('status', $book->status)): echo 'checked'; endif; ?>>
            Mahsulot faol bo‘lsin
          </label>
        </div>
      </section>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="<?php echo e(route('admin.books.show', $book)); ?>" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-check2-circle"></i> O‘zgarishlarni saqlash</button>
    </div>
  </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/books/edit.blade.php ENDPATH**/ ?>