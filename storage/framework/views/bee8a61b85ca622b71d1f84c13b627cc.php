<?php $__env->startSection('title', 'Tahrirlash: '.$seller->shop_name); ?>
<?php $__env->startSection('page-title', 'Sotuvchini tahrirlash'); ?>

<?php $__env->startSection('content'); ?>

<?php
  $isMainShop = $isMainShop ?? true;
  $curTypes   = $seller->activity_types; // accessor — har doim array
?>

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="<?php echo e(route('panel.sellers.show', $seller)); ?>" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title"><?php echo e($seller->shop_name); ?></h1>
    <p class="page-sub">ID: #<?php echo e($seller->id); ?></p>
  </div>
</div>

<form method="POST" action="<?php echo e(route('panel.sellers.update', $seller)); ?>"
      enctype="multipart/form-data">
  <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

  <div class="row g-3">

    
    <div class="col-xl-8 fade-up">
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Asosiy ma'lumotlar</div></div>
        <div style="padding:0 18px 18px">
          <div class="row g-3">

            <div class="col-12">
              <label class="p-form-label">Do'kon nomi <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="shop_name"
                     class="p-form-control <?php $__errorArgs = ['shop_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('shop_name', $seller->shop_name)); ?>" required>
              <?php $__errorArgs = ['shop_name'];
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

            <div class="col-md-6">
              <label class="p-form-label">Ism</label>
              <input type="text" name="firstname" class="p-form-control"
                     value="<?php echo e(old('firstname', $seller->firstname)); ?>" placeholder="Ism">
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Familiya</label>
              <input type="text" name="lastname" class="p-form-control"
                     value="<?php echo e(old('lastname', $seller->lastname)); ?>" placeholder="Familiya">
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Telefon <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="phone_number"
                     class="p-form-control <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('phone_number', $seller->phone_number)); ?>" required>
              <?php $__errorArgs = ['phone_number'];
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

            <div class="col-md-6">
              <label class="p-form-label">Viloyat <span style="color:var(--p-danger)">*</span></label>
              <select name="region" class="p-form-control" required>
                <?php $__currentLoopData = [
                  "Toshkent shahri","Toshkent viloyati","Samarqand","Buxoro",
                  "Namangan","Andijon","Farg'ona","Qashqadaryo","Surxondaryo",
                  "Jizzax","Sirdaryo","Navoiy","Xorazm","Qoraqalpog'iston"
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $region): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($region); ?>"
                  <?php echo e(old('region', $seller->region) === $region ? 'selected' : ''); ?>>
                  <?php echo e($region); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="p-form-label">
                Yangi parol
                <span style="color:var(--p-hint);font-size:11px">(ixtiyoriy)</span>
              </label>
              <input type="password" name="password" class="p-form-control"
                     placeholder="Yangi parol..." autocomplete="new-password">
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Balans (UZS)</label>
              <input type="number" name="balance" class="p-form-control"
                     value="<?php echo e(old('balance', $seller->balance ?? 0)); ?>" min="0" step="1">
            </div>

            <div class="col-md-6">
              <label class="p-form-label">
                Komissiya foizi (%)
                <span style="color:var(--p-hint);font-size:11px">— global: <?php echo e(\App\Models\CommissionSetting::orderBy('priceFrom')->first()?->percent ?? '—'); ?>%</span>
              </label>
              <div style="position:relative">
                <input type="number" name="commission_percent" class="p-form-control"
                       value="<?php echo e(old('commission_percent', $seller->commission_percent ?? '')); ?>"
                       min="0" max="100" step="0.1"
                       placeholder="Global komissiya">
                <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                             font-size:13px;color:var(--p-hint)">%</span>
              </div>
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
                Bo'sh qoldirilsa global komissiya qo'llaniladi
              </div>
            </div>

            
            <?php if(!$isMainShop): ?>
            <div class="col-md-6">
              <label class="p-form-label">Rol</label>
              <select name="role" class="p-form-control">
                <option value="">Tanlang</option>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rKey => $rLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($rKey); ?>"
                  <?php echo e(old('role', $seller->role) === $rKey ? 'selected' : ''); ?>>
                  <?php echo e($rLabel); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="p-form-label">Hodim holati</label>
              <select name="staff_status" class="p-form-control">
                <option value="active"
                  <?php echo e(old('staff_status', $seller->staff_status) === 'active' ? 'selected' : ''); ?>>
                  Faol
                </option>
                <option value="inactive"
                  <?php echo e(old('staff_status', $seller->staff_status) === 'inactive' ? 'selected' : ''); ?>>
                  Nofaol
                </option>
              </select>
            </div>
            <?php endif; ?>

            
            <?php if($isMainShop): ?>
            <div class="col-12">
              <label class="p-form-label">Faoliyat turlari</label>
              <div class="d-flex gap-3 flex-wrap">
                <?php $__currentLoopData = ['Kitob', 'Kanstovar']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $checked = in_array($type, old('activity_types', $curTypes));
                ?>
                <label style="display:flex;align-items:center;gap:8px;padding:10px 16px;
                               background:var(--p-elevated);border-radius:8px;cursor:pointer;
                               border:1px solid <?php echo e($checked ? 'var(--p-accent)' : 'transparent'); ?>">
                  <input type="checkbox" name="activity_types[]" value="<?php echo e($type); ?>"
                         id="type_<?php echo e($type); ?>"
                         <?php echo e($checked ? 'checked' : ''); ?>

                         style="accent-color:var(--p-accent)">
                  <span style="font-size:13px;color:var(--p-text)"><?php echo e($type); ?></span>
                </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

    
    <div class="col-xl-4 fade-up">

      
      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Rasm</div></div>
        <div style="padding:0 18px 18px">
          <?php if($seller->photo): ?>
          <div style="margin-bottom:12px;text-align:center">
            <img src="<?php echo e(Storage::url($seller->photo)); ?>"
                 style="width:72px;height:72px;border-radius:10px;object-fit:cover;
                        border:2px solid var(--p-border)">
          </div>
          <?php endif; ?>
          <input type="file" name="photo" class="p-form-control" accept="image/*">
          <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
        </div>
      </div>

      
      <div class="p-card">
        <div class="p-card-header"><div class="p-card-title">Holat</div></div>
        <div style="padding:0 18px 18px">
          <label class="p-form-label mb-2">Moderatsiya</label>
          <select name="status" class="p-form-control mb-3">
            <?php $__currentLoopData = [
              'pending'  => 'Kutilmoqda',
              'approved' => 'Tasdiqlangan',
              'rejected' => 'Rad etilgan',
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($val); ?>"
              <?php echo e(old('status', $seller->status) === $val ? 'selected' : ''); ?>>
              <?php echo e($lbl); ?>

            </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>

          <div style="display:flex;align-items:center;justify-content:space-between;
                      padding:10px;background:var(--p-elevated);border-radius:8px">
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">Yashirin</div>
              <div style="font-size:11px;color:var(--p-hint)">Marketplaceda ko'rinmaydi</div>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" name="is_hidden" value="1"
                     <?php echo e(old('is_hidden', $seller->is_hidden) ? 'checked' : ''); ?>>
            </div>
          </div>
        </div>
      </div>

    </div>

    
    <div class="col-12 fade-up">
      <div class="d-flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
        <a href="<?php echo e(route('panel.sellers.show', $seller)); ?>" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>
    </div>

  </div>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/sellers/edit.blade.php ENDPATH**/ ?>