<?php $__env->startSection('title', 'Profil'); ?>
<?php $__env->startSection('page-title', 'Profil'); ?>

<?php $__env->startSection('content'); ?>
<?php $admin = auth('panel')->user(); ?>

<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
   <?php $__env->slot('heading', null, []); ?> Mening profilim <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Admin akkaunti, xavfsizlik va shaxsiy ma'lumotlar shu yerda boshqariladi. <?php $__env->endSlot(); ?>
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

<div class="grid grid-cols-1 gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
  <section class="p-card fade-up">
    <div class="flex flex-col items-center text-center gap-4">
      <?php echo $__env->make('a122.partials.avatar', [
        'name' => $admin->name,
        'image' => $admin->avatar,
        'class' => 'h-24 w-24 text-3xl rounded-[28px]',
      ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      <div>
        <h2 class="text-xl font-semibold text-[var(--p-text)]"><?php echo e($admin->name); ?></h2>
        <p class="text-sm text-[var(--p-hint)] mt-1"><?php echo e($admin->email); ?></p>
      </div>

      <div class="flex flex-wrap justify-center gap-2">
        <span class="s-pill accent"><?php echo e($admin->getRoleLabelAttribute()); ?></span>
        <span class="s-pill <?php echo e($admin->is_active ? 'success' : 'danger'); ?>">
          <?php echo e($admin->is_active ? 'Faol' : 'Bloklangan'); ?>

        </span>
      </div>
    </div>

    <div class="mt-6 space-y-3 border-t border-[var(--p-border)] pt-5">
      <?php $__currentLoopData = [
        ['icon' => 'map-pinned', 'label' => 'Oxirgi IP', 'value' => $admin->last_ip ?: '—'],
        ['icon' => 'log-in', 'label' => 'Oxirgi kirish', 'value' => $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
        ['icon' => 'calendar-days', 'label' => 'Yaratilgan', 'value' => $admin->created_at?->format('d.m.Y') ?: '—'],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] px-4 py-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--p-soft)] text-[var(--p-accent)]">
            <i data-lucide="<?php echo e($item['icon']); ?>" class="h-4.5 w-4.5"></i>
          </div>
          <div class="min-w-0">
            <div class="text-xs uppercase tracking-[0.16em] text-[var(--p-hint)]"><?php echo e($item['label']); ?></div>
            <div class="truncate text-sm font-medium text-[var(--p-text)]"><?php echo e($item['value']); ?></div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </section>

  <form method="POST" action="<?php echo e(route('admin.profile.update')); ?>" enctype="multipart/form-data" class="space-y-4 fade-up">
    <?php echo csrf_field(); ?>

    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Shaxsiy ma'lumotlar</div>
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
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
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div>
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
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="lg:col-span-2">
          <label class="p-form-label">Avatar</label>
          <input type="file" name="avatar" class="p-form-control <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-danger <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept="image/jpeg,image/png,image/jpg">
          <p class="mt-2 text-xs text-[var(--p-hint)]">JPG yoki PNG, maksimal 2MB.</p>
          <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
      </div>
    </section>

    <section class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Parolni yangilash</div>
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
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
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div>
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
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-xs text-[var(--p-danger)]"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="lg:col-span-2">
          <label class="p-form-label">Yangi parolni tasdiqlash</label>
          <input type="password" name="password_confirmation" class="p-form-control" autocomplete="new-password">
        </div>
      </div>
    </section>

    <div class="flex flex-wrap justify-end gap-2">
      <button type="reset" class="btn-p ghost">Tozalash</button>
      <button type="submit" class="btn-p primary">Saqlash</button>
    </div>
  </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/auth/profile.blade.php ENDPATH**/ ?>