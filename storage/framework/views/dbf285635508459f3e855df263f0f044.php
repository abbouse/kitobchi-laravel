

<div class="p-card mb-3 fade-up">
  <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
  <div style="padding:0 18px 18px">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

      <div class="">
        <label class="p-form-label">
          Nomi <span style="color:var(--p-danger)">*</span>
        </label>
        <input type="text" name="name" class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               value="<?php echo e(old('name', $apiClient?->name)); ?>"
               placeholder="Masalan: iOS App v2, Partner Service" required>
        <?php $__errorArgs = ['name'];
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

      
      <?php if($apiClient): ?>
      <div class="">
        <label class="p-form-label">App ID</label>
        <div class="flex items-center gap-2">
          <input type="text" class="p-form-control" value="<?php echo e($apiClient->app_id); ?>"
                 readonly style="font-family:'JetBrains Mono',monospace;font-size:13px;
                                 background:var(--p-elevated);color:var(--p-accent)">
          <button type="button" onclick="copyText('<?php echo e($apiClient->app_id); ?>')"
                  class="btn-p ghost sm"><i class="bi bi-copy"></i></button>
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">Avtomatik — o'zgartirib bo'lmaydi</div>
      </div>

      <div class="">
        <label class="p-form-label">App Secret</label>
        <div class="flex items-center gap-2">
          <input type="text" id="secretField" class="p-form-control"
                 value="<?php echo e(str_repeat('•', 16)); ?>"
                 readonly style="font-family:'JetBrains Mono',monospace;font-size:13px;
                                 background:var(--p-elevated);color:var(--p-muted)">
          <button type="button" onclick="toggleSecret()" class="btn-p ghost sm" id="eyeBtn">
            <i class="bi bi-eye"></i>
          </button>
          <button type="button" onclick="copyText('<?php echo e(addslashes($apiClient->app_secret)); ?>')"
                  class="btn-p ghost sm"><i class="bi bi-copy"></i></button>
        </div>
        <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
          Yangilash uchun
          <a href="#" onclick="document.getElementById('regenForm').submit();return false"
             style="color:var(--p-warning)">Secret yangilash →</a>
        </div>
      </div>
      <?php endif; ?>

      <div class="">
        <label class="p-form-label">
          Huquqlar (JSON array)
          <span style="color:var(--p-hint);font-size:11px;font-weight:400">
            — e.g. ["read","write","delete"]
          </span>
        </label>
        <textarea name="abilities" class="p-form-control <?php $__errorArgs = ['abilities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                  rows="3" style="font-family:'JetBrains Mono',monospace;font-size:13px"
                  placeholder='["read"]'><?php echo e(old('abilities', $apiClient?->abilities)); ?></textarea>
        <?php $__errorArgs = ['abilities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div style="font-size:11px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <div style="font-size:11px;color:var(--p-hint);margin-top:5px">
          Mavjud huquqlar:
          <?php $__currentLoopData = ['read','write','delete','admin','push','orders','users']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <code onclick="addAbility('<?php echo e($ab); ?>')"
                style="cursor:pointer;background:var(--p-elevated);padding:1px 6px;
                       border-radius:4px;font-size:10px;margin-right:3px;
                       color:var(--p-accent)"><?php echo e($ab); ?></code>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>

      <div class="">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="hidden" name="is_active" value="0">
          <input type="checkbox" name="is_active" value="1"
                 <?php echo e(old('is_active', $apiClient?->is_active ?? true) ? 'checked' : ''); ?>

                 style="width:18px;height:18px;accent-color:var(--p-accent)">
          <span style="font-size:13px;color:var(--p-text)">Faol</span>
          <span style="font-size:11px;color:var(--p-hint)">(so'rovlarga javob beradi)</span>
        </label>
      </div>

    </div>
  </div>
</div>

<div class="flex gap-2 fade-up">
  <button type="submit" class="btn-p primary">
    <i class="bi bi-check-lg"></i>
    <?php echo e($apiClient ? 'Saqlash' : 'Yaratish'); ?>

  </button>
  <a href="<?php echo e(route('panel.api-clients.index')); ?>" class="btn-p ghost">Bekor</a>
</div>

<?php if($apiClient): ?>

<form id="regenForm" method="POST"
      action="<?php echo e(route('panel.api-clients.regenerate',$apiClient)); ?>"
      onsubmit="return confirm('Eski secret kalit endi ishlamaydi!')">
  <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
</form>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($apiClient ?? null): ?>
const SECRET = '<?php echo e(addslashes($apiClient->app_secret)); ?>';
let visible = false;
function toggleSecret(){
  const el  = document.getElementById('secretField');
  const eye = document.getElementById('eyeBtn').querySelector('i');
  visible = !visible;
  el.value    = visible ? SECRET : '•'.repeat(16);
  eye.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
}
<?php endif; ?>

function copyText(text){
  navigator.clipboard.writeText(text).then(()=>{
    const t = document.createElement('div');
    t.textContent = 'Nusxalandi!';
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:9999;
      background:var(--p-surface);border:1px solid var(--p-border);
      padding:8px 16px;border-radius:8px;font-size:13px;
      color:var(--p-success);box-shadow:0 4px 20px rgba(0,0,0,.15)`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 1800);
  });
}

function addAbility(ab){
  const ta = document.querySelector('textarea[name="abilities"]');
  try{
    let arr = JSON.parse(ta.value || '[]');
    if(!arr.includes(ab)){ arr.push(ab); }
    ta.value = JSON.stringify(arr);
  } catch(e){
    ta.value = JSON.stringify([ab]);
  }
}
</script>
<?php $__env->stopPush(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/api-clients/_form.blade.php ENDPATH**/ ?>