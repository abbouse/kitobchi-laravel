<?php $__env->startSection('title', isset($promocode) ? 'Tahrirlash: '.$promocode->code : 'Yangi promokod'); ?>
<?php $__env->startSection('page-title', isset($promocode) ? 'Promokod tahrirlash' : 'Yangi promokod'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
  <div class="col-xl-6">
    <form method="POST"
      action="<?php echo e(isset($promocode) ? route('panel.promocodes.update',$promocode) : route('panel.promocodes.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(isset($promocode)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Promokod ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="row g-3">

            
            <?php if(!isset($promocode)): ?>
            <div class="col-12">
              <label class="p-label">Kod <span style="color:var(--p-danger)">*</span></label>
              <div class="d-flex gap-2">
                <input type="text" name="code" id="promoCode"
                       class="p-form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('code')); ?>" required
                       style="text-transform:uppercase;font-family:'DM Mono',monospace;font-weight:700;font-size:15px;letter-spacing:.05em">
                <button type="button" class="btn-p ghost" onclick="generateCode()">
                  <i class="bi bi-arrow-repeat"></i> Yaratish
                </button>
              </div>
              <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <?php else: ?>
            <div class="col-12">
              <label class="p-label">Kod</label>
              <code style="display:block;font-family:'DM Mono',monospace;font-size:18px;font-weight:700;
                           color:var(--p-accent);background:var(--p-elevated);padding:10px 16px;
                           border-radius:8px;letter-spacing:.08em"><?php echo e($promocode->code); ?></code>
            </div>
            <?php endif; ?>

            
            <div class="col-sm-6">
              <label class="p-label">Chegirma turi <span style="color:var(--p-danger)">*</span></label>
              <select name="type" id="promoType" class="p-form-control" onchange="updateAmountLabel()">
                <option value="percent" <?php echo e(old('type',$promocode->type??'')=='percent'?'selected':''); ?>>Foiz (%)</option>
                <option value="fixed"   <?php echo e(old('type',$promocode->type??'')=='fixed'?'selected':''); ?>>Miqdor (UZS)</option>
              </select>
            </div>

            
            <div class="col-sm-6">
              <label class="p-label" id="amountLabel">Chegirma miqdori <span style="color:var(--p-danger)">*</span></label>
              <input type="number" name="amount" class="p-form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('amount',$promocode->amount??'')); ?>" min="1" required>
              <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="col-sm-6">
              <label class="p-label">Minimal buyurtma (UZS)</label>
              <input type="number" name="min_order_amount" class="p-form-control"
                     value="<?php echo e(old('min_order_amount',$promocode->min_order_amount??0)); ?>" min="0">
            </div>

            
            <div class="col-sm-6">
              <label class="p-label">Foydalanish limiti</label>
              <input type="number" name="usesLimit" class="p-form-control"
                     value="<?php echo e(old('usesLimit',$promocode->usesLimit??0)); ?>" min="0"
                     placeholder="0 = cheksiz">
            </div>

            
            <div class="col-sm-6">
              <label class="p-label">Amal qilish muddati <span style="color:var(--p-danger)">*</span></label>
              <input type="datetime-local" name="expires_at" class="p-form-control <?php $__errorArgs = ['expires_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('expires_at', isset($promocode) ? \Carbon\Carbon::parse($promocode->expires_at)->format('Y-m-d\TH:i') : '')); ?>"
                     required>
              <?php $__errorArgs = ['expires_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="col-sm-6">
              <label class="p-label">Holat</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1"
                       <?php echo e(old('status',$promocode->status??1) ? 'checked' : ''); ?>

                       style="width:18px;height:18px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Aktiv</span>
              </label>
            </div>

          </div>
        </div>
      </div>

      <?php if(isset($promocode)): ?>
      <div class="p-card mb-3" style="background:var(--p-warning-d);border-color:rgba(245,166,35,.2)">
        <div class="dash-card-body" style="padding:14px 20px">
          <div class="d-flex gap-3">
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-warning)"><?php echo e($promocode->usedCount); ?></div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Ishlatildi</div>
            </div>
            <div style="width:1px;background:rgba(245,166,35,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-warning)"><?php echo e($promocode->usesLimit ?: '∞'); ?></div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Limit</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo e(route('panel.promocodes.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function generateCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let code = '';
  for (let i = 0; i < 8; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
  document.getElementById('promoCode').value = code;
}

function updateAmountLabel() {
  const type = document.getElementById('promoType').value;
  document.getElementById('amountLabel').textContent = type === 'percent'
    ? 'Chegirma foizi (%) *'
    : 'Chegirma miqdori (UZS) *';
}
updateAmountLabel();
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/promocodes/edit.blade.php ENDPATH**/ ?>