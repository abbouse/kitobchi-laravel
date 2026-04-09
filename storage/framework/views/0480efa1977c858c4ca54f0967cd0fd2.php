


<div>
  <label class="p-form-label">
    <span class="s-pill <?php echo e($color); ?>" style="font-size:10px;margin-right:4px">
      <?php echo e($label); ?>

    </span>
    <?php if($required): ?>
      <span style="color:var(--p-danger)">*</span>
    <?php else: ?>
      <span style="color:var(--p-hint);font-size:10px">ixtiyoriy</span>
    <?php endif; ?>
  </label>

  <div id="drop-<?php echo e($field); ?>"
       style="border:2px dashed var(--p-border);border-radius:10px;
              padding:16px 12px;text-align:center;cursor:pointer;
              transition:border-color .2s,background .2s;position:relative"
       onclick="document.getElementById('<?php echo e($field); ?>').click()"
       ondragover="event.preventDefault();this.style.borderColor='var(--p-<?php echo e($color); ?>)'"
       ondragleave="this.style.borderColor='var(--p-border)'"
       ondrop="handleDrop(event,'<?php echo e($field); ?>')">

    <div id="icon-<?php echo e($field); ?>">
      <i class="bi bi-cloud-arrow-up"
         style="font-size:22px;color:var(--p-<?php echo e($color); ?>);display:block;margin-bottom:6px"></i>
      <div style="font-size:12px;color:var(--p-muted)">
        Fayl tanlang yoki tashlang
      </div>
      <div style="font-size:10px;color:var(--p-hint);margin-top:2px">
        MP4, WebM · Maks <?php echo e($maxMb); ?>MB
      </div>
    </div>

    
    <div id="name-<?php echo e($field); ?>"
         style="display:none;font-size:11.5px;color:var(--p-text);
                font-weight:500;word-break:break-all"></div>
  </div>

  <input type="file" id="<?php echo e($field); ?>" name="<?php echo e($field); ?>"
         accept="video/mp4,video/webm,video/quicktime"
         style="display:none"
         <?php echo e($required ? 'required' : ''); ?>

         onchange="showFileName('<?php echo e($field); ?>')">

  <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
  <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/reels/_video-upload.blade.php ENDPATH**/ ?>