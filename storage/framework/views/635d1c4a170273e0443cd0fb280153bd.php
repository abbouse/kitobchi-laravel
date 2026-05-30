<?php $__env->startSection('title', 'Kirish'); ?>

<?php $__env->startSection('content'); ?>
<section class="admin-auth">
  <div class="admin-auth__panel">
    <div class="admin-auth__brand">
      <a href="<?php echo e(url('/')); ?>" class="admin-auth__back" aria-label="Saytga qaytish">
        <i class="bi bi-arrow-left"></i>
      </a>

      <div class="admin-auth__logo">
        <i class="bi bi-book-half"></i>
      </div>
      <div>
        <h1 class="admin-auth__title">Kitobchi Admin</h1>
      </div>
    </div>

    <button data-theme-toggle class="admin-auth__theme" aria-label="Tema almashtirish" title="Light / Dark mode">
      <span class="theme-icon-light"><i data-lucide="sun-medium" class="w-4 h-4"></i></span>
      <span class="theme-icon-dark"><i data-lucide="moon-star" class="w-4 h-4"></i></span>
    </button>

    <div class="admin-auth__head">
      <h2>Tizimga kirish</h2>
    </div>

    <?php if(session('error')): ?>
      <div class="p-alert danger">
        <i class="bi bi-exclamation-circle"></i>
        <span><?php echo e(session('error')); ?></span>
      </div>
    <?php endif; ?>

    <?php if(session('success')): ?>
      <div class="p-alert success">
        <i class="bi bi-check-circle"></i>
        <span><?php echo e(session('success')); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.login.post')); ?>" class="admin-auth__form">
      <?php echo csrf_field(); ?>
      <div>
        <label for="email" class="p-form-label">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email" placeholder="admin@example.com" class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="invalid-feedback"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      <div x-data="{ showPassword: false }">
        <label for="password" class="p-form-label">Parol</label>
        <div class="position-relative">
          <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="Parolingizni kiriting" class="p-form-control pe-5">
          <button type="button" @click="showPassword = !showPassword" class="admin-auth__password-toggle" aria-label="Parolni ko‘rsatish">
            <i x-show="!showPassword" data-lucide="eye" class="w-4 h-4"></i>
            <i x-show="showPassword" data-lucide="eye-off" class="w-4 h-4"></i>
          </button>
        </div>
      </div>

      <label class="admin-auth__remember">
        <input type="checkbox" name="remember" value="1">
        <span>Meni eslab qol</span>
      </label>

      <button type="submit" class="btn-primary-gradient admin-auth__submit">
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Kirish</span>
      </button>
    </form>
  </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/auth/login.blade.php ENDPATH**/ ?>