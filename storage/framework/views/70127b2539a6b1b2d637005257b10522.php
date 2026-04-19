<?php $__env->startSection('title', isset($stationeryCategory) ? 'Tahrirlash: '.$stationeryCategory->name_uz : 'Yangi kategoriya'); ?>
<?php $__env->startSection('page-title', isset($stationeryCategory) ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.stationery-categories.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.stationery-categories.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($stationeryCategory) ? $stationeryCategory->name_uz : 'Yangi kategoriya'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($stationeryCategory) ? 'Kategoriyani tahrirlash' : 'Yangi kantselyariya kategoriyasi'); ?> <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>


    <form method="POST"
      action="<?php echo e(isset($stationeryCategory) ? route('panel.stationery-categories.update',$stationeryCategory) : route('panel.stationery-categories.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(isset($stationeryCategory)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Kategoriya ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Icon (emoji)</label>
              <input type="text" name="icon" class="p-form-control" maxlength="2"
                     value="<?php echo e(old('icon', $stationeryCategory->icon ?? '')); ?>"
                     style="font-size:24px;width:80px;text-align:center">
            </div>

            <?php $__currentLoopData = [
              ['name_uz','O\'zbekcha','UZ'],
              ['name_ru','Ruscha','RU'],
              ['name_en','Inglizcha','EN'],
              ['name_ja','Yaponcha','JA'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$field,$label,$lang]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="">
              <label class="p-form-label">
                <?php echo e($label); ?> <span class="s-pill muted" style="font-size:10px"><?php echo e($lang); ?></span>
                <span style="color:var(--p-danger)">*</span>
              </label>
              <input type="text" name="<?php echo e($field); ?>"
                     class="p-form-control <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old($field, $stationeryCategory->$field ?? '')); ?>" required>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div class="">
              <label class="p-form-label">Slug</label>
              <input type="text" name="slug" class="p-form-control"
                     value="<?php echo e(old('slug', $stationeryCategory->slug ?? '')); ?>"
                     placeholder="avtomatik-yaratiladi">
            </div>

            <div class="">
              <label class="p-form-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       <?php echo e(old('is_active', $stationeryCategory->is_active ?? true) ? 'checked' : ''); ?>

                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      
      <?php if($tags->count()): ?>
      <div class="p-card mb-3">
        <div class="dash-card-head">
          <div class="dash-card-title">Teglar</div>
          <div class="dash-card-sub">Kategoriyaga tegishli teglar</div>
        </div>
        <div class="dash-card-body">
          <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;
                          background:var(--p-elevated);border:1px solid var(--p-border);
                          border-radius:20px;padding:5px 12px;transition:all .15s">
              <input type="checkbox" name="tags[]" value="<?php echo e($tag->id); ?>"
                     style="accent-color:var(--p-accent)"
                     <?php echo e(in_array($tag->id, old('tags', $stationeryCategory?->tags?->pluck('id')->toArray() ?? [])) ? 'checked' : ''); ?>>
              <span style="font-size:12px;color:var(--p-text)"><?php echo e($tag->name_uz); ?></span>
            </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="flex gap-2 justify-end">
        <a href="<?php echo e(route('panel.stationery-categories.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/stationery-categories/edit.blade.php ENDPATH**/ ?>