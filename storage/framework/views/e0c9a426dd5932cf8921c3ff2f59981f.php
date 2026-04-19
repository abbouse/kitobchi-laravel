<?php $__env->startSection('title', 'Hodim qo\'shish'); ?>
<?php $__env->startSection('page-title', 'Hodim qo\'shish'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.sellers.show', $seller)).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.sellers.show', $seller)).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Yangi hodim <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> <?php echo e($seller->shop_name); ?> uchun <?php $__env->endSlot(); ?>
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


<div class="kc-page-inner w-full min-w-0 fade-up">
    <form method="POST"
          action="<?php echo e(route('panel.sellers.staff.store', $seller)); ?>"
          enctype="multipart/form-data">
      <?php echo csrf_field(); ?>

      <div class="p-card mb-3">
        <div class="p-card-header"><div class="p-card-title">Hodim ma'lumotlari</div></div>
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <div class="">
              <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
              <input type="text" name="firstname"
                     class="p-form-control <?php $__errorArgs = ['firstname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old('firstname')); ?>" required maxlength="100">
              <?php $__errorArgs = ['firstname'];
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

            <div class="">
              <label class="p-form-label">Familiya</label>
              <input type="text" name="lastname" class="p-form-control"
                     value="<?php echo e(old('lastname')); ?>" maxlength="100">
            </div>

            <div class="">
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
                     value="<?php echo e(old('phone_number')); ?>" required placeholder="998901234567">
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

            <div class="">
              <label class="p-form-label">Parol <span style="color:var(--p-danger)">*</span></label>
              <input type="password" name="password"
                     class="p-form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     required minlength="6" autocomplete="new-password">
              <?php $__errorArgs = ['password'];
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

            <div class="">
              <label class="p-form-label">Rol <span style="color:var(--p-danger)">*</span></label>
              <select name="role" class="p-form-control <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                      required>
                <option value="">Tanlang...</option>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($key); ?>" <?php echo e(old('role') === $key ? 'selected' : ''); ?>>
                  <?php echo e($label); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <?php $__errorArgs = ['role'];
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

            <div class="">
              <label class="p-form-label">Holat</label>
              <select name="staff_status" class="p-form-control">
                <option value="active" <?php echo e(old('staff_status','active')==='active' ? 'selected':''); ?>>
                  Faol
                </option>
                <option value="inactive" <?php echo e(old('staff_status')==='inactive' ? 'selected':''); ?>>
                  Nofaol
                </option>
              </select>
            </div>

            <div class="">
              <label class="p-form-label">Profil rasmi</label>
              <input type="file" name="photo" class="p-form-control" accept="image/*">
              <div style="font-size:11px;color:var(--p-hint);margin-top:4px">JPG, PNG · max 2MB</div>
            </div>

          </div>
        </div>
      </div>

      
      <div class="p-card mb-3" style="background:var(--p-elevated)">
        <div style="padding:14px 18px;display:flex;align-items:center;gap:12px">
          <div style="width:36px;height:36px;border-radius:8px;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-warning),#f97316);
                      display:flex;align-items:center;justify-content:center;
                      font-size:15px;font-weight:700;color:#fff">
            <?php if($seller->photo): ?>
              <img src="<?php echo e(Storage::url($seller->photo)); ?>"
                   style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo e(strtoupper(substr($seller->shop_name, 0, 1))); ?>

            <?php endif; ?>
          </div>
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              <?php echo e($seller->shop_name); ?>

            </div>
            <div style="font-size:11px;color:var(--p-hint)">
              Hodim shu do'konga biriktiriladi
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="btn-p primary">
          <i class="bi bi-person-plus"></i> Hodim qo'shish
        </button>
        <a href="<?php echo e(route('panel.sellers.show', $seller)); ?>" class="btn-p ghost">
          Bekor qilish
        </a>
      </div>

    </form>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/sellers/create-staff.blade.php ENDPATH**/ ?>