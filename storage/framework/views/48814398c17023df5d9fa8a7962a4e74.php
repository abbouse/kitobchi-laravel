<?php $__env->startSection('title', 'Profil'); ?>
<?php $__env->startSection('page-title', 'Profil'); ?>

<?php $__env->startSection('content'); ?>
<?php $admin = auth('panel')->user(); ?>

<div class="d-flex flex-column gap-4">
  <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['eyebrow' => 'Account workspace','title' => 'Mening profilim','subtitle' => 'Admin akkaunti, xavfsizlik va shaxsiy ma\'lumotlar bir xil boshqaruv tilida shu sahifada yangilanadi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Account workspace','title' => 'Mening profilim','subtitle' => 'Admin akkaunti, xavfsizlik va shaxsiy ma\'lumotlar bir xil boshqaruv tilida shu sahifada yangilanadi.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <section class="card-panel h-100 fade-up">
        <div class="p-4">
          <div class="d-flex flex-column align-items-center text-center gap-3">
            <?php echo $__env->make('a122.partials.avatar', [
              'name' => $admin->name,
              'image' => $admin->avatar,
              'class' => 'rounded-4',
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div>
              <h2 class="h4 fw-bold mb-1 text-dark"><?php echo e($admin->name); ?></h2>
              <p class="text-secondary mb-0"><?php echo e($admin->email); ?></p>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
              <span class="badge rounded-pill text-bg-primary px-3 py-2"><?php echo e($admin->getRoleLabelAttribute()); ?></span>
              <span class="badge rounded-pill <?php echo e($admin->is_active ? 'text-bg-success' : 'text-bg-danger'); ?> px-3 py-2">
                <?php echo e($admin->is_active ? 'Faol' : 'Bloklangan'); ?>

              </span>
            </div>
          </div>

          <div class="mt-4 pt-4 border-top">
            <div class="d-flex flex-column gap-3">
              <?php $__currentLoopData = [
                ['icon' => 'globe2', 'label' => 'Oxirgi IP', 'value' => $admin->last_ip ?: '—'],
                ['icon' => 'clock-history', 'label' => 'Oxirgi kirish', 'value' => $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
                ['icon' => 'calendar3', 'label' => 'Yaratilgan', 'value' => $admin->created_at?->format('d.m.Y') ?: '—'],
              ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="d-flex align-items-center gap-3 rounded-4 border bg-light-subtle px-3 py-3">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-4 text-primary bg-primary-subtle" style="width:2.75rem;height:2.75rem;">
                    <i class="bi bi-<?php echo e($item['icon']); ?>"></i>
                  </span>
                  <div class="min-w-0">
                    <div class="text-uppercase small fw-semibold text-secondary"><?php echo e($item['label']); ?></div>
                    <div class="fw-semibold text-dark text-truncate"><?php echo e($item['value']); ?></div>
                  </div>
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="col-12 col-xl-8">
      <form method="POST" action="<?php echo e(route('admin.profile.update')); ?>" enctype="multipart/form-data" class="d-flex flex-column gap-4 fade-up">
        <?php echo csrf_field(); ?>

        <section class="card-panel">
          <div class="card-panel-header">
            <h3 class="card-panel-title mb-0">Shaxsiy ma'lumotlar</h3>
            <div class="card-panel-sub">Email, ism va avatar shu blokdan yangilanadi.</div>
          </div>
          <div class="p-4">
            <div class="row g-4">
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Ism</label>
                <input type="text" name="name" class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', $admin->name)); ?>" required maxlength="100">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Email</label>
                <input type="email" name="email" class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', $admin->email)); ?>" required>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
              <div class="col-12">
                <label class="p-form-label">Avatar</label>
                <input type="file" name="avatar" class="p-form-control <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept="image/jpeg,image/png,image/jpg">
                <div class="form-text">JPG yoki PNG, maksimal 2MB.</div>
                <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
            </div>
          </div>
        </section>

        <section class="card-panel">
          <div class="card-panel-header">
            <h3 class="card-panel-title mb-0">Parolni yangilash</h3>
            <div class="card-panel-sub">Xavfsizlikni kuchaytirish uchun yangi parol kiritishingiz mumkin.</div>
          </div>
          <div class="p-4">
            <div class="row g-4">
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Joriy parol</label>
                <input type="password" name="current_password" class="p-form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" autocomplete="current-password">
                <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
              <div class="col-12 col-lg-6">
                <label class="p-form-label">Yangi parol</label>
                <input type="password" name="password" class="p-form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" autocomplete="new-password" minlength="8">
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
              <div class="col-12">
                <label class="p-form-label">Yangi parolni tasdiqlash</label>
                <input type="password" name="password_confirmation" class="p-form-control" autocomplete="new-password">
              </div>
            </div>
          </div>
        </section>

        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button type="reset" class="btn-p ghost">Tozalash</button>
          <button type="submit" class="btn-p primary">Saqlash</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/auth/profile.blade.php ENDPATH**/ ?>