<?php $__env->startSection('title', 'Yangi kitob'); ?>
<?php $__env->startSection('page-title', 'Yangi kitob'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
  <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.books.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.books.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> Yangi kitob qo‘shish <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> Marketplace katalogiga yangi mahsulot joylashtirish <?php $__env->endSlot(); ?>
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

  <form method="POST" action="<?php echo e(route('admin.books.store')); ?>" class="space-y-6">
    <?php echo csrf_field(); ?>
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
      <section class="card p-5 xl:col-span-8">
        <h3 class="text-lg font-black mb-4">Asosiy ma’lumotlar</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="p-form-label">Nomi</label><input name="name" class="p-form-control" required value="<?php echo e(old('name')); ?>"></div>
          <div><label class="p-form-label">Muallif</label><input name="author" class="p-form-control" required value="<?php echo e(old('author')); ?>"></div>
          <div><label class="p-form-label">Kategoriya</label><select name="category_id" class="p-form-control" required><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($c->id); ?>"><?php echo e($c->name_uz); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
          <div><label class="p-form-label">Narx</label><input name="price" type="number" class="p-form-control" required value="<?php echo e(old('price')); ?>"></div>
          <div><label class="p-form-label">Ombor soni</label><input name="count" type="number" class="p-form-control" required value="<?php echo e(old('count', 0)); ?>"></div>
          <div class="md:col-span-2"><label class="p-form-label">Tavsif</label><textarea name="description" class="p-form-control" rows="7"><?php echo e(old('description')); ?></textarea></div>
        </div>
      </section>
      <section class="card p-5 xl:col-span-4">
        <h3 class="text-lg font-black mb-4">Nashr parametrlari</h3>
        <div class="space-y-4">
          <label class="flex items-center gap-2 text-sm font-medium text-[var(--p-muted)]"><input type="checkbox" name="status" value="1" class="rounded" checked> Faol holatda yaratilsin</label>
          <div class="kpi-soft">
            <div class="metric-label">Tavsiyalar</div>
            <div class="metric-meta mt-2">Mahsulot yaratilgach, `show` sahifada KPI va moderatsiya bloklari ko‘rinadi.</div>
          </div>
        </div>
      </section>
    </div>
    <div class="flex justify-end gap-2">
      <a href="<?php echo e(route('admin.books.index')); ?>" class="btn-p ghost">Bekor qilish</a>
      <button class="btn-p primary"><i class="bi bi-plus-circle"></i> Kitobni yaratish</button>
    </div>
  </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/books/create.blade.php ENDPATH**/ ?>