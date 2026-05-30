<?php
  $abilitySuggestions = ['read', 'write', 'delete', 'admin', 'push', 'orders', 'users'];
?>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
  <section class="card-panel fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">Asosiy ma'lumotlar</div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="lg:col-span-2">
        <label class="p-form-label">Nomi <span style="color:var(--p-danger)">*</span></label>
        <input type="text" name="name" class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', $apiClient?->name)); ?>" placeholder="Masalan: iOS App v2, Partner Service" required>
        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <?php if($apiClient): ?>
        <div>
          <label class="p-form-label">App ID</label>
          <div class="flex items-center gap-2">
            <input type="text" class="p-form-control font-mono text-sm" value="<?php echo e($apiClient->app_id); ?>" readonly>
            <button type="button" onclick="copyText('<?php echo e($apiClient->app_id); ?>')" class="btn-p ghost sm">Nusxa</button>
          </div>
          <p class="mt-2 text-xs text-[var(--p-hint)]">Avtomatik yaratiladi va o'zgarmaydi.</p>
        </div>

        <div>
          <label class="p-form-label">App Secret</label>
          <div class="flex items-center gap-2">
            <input type="text" id="secretField" class="p-form-control font-mono text-sm" value="<?php echo e(str_repeat('•', 16)); ?>" readonly>
            <button type="button" onclick="toggleSecret()" class="btn-p ghost sm" id="eyeBtn">Ko'rsatish</button>
            <button type="button" onclick="copyText('<?php echo e(addslashes($apiClient->app_secret)); ?>')" class="btn-p ghost sm">Nusxa</button>
          </div>
          <p class="mt-2 text-xs text-[var(--p-hint)]">Secret faqat ishonchli tizimlarga beriladi.</p>
        </div>
      <?php endif; ?>

      <div class="lg:col-span-2">
        <label class="p-form-label">Huquqlar</label>
        <textarea name="abilities" class="p-form-control <?php $__errorArgs = ['abilities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> font-mono text-sm" rows="5" placeholder='["read","orders"]'><?php echo e(old('abilities', isset($apiClient) && is_array($apiClient?->abilities) ? json_encode($apiClient->abilities, JSON_UNESCAPED_SLASHES) : $apiClient?->abilities)); ?></textarea>
        <?php $__errorArgs = ['abilities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <div class="mt-3 flex flex-wrap gap-2">
          <?php $__currentLoopData = $abilitySuggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button" onclick="addAbility('<?php echo e($ab); ?>')" class="btn-p ghost sm"><?php echo e($ab); ?></button>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>

      <div>
        <label class="p-form-label">Rate limit / soniya</label>
        <input
          type="number"
          min="1"
          max="10000"
          name="rate_limit_per_second"
          class="p-form-control <?php $__errorArgs = ['rate_limit_per_second'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
          value="<?php echo e(old('rate_limit_per_second', $apiClient?->rate_limit_per_second ?? 8)); ?>"
          placeholder="8"
        >
        <?php $__errorArgs = ['rate_limit_per_second'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <p class="mt-2 text-xs text-[var(--p-hint)]">Bitta client bir soniyada necha so‘rov yubora oladi.</p>
      </div>

      <div>
        <label class="p-form-label">Rate limit / daqiqa</label>
        <input
          type="number"
          min="1"
          max="500000"
          name="rate_limit_per_minute"
          class="p-form-control <?php $__errorArgs = ['rate_limit_per_minute'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
          value="<?php echo e(old('rate_limit_per_minute', $apiClient?->rate_limit_per_minute ?? 240)); ?>"
          placeholder="240"
        >
        <?php $__errorArgs = ['rate_limit_per_minute'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <p class="mt-2 text-xs text-[var(--p-hint)]">Qisqa burst’lardan tashqari umumiy daqiqalik limit.</p>
      </div>
    </div>
  </section>

  <aside class="space-y-4 fade-up">
    <section class="card-panel">
      <div class="dash-card-head">
        <div class="dash-card-title">Holat va boshqaruv</div>
      </div>

      <label class="flex items-start gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" <?php echo e(old('is_active', $apiClient?->is_active ?? true) ? 'checked' : ''); ?> class="mt-1 h-4 w-4 rounded">
        <span class="min-w-0">
          <span class="block text-sm font-medium text-[var(--p-text)]">Faol holatda saqlash</span>
          <span class="mt-1 block text-xs text-[var(--p-hint)]">Faol bo'lsa, mijoz API so'rov yubora oladi.</span>
        </span>
      </label>

      <?php if($apiClient): ?>
        <button type="button" onclick="document.getElementById('regenForm').submit()" class="btn-p ghost w-full mt-4">Secretni yangilash</button>
      <?php endif; ?>
    </section>

    <section class="card-panel">
      <div class="dash-card-head">
        <div class="dash-card-title">Tez eslatma</div>
      </div>
      <div class="space-y-3 text-sm text-[var(--p-hint)]">
        <p>Huquqlar JSON ko'rinishida saqlanadi. Misol: <code>["read","orders"]</code>.</p>
        <p><code>read</code> bo'lmasa hozirgi client endpointlari ishlamaydi.</p>
        <p>Secret yangilansa, eski secret darhol ishlamay qoladi.</p>
        <p>Rate limit har bir client uchun alohida ishlaydi va response headerlarda ham qaytadi.</p>
      </div>
    </section>
  </aside>
</div>

<div class="mt-4 flex flex-wrap gap-2 fade-up">
  <button type="submit" class="btn-p primary"><?php echo e($apiClient ? 'Saqlash' : 'Yaratish'); ?></button>
  <a href="<?php echo e(route('admin.api-clients.index')); ?>" class="btn-p ghost">Bekor</a>
</div>

<?php if($apiClient): ?>
<form id="regenForm" method="POST"
      action="<?php echo e(route('admin.api-clients.regenerate',$apiClient)); ?>"
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
  visible = !visible;
  el.value    = visible ? SECRET : '•'.repeat(16);
  document.getElementById('eyeBtn').textContent = visible ? 'Yashirish' : 'Ko\\'rsatish';
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
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/api-clients/_form.blade.php ENDPATH**/ ?>