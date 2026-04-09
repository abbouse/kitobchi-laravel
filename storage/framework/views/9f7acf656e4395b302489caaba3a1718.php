<?php $__env->startSection('title', isset($stationeryCategory) ? 'Tahrirlash: '.$stationeryCategory->name_uz : 'Yangi kategoriya'); ?>
<?php $__env->startSection('page-title', isset($stationeryCategory) ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
  <div class="col-xl-7">
    <form method="POST"
      action="<?php echo e(isset($stationeryCategory) ? route('panel.stationery-categories.update',$stationeryCategory) : route('panel.stationery-categories.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(isset($stationeryCategory)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Kategoriya ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            <div class="col-12">
              <label class="p-label">Icon (emoji)</label>
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
            <div class="col-sm-6">
              <label class="p-label">
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

            <div class="col-sm-6">
              <label class="p-label">Slug</label>
              <input type="text" name="slug" class="p-form-control"
                     value="<?php echo e(old('slug', $stationeryCategory->slug ?? '')); ?>"
                     placeholder="avtomatik-yaratiladi">
            </div>

            <div class="col-sm-6">
              <label class="p-label">Holat</label>
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
          <div class="d-flex flex-wrap gap-2">
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

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo e(route('panel.stationery-categories.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/stationery-categories/edit.blade.php ENDPATH**/ ?>