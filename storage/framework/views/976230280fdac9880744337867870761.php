<?php $__env->startSection('title', 'Profil'); ?>

<?php $__env->startSection('content'); ?>

<?php $admin = auth('panel')->user(); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Mening profilim <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
  
  <div class="lg:col-span-4 fade-up">
    <div class="p-card" style="text-align:center;padding:32px 20px">
      <div style="width:80px;height:80px;border-radius:50%;
                  background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                  display:flex;align-items:center;justify-content:center;
                  font-size:28px;font-weight:700;color:#fff;
                  margin:0 auto 16px;overflow:hidden;
                  box-shadow:0 8px 24px rgba(91,135,255,.35)">
        <?php if($admin->avatar): ?>
          <img src="<?php echo e(asset('storage/'.$admin->avatar)); ?>"
               style="width:100%;height:100%;object-fit:cover" alt="<?php echo e($admin->name); ?>">
        <?php else: ?>
          <?php echo e(strtoupper(substr($admin->name, 0, 1))); ?>

        <?php endif; ?>
      </div>
      <div style="font-size:18px;font-weight:700;color:var(--p-text);letter-spacing:-.3px"><?php echo e($admin->name); ?></div>
      <div style="font-size:13px;color:var(--p-hint);margin-top:4px"><?php echo e($admin->email); ?></div>
      <div class="flex justify-center gap-2 mt-3">
        <span class="s-pill accent"><?php echo e($admin->getRoleLabelAttribute()); ?></span>
        <span class="s-pill <?php echo e($admin->is_active ? 'success' : 'danger'); ?>">
          <?php echo e($admin->is_active ? 'Faol' : 'Bloklangan'); ?>

        </span>
      </div>

      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--p-border)">
        <?php $__currentLoopData = [
          ['bi-geo','Oxirgi IP',$admin->last_ip ?? '—'],
          ['bi-box-arrow-in-right','Kirdi',$admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
          ['bi-calendar','Yaratildi',$admin->created_at?->format('d.m.Y') ?? '—'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon,$lbl,$val]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-center gap-2 text-start mb-2"
             style="font-size:12px;color:var(--p-hint)">
          <i class="bi <?php echo e($icon); ?>" style="width:14px;text-align:center"></i>
          <span><?php echo e($lbl); ?>:</span>
          <span style="color:var(--p-muted);font-weight:500;font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo e($val); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>

  
  <div class="lg:col-span-8 fade-up">
    <form method="POST" action="<?php echo e(route('panel.profile.update')); ?>" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>

      <div class="p-card mb-3">
        <div style="padding:0 0 14px;margin-bottom:16px;border-bottom:1px solid var(--p-border);
                    font-size:13px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-person mr-2" style="color:var(--p-accent)"></i>Shaxsiy ma'lumotlar
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Ism <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="name"
                   class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('name', $admin->name)); ?>" required maxlength="100">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Email <span style="color:var(--p-danger)">*</span></label>
            <input type="email" name="email"
                   class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('email', $admin->email)); ?>" required>
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Avatar rasmi</label>
            <input type="file" name="avatar"
                   class="p-form-control <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   accept="image/jpeg,image/png,image/jpg">
            <div style="font-size:11px;color:var(--p-hint);margin-top:4px">
              <i class="bi bi-info-circle mr-1"></i>JPG, PNG &middot; Maksimal 2MB
            </div>
            <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>
      </div>

      <div class="p-card mb-3">
        <div style="padding:0 0 14px;margin-bottom:16px;border-bottom:1px solid var(--p-border);
                    font-size:13px;font-weight:600;color:var(--p-text)">
          <i class="bi bi-lock mr-2" style="color:var(--p-warning)"></i>Parolni o'zgartirish
          <span style="font-size:11px;color:var(--p-hint);font-weight:400;margin-left:6px">
            (ixtiyoriy)
          </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Joriy parol</label>
            <input type="password" name="current_password"
                   class="p-form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Yangi parol</label>
            <input type="password" name="password"
                   class="p-form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   autocomplete="new-password" minlength="8" placeholder="Kamida 8 belgi">
            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <div style="font-size:11.5px;color:var(--p-danger);margin-top:4px"><?php echo e($message); ?></div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="">
            <label class="p-form-label">Yangi parolni tasdiqlash</label>
            <input type="password" name="password_confirmation"
                   class="p-form-control" autocomplete="new-password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button type="reset" class="btn-p ghost">
          <i class="bi bi-arrow-counterclockwise"></i> Tozalash
        </button>
        <button type="submit" class="btn-p primary">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>

    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/auth/profile.blade.php ENDPATH**/ ?>