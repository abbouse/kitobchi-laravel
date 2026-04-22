<?php $__env->startSection('title', 'Yangi bildirishnoma'); ?>
<?php $__env->startSection('page-title', 'Push bildirishnoma yuborish'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.push.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.push.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Yangi push xabar <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Foydalanuvchilar, sotuvchilar yoki kuryerlarga <?php $__env->endSlot(); ?>
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


    
    <div class="row g-2 mb-4 fade-up">
      <?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="col-4">
        <div class="p-card flex items-center gap-3" style="padding:14px"
             id="preview-<?php echo e($key); ?>">
          <div style="width:36px;height:36px;border-radius:9px;display:flex;
                      align-items:center;justify-content:center;font-size:16px;
                      background:var(--p-<?php echo e($t['color']); ?>-d,var(--p-elevated));
                      color:var(--p-<?php echo e($t['color']); ?>);flex-shrink:0">
            <i class="bi <?php echo e($t['icon']); ?>"></i>
          </div>
          <div>
            <div style="font-size:18px;font-weight:700;font-family:'JetBrains Mono',monospace;
                        color:var(--p-<?php echo e($t['color']); ?>)">
              <?php echo e(number_format($tokenCounts[$key] ?? 0)); ?>

            </div>
            <div style="font-size:10px;color:var(--p-hint)">faol qurilma</div>
          </div>
        </div>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="POST" action="<?php echo e(route('admin.push.store')); ?>">
      <?php echo csrf_field(); ?>

      <div class="p-card mb-3 fade-up">
        <div class="dash-card-head"><div class="dash-card-title">Xabar ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            
            <div class="">
              <label class="p-form-label">Sarlavha <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="name"
                     class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('name')); ?>" required maxlength="255"
                     placeholder="Yangi chegirma 20%! 🎉"
                     id="previewTitle">
              <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="">
              <label class="p-form-label">Xabar matni <span style="color:var(--p-danger)">*</span></label>
              <textarea name="description" rows="3"
                        class="p-form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        required maxlength="500"
                        placeholder="Qisqa va aniq yozing. Maksimal 500 belgi."
                        id="previewBody"><?php echo e(old('description')); ?></textarea>
              <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px;text-align:right">
                <span id="charCount">0</span> / 500
              </div>
            </div>

            
            <div class="">
              <label class="p-form-label mb-2">Qabul qiluvchilar <span style="color:var(--p-danger)">*</span></label>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="">
                  <label style="display:flex;align-items:center;gap:12px;cursor:pointer;
                                background:var(--p-elevated);border:2px solid var(--p-border);
                                border-radius:10px;padding:14px 16px;transition:all .15s"
                         class="target-label" data-target="<?php echo e($key); ?>">
                    <input type="radio" name="who" value="<?php echo e($key); ?>"
                           <?php echo e(old('who')===$key ? 'checked' : ''); ?>

                           style="accent-color:var(--p-<?php echo e($t['color']); ?>);width:18px;height:18px;flex-shrink:0"
                           class="target-radio">
                    <div style="width:36px;height:36px;border-radius:8px;display:flex;
                                align-items:center;justify-content:center;font-size:16px;
                                background:var(--p-<?php echo e($t['color']); ?>-d,var(--p-surface));
                                color:var(--p-<?php echo e($t['color']); ?>);flex-shrink:0">
                      <i class="bi <?php echo e($t['icon']); ?>"></i>
                    </div>
                    <div style="flex:1">
                      <div style="font-size:13px;font-weight:600;color:var(--p-text)">
                        <?php echo e($t['label']); ?>

                      </div>
                      <div style="font-size:11px;color:var(--p-hint)">
                        <?php echo e(number_format($tokenCounts[$key] ?? 0)); ?> ta faol qurilma
                      </div>
                    </div>
                    <span class="s-pill <?php echo e($t['color']); ?>" style="font-size:13px;font-weight:700;
                                font-family:'JetBrains Mono',monospace">
                      <?php echo e(number_format($tokenCounts[$key] ?? 0)); ?>

                    </span>
                  </label>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
              <?php $__errorArgs = ['who'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger);margin-top:6px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

          </div>
        </div>
      </div>

      
      <div class="p-card mb-3 fade-up" style="background:var(--p-elevated);border-style:dashed">
        <div class="dash-card-head">
          <div class="dash-card-title" style="font-size:12px;color:var(--p-hint)">
            <i class="bi bi-phone mr-1"></i> Telefon ko'rinishi (preview)
          </div>
        </div>
        <div class="dash-card-body">
          <div style="background:var(--p-surface);border-radius:10px;padding:12px 14px;
                      border:1px solid var(--p-border);max-width:320px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <div style="width:24px;height:24px;border-radius:6px;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          flex-shrink:0"></div>
              <span style="font-size:11px;font-weight:600;color:var(--p-muted)">Kitobchi</span>
              <span style="font-size:10px;color:var(--p-hint);margin-left:auto">hozir</span>
            </div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)" id="phone-title">
              Sarlavha bu yerda ko'rinadi
            </div>
            <div style="font-size:12px;color:var(--p-muted);margin-top:3px" id="phone-body">
              Matn bu yerda ko'rinadi...
            </div>
          </div>
        </div>
      </div>

      
      <div style="padding:12px 16px;background:var(--p-warning-d);border-radius:8px;
                  border:1px solid rgba(245,166,35,.2);margin-bottom:20px"
           class="fade-up">
        <div style="display:flex;gap:8px;font-size:12px;color:var(--p-warning)">
          <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px"></i>
          <div>
            <strong>Diqqat!</strong> Yuborilgan bildirishnomani bekor qilib bo'lmaydi.
            Matnni ehtiyotkorlik bilan yozing. Barcha faol qurilmalarga bir vaqtda yuboriladi.
          </div>
        </div>
      </div>

      <div class="flex gap-2 justify-end fade-up">
        <a href="<?php echo e(route('admin.push.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary" id="submitBtn">
          <i class="bi bi-send-fill"></i> Yuborish
        </button>
      </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Preview
const titleInput = document.getElementById('previewTitle');
const bodyInput  = document.getElementById('previewBody');
const phoneTitle = document.getElementById('phone-title');
const phoneBody  = document.getElementById('phone-body');
const charCount  = document.getElementById('charCount');

titleInput.addEventListener('input', () => {
  phoneTitle.textContent = titleInput.value || 'Sarlavha bu yerda ko\'rinadi';
});
bodyInput.addEventListener('input', () => {
  phoneBody.textContent  = bodyInput.value  || 'Matn bu yerda ko\'rinadi...';
  charCount.textContent  = bodyInput.value.length;
});

// Target radio styling
document.querySelectorAll('.target-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.target-label').forEach(l => {
      l.style.borderColor = 'var(--p-border)';
      l.style.background  = 'var(--p-elevated)';
    });
    const label = this.closest('.target-label');
    const clrMap = { users: 'accent', business: 'warning', courier: 'success' };
    const clr = clrMap[this.value] || 'accent';
    label.style.borderColor = `var(--p-${clr})`;
    label.style.background  = `var(--p-elevated)`;
  });
});

// Olddan tanlanganlarga stil berish
document.querySelectorAll('.target-radio:checked').forEach(r => r.dispatchEvent(new Event('change')));

// Double click protection
document.querySelector('form').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Yuborilmoqda...';
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/push/create.blade.php ENDPATH**/ ?>