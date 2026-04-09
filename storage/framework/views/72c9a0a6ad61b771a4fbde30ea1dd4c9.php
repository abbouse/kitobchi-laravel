


<div class="p-card mb-3 fade-up">
  <div class="p-card-header"><div class="p-card-title">Ma'lumotlar</div></div>
  <div style="padding:0 18px 18px">
    <div class="row g-3">

      <div class="col-12">
        <label class="p-form-label">
          Sarlavha <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="text" name="title" class="p-form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               value="<?php echo e(old('title', $reel?->title)); ?>"
               placeholder="Reel sarlavhasi..." required>
        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div class="col-md-8">
        <label class="p-form-label">Tavsif</label>
        <textarea name="description" class="p-form-control" rows="3"
                  placeholder="Ixtiyoriy tavsif..."><?php echo e(old('description', $reel?->description)); ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="p-form-label">
          Tartib (order) <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="number" name="order" class="p-form-control <?php $__errorArgs = ['order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               value="<?php echo e(old('order', $reel?->order ?? $nextOrder ?? 1)); ?>"
               min="0" required>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
          Kichik raqam = oldin ko'rinadi
        </div>
        <?php $__errorArgs = ['order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

    </div>
  </div>
</div>

<div class="d-flex gap-2 fade-up">
  <button type="submit" class="btn-p primary">
    <i class="bi bi-check-lg"></i>
    <?php echo e($reel ? 'Saqlash' : 'Yaratish'); ?>

  </button>
  <a href="<?php echo e($reel ? route('panel.reels.show', $reel) : route('panel.reels.index')); ?>"
     class="btn-p ghost">
    Bekor
  </a>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/reels/_form.blade.php ENDPATH**/ ?>