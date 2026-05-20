<?php $__env->startSection('title', isset($promocode) ? 'Tahrirlash: '.$promocode->code : 'Yangi promokod'); ?>
<?php $__env->startSection('page-title', isset($promocode) ? 'Promokod tahrirlash' : 'Yangi promokod'); ?>

<?php $__env->startSection('content'); ?>
<div class="kc-page-inner w-full min-w-0">
    <?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.promocodes.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.promocodes.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> <?php echo e(isset($promocode) ? $promocode->code : 'Yangi promokod'); ?> <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e(isset($promocode) ? 'Promokodni tahrirlash' : 'Yangi chegirma kodi yaratish'); ?> <?php $__env->endSlot(); ?>
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


    <form method="POST"
      action="<?php echo e(isset($promocode) ? route('admin.promocodes.update',$promocode) : route('admin.promocodes.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(isset($promocode)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

      <div class="p-card mb-3">
        <div class="dash-card-head"><div class="dash-card-title">Promokod ma'lumotlari</div></div>
        <div class="dash-card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            
            <?php if(!isset($promocode)): ?>
            <div class="">
              <label class="p-form-label">Kod <span style="color:var(--p-danger)">*</span></label>
              <div class="flex gap-2">
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
                       style="text-transform:uppercase;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:15px;letter-spacing:.05em">
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
            <div class="">
              <label class="p-form-label">Kod</label>
              <code style="display:block;font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;
                           color:var(--p-accent);background:var(--p-elevated);padding:10px 16px;
                           border-radius:8px;letter-spacing:.08em"><?php echo e($promocode->code); ?></code>
            </div>
            <?php endif; ?>

            
            <div class="">
              <label class="p-form-label">Chegirma turi <span style="color:var(--p-danger)">*</span></label>
              <select name="type" id="promoType" class="p-form-control" onchange="updateAmountLabel()">
                <option value="percent" <?php echo e(old('type',$promocode->type??'')=='percent'?'selected':''); ?>>Foiz (%)</option>
                <option value="fixed"   <?php echo e(in_array(old('type',$promocode->type??''), ['fixed','uzs'], true) ? 'selected' : ''); ?>>Miqdor (UZS)</option>
              </select>
            </div>

            
            <div class="">
              <label class="p-form-label" id="amountLabel">Chegirma miqdori <span style="color:var(--p-danger)">*</span></label>
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

            <div class="">
              <label class="p-form-label" id="maxDiscountLabel">Maksimal chegirma summasi (UZS)</label>
              <input
                type="number"
                name="max_discount_amount"
                id="maxDiscountAmount"
                class="p-form-control <?php $__errorArgs = ['max_discount_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                value="<?php echo e(old('max_discount_amount', $promocode->max_discount_amount ?? '')); ?>"
                min="0"
                placeholder="Foizli promokod uchun ixtiyoriy limit">
              <div id="maxDiscountHint" style="font-size:12px;color:var(--p-hint);margin-top:4px">
                Foizli promokodda chegirma shu summadan oshmaydi.
              </div>
              <?php $__errorArgs = ['max_discount_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="font-size:12px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="">
              <label class="p-form-label">Minimal buyurtma (UZS)</label>
              <input type="number" name="min_order_amount" class="p-form-control"
                     value="<?php echo e(old('min_order_amount',$promocode->min_order_amount??0)); ?>" min="0">
            </div>

            <div class="">
              <label class="p-form-label">Bir user uchun limit</label>
              <input type="number" name="per_user_limit" class="p-form-control"
                     value="<?php echo e(old('per_user_limit',$promocode->per_user_limit ?? 1)); ?>" min="0"
                     placeholder="1 = faqat bir marta, 0 = cheksiz">
              <div style="font-size:12px;color:var(--p-hint);margin-top:4px">
                Har bir foydalanuvchi bu promokoddan necha marta foydalana olishini belgilang.
              </div>
            </div>

            
            <div class="">
              <label class="p-form-label">Jami foydalanish limiti</label>
              <input type="number" name="usesLimit" class="p-form-control"
                     value="<?php echo e(old('usesLimit',$promocode->usesLimit??0)); ?>" min="0"
                     placeholder="0 = cheksiz">
            </div>

            
            <div class="">
              <label class="p-form-label">Amal qilish muddati <span style="color:var(--p-danger)">*</span></label>
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

            
            <div class="">
              <label class="p-form-label">Holat</label>
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
          <div class="flex gap-3">
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)"><?php echo e($promocode->usedCount); ?></div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Ishlatildi</div>
            </div>
            <div style="width:1px;background:rgba(245,166,35,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)"><?php echo e($promocode->usesLimit ?: '∞'); ?></div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">Jami limit</div>
            </div>
            <div style="width:1px;background:rgba(245,166,35,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:24px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)"><?php echo e(($promocode->per_user_limit ?? 1) ?: '∞'); ?></div>
              <div style="font-size:10px;color:var(--p-warning);opacity:.7;text-transform:uppercase">User limiti</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="flex gap-2 justify-end">
        <a href="<?php echo e(route('admin.promocodes.index')); ?>" class="btn-p ghost">Bekor qilish</a>
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
      </div>
    </form>
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
  const amountLabel = document.getElementById('amountLabel');
  const maxDiscountInput = document.getElementById('maxDiscountAmount');
  const maxDiscountLabel = document.getElementById('maxDiscountLabel');
  const maxDiscountHint = document.getElementById('maxDiscountHint');

  amountLabel.textContent = type === 'percent'
    ? 'Chegirma foizi (%) *'
    : 'Chegirma miqdori (UZS) *';

  const enabled = type === 'percent';
  maxDiscountInput.disabled = !enabled;
  maxDiscountInput.style.background = enabled ? '' : 'var(--p-elevated)';
  maxDiscountLabel.style.opacity = enabled ? '1' : '.55';
  maxDiscountHint.textContent = enabled
    ? 'Foizli promokodda chegirma shu summadan oshmaydi.'
    : 'Miqdorli promokodda bu maydon ishlatilmaydi.';
}
updateAmountLabel();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/promocodes/edit.blade.php ENDPATH**/ ?>